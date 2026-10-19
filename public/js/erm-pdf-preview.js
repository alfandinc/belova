// Shared by the ERM pages: stacked Bootstrap modals and the in-page PDF / print preview (#modalPdfPreview).
// Any .btn-pdf-preview link opens its PDF in the modal and a .btn-pdf-print link prints it straight away;
// each PDF is fetched once and kept as a blob, and resting the mouse on the button already starts the fetch.
// A page can stop a preview by handling the cancellable 'erm:pdf-preview' event on the button, and drop a stale PDF with ErmPdfPreview.drop(urlPart).
$(function () {
    // ---- Stacked modals: a detail / add modal opened from the Hasil Lab modal goes on top of it ----
    $(document).on('show.bs.modal', '.modal', function () {
        const z = 1050 + 10 * $('.modal.show').not(this).length;
        $(this).css('z-index', z);
        setTimeout(function () {
            $('.modal-backdrop').not('.elab-stacked').css('z-index', z - 1).addClass('elab-stacked');
        }, 0);
    });
    $(document).on('hidden.bs.modal', '.modal', function () {
        // Closing the top modal must not unlock the page scroll while the one below is still open
        if ($('.modal.show').length) $('body').addClass('modal-open');
    });

    // ---- PDF preview: each PDF is fetched once and kept as a blob (reopening is instant);
    //      resting the mouse on a PDF button already starts the fetch ----
    const pdfCache = {};
    let pdfShownUrl = null;
    let pdfWantedUrl = null;

    function fetchPdf(url) {
        if (!pdfCache[url]) {
            pdfCache[url] = fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/pdf' } })
                .then(function (res) {
                    if (!res.ok || (res.headers.get('Content-Type') || '').indexOf('application/pdf') === -1) {
                        return res.json().catch(function () { return {}; }).then(function (json) {
                            throw new Error(json.message || 'PDF gagal dibuat.');
                        });
                    }
                    const match = (res.headers.get('Content-Disposition') || '').match(/filename="?([^";]+)"?/);
                    return res.blob().then(function (blob) {
                        return { blobUrl: URL.createObjectURL(blob), filename: match ? match[1] : 'dokumen.pdf' };
                    });
                })
                .catch(function (err) {
                    delete pdfCache[url];
                    throw err;
                });
        }
        return pdfCache[url];
    }

    // Data behind a PDF changed: make it again next time
    function dropPdfCache(part) {
        Object.keys(pdfCache).forEach(function (url) {
            if (url.indexOf(part) === -1) return;
            pdfCache[url].then(function (pdf) { URL.revokeObjectURL(pdf.blobUrl); }).catch(function () {});
            delete pdfCache[url];
            if (pdfShownUrl === url) pdfShownUrl = null;
        });
    }

    let pdfHoverTimer = null;
    $(document).on('mouseenter', '.btn-pdf-preview', function () {
        const url = this.href;
        clearTimeout(pdfHoverTimer);
        // Short delay so sweeping the mouse over a table does not make every PDF
        pdfHoverTimer = setTimeout(function () { fetchPdf(url).catch(function () {}); }, 200);
    }).on('mouseleave', '.btn-pdf-preview', function () {
        clearTimeout(pdfHoverTimer);
    });

    $(document).on('click', '.btn-pdf-preview', function (e) {
        e.preventDefault();
        const before = $.Event('erm:pdf-preview');
        $(this).trigger(before);
        if (before.isDefaultPrevented()) return;
        openPreview(this.href, $(this).data('title'));
    });

    function openPreview(url, title) {
        pdfWantedUrl = url;
        $('#modalPdfPreviewLabel').text(title || 'Preview PDF');
        $('#pdfPreviewError').hide();
        $('#modalPdfPreview').modal('show');
        if (pdfShownUrl === url) {
            $('#pdfPreviewLoading').hide();
            return;
        }
        $('#pdfPreviewLoading').css('display', 'flex');
        $('#pdfPreviewPrint').prop('disabled', true);
        $('#pdfPreviewDownload').removeClass('d-none').addClass('disabled').attr('href', '#');

        fetchPdf(url).then(function (pdf) {
            if (pdfWantedUrl !== url) return;
            pdfShownUrl = url;
            $('#pdfPreviewFrame').attr('src', pdf.blobUrl);
            $('#pdfPreviewDownload').removeClass('disabled').attr({ href: pdf.blobUrl, download: pdf.filename });
            $('#pdfPreviewPrint').prop('disabled', false);
        }).catch(function (err) {
            if (pdfWantedUrl !== url) return;
            $('#pdfPreviewLoading').hide();
            $('#pdfPreviewError').text(err.message || 'PDF gagal dimuat.').css('display', 'flex');
        });
    }

    function openHtml(url, title) {
        pdfWantedUrl = url;
        $('#modalPdfPreviewLabel').text(title || 'Preview');
        $('#pdfPreviewError').hide();
        $('#pdfPreviewDownload').addClass('d-none');
        $('#modalPdfPreview').modal('show');
        if (pdfShownUrl === url) {
            $('#pdfPreviewLoading').hide();
            return;
        }
        pdfShownUrl = url;
        $('#pdfPreviewLoading').css('display', 'flex');
        $('#pdfPreviewPrint').prop('disabled', true);
        $('#pdfPreviewFrame').attr('src', url);
    }

    // Print a page straight away: load it in a hidden iframe and open the browser's print dialog for it
    // (no preview, no new tab). The iframe stays sized 0x0 in the page: display:none would print blank.
    let printFrame = null;
    let printNotice = null;
    function printHtml(url) {
        if (!printFrame) {
            printFrame = $('<iframe title="Cetak" aria-hidden="true" tabindex="-1">')
                .css({ position: 'fixed', right: 0, bottom: 0, width: 0, height: 0, border: 0, visibility: 'hidden' })
                .appendTo('body')[0];
        }
        // A plain notice, not SweetAlert: print() blocks the page, so a Swal closing with an animation never
        // finishes and leaves its (invisible) overlay over the whole page. This one takes no clicks, there is
        // only ever one, and it is removed at once, before the print dialog opens.
        if (!printNotice) {
            printNotice = $('<div role="status">Menyiapkan cetak…</div>').css({
                position: 'fixed', right: '16px', bottom: '16px', zIndex: 2000, pointerEvents: 'none',
                padding: '8px 14px', borderRadius: '10px', background: 'rgba(15, 23, 42, .85)', color: '#fff', fontSize: '.85rem', fontWeight: 600
            }).appendTo('body');
        }
        printFrame.onload = function () {
            // Only the page asked for (not the frame's initial about:blank)
            let href = '';
            try { href = printFrame.contentWindow.location.href; } catch (err) { /* other origin: treat as loaded */ href = url; }
            if (!href || href === 'about:blank') return;
            if (printNotice) {
                printNotice.remove();
                printNotice = null;
            }
            try {
                printFrame.contentWindow.focus();
                printFrame.contentWindow.print();
            } catch (err) {
                window.open(url, '_blank');
            }
        };
        printFrame.src = url;
    }

    // Print a PDF straight away (no preview): the PDF (fetched once, kept as a blob like the preview) is loaded in
    // its own hidden iframe and the browser's print dialog opens for it. Falls back to the preview modal when the
    // browser does not let a page print an embedded PDF.
    let pdfPrintFrame = null;
    let pdfPrintNotice = null;
    function removePdfPrintNotice() {
        if (pdfPrintNotice) {
            pdfPrintNotice.remove();
            pdfPrintNotice = null;
        }
    }
    // Shows the notice, waits for the PDF (a promise of { blobUrl }), prints it in the hidden frame; fallback()
    // when the browser does not let a page print an embedded PDF
    function printLoadedPdf(pdfPromise, fallback) {
        if (!pdfPrintNotice) {
            // Plain notice, not SweetAlert (see printHtml)
            pdfPrintNotice = $('<div role="status">Menyiapkan cetak…</div>').css({
                position: 'fixed', right: '16px', bottom: '16px', zIndex: 2000, pointerEvents: 'none',
                padding: '8px 14px', borderRadius: '10px', background: 'rgba(15, 23, 42, .85)', color: '#fff', fontSize: '.85rem', fontWeight: 600
            }).appendTo('body');
        }
        return Promise.resolve(pdfPromise).then(function (pdf) {
            if (!pdfPrintFrame) {
                pdfPrintFrame = $('<iframe title="Cetak PDF" aria-hidden="true" tabindex="-1">')
                    .css({ position: 'fixed', right: 0, bottom: 0, width: 0, height: 0, border: 0, visibility: 'hidden' })
                    .appendTo('body')[0];
            }
            pdfPrintFrame.onload = function () {
                if (!pdfPrintFrame.src || pdfPrintFrame.src === 'about:blank') return;
                // The PDF viewer needs a moment after load before it can print
                setTimeout(function () {
                    removePdfPrintNotice();
                    try {
                        pdfPrintFrame.contentWindow.focus();
                        pdfPrintFrame.contentWindow.print();
                    } catch (err) {
                        fallback();
                    }
                }, 250);
            };
            if (pdfPrintFrame.src === pdf.blobUrl) {
                // Same PDF as last time: print it again without reloading
                pdfPrintFrame.onload();
            } else {
                pdfPrintFrame.src = pdf.blobUrl;
            }
        }).catch(function (err) {
            removePdfPrintNotice();
            if (window.Swal) Swal.fire({ icon: 'error', title: 'Gagal', text: err.message || 'PDF gagal dibuat.' });
        });
    }
    function printPdf(url, title) {
        return printLoadedPdf(fetchPdf(url), function () { openPreview(url, title); });
    }
    // A PDF made on the fly (e.g. from a POST form): a Blob, or a promise of one
    let printedBlobUrl = null;
    function printBlob(blobOrPromise, title, filename) {
        let blob = null;
        return printLoadedPdf(Promise.resolve(blobOrPromise).then(function (b) {
            blob = b;
            if (printedBlobUrl) URL.revokeObjectURL(printedBlobUrl);
            printedBlobUrl = URL.createObjectURL(b);
            return { blobUrl: printedBlobUrl };
        }), function () { showBlob(blob, title, filename); });
    }

    // Any .btn-pdf-print link prints its PDF straight away; resting the mouse on it already fetches the PDF
    $(document).on('mouseenter', '.btn-pdf-print', function () {
        const url = this.href;
        clearTimeout(pdfHoverTimer);
        pdfHoverTimer = setTimeout(function () { fetchPdf(url).catch(function () {}); }, 200);
    }).on('mouseleave', '.btn-pdf-print', function () {
        clearTimeout(pdfHoverTimer);
    }).on('click', '.btn-pdf-print', function (e) {
        e.preventDefault();
        printPdf(this.href, $(this).data('title'));
    });

    $('#pdfPreviewFrame').on('load', function () {
        if (!$(this).attr('src')) return;
        $('#pdfPreviewLoading').hide();
        $('#pdfPreviewPrint').prop('disabled', false);
    });

    $('#pdfPreviewPrint').on('click', function () {
        const frame = document.getElementById('pdfPreviewFrame');
        try {
            frame.contentWindow.focus();
            frame.contentWindow.print();
        } catch (err) {
            // Viewer that does not allow scripted print: open the PDF itself to print from there
            window.open(frame.src, '_blank');
        }
    });

    // A PDF made on the fly (e.g. a POST preview that is not stored): shown as it is, never cached
    let shownBlobUrl = null;
    function showBlob(blob, title, filename) {
        if (shownBlobUrl) URL.revokeObjectURL(shownBlobUrl);
        shownBlobUrl = URL.createObjectURL(blob);
        pdfWantedUrl = shownBlobUrl;
        pdfShownUrl = shownBlobUrl;
        $('#modalPdfPreviewLabel').text(title || 'Preview PDF');
        $('#pdfPreviewError').hide();
        $('#pdfPreviewLoading').css('display', 'flex');
        $('#pdfPreviewDownload').removeClass('d-none disabled').attr({ href: shownBlobUrl, download: filename || 'preview.pdf' });
        $('#pdfPreviewPrint').prop('disabled', false);
        $('#pdfPreviewFrame').attr('src', shownBlobUrl);
        $('#modalPdfPreview').modal('show');
    }

    window.ErmPdfPreview = { fetch: fetchPdf, drop: dropPdfCache, open: openPreview, openHtml: openHtml, printHtml: printHtml, printPdf: printPdf, printBlob: printBlob, showBlob: showBlob };
});
