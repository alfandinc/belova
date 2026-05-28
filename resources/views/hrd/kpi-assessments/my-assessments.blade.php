@extends('layouts.hrd.app')
@section('title', 'HRD | My KPI Assessments')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <div id="myKpiAssessmentsSection">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="card-title mb-1">Assessment Saya</h4>
                        <p class="text-muted mb-0">HRD mengisi indikator global. Manager mengisi indikator technical untuk timnya. Head manager mengisi kombinasi global dan technical untuk manager serta HRD.</p>
                    </div>
                </div>

                <form method="GET" action="{{ route('hrd.kpi_assessments.my') }}" class="mb-3" id="myKpiAssessmentsFilterForm">
                    <div class="form-row align-items-end">
                        <div class="col-md-3">
                            <label for="assessment_month">Filter Periode</label>
                            <input type="month" id="assessment_month" name="assessment_month" class="form-control" value="{{ $selectedMonth }}" list="kpi-assessment-period-list">
                            <datalist id="kpi-assessment-period-list">
                                @foreach($availablePeriods as $period)
                                    <option value="{{ $period->assessment_month->format('Y-m') }}">{{ $period->assessment_month->format('F Y') }}</option>
                                @endforeach
                            </datalist>
                        </div>
                        <div class="col-md-auto">
                            <button type="submit" class="btn btn-primary">Filter</button>
                        </div>
                        <div class="col-md-auto">
                            <a href="{{ route('hrd.kpi_assessments.my') }}" class="btn btn-light" id="resetMyKpiAssessmentsFilter">Bulan Ini</a>
                        </div>
                    </div>
                </form>

                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                <div class="table-responsive">
                    <table class="table table-bordered table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Periode</th>
                                <th>Dinilai</th>
                                <th>Divisi</th>
                                <th>Jabatan</th>
                                <th>Assessor Role</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($assessments as $assessment)
                                <tr>
                                    <td>{{ $assessment->period->assessment_month->format('F Y') }}</td>
                                    <td>{{ $assessment->evaluatee->nama }}</td>
                                    <td>{{ optional($assessment->evaluatee->division)->name ?: '-' }}</td>
                                    <td>{{ optional($assessment->evaluatee->position)->name ?: '-' }}</td>
                                    <td>{{ strtoupper(str_replace('_', ' ', $assessment->evaluator_type)) }}</td>
                                    <td>
                                        <span class="badge badge-{{ $assessment->status === 'submitted' ? 'success' : 'warning' }}">
                                            {{ strtoupper($assessment->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('hrd.kpi_assessments.fill', $assessment) }}" class="btn btn-sm btn-outline-primary">
                                            {{ $assessment->status === 'submitted' ? 'Lihat' : 'Isi Assessment' }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">Tidak ada assignment KPI Assessment untuk akun ini.</td>
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
        function refreshMyKpiAssessments(url) {
            return $.get(url, function (html) {
                const $html = $(html);
                $('#myKpiAssessmentsSection').html($html.find('#myKpiAssessmentsSection').html());
            });
        }

        function submitMyKpiAssessmentFilter() {
            const $form = $('#myKpiAssessmentsFilterForm');
            const query = $form.serialize();
            const url = $form.attr('action') + (query ? '?' + query : '');

            refreshMyKpiAssessments(url).fail(function () {
                Swal.fire('Error', 'Gagal memuat assessment berdasarkan periode.', 'error');
            });
        }

        $(document).on('submit', '#myKpiAssessmentsFilterForm', function (event) {
            event.preventDefault();
            submitMyKpiAssessmentFilter();
        });

        $(document).on('change', '#assessment_month', function () {
            submitMyKpiAssessmentFilter();
        });

        $(document).on('click', '#resetMyKpiAssessmentsFilter', function (event) {
            event.preventDefault();
            refreshMyKpiAssessments($(this).attr('href')).fail(function () {
                Swal.fire('Error', 'Gagal mengembalikan filter ke bulan ini.', 'error');
            });
        });
    });
</script>
@endsection