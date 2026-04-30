@extends('layouts.hrd.app')
@section('title', 'HRD | KPI Assessment Period Detail')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <div id="kpiPeriodDetailSection">
        <div class="row mb-3">
            <div class="col-md-8">
                <h3 class="mb-1">{{ $period->name }}</h3>
                <p class="text-muted mb-0">Bulan {{ $period->assessment_month->format('F Y') }} | Status {{ strtoupper($period->status) }}</p>
            </div>
            <div class="col-md-4 text-md-right mt-3 mt-md-0">
                <a href="{{ route('hrd.kpi_assessments.periods.index') }}" class="btn btn-light">Kembali</a>
                @if($period->status !== 'closed')
                    <form method="POST" action="{{ route('hrd.kpi_assessments.periods.close', $period) }}" class="d-inline" id="kpiPeriodCloseForm">
                        @csrf
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Tutup periode ini? Setelah ditutup assessment tidak bisa diubah lagi.')">Tutup Periode</button>
                    </form>
                @endif
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Divisi</th>
                                <th>Jabatan</th>
                                <th>Submitted</th>
                                <th>Pending</th>
                                <th>Manager</th>
                                <th>Head Manager</th>
                                <th>HRD</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rows as $row)
                                <tr>
                                    <td>{{ $row['employee']->nama }}</td>
                                    <td>{{ optional($row['employee']->division)->name ?: '-' }}</td>
                                    <td>{{ optional($row['employee']->position)->name ?: '-' }}</td>
                                    <td>{{ $row['submitted_count'] }}</td>
                                    <td>{{ $row['pending_count'] }}</td>
                                    <td>{{ $row['manager_score'] ?? '-' }}</td>
                                    <td>{{ $row['head_manager_score'] ?? '-' }}</td>
                                    <td>{{ $row['hrd_score'] ?? '-' }}</td>
                                    <td><strong>{{ $row['total_score'] }}</strong></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted">Belum ada assignment pada periode ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function () {
        $(document).on('submit', '#kpiPeriodCloseForm', function (event) {
            event.preventDefault();

            const $form = $(this);

            $.ajax({
                url: $form.attr('action'),
                method: 'POST',
                data: $form.serialize(),
                headers: {
                    'Accept': 'application/json'
                }
            }).done(function (response) {
                $.get(window.location.href, function (html) {
                    const $html = $(html);
                    $('#kpiPeriodDetailSection').html($html.find('#kpiPeriodDetailSection').html());
                    Swal.fire('Sukses', response.message, 'success');
                }).fail(function () {
                    Swal.fire('Warning', response.message + ' Namun refresh tampilan gagal, silakan muat ulang manual.', 'warning');
                });
            }).fail(function (xhr) {
                const message = xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message
                    : 'Gagal menutup periode KPI Assessment.';
                Swal.fire('Error', message, 'error');
            });
        });
    });
</script>
@endsection