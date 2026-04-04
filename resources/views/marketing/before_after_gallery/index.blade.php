@extends('layouts.marketing.app')

@section('title', 'Before After Gallery - Marketing')

@section('navbar')
    @include('layouts.marketing.navbar')
@endsection

@section('content')
<style>
    .gallery-filter-card {
        border: 1px solid rgba(31, 41, 55, 0.08);
        box-shadow: 0 18px 48px rgba(15, 23, 42, 0.08);
    }

    .gallery-summary-card {
        background: linear-gradient(135deg, #f8fbff 0%, #eef6ff 100%);
        border: 1px solid rgba(59, 130, 246, 0.14);
        border-radius: 14px;
    }

    .gallery-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1.25rem;
    }

    .gallery-card {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 14px;
        overflow: hidden;
        background: #fff;
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.06);
    }

    .gallery-card__body {
        padding: 0.9rem;
    }

    .gallery-info-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin-bottom: 0.75rem;
        overflow: hidden;
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 10px;
    }

    .gallery-info-table tr:not(:last-child) td {
        border-bottom: 1px solid rgba(15, 23, 42, 0.06);
    }

    .gallery-info-table td {
        padding: 0.48rem 0.7rem;
        vertical-align: top;
        background: #fff;
    }

    .gallery-info-table td:first-child {
        width: 118px;
        background: #f8fafc;
    }

    .gallery-info-table td:nth-child(3) {
        width: 118px;
        background: #f8fafc;
    }

    .gallery-info-label {
        font-size: 0.65rem;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #64748b;
        font-weight: 700;
        text-align: left;
    }

    .gallery-info-value {
        color: #0f172a;
        font-weight: 400;
        font-size: 0.88rem;
        word-break: break-word;
        line-height: 1.3;
        text-align: left;
    }

    .gallery-code-list {
        display: flex;
        flex-wrap: wrap;
        gap: 0.3rem;
    }

    .gallery-code-pill {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 0.2rem 0.55rem;
        background: #e0f2fe;
        color: #0c4a6e;
        font-size: 0.72rem;
        font-weight: 600;
    }

    .gallery-images {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.7rem;
    }

    .gallery-image-panel {
        border-radius: 10px;
        overflow: hidden;
        background: #f8fafc;
        border: 1px solid rgba(15, 23, 42, 0.08);
    }

    .gallery-image-panel__label {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0.65rem;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #334155;
        background: rgba(148, 163, 184, 0.12);
    }

    .gallery-image-panel__body {
        aspect-ratio: 4 / 4.4;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);
        padding: 0.55rem;
    }

    .gallery-image-panel__body img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 8px;
        display: block;
    }

    .gallery-image-empty {
        color: #94a3b8;
        font-size: 0.9rem;
        text-align: center;
        padding: 1.25rem;
    }

    .gallery-empty-state {
        border: 1px dashed rgba(100, 116, 139, 0.4);
        border-radius: 14px;
        padding: 2.5rem 1.5rem;
        text-align: center;
        background: #fff;
        color: #64748b;
    }

    @media (max-width: 991.98px) {
        .gallery-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 767.98px) {
        .gallery-info-table,
        .gallery-info-table tbody,
        .gallery-info-table tr,
        .gallery-info-table td,
        .gallery-images {
            display: block;
            width: 100%;
        }

        .gallery-info-table td:first-child {
            width: 100%;
            border-bottom: none;
            padding-bottom: 0.25rem;
        }

        .gallery-info-table td:nth-child(3) {
            width: 100%;
            border-top: 1px solid rgba(15, 23, 42, 0.06);
            border-bottom: none;
            padding-top: 0.55rem;
            padding-bottom: 0.25rem;
        }

        .gallery-info-table td:last-child {
            padding-top: 0;
        }
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row align-items-center">
                    <div class="col">
                        <h4 class="page-title mb-1">Before After Gallery</h4>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('marketing.dashboard') }}">Marketing</a></li>
                            <li class="breadcrumb-item active">Before After Gallery</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card gallery-filter-card">
                <div class="card-body">
                    <div class="form-row align-items-end">
                        <div class="form-group col-lg-4 col-md-6">
                            <label for="filter_tindakan">Tindakan</label>
                            <select id="filter_tindakan" class="form-control"></select>
                        </div>
                        <div class="form-group col-lg-4 col-md-6">
                            <label for="filter_kode_tindakan">Kode Tindakan</label>
                            <select id="filter_kode_tindakan" class="form-control"></select>
                        </div>
                        <div class="form-group col-lg-4 col-md-6">
                            <label for="filter_pasien">Pasien</label>
                            <select id="filter_pasien" class="form-control"></select>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap align-items-center">
                        <button type="button" id="searchGalleryBtn" class="btn btn-primary mr-2 mb-2">
                            <i class="fas fa-search mr-1"></i> Search
                        </button>
                        <button type="button" id="resetGalleryBtn" class="btn btn-outline-secondary mb-2">
                            Reset
                        </button>
                        <div class="text-muted ml-auto mb-2">Leave filters empty to show all available before/after photos.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="gallery-summary-card p-3 p-md-4 d-flex flex-wrap align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase text-muted small font-weight-bold">Search Result</div>
                    <h4 class="mb-0" id="galleryResultCount">0 items</h4>
                </div>
                <div class="text-muted mt-3 mt-md-0" id="galleryResultHint">Use the filters above, then click Search.</div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div id="galleryResults" class="gallery-empty-state">
                No gallery data loaded yet.
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        function escapeHtml(value) {
            if (value === null || value === undefined) return '';
            return $('<div>').text(String(value)).html();
        }

        function renderImagePanel(label, imageUrl) {
            if (!imageUrl) {
                return '<div class="gallery-image-panel">'
                    + '<div class="gallery-image-panel__label">' + label + '</div>'
                    + '<div class="gallery-image-panel__body"><div class="gallery-image-empty">No image available</div></div>'
                    + '</div>';
            }

            return '<div class="gallery-image-panel">'
                + '<div class="gallery-image-panel__label">'
                + '<span>' + label + '</span>'
                + '<a href="' + escapeHtml(imageUrl) + '" target="_blank" class="btn btn-sm btn-light">Open</a>'
                + '</div>'
                + '<a href="' + escapeHtml(imageUrl) + '" target="_blank" class="gallery-image-panel__body">'
                + '<img src="' + escapeHtml(imageUrl) + '" alt="' + label + '">'
                + '</a>'
                + '</div>';
        }

        function renderGallery(records) {
            if (!Array.isArray(records) || records.length === 0) {
                $('#galleryResults').html('<div class="gallery-empty-state">No before/after photos found for the selected filters.</div>');
                return;
            }

            var html = '<div class="gallery-grid">';

            records.forEach(function(record) {
                var kodeHtml = '';
                if (Array.isArray(record.kode_tindakans) && record.kode_tindakans.length) {
                    record.kode_tindakans.forEach(function(kode) {
                        kodeHtml += '<span class="gallery-code-pill">' + escapeHtml(kode) + '</span>';
                    });
                } else {
                    kodeHtml = '<span class="text-muted">No kode tindakan linked</span>';
                }

                html += '<div class="gallery-card">'
                    + '<div class="gallery-card__body">'
                    + '<div class="d-flex align-items-start justify-content-between mb-3">'
                    + (record.allow_post
                        ? '<span class="badge badge-success">Allowed Post</span>'
                        : '<span class="badge badge-secondary">Not Allowed</span>')
                    + '</div>'
                    + '<table class="gallery-info-table">'
                    + '<tbody>'
                    + '<tr>'
                    + '<td><div class="gallery-info-label">Tindakan</div></td>'
                    + '<td><div class="gallery-info-value">' + escapeHtml(record.tindakan_nama) + '</div></td>'
                    + '<td><div class="gallery-info-label">Tanggal Visit</div></td>'
                    + '<td><div class="gallery-info-value">' + escapeHtml(record.tanggal_visit) + '</div></td>'
                    + '</tr>'
                    + '<tr>'
                    + '<td><div class="gallery-info-label">Pasien</div></td>'
                    + '<td><div class="gallery-info-value">' + escapeHtml(record.pasien_nama) + ' (' + escapeHtml(record.pasien_id) + ')</div></td>'
                    + '<td><div class="gallery-info-label">Dokter</div></td>'
                    + '<td><div class="gallery-info-value">' + escapeHtml(record.dokter_nama) + '</div></td>'
                    + '</tr>'
                    + '<tr>'
                    + '<td><div class="gallery-info-label">Kode Tindakan</div></td>'
                    + '<td colspan="3"><div class="gallery-info-value"><div class="gallery-code-list">' + kodeHtml + '</div></div></td>'
                    + '</tr>'
                    + '</tbody>'
                    + '</table>'
                    + '<div class="gallery-images">'
                    + renderImagePanel('Before', record.before_image_url)
                    + renderImagePanel('After', record.after_image_url)
                    + '</div>'
                    + '</div>'
                    + '</div>';
            });

            html += '</div>';
            $('#galleryResults').html(html);
        }

        function loadGallery() {
            var $btn = $('#searchGalleryBtn');
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Searching');
            $('#galleryResultHint').text('Loading gallery data...');

            $.ajax({
                url: '{{ route('marketing.before_after_gallery.search') }}',
                type: 'GET',
                dataType: 'json',
                data: {
                    tindakan_id: $('#filter_tindakan').val(),
                    kode_tindakan_id: $('#filter_kode_tindakan').val(),
                    pasien_id: $('#filter_pasien').val()
                },
                success: function(response) {
                    var count = response && typeof response.count !== 'undefined' ? response.count : 0;
                    $('#galleryResultCount').text(count + ' items');
                    $('#galleryResultHint').text(count > 0
                        ? 'Showing before/after photos based on the selected filters.'
                        : 'No matching gallery data found.');
                    renderGallery(response.data || []);
                },
                error: function(xhr) {
                    var message = (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : 'Failed to load gallery data.';
                    $('#galleryResultCount').text('0 items');
                    $('#galleryResultHint').text('Search failed.');
                    $('#galleryResults').html('<div class="gallery-empty-state text-danger">' + escapeHtml(message) + '</div>');
                },
                complete: function() {
                    $btn.prop('disabled', false).html('<i class="fas fa-search mr-1"></i> Search');
                }
            });
        }

        $('#filter_tindakan').select2({
            width: '100%',
            placeholder: 'Search tindakan...',
            allowClear: true,
            minimumInputLength: 1,
            ajax: {
                url: '{{ route('marketing.tindakan.search') }}',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return { q: params.term };
                },
                processResults: function(data) {
                    return { results: data.results || [] };
                }
            }
        });

        $('#filter_kode_tindakan').select2({
            width: '100%',
            placeholder: 'Search kode tindakan...',
            allowClear: true,
            minimumInputLength: 1,
            ajax: {
                url: '{{ route('marketing.kode_tindakan.search') }}',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return { q: params.term };
                },
                processResults: function(data) {
                    return { results: data.results || [] };
                }
            }
        });

        $('#filter_pasien').select2({
            width: '100%',
            placeholder: 'Search pasien...',
            allowClear: true,
            minimumInputLength: 2,
            ajax: {
                url: '{{ route('marketing.before_after_gallery.pasien.search') }}',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return { q: params.term };
                },
                processResults: function(data) {
                    return { results: data.results || [] };
                }
            }
        });

        $('#searchGalleryBtn').on('click', function() {
            loadGallery();
        });

        $('#resetGalleryBtn').on('click', function() {
            $('#filter_tindakan').val(null).trigger('change');
            $('#filter_kode_tindakan').val(null).trigger('change');
            $('#filter_pasien').val(null).trigger('change');
            $('#galleryResultCount').text('0 items');
            $('#galleryResultHint').text('Use the filters above, then click Search.');
            $('#galleryResults').html('<div class="gallery-empty-state">No gallery data loaded yet.</div>');
        });
    });
</script>
@endsection