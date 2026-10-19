/*
 * Image drop zones for BCL forms: paste (Ctrl+V), drag & drop or pick a file.
 *
 * Markup:
 *   <div class="bcl-dropzone" data-default>            <- data-default: receives Ctrl+V when no zone is focused
 *       <input type="file" name="bukti_transfer" accept="image/*">
 *   </div>
 * The zone builds its own UI around the input. Large images are re-encoded to JPEG (max 1800px)
 * before they are put into the input, so pasted screenshots upload fast and pass size limits.
 */
(function ($) {
    'use strict';

    var MAX_SIDE = 1800;
    var COMPRESS_OVER = 900 * 1024;

    function humanSize(bytes) {
        return bytes > 1024 * 1024 ? (bytes / 1024 / 1024).toFixed(1) + ' MB' : Math.round(bytes / 1024) + ' KB';
    }

    function compress(file) {
        var d = $.Deferred();
        if (!/^image\//.test(file.type) || file.type === 'image/gif' || (file.size <= COMPRESS_OVER && file.type !== 'image/png')) {
            return d.resolve(file).promise();
        }
        var img = new Image();
        var url = URL.createObjectURL(file);
        img.onload = function () {
            var scale = Math.min(1, MAX_SIDE / Math.max(img.width, img.height));
            var canvas = document.createElement('canvas');
            canvas.width = Math.round(img.width * scale);
            canvas.height = Math.round(img.height * scale);
            var ctx = canvas.getContext('2d');
            ctx.fillStyle = '#fff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
            URL.revokeObjectURL(url);
            canvas.toBlob(function (blob) {
                if (!blob || blob.size >= file.size) return d.resolve(file);
                var name = (file.name || 'gambar').replace(/\.[^.]+$/, '') + '.jpg';
                d.resolve(new File([blob], name, { type: 'image/jpeg', lastModified: Date.now() }));
            }, 'image/jpeg', 0.85);
        };
        img.onerror = function () { URL.revokeObjectURL(url); d.resolve(file); };
        img.src = url;
        return d.promise();
    }

    function setFile($zone, file) {
        var input = $zone.find('input[type="file"]')[0];
        if (!file) {
            input.value = '';
            render($zone, null);
            notify($zone, input);
            return;
        }
        if (!/^image\//.test(file.type)) {
            if (window.bclToast) window.bclToast('File harus berupa gambar.', 'error');
            return;
        }
        $zone.addClass('is-busy');
        compress(file).done(function (finalFile) {
            var dt = new DataTransfer();
            dt.items.add(finalFile);
            input.files = dt.files;
            render($zone, finalFile);
            notify($zone, input);
        }).always(function () { $zone.removeClass('is-busy'); });
    }

    // Let other scripts react (e.g. 'bukti wajib' hints) without re-processing our own change.
    function notify($zone, input) {
        $zone.data('internal', true);
        $(input).trigger('change');
        $zone.data('internal', false);
    }

    function render($zone, file) {
        var $preview = $zone.find('.bcl-dropzone__preview');
        var old = $preview.data('url');
        if (old) URL.revokeObjectURL(old);
        if (!file) {
            $zone.removeClass('has-file');
            $preview.empty().data('url', null);
            return;
        }
        var url = URL.createObjectURL(file);
        $zone.addClass('has-file');
        $preview.data('url', url).html(
            '<img src="' + url + '" alt="">' +
            '<span class="bcl-dropzone__name">' + $('<div>').text(file.name).html() + ' · ' + humanSize(file.size) + '</span>' +
            '<button type="button" class="btn btn-sm btn-link text-danger bcl-dropzone__remove">Hapus</button>'
        );
    }

    function init(zone) {
        var $zone = $(zone);
        if ($zone.data('ready')) return;
        $zone.data('ready', true).attr('tabindex', 0);
        var $input = $zone.find('input[type="file"]').addClass('d-none');
        $zone.append(
            '<div class="bcl-dropzone__empty"><i data-feather="image"></i><span><strong>Tempel (Ctrl+V)</strong>, seret gambar ke sini, atau <a href="#" class="bcl-dropzone__pick">pilih file</a></span></div>' +
            '<div class="bcl-dropzone__preview"></div>'
        );
        if (window.feather) window.feather.replace();

        $zone.on('click', '.bcl-dropzone__pick', function (e) { e.preventDefault(); $input.trigger('click'); });
        $zone.on('click', '.bcl-dropzone__remove', function (e) { e.preventDefault(); setFile($zone, null); });
        $input.on('change', function () {
            if ($zone.data('internal')) return;
            if (this.files && this.files[0]) setFile($zone, this.files[0]);
        });
        $zone.on('dragover dragenter', function (e) { e.preventDefault(); $zone.addClass('is-drag'); });
        $zone.on('dragleave drop', function () { $zone.removeClass('is-drag'); });
        $zone.on('drop', function (e) {
            e.preventDefault();
            var files = e.originalEvent.dataTransfer && e.originalEvent.dataTransfer.files;
            if (files && files[0]) setFile($zone, files[0]);
        });
        $zone.on('focusin mouseenter', function () {
            $('.bcl-dropzone.is-target').removeClass('is-target');
            $zone.addClass('is-target');
        });
    }

    window.bclDropzones = function (scope) {
        $(scope || document).find('.bcl-dropzone').each(function () { init(this); });
    };
    window.bclDropzoneReset = function (scope) {
        $(scope || document).find('.bcl-dropzone').each(function () { setFile($(this), null); });
    };

    // Ctrl+V anywhere in an open dialog: the focused/hovered zone, else the dialog's default zone.
    $(document).on('paste', function (e) {
        var items = (e.originalEvent.clipboardData || {}).items || [];
        var file = null;
        for (var i = 0; i < items.length; i++) {
            if (items[i].kind === 'file' && /^image\//.test(items[i].type)) {
                file = items[i].getAsFile();
                break;
            }
        }
        if (!file) return;
        var $scope = $('.modal.show').filter(function () { return $(this).find('.bcl-dropzone').length > 0; }).last();
        if (!$scope.length) $scope = $(document.body);
        var $zone = $scope.find('.bcl-dropzone.is-target:visible').first();
        if (!$zone.length) $zone = $scope.find('.bcl-dropzone[data-default]:visible').first();
        if (!$zone.length) $zone = $scope.find('.bcl-dropzone:visible').first();
        if (!$zone.length) return;
        e.preventDefault();
        setFile($zone, file);
        if (window.bclToast) window.bclToast('Gambar ditempel.', 'success');
    });

    $(function () { window.bclDropzones(); });
})(jQuery);
