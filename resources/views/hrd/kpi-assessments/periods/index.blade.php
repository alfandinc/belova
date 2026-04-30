@extends('layouts.hrd.app')
@section('title', 'HRD | KPI Assessment Periods')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card" id="kpiPeriodCreateSection">
                <div class="card-body">
                    <h4 class="card-title mb-3">Buat Periode Baru</h4>
                    <p class="text-muted">Periode baru langsung menyalin indikator aktif bulan ini dan membuat assignment assessor sesuai hierarki.</p>

                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <form method="POST" action="{{ route('hrd.kpi_assessments.periods.store') }}" id="kpiPeriodCreateForm">
                        @csrf
                        <div class="form-group">
                            <label for="assessment_month">Bulan Assessment</label>
                            <input type="month" id="assessment_month" name="assessment_month" class="form-control @error('assessment_month') is-invalid @enderror" value="{{ old('assessment_month') }}" required>
                            @error('assessment_month')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="name">Nama Periode</label>
                            <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Opsional, default otomatis per bulan">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary">Buat Periode</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8 mb-4">
            <div class="card" id="kpiPeriodListSection">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h4 class="card-title mb-1">Periode KPI Assessment</h4>
                            <p class="text-muted mb-0">Setiap periode menyimpan snapshot indikator sendiri sehingga perubahan bulan berikutnya tidak mengubah data lama.</p>
                        </div>
                        <span class="badge badge-info">{{ $periods->count() }} periode</span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Periode</th>
                                    <th>Bulan</th>
                                    <th>Status</th>
                                    <th>Total Assignment</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($periods as $period)
                                    <tr>
                                        <td>{{ $period->name }}</td>
                                        <td>{{ $period->assessment_month->format('F Y') }}</td>
                                        <td>
                                            <span class="badge badge-{{ $period->status === 'closed' ? 'secondary' : 'success' }}">
                                                {{ strtoupper($period->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $period->assessments_count }}</td>
                                        <td>
                                            <a href="{{ route('hrd.kpi_assessments.periods.show', $period) }}" class="btn btn-sm btn-outline-primary">Lihat</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">Belum ada periode KPI Assessment.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function () {
        function refreshPeriodIndexSections() {
            return $.get(window.location.href, function (html) {
                const $html = $(html);
                $('#kpiPeriodCreateSection').html($html.find('#kpiPeriodCreateSection').html());
                $('#kpiPeriodListSection').html($html.find('#kpiPeriodListSection').html());
            });
        }

        $(document).on('submit', '#kpiPeriodCreateForm', function (event) {
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
                refreshPeriodIndexSections().done(function () {
                    Swal.fire('Sukses', response.message, 'success');
                }).fail(function () {
                    Swal.fire('Warning', response.message + ' Namun refresh tampilan gagal, silakan muat ulang manual.', 'warning');
                });
            }).fail(function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    Swal.fire('Validasi gagal', Object.values(xhr.responseJSON.errors).flat().join('<br>'), 'warning');
                    return;
                }

                Swal.fire('Error', 'Gagal membuat periode KPI Assessment.', 'error');
            });
        });
    });
</script>
@endsection