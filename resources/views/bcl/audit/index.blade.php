@extends('layouts.bcl.app')

@section('title', 'Log Aktivitas')

@section('bcl-content')
<div class="page-title-box d-flex flex-wrap align-items-center justify-content-between" style="gap: 10px;">
    <div>
        <h4 class="page-title mb-0">Log Aktivitas Kos BCL</h4>
        <span>Penghapusan, koreksi, pembatalan, refund dan uang jaminan (500 terakhir).</span>
    </div>
    <form method="GET" action="{{ route('bcl.audit') }}" class="d-flex" style="gap: 8px;">
        <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Cari nomor / keterangan">
        <button class="btn btn-primary bcl-btn" type="submit"><i data-feather="search"></i> Cari</button>
    </form>
</div>
@include('bcl.partials.subnav', ['group' => 'settings'])

@php
    $labels = [
        'delete_transaction' => ['Hapus transaksi', 'is-bad'], 'delete_payment' => ['Hapus pembayaran', 'is-bad'], 'delete_expense' => ['Hapus pengeluaran', 'is-bad'],
        'cancel_extra' => ['Batal tambahan sewa', 'is-warn'], 'cancel_booking' => ['Batal booking', 'is-warn'], 'refund' => ['Refund', 'is-warn'],
        'update_transaction' => ['Koreksi transaksi', 'is-info'], 'update_payment' => ['Koreksi pembayaran', 'is-info'], 'checkout' => ['Keluar', 'is-good'],
        'guarantee' => ['Uang jaminan', 'is-accent'], 'discount' => ['Diskon', 'is-info'], 'custom_charge' => ['Tagihan lain', 'is-info'], 'data_fix' => ['Koreksi data lama', ''],
    ];
@endphp
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead><tr><th>Waktu</th><th>Oleh</th><th>Aksi</th><th>Nomor</th><th>Keterangan</th><th>Detail</th></tr></thead>
                <tbody>
                @forelse($logs as $log)
                    @php [$label, $tone] = $labels[$log->action] ?? [$log->action, '']; @endphp
                    <tr>
                        <td class="text-nowrap">{{ optional($log->created_at)->format('d/m/Y H:i') }}</td>
                        <td>{{ $log->user->name ?? 'Sistem' }}</td>
                        <td><span class="bcl-pill {{ $tone }}">{{ $label }}</span></td>
                        <td>{{ $log->subject }}</td>
                        <td>{{ $log->description }}</td>
                        <td>
                            @if($log->data)
                            <details><summary class="bcl-link">lihat</summary><pre class="small mb-0" style="white-space: pre-wrap; max-width: 420px;">{{ json_encode($log->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></details>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-3">Belum ada aktivitas.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
