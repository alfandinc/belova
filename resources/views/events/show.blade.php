@extends('layouts.erm.app')

@section('title', 'Event | ' . $event->nama_event)

@section('navbar')
    @include('layouts.erm.navbar-ngaji')
@endsection

@section('content')
@php
    $isAktif = $event->status === 'aktif';
@endphp
<style>
    /* Billing page shown in a modal (iframe with ?embed=1), same as the Finance billing list */
    #modalEventBilling .modal-dialog { max-width: 98vw; width: 98vw; height: 96vh; margin: 2vh auto; }
    #modalEventBilling .modal-content { height: 100%; }
    #modalEventBilling .modal-header { padding: .5rem 1rem; }
    #modalEventBilling .modal-body { padding: 0; position: relative; flex: 1 1 auto; overflow: hidden; }
    #modalEventBilling iframe { width: 100%; height: 100%; border: 0; display: block; }
    #modalEventBilling .billing-create-loading { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; }
    body.billing-create-open #belovaChatWidget { display: none !important; }
    .event-stat .stat-value { font-size: 1.35rem; font-weight: 700; }
</style>

<div class="container-fluid">
    <div class="row mt-3 mb-2">
        <div class="col-12 d-flex flex-wrap justify-content-between align-items-start" style="gap:.5rem;">
            <div>
                <a href="{{ route('events.index') }}" class="small"><i class="fas fa-arrow-left mr-1"></i> Semua Event</a>
                <h3 class="mb-0 font-weight-bold mt-1">
                    {{ $event->nama_event }}
                    @if($isAktif)
                        <span class="badge badge-success align-middle" style="font-size:.6em;">Aktif</span>
                    @else
                        <span class="badge badge-secondary align-middle" style="font-size:.6em;">Selesai</span>
                    @endif
                </h3>
                <div class="text-muted small">
                    {{ $event->kode_event }}
                    &middot; {{ optional($event->tanggal_mulai)->translatedFormat('d M Y H:i') ?? '-' }}
                    @if($event->tanggal_selesai) s/d {{ $event->tanggal_selesai->translatedFormat('d M Y H:i') }} @endif
                    &middot; {{ optional($event->klinik)->nama ?? '-' }}
                    @if($event->lokasi) &middot; {{ $event->lokasi }} @endif
                </div>
                @if($event->promos->isNotEmpty())
                    <div class="mt-1">
                        @foreach($event->promos as $promo)
                            <span class="badge badge-info mr-1">{{ $promo->name }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
            @if($isAktif)
                <a href="{{ route('events.billing.create', $event->id) }}" class="btn btn-primary btn-open-event-billing" data-title="Billing Baru: {{ $event->nama_event }}">
                    <i class="fas fa-file-invoice-dollar mr-1"></i> Billing Baru
                </a>
            @endif
        </div>
    </div>

    <div class="row">
        <div class="col-6 col-md-3 mb-3"><div class="card mb-0 event-stat"><div class="card-body py-3"><div class="text-muted small">Pasien</div><div class="stat-value" id="stat-patients">-</div></div></div></div>
        <div class="col-6 col-md-3 mb-3"><div class="card mb-0 event-stat"><div class="card-body py-3"><div class="text-muted small">Pasien Baru dari Event</div><div class="stat-value" id="stat-new">-</div></div></div></div>
        <div class="col-6 col-md-3 mb-3"><div class="card mb-0 event-stat"><div class="card-body py-3"><div class="text-muted small">Total Billing</div><div class="stat-value" id="stat-total">-</div></div></div></div>
        <div class="col-6 col-md-3 mb-3"><div class="card mb-0 event-stat"><div class="card-body py-3"><div class="text-muted small">Sudah Dibayar</div><div class="stat-value text-success" id="stat-paid">-</div></div></div></div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Pasien Event</h5>
                    <table id="event-patients-table" class="table table-striped table-bordered w-100">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Pasien</th>
                                <th>No HP</th>
                                <th>Invoice</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEventBilling" tabindex="-1" role="dialog" aria-labelledby="modalEventBillingLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEventBillingLabel">Billing</h5>
                <div class="d-flex align-items-center">
                    <a href="#" id="eventBillingOpenTab" class="btn btn-sm btn-light mr-2" target="_blank" title="Buka di tab baru"><i class="fas fa-external-link-alt"></i></a>
                    <button type="button" class="close" id="eventBillingCloseBtn" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
                </div>
            </div>
            <div class="modal-body">
                <div class="billing-create-loading">
                    <div class="spinner-border text-primary" role="status"><span class="sr-only">Memuat...</span></div>
                </div>
                <iframe id="eventBillingFrame" title="Billing" src="about:blank"></iframe>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(function () {
        const patientsUrl = '{{ route('events.patients', $event->id) }}';

        function esc(value) {
            return $('<div>').text(value == null ? '' : String(value)).html();
        }

        function rupiah(n) {
            return 'Rp ' + Number(n || 0).toLocaleString('id-ID', { maximumFractionDigits: 0 });
        }

        const statusBadge = {
            'Lunas': 'badge-success',
            'Belum Lunas': 'badge-warning',
            'Piutang': 'badge-info',
            'Belum Transaksi': 'badge-danger'
        };

        const table = $('#event-patients-table').DataTable({
            ajax: {
                url: patientsUrl,
                dataSrc: function (json) {
                    const s = json.summary || {};
                    $('#stat-patients').text(s.patients || 0);
                    $('#stat-new').text(s.new_patients || 0);
                    $('#stat-total').text(rupiah(s.total));
                    $('#stat-paid').text(rupiah(s.paid));
                    return json.data || [];
                }
            },
            order: [[0, 'desc']],
            language: {
                search: 'Cari:',
                lengthMenu: 'Tampilkan _MENU_ data',
                zeroRecords: 'Belum ada pasien untuk event ini',
                emptyTable: 'Belum ada pasien untuk event ini',
                info: 'Menampilkan _START_ - _END_ dari _TOTAL_ pasien',
                infoEmpty: '',
                infoFiltered: '(difilter dari _MAX_)',
                paginate: { next: 'Selanjutnya', previous: 'Sebelumnya' }
            },
            columns: [
                { data: 'tanggal', render: function (data, type, row) {
                    if (type !== 'display') return (data || '') + ' ' + (row.waktu || '');
                    const d = data ? moment(data).format('DD MMM YYYY') : '-';
                    return `<div class="font-weight-bold">${esc(d)}</div>` + (row.waktu ? `<div class="small text-muted">${esc(row.waktu)}</div>` : '');
                } },
                { data: 'nama', render: function (data, type, row) {
                    if (type !== 'display') return (data || '') + ' ' + (row.pasien_id || '');
                    const badge = row.is_new_patient ? ' <span class="badge badge-primary ml-1" title="Pasien baru terdaftar dari event ini">BARU</span>' : '';
                    return `<div class="font-weight-bold">${esc(data)}${badge}</div><small class="text-muted">${esc(row.pasien_id || '-')}</small>`;
                } },
                { data: 'no_hp', defaultContent: '-', render: function (data) { return data ? esc(data) : '-'; } },
                { data: 'invoice_number', render: function (data) { return data ? esc(data) : '<span class="text-muted">-</span>'; } },
                { data: 'total', className: 'text-nowrap', render: function (data, type) {
                    if (type !== 'display') return data || 0;
                    return data === null ? '<span class="text-muted">-</span>' : `<span class="font-weight-bold">${rupiah(data)}</span>`;
                } },
                { data: 'status', render: function (data, type) {
                    if (type !== 'display') return data;
                    return `<span class="badge ${statusBadge[data] || 'badge-secondary'}">${esc(data)}</span>`;
                } },
                { data: null, orderable: false, searchable: false, className: 'text-nowrap', render: function (data, type, row) {
                    let html = '<div class="btn-group" role="group">';
                    if (row.billing_url) {
                        html += `<a href="${esc(row.billing_url)}" class="btn btn-sm btn-primary btn-open-event-billing" data-title="Billing: ${esc(row.nama)}${row.invoice_number ? ' - ' + esc(row.invoice_number) : ''}"><i class="fas fa-file-invoice-dollar mr-1"></i>Billing</a>`;
                    }
                    if (row.print_url) {
                        html += `<a href="${esc(row.print_url)}" class="btn btn-sm btn-light" target="_blank" title="Cetak Nota"><i class="ti-printer mr-1"></i>Print</a>`;
                    }
                    html += '</div>';
                    return (row.billing_url || row.print_url) ? html : '<span class="text-muted small">Billing di Finance</span>';
                } }
            ]
        });

        // ---- Event billing in a modal (iframe with ?embed=1) ----
        function embedUrl(href) {
            const u = new URL(href, window.location.href);
            u.searchParams.set('embed', '1');
            return u.toString();
        }

        $(document).on('click', '.btn-open-event-billing', function (e) {
            if (e.ctrlKey || e.metaKey || e.shiftKey || e.which === 2) return;
            e.preventDefault();
            const href = $(this).attr('href');
            $('#modalEventBillingLabel').text($(this).data('title') || 'Billing');
            $('#eventBillingOpenTab').attr('href', href);
            $('#modalEventBilling .billing-create-loading').show();
            $('#eventBillingFrame').attr('src', embedUrl(href));
            $('body').addClass('billing-create-open');
            $('#modalEventBilling').modal('show');
        });

        $('#eventBillingFrame').on('load', function () {
            if (this.getAttribute('src') !== 'about:blank') {
                $('#modalEventBilling .billing-create-loading').hide();
            }
        });

        $('#eventBillingCloseBtn').on('click', function () {
            $('#modalEventBilling').modal('hide');
        });

        // The embedded billing page asks to close the modal / reports it is showing its own loader
        window.addEventListener('message', function (e) {
            if (e.origin !== window.location.origin || !e.data) return;
            if (e.data.type === 'billing-embed:close') $('#modalEventBilling').modal('hide');
            if (e.data.type === 'billing-embed:ready') $('#modalEventBilling .billing-create-loading').hide();
        });

        $('#modalEventBilling').on('hidden.bs.modal', function () {
            $('body').removeClass('billing-create-open');
            $('#eventBillingFrame').attr('src', 'about:blank');
            table.ajax.reload(null, false);
        });
    });
</script>
@endsection
