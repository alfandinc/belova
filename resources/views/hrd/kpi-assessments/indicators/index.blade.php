@extends('layouts.hrd.app')
@section('title', 'HRD | KPI Assessment Indicators')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card" id="kpiIndicatorCreateSection">
                <div class="card-body">
                    <h4 class="card-title mb-3">Tambah Indikator</h4>
                    <p class="text-muted">Admin menyiapkan indikator, bobot, dan alur applicability. Jadi indikator seperti Kepemimpinan cukup dibuat sekali untuk Head Manager ke Manager, tanpa duplikat per manager.</p>

                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <form method="POST" action="{{ route('hrd.kpi_assessments.indicators.store') }}" id="kpiIndicatorCreateForm">
                        @csrf
                        <div class="form-group">
                            <label for="name">Nama Indikator</label>
                            <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="indicator_type">Tipe</label>
                            <select id="indicator_type" name="indicator_type" class="form-control @error('indicator_type') is-invalid @enderror" required>
                                <option value="global" {{ old('indicator_type') === 'global' ? 'selected' : '' }}>Global</option>
                                <option value="technical" {{ old('indicator_type') === 'technical' ? 'selected' : '' }}>Technical (Position Based)</option>
                            </select>
                            @error('indicator_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="applicability_scope">Berlaku Untuk Alur</label>
                            <select id="applicability_scope" name="applicability_scope" class="form-control @error('applicability_scope') is-invalid @enderror" required>
                                @foreach($applicabilityOptions as $value => $label)
                                    <option value="{{ $value }}" {{ old('applicability_scope', 'hrd_to_all') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('applicability_scope')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Contoh: pilih Head Manager -> Manager untuk indikator seperti Kepemimpinan, jadi tidak perlu dibuat ulang per manager.</small>
                        </div>

                        <div class="form-group">
                            <label for="position_id">Jabatan</label>
                            <select id="position_id" name="position_id" class="form-control @error('position_id') is-invalid @enderror">
                                <option value="">Semua / tidak perlu</option>
                                @foreach($positions as $position)
                                    <option value="{{ $position->id }}" {{ (string) old('position_id') === (string) $position->id ? 'selected' : '' }}>{{ $position->name }}</option>
                                @endforeach
                            </select>
                            @error('position_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="weight_percentage">Bobot (%)</label>
                            <input type="number" step="0.01" id="weight_percentage" name="weight_percentage" class="form-control @error('weight_percentage') is-invalid @enderror" value="{{ old('weight_percentage') }}" required>
                            @error('weight_percentage')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="max_score">Skor Maksimum</label>
                            <select id="max_score" name="max_score" class="form-control @error('max_score') is-invalid @enderror" required>
                                @for($score = 2; $score <= 5; $score++)
                                    <option value="{{ $score }}" {{ (string) old('max_score', 5) === (string) $score ? 'selected' : '' }}>{{ $score }}</option>
                                @endfor
                            </select>
                            @error('max_score')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Contoh: pilih 3 jika indikator ini hanya boleh dinilai 1 sampai 3.</small>
                        </div>

                        <div class="border rounded p-3 mb-3">
                            <h6 class="mb-3">Teks Opsi Skor 1-5</h6>
                            @for($score = 1; $score <= 5; $score++)
                                <div class="form-group mb-2">
                                    <label for="score_label_{{ $score }}">Skor {{ $score }}</label>
                                    <input type="text" id="score_label_{{ $score }}" name="score_label_{{ $score }}" class="form-control @error('score_label_' . $score) is-invalid @enderror" value="{{ old('score_label_' . $score) }}" placeholder="Contoh: {{ $score === 1 ? 'Sangat kurang' : ($score === 5 ? 'Sangat baik' : 'Deskripsi skor ' . $score) }}">
                                    @error('score_label_' . $score)
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            @endfor
                        </div>

                        <div class="form-group">
                            <label for="description">Deskripsi</label>
                            <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group form-check">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Aktif</label>
                        </div>

                        <button type="submit" class="btn btn-primary">Simpan Indikator</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h4 class="card-title mb-1">Preview Penilaian Per Jabatan</h4>
                            <p class="text-muted mb-0">Klik detail untuk melihat indikator yang dinilai HRD, Manager, atau Head Manager untuk setiap jabatan beserta bobot dan struktur perhitungannya.</p>
                        </div>
                        <button class="btn btn-outline-secondary btn-sm" type="button" data-toggle="collapse" data-target="#rawIndicatorTable" aria-expanded="false" aria-controls="rawIndicatorTable">
                            Tampilkan Kelola Indikator
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="positionPreviewTable" style="width:100%;">
                            <thead>
                                <tr>
                                    <th>Jabatan</th>
                                    <th>Divisi</th>
                                    <th>Target Role</th>
                                    <th>Formula</th>
                                    <th>Total Bobot</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>

                    <div class="collapse mt-4" id="rawIndicatorTable">
                        <div class="border-top pt-4">
                            <h5 class="mb-3">Kelola Indikator Satuan</h5>
                            <div class="table-responsive" id="kpiIndicatorRawTableContainer">
                                <table class="table table-bordered table-striped mb-0">
                                    <thead>
                                        <tr>
                                            <th>Indikator</th>
                                            <th>Tipe</th>
                                            <th>Applicability</th>
                                            <th>Jabatan</th>
                                            <th>Bobot</th>
                                            <th>Max Skor</th>
                                            <th>Status</th>
                                            <th style="width: 260px;">Ubah Cepat</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($indicators as $indicator)
                                            <tr>
                                                <td>
                                                    <strong>{{ $indicator->name }}</strong>
                                                    @if($indicator->description)
                                                        <div class="text-muted small">{{ $indicator->description }}</div>
                                                    @endif
                                                </td>
                                                <td>{{ strtoupper($indicator->indicator_type) }}</td>
                                                <td>
                                                    <span class="badge badge-light border text-wrap">{{ $indicator->applicabilityLabel() }}</span>
                                                </td>
                                                <td>
                                                    {{ optional($indicator->position)->name ?: '-' }}
                                                    @if($indicator->indicator_type === 'technical' && $indicator->position_id)
                                                        <div class="small text-muted">Total aktif posisi ini: {{ $technicalWeightTotals[$indicator->position_id] ?? 0 }}%</div>
                                                    @endif
                                                </td>
                                                <td>{{ $indicator->weight_percentage }}%</td>
                                                <td>{{ $indicator->max_score }}</td>
                                                <td>
                                                    <span class="badge badge-{{ $indicator->is_active ? 'success' : 'secondary' }}">{{ $indicator->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                                </td>
                                                <td>
                                                    <form method="POST" action="{{ route('hrd.kpi_assessments.indicators.update', $indicator) }}" class="mb-2 kpi-indicator-update-form">
                                                        @csrf
                                                        @method('PUT')
                                                        <div class="form-row">
                                                            <div class="col-4">
                                                                <input type="text" name="name" class="form-control form-control-sm" value="{{ $indicator->name }}" required>
                                                            </div>
                                                            <div class="col-3">
                                                                <input type="number" step="0.01" name="weight_percentage" class="form-control form-control-sm" value="{{ $indicator->weight_percentage }}" required>
                                                            </div>
                                                            <div class="col-2">
                                                                <select name="max_score" class="form-control form-control-sm" required>
                                                                    @for($score = 2; $score <= 5; $score++)
                                                                        <option value="{{ $score }}" {{ (int) $indicator->max_score === $score ? 'selected' : '' }}>{{ $score }}</option>
                                                                    @endfor
                                                                </select>
                                                            </div>
                                                            <div class="col-3">
                                                                <button type="submit" class="btn btn-sm btn-outline-primary btn-block">Update</button>
                                                            </div>
                                                        </div>
                                                        <input type="hidden" name="indicator_type" value="{{ $indicator->indicator_type }}">
                                                        <input type="hidden" name="applicability_scope" value="{{ $indicator->applicability_scope }}">
                                                        <input type="hidden" name="position_id" value="{{ $indicator->position_id }}">
                                                        <input type="hidden" name="description" value="{{ $indicator->description }}">
                                                        <input type="hidden" name="score_label_1" value="{{ $indicator->score_label_1 }}">
                                                        <input type="hidden" name="score_label_2" value="{{ $indicator->score_label_2 }}">
                                                        <input type="hidden" name="score_label_3" value="{{ $indicator->score_label_3 }}">
                                                        <input type="hidden" name="score_label_4" value="{{ $indicator->score_label_4 }}">
                                                        <input type="hidden" name="score_label_5" value="{{ $indicator->score_label_5 }}">
                                                        <input type="hidden" name="is_active" value="{{ $indicator->is_active ? 1 : 0 }}">
                                                    </form>

                                                    <div class="small text-muted mb-2">
                                                        @for($score = 1; $score <= 5; $score++)
                                                            @php($label = $indicator->{'score_label_' . $score})
                                                            @if($label)
                                                                <div>{{ $score }}: {{ $label }}</div>
                                                            @endif
                                                        @endfor
                                                    </div>

                                                    <form method="POST" action="{{ route('hrd.kpi_assessments.indicators.destroy', $indicator) }}" class="kpi-indicator-delete-form" onsubmit="return confirm('Hapus indikator ini? Snapshot periode lama tidak akan berubah.')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger btn-block">Hapus</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="text-center text-muted">Belum ada indikator KPI Assessment.</td>
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
    </div>
</div>

<div class="modal fade" id="positionPreviewModal" tabindex="-1" role="dialog" aria-labelledby="positionPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="positionPreviewModalLabel">Preview KPI</h5>
                    <div class="text-muted small" id="positionPreviewModalMeta"></div>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="positionPreviewModalBody">
                <div class="text-center text-muted py-4">Memuat detail...</div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function () {
        const indicatorPreviewTable = $('#positionPreviewTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('hrd.kpi_assessments.indicators.preview.data') }}",
                error: function () {
                    Swal.fire('Error', 'Gagal memuat preview penilaian per jabatan.', 'error');
                }
            },
            columns: [
                { data: 'position_name', name: 'position_name' },
                { data: 'division_name', name: 'division_name' },
                { data: 'target_role_badge', name: 'target_role', orderable: false, searchable: false },
                { data: 'formula_display', name: 'formula_display', orderable: false, searchable: false },
                { data: 'total_weight_display', name: 'total_weight_display', orderable: false, searchable: false },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
            language: {
                emptyTable: 'Belum ada preview jabatan yang bisa ditampilkan dari indikator aktif saat ini.'
            }
        });

        function refreshIndicatorSections() {
            return $.get(window.location.href, function (html) {
                const $html = $(html);
                $('#kpiIndicatorCreateSection').html($html.find('#kpiIndicatorCreateSection').html());
                $('#kpiIndicatorRawTableContainer').html($html.find('#kpiIndicatorRawTableContainer').html());
            });
        }

        function showAjaxValidationErrors(xhr) {
            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                const messages = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                Swal.fire('Validasi gagal', messages, 'warning');
                return;
            }

            Swal.fire('Error', 'Proses gagal dijalankan.', 'error');
        }

        $(document).on('submit', '#kpiIndicatorCreateForm, .kpi-indicator-update-form, .kpi-indicator-delete-form', function (event) {
            event.preventDefault();

            const $form = $(this);
            const method = ($form.find('input[name="_method"]').val() || $form.attr('method') || 'POST').toUpperCase();

            $.ajax({
                url: $form.attr('action'),
                method: method,
                data: $form.serialize(),
                headers: {
                    'Accept': 'application/json'
                }
            }).done(function (response) {
                refreshIndicatorSections().done(function () {
                    indicatorPreviewTable.ajax.reload(null, false);
                    Swal.fire('Sukses', response.message, 'success');
                }).fail(function () {
                    Swal.fire('Warning', response.message + ' Namun refresh tampilan gagal, silakan muat ulang manual.', 'warning');
                });
            }).fail(showAjaxValidationErrors);
        });

        $(document).on('click', '.preview-detail-btn', function () {
            const url = $(this).data('url');
            $('#positionPreviewModal').modal('show');
            $('#positionPreviewModalBody').html('<div class="text-center text-muted py-4">Memuat detail...</div>');

            $.get(url, function (response) {
                $('#positionPreviewModalLabel').text('Preview KPI - ' + response.position_name);
                $('#positionPreviewModalMeta').text(response.division_name + ' | Target role ' + response.target_role);

                let html = '';
                html += '<div class="alert alert-light border">';
                html += '<div><strong>Rumus struktur bobot</strong></div>';
                html += '<div>' + response.formula + '</div>';
                html += '<div class="small text-muted mt-1">Final KPI dibentuk dari penjumlahan semua indikator aktif: nilai indikator x bobot indikator.</div>';
                html += '</div>';

                if (!response.sections.length) {
                    html += '<div class="alert alert-warning mb-0">Belum ada indikator aktif untuk jabatan ini.</div>';
                }

                response.sections.forEach(function (section) {
                    html += '<div class="card mb-3">';
                    html += '<div class="card-body">';
                    html += '<div class="d-flex justify-content-between align-items-center mb-3">';
                    html += '<div><h6 class="mb-1">' + section.title + '</h6><div class="small text-muted">Subtotal assessor ini: ' + section.total_weight + '</div></div>';
                    html += '<span class="badge badge-primary">' + section.short_label + '</span>';
                    html += '</div>';
                    html += '<div class="table-responsive">';
                    html += '<table class="table table-sm table-bordered mb-0">';
                    html += '<thead><tr><th>Indikator</th><th>Tipe</th><th>Applicability</th><th>Bobot</th><th>Deskripsi</th></tr></thead><tbody>';

                    section.indicators.forEach(function (indicator) {
                        html += '<tr>' +
                            '<td>' + indicator.name + '</td>' +
                            '<td>' + indicator.type + '</td>' +
                            '<td>' + indicator.applicability + '</td>' +
                            '<td>' + indicator.weight + '</td>' +
                            '<td>' + indicator.description + '</td>' +
                            '</tr>';
                    });

                    html += '</tbody></table></div></div></div>';
                });

                $('#positionPreviewModalBody').html(html);
            }).fail(function () {
                $('#positionPreviewModalBody').html('<div class="alert alert-danger mb-0">Gagal memuat detail preview jabatan.</div>');
            });
        });
    });
</script>
@endsection