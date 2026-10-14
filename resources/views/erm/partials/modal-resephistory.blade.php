{{-- Riwayat resep modal (E-Resep dokter + farmasi). Content: erm.partials.resep-riwayat, loaded by the page's .btn-riwayat handler. --}}
<div class="modal fade" id="riwayatModal" tabindex="-1" role="dialog" aria-labelledby="riwayatModalTitle">
  <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
    <div class="modal-content rr-modal">
      <div class="modal-header">
        <h5 class="modal-title" id="riwayatModalTitle">Riwayat Resep</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">&times;</button>
      </div>
      <div class="modal-body" id="riwayatModalContent">
        <!-- Loaded by AJAX -->
      </div>
    </div>
  </div>
</div>

@once
<style>
    .rr-modal { border-radius: 14px; overflow: hidden; background: var(--sb-bg, #fff); color: var(--sb-text-strong, #1e2538); border: 1px solid var(--sb-line, #e2e8f0); }
    .rr-modal .modal-header { border-bottom: 1px solid var(--sb-line, #e2e8f0); }
    .rr-modal .modal-header .modal-title { font-size: 1rem; font-weight: 700; }
    .rr-modal .close { color: var(--sb-text-strong, #1e2538); }
    #riwayatModalContent { background: var(--sb-tile, #f4f6fa); padding: 14px 16px; }

    .rr-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; margin-bottom: 12px; }
    .rr-count { font-size: .78rem; font-weight: 700; color: var(--sb-muted, #8a94ad); text-transform: uppercase; letter-spacing: .04em; }
    .rr-search { max-width: 280px; border-radius: 9px; background: var(--sb-bg, #fff); color: var(--sb-text-strong, #1e2538); border-color: var(--sb-line, #e2e8f0); }
    .rr-empty, .rr-loading { padding: 32px 10px; text-align: center; color: var(--sb-muted, #8a94ad); font-size: .9rem; }

    /* Visit = card with a tinted header band; cards are told apart by the gap + shadow */
    .rr-visit { background: var(--sb-bg, #fff); border: 1px solid var(--sb-line, #e2e8f0);
        border-radius: 12px; margin-bottom: 18px; overflow: hidden; box-shadow: 0 1px 2px rgba(15, 23, 42, .05), 0 4px 14px rgba(15, 23, 42, .06); }
    .rr-visit.is-current { border-color: rgba(37, 99, 235, .55); }
    .rr-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 10px 14px;
        background: var(--sb-tile, #f1f5f9); border-bottom: 1px solid var(--sb-line, #e2e8f0); }
    .rr-visit.is-current .rr-head { background: rgba(37, 99, 235, .13); }
    html.theme-dark .rr-visit { box-shadow: none; }
    /* header: two blocks of the same shape — [icon tile] main text / small line — then Salin on the right.
       The date block has a fixed width so the dokter block starts at the same x in every card. */
    .rr-when { display: flex; align-items: center; flex: 1 1 auto; gap: 8px 24px; min-width: 0; flex-wrap: wrap; }
    .rr-meta { display: flex; align-items: center; gap: 10px; min-width: 0; }
    .rr-meta-date { flex: 0 0 230px; }
    .rr-meta-dokter { flex: 1 1 220px; }
    .rr-meta-text { min-width: 0; line-height: 1.25; }
    .rr-ico { flex: 0 0 32px; width: 32px; height: 32px; border-radius: 9px; display: inline-flex; align-items: center; justify-content: center;
        background: var(--sb-bg, #fff); border: 1px solid var(--sb-line, #e2e8f0); color: #2563eb; font-size: .85rem; }
    .rr-meta-dokter .rr-ico { color: #0d9488; }
    .rr-strong { font-weight: 700; font-size: .88rem; color: var(--sb-text-strong, #1e2538); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .rr-ago { margin-top: 2px; font-weight: 500; color: var(--sb-muted, #8a94ad); font-size: .74rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    html.theme-dark .rr-ico { color: #93c5fd; }
    html.theme-dark .rr-meta-dokter .rr-ico { color: #5eead4; }
    @media (max-width: 575.98px) { .rr-meta-date, .rr-meta-dokter { flex: 1 1 100%; } }
    .rr-badge { flex: 0 0 auto; font-size: .7rem; font-weight: 700; padding: 3px 9px; border-radius: 999px; background: rgba(37, 99, 235, .12); color: #2563eb; }
    .rr-copy { flex: 0 0 auto; border-radius: 8px; font-weight: 600; font-size: .78rem; padding: 3px 10px;
        border: 1px solid rgba(37, 99, 235, .35); background: rgba(37, 99, 235, .07); color: #2563eb; }
    .rr-copy:hover { background: rgba(37, 99, 235, .15); color: #1d4ed8; }

    /* Tables in a visit; fixed layout + colgroup = the same columns in every table and card */
    .rr-table { width: 100%; table-layout: fixed; border-collapse: collapse; font-size: .85rem; }
    .rr-col-obat { width: 44%; }
    .rr-col-jml { width: 12%; }
    .rr-col-aturan { width: 26%; }
    .rr-col-dibuat { width: 18%; }
    .rr-table th { font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--sb-muted, #8a94ad);
        padding: 8px 14px 7px; text-align: left; white-space: nowrap; border-bottom: 1px solid var(--sb-line, #e2e8f0); }
    .rr-table td { padding: 8px 14px; border-bottom: 1px solid var(--sb-line, #e2e8f0); vertical-align: top; word-wrap: break-word; line-height: 1.4; }
    .rr-table > tbody > tr:last-child > td { border-bottom: 0; }
    .rr-table td:first-child { font-weight: 600; }
    .rr-table td:nth-child(3) { color: var(--sb-text, #4b5671); }
    .rr-table td.rr-dibuat { font-size: .76rem; color: var(--sb-muted, #8a94ad); white-space: nowrap; font-weight: 400; }
    .rr-table + .rr-table { border-top: 1px solid var(--sb-line, #e2e8f0); }

    /* Non racikan = blue, racikan = purple: tinted column header with an icon */
    .rr-table th i { margin-right: 6px; }
    .rr-table-nonracikan thead th { background: rgba(37, 99, 235, .06); }
    .rr-table-nonracikan thead th:first-child { color: #2563eb; }
    .rr-table-racikan thead th { background: rgba(124, 58, 237, .07); }
    .rr-table-racikan thead th:first-child { color: #7c3aed; }
    html.theme-dark .rr-table-nonracikan thead th:first-child { color: #93c5fd; }
    html.theme-dark .rr-table-racikan thead th:first-child { color: #c4b5fd; }

    /* Racikan row, then its isi as a sub-table with the same columns (Dosis under Jumlah) */
    .rr-sub { margin-top: 2px; font-size: .74rem; font-weight: 400; color: var(--sb-muted, #8a94ad); }
    .rr-table .rr-racikan > td { border-bottom: 0; padding-bottom: 6px; }
    .rr-table .rr-isi-row > td { padding: 0 0 10px; }
    .rr-isi-table { width: 100%; table-layout: fixed; border-collapse: collapse; font-size: .8rem; }
    /* the outer .rr-table th / td rules reach into the sub-table: set its own here */
    .rr-table .rr-isi-table th { font-size: .62rem; padding: 4px 14px; background: transparent; color: var(--sb-muted, #8a94ad);
        border-bottom: 1px dashed var(--sb-line, #e2e8f0); }
    .rr-table .rr-isi-table td { padding: 4px 14px; font-weight: 400; color: var(--sb-text, #4b5671); border-bottom: 1px dashed var(--sb-line, #e2e8f0); }
    .rr-table .rr-isi-table tbody tr:last-child td { border-bottom: 0; }
    .rr-table .rr-isi-table th:first-child, .rr-table .rr-isi-table td:first-child { padding-left: 30px; }
    .is-off { text-decoration: line-through; opacity: .6; }
    .rr-flag { font-size: .66rem; font-weight: 700; color: #b45309; background: rgba(217, 119, 6, .12); padding: 1px 6px; border-radius: 5px; }
    .rr-more { border-radius: 10px; font-weight: 600; background: var(--sb-bg, #fff); border: 1px solid var(--sb-line, #e2e8f0); color: var(--sb-text-strong, #1e2538); }
    .rr-more:hover { border-color: rgba(37, 99, 235, .45); color: #2563eb; }

    html.theme-dark .rr-badge, html.theme-dark .rr-copy, html.theme-dark .rr-more:hover { color: #93c5fd; }
    html.theme-dark .rr-flag { color: #fbbf24; }
</style>
<script>
    // Search over the visits shown so far, and "Muat lebih banyak" (next page appended in place).
    // This partial sits in the page content, before jQuery is loaded: bind once the page is ready.
    document.addEventListener('DOMContentLoaded', function () {
    $(document).on('input', '#riwayatModalContent .rr-search', function () {
        const term = this.value.trim().toLowerCase();
        $('#riwayatModalContent .rr-visit').each(function () {
            $(this).toggle(!term || $(this).text().toLowerCase().includes(term));
        });
    });
    $(document).on('click', '#riwayatModalContent .rr-more', function () {
        const $btn = $(this).prop('disabled', true).text('Memuat...');
        $.get($btn.data('url'))
            .done(function (html) {
                $btn.replaceWith(html);
                $('#riwayatModalContent .rr-search').trigger('input');
            })
            .fail(function () {
                $btn.prop('disabled', false).text('Gagal memuat, coba lagi');
            });
    });
    });
</script>
@endonce
