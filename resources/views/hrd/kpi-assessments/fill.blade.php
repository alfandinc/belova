@extends('layouts.hrd.app')
@section('title', 'HRD | Fill KPI Assessment')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-8">
            <h3 class="mb-1">KPI Assessment {{ $assessment->period->assessment_month->format('F Y') }}</h3>
            <p class="text-muted mb-0">
                Menilai {{ $assessment->evaluatee->nama }} | {{ optional($assessment->evaluatee->division)->name ?: '-' }} | {{ optional($assessment->evaluatee->position)->name ?: '-' }}
            </p>
        </div>
        <div class="col-md-4 text-md-right mt-3 mt-md-0">
            <a href="{{ route('hrd.kpi_assessments.my') }}" class="btn btn-light">Kembali</a>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if($indicators->isEmpty())
        <div class="alert alert-warning">Belum ada indikator yang cocok untuk assessment ini.</div>
    @else
        <div class="card" id="kpiAssessmentFillSection">
            <div class="card-body">
                <div class="mb-3">
                    <span class="badge badge-info">Role assessor: {{ strtoupper(str_replace('_', ' ', $assessment->evaluator_type)) }}</span>
                    <span class="badge badge-secondary">
                        {{ in_array($assessment->evaluator_type, ['hrd', 'ceo'], true) ? 'Global Only' : ($assessment->evaluator_type === 'manager' ? 'Technical Only' : 'Global + Technical') }}
                    </span>
                </div>

                <form method="POST" action="{{ route('hrd.kpi_assessments.submit', $assessment) }}" id="kpiAssessmentFillForm">
                    @csrf
                    <div class="mb-4">
                        @foreach($indicators as $indicator)
                            @php($existingScore = $scores->get($indicator->id))
                            <div class="border rounded p-3 p-md-4 mb-3 bg-white">
                                <div class="row align-items-start">
                                    <div class="col-lg-7 pr-lg-4 mb-3 mb-lg-0">
                                        <h4 class="mb-2">{{ $loop->iteration }}. {{ $indicator->name }} ({{ number_format((float) $indicator->weight_percentage, 2) }}%)</h4>
                                        @if($indicator->description)
                                            <p class="text-muted mb-3">{{ $indicator->description }}</p>
                                        @endif
                                    </div>
                                    <div class="col-lg-5">
                                        <div class="form-group mb-3">
                                            <label class="font-weight-bold">Jawaban</label>
                                            @if($indicator->indicator_type === 'global')
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    max="{{ $indicator->max_score }}"
                                                    name="scores[{{ $indicator->id }}]"
                                                    class="form-control @error('scores.' . $indicator->id) is-invalid @enderror"
                                                    value="{{ old('scores.' . $indicator->id, optional($existingScore)->score) }}"
                                                    placeholder="Contoh: 2.70"
                                                    {{ $assessment->status === 'submitted' ? 'disabled' : '' }}
                                                    required>
                                                <small class="form-text text-muted">Input angka desimal diperbolehkan. Rentang skor 0 sampai {{ $indicator->max_score }}.</small>
                                            @else
                                                <select name="scores[{{ $indicator->id }}]" class="form-control @error('scores.' . $indicator->id) is-invalid @enderror" {{ $assessment->status === 'submitted' ? 'disabled' : '' }} required>
                                                    <option value="">Pilih skor</option>
                                                    @for($score = 1; $score <= $indicator->max_score; $score++)
                                                        @php($label = $indicator->{'score_label_' . $score})
                                                        <option value="{{ $score }}" {{ (string) old('scores.' . $indicator->id, optional($existingScore)->score) === (string) $score ? 'selected' : '' }}>
                                                            {{ $score }}{{ $label ? ' - ' . $label : '' }}
                                                        </option>
                                                    @endfor
                                                </select>
                                                <small class="form-text text-muted">Skor maksimum indikator ini: {{ $indicator->max_score }}</small>
                                            @endif
                                            @error('scores.' . $indicator->id)
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="form-group mb-0">
                                            <label class="font-weight-bold">Catatan</label>
                                            <textarea name="notes[{{ $indicator->id }}]" rows="3" class="form-control @error('notes.' . $indicator->id) is-invalid @enderror" placeholder="Tambahkan catatan bila perlu" {{ $assessment->status === 'submitted' ? 'disabled' : '' }}>{{ old('notes.' . $indicator->id, optional($existingScore)->note) }}</textarea>
                                            @error('notes.' . $indicator->id)
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if($assessment->status === 'submitted')
                        <div class="alert alert-success mb-0">Assessment ini sudah disubmit pada {{ optional($assessment->submitted_at)->format('d M Y H:i') }}.</div>
                    @else
                        <button type="submit" class="btn btn-primary">Submit Assessment</button>
                    @endif
                </form>
            </div>
        </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function () {
        $(document).on('submit', '#kpiAssessmentFillForm', function (event) {
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
                    $('#kpiAssessmentFillSection').html($html.find('#kpiAssessmentFillSection').html());
                    Swal.fire('Sukses', response.message, 'success');
                }).fail(function () {
                    Swal.fire('Warning', response.message + ' Namun refresh tampilan gagal, silakan muat ulang manual.', 'warning');
                });
            }).fail(function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    Swal.fire('Validasi gagal', Object.values(xhr.responseJSON.errors).flat().join('<br>'), 'warning');
                    return;
                }

                const message = xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message
                    : 'Gagal menyimpan KPI Assessment.';
                Swal.fire('Error', message, 'error');
            });
        });
    });
</script>
@endsection