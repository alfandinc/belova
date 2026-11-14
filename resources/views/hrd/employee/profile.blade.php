@extends('layouts.hrd.app')
@section('title', 'Profil Saya')

@section('navbar')
    @include('layouts.shared.sidebar')
@endsection

{{-- Profil Saya: view + edit in one page (replaces the old "Edit Profil" modal and "Halaman Profil") --}}
@use('App\Models\HRD\Employee')
@php
    $fmt = fn($d) => $d ? $d->locale('id')->translatedFormat('j F Y') : '-';
    $statusColor = ['tetap' => 'success', 'kontrak' => 'warning', 'freelance' => 'info', 'tidak aktif' => 'danger'];
    $photoUrl = $employee && $employee->photo ? asset('storage/' . $employee->photo) : asset('img/avatar.png');
    if ($employee) {
        [$jenjang, $jurusan] = Employee::splitPendidikan($employee->pendidikan);
        $instagram = $employee->instagram;
        if (is_string($instagram)) {
            $instagram = json_decode($instagram, true) ?? [$instagram];
        }
        $instagram = implode(', ', array_filter((array) $instagram));
        $positions = $employee->positions->sortByDesc(fn($p) => (int) $p->pivot->is_primary);
        $divisions = $employee->positions->flatMap(fn($p) => $p->divisions->pluck('name'))->unique()->filter()->implode(', ');
    }
@endphp

@section('content')
<style>
    .pf-avatar-wrap { position: relative; width: 120px; height: 120px; margin: 0 auto; }
    .pf-avatar { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid rgba(127, 127, 127, .25); }
    .pf-avatar-btn { position: absolute; right: 2px; bottom: 2px; width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; margin: 0; }
    .pf-facts { font-size: 13px; }
    .pf-facts div { display: flex; justify-content: space-between; gap: 8px; padding: 5px 0; border-bottom: 1px dashed rgba(127, 127, 127, .25); }
    .pf-facts div:last-child { border-bottom: 0; }
    .pf-facts span:first-child { opacity: .7; }
    .pf-readonly dt { font-weight: 500; opacity: .7; font-size: 12px; margin-bottom: 0; }
    .pf-readonly dd { margin-bottom: 12px; }
    /* Bootstrap hides only direct children of .tab-content; the Data Diri / Dokumen panes sit inside the form */
    #pf-form > .tab-pane { display: none; }
    #pf-form > .tab-pane.active { display: block; }
    .pf-tabs { flex-wrap: nowrap; overflow-x: auto; overflow-y: hidden; scrollbar-width: none; }
    .pf-tabs .nav-link { white-space: nowrap; }
    .pf-savebar { margin-top: 8px; padding-top: 12px; border-top: 1px solid rgba(127, 127, 127, .25); display: flex; justify-content: flex-end; align-items: center; flex-wrap: wrap; gap: 10px; }
    .pf-dirty { display: none; font-size: 12px; }
    .pf-section-title { font-size: 12px; text-transform: uppercase; letter-spacing: .04em; opacity: .7; font-weight: 600; margin: 4px 0 10px; }
</style>

<div class="container-fluid py-3">
    <div class="row">
        {{-- Kartu ringkas --}}
        <div class="col-lg-4 col-xl-3 mb-3">
            <div class="card">
                <div class="card-body text-center">
                    <div class="pf-avatar-wrap mb-3">
                        <img src="{{ $photoUrl }}" alt="Foto profil" class="pf-avatar" id="pf-avatar">
                        @if($employee)
                            <label for="pf-photo" class="btn btn-primary btn-sm pf-avatar-btn" title="Ganti foto"><i class="fas fa-camera"></i></label>
                            <input type="file" id="pf-photo" name="photo" form="pf-form" accept="image/jpeg,image/png,image/webp" class="d-none">
                        @endif
                    </div>
                    <h5 class="mb-1">{{ $employee->nama ?? $user->name }}</h5>
                    @if($employee)
                        <div class="text-muted small mb-2">{{ optional($positions->first())->name ?? 'Tanpa posisi' }}{{ $divisions ? ' · ' . $divisions : '' }}</div>
                        <span class="badge badge-{{ $statusColor[$employee->status] ?? 'secondary' }}">{{ ucfirst($employee->status ?? '-') }}</span>
                        <div class="small text-muted mt-1 d-none" id="pf-photo-note">Foto baru tersimpan setelah klik <b>Simpan Perubahan</b>.</div>
                    @else
                        <div class="text-muted small">{{ $user->email }}</div>
                    @endif
                </div>
                @if($employee)
                    <div class="card-body border-top pf-facts">
                        <div><span>No Induk</span><span>{{ $employee->no_induk ?: '-' }}</span></div>
                        <div><span>Tanggal masuk</span><span>{{ $fmt($employee->tanggal_masuk) }}</span></div>
                        <div><span>Masa kerja</span><span>{{ $employee->masaKerja() ?: '-' }}</span></div>
                        @if($employee->status === 'kontrak')
                            <div><span>Kontrak berakhir</span><span>{{ $fmt($employee->kontrak_berakhir) }}</span></div>
                        @endif
                        <div><span>Akun</span><span class="text-truncate" style="max-width: 60%;">{{ $user->email }}</span></div>
                    </div>
                @endif
                <div class="card-body border-top">
                    {{-- Modal lives in the topbar (partials.topbar_user_modals) --}}
                    <button type="button" class="btn btn-outline-primary btn-block btn-sm js-open-password-modal"><i class="fas fa-key mr-1"></i>Ganti Password</button>
                </div>
            </div>
        </div>

        {{-- Tab --}}
        <div class="col-lg-8 col-xl-9">
            @unless($employee)
                <div class="alert alert-info"><i class="fas fa-info-circle mr-1"></i>Akun ini belum terhubung dengan data karyawan, jadi hanya password yang bisa diubah (tombol <b>Ganti Password</b>). Hubungi HRD bila seharusnya terhubung.</div>
            @else
            <div class="card">
                <div class="card-header pb-0">
                    <ul class="nav nav-tabs card-header-tabs pf-tabs" role="tablist">
                        @if($employee)
                            <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-data" role="tab"><i class="fas fa-user mr-1"></i>Data Diri</a></li>
                            <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-kepegawaian" role="tab"><i class="fas fa-id-badge mr-1"></i>Kepegawaian</a></li>
                            @if($employee->status === 'kontrak' || $contracts->isNotEmpty())
                                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-kontrak" role="tab"><i class="fas fa-file-contract mr-1"></i>Kontrak</a></li>
                            @endif
                            <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-dokumen" role="tab"><i class="fas fa-folder-open mr-1"></i>Dokumen</a></li>
                        @endif
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        @if($employee)
                            {{-- Data diri + dokumen: one form, one save button --}}
                            <form id="pf-form" action="{{ route('hrd.employee.profile.update') }}" method="POST" enctype="multipart/form-data" novalidate>
                                @csrf
                                @method('PUT')

                                <div class="tab-pane fade" id="tab-data" role="tabpanel">
                                    <div class="pf-section-title">Identitas</div>
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label for="pf-nama">Nama lengkap <span class="text-danger">*</span></label>
                                            <input type="text" id="pf-nama" name="nama" class="form-control" maxlength="255" value="{{ $employee->nama }}" required>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>NIK</label>
                                            <input type="text" class="form-control" value="{{ $employee->nik }}" readonly>
                                            <small class="form-text text-muted">Diubah oleh HRD.</small>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="pf-tempat_lahir">Tempat lahir</label>
                                            <input type="text" id="pf-tempat_lahir" name="tempat_lahir" class="form-control" maxlength="100" value="{{ $employee->tempat_lahir }}">
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="pf-tanggal_lahir">Tanggal lahir</label>
                                            <input type="date" id="pf-tanggal_lahir" name="tanggal_lahir" class="form-control" max="{{ today()->subDay()->format('Y-m-d') }}" value="{{ optional($employee->tanggal_lahir)->format('Y-m-d') }}">
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="pf-gol_darah">Golongan darah</label>
                                            <select id="pf-gol_darah" name="gol_darah" class="form-control">
                                                <option value="">-</option>
                                                {{-- Keep an older value (e.g. "A+") selectable so saving does not erase it --}}
                                                @foreach(array_unique(array_filter(['A', 'B', 'AB', 'O', $employee->gol_darah])) as $gol)
                                                    <option value="{{ $gol }}" {{ $employee->gol_darah === $gol ? 'selected' : '' }}>{{ $gol }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="pf-jenjang">Pendidikan</label>
                                            <select id="pf-jenjang" name="pendidikan_jenjang" class="form-control">
                                                <option value="">-</option>
                                                @foreach(Employee::PENDIDIKAN as $j)
                                                    <option value="{{ $j }}" {{ $jenjang === $j ? 'selected' : '' }}>{{ $j }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group col-md-5">
                                            <label for="pf-jurusan">Jurusan</label>
                                            <input type="text" id="pf-jurusan" name="pendidikan_jurusan" class="form-control" maxlength="80" value="{{ $jurusan }}" list="pf-jurusan-list" autocomplete="off" placeholder="mis. Keperawatan">
                                            <datalist id="pf-jurusan-list">
                                                @foreach($jurusanList as $item)<option value="{{ $item }}">@endforeach
                                            </datalist>
                                        </div>
                                    </div>

                                    <div class="pf-section-title mt-2">Kontak</div>
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label for="pf-no_hp">No HP</label>
                                            <input type="tel" id="pf-no_hp" name="no_hp" class="form-control" maxlength="30" value="{{ $employee->no_hp }}">
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="pf-email">Email</label>
                                            <input type="email" id="pf-email" name="email" class="form-control" maxlength="255" value="{{ $employee->email }}">
                                        </div>
                                        <div class="form-group col-12">
                                            <label for="pf-alamat">Alamat</label>
                                            <textarea id="pf-alamat" name="alamat" class="form-control" rows="2" maxlength="500">{{ $employee->alamat }}</textarea>
                                        </div>
                                        <div class="form-group col-12">
                                            <label for="pf-instagram">Instagram</label>
                                            <input type="text" id="pf-instagram" name="instagram" class="form-control" maxlength="255" value="{{ $instagram }}" placeholder="username1, username2">
                                            <small class="form-text text-muted">Pisahkan dengan koma bila lebih dari satu akun.</small>
                                        </div>
                                    </div>

                                    <div class="pf-section-title mt-2">Kontak darurat</div>
                                    <div class="form-row">
                                        <div class="form-group col-md-4">
                                            <label for="pf-darurat_nama">Nama</label>
                                            <input type="text" id="pf-darurat_nama" name="darurat_nama" class="form-control" maxlength="100" value="{{ $employee->darurat_nama }}">
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="pf-darurat_hubungan">Hubungan</label>
                                            <input type="text" id="pf-darurat_hubungan" name="darurat_hubungan" class="form-control" maxlength="50" value="{{ $employee->darurat_hubungan }}" list="pf-hubungan-list" autocomplete="off">
                                            <datalist id="pf-hubungan-list">
                                                @foreach(['Orang tua', 'Suami', 'Istri', 'Saudara kandung', 'Anak', 'Kerabat', 'Teman'] as $h)<option value="{{ $h }}">@endforeach
                                            </datalist>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="pf-no_darurat">No HP</label>
                                            <input type="tel" id="pf-no_darurat" name="no_darurat" class="form-control" maxlength="50" value="{{ $employee->no_darurat }}">
                                        </div>
                                    </div>
                                </div>

                                <div class="tab-pane fade" id="tab-dokumen" role="tabpanel">
                                    <p class="small text-muted">PDF / gambar / Word, maks. 2MB per file. File baru menggantikan file lama setelah disimpan. Dokumen kontrak diunggah HRD (tab Kontrak).</p>
                                    @foreach($documents as $field => $label)
                                        <div class="form-group">
                                            <label class="d-flex justify-content-between">
                                                <span>{{ $label }}</span>
                                                @if($employee->$field)
                                                    <a href="{{ asset('storage/' . $employee->$field) }}" target="_blank" class="small"><i class="fas fa-eye mr-1"></i>Lihat file sekarang</a>
                                                @else
                                                    <span class="badge badge-secondary">Belum ada</span>
                                                @endif
                                            </label>
                                            <div class="custom-file">
                                                <input type="file" class="custom-file-input" id="pf-{{ $field }}" name="{{ $field }}" accept=".pdf,.jpg,.jpeg,.png{{ $field === 'doc_ktp' ? '' : ',.doc,.docx' }}{{ $field === 'doc_pendukung' ? ',.zip' : '' }}">
                                                <label class="custom-file-label text-truncate" for="pf-{{ $field }}">{{ $employee->$field ? 'Ganti file…' : 'Pilih file…' }}</label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="pf-savebar" id="pf-savebar">
                                    <span class="pf-dirty text-warning" id="pf-dirty"><i class="fas fa-circle mr-1" style="font-size: 8px;"></i>Ada perubahan belum disimpan</span>
                                    <button type="button" class="btn btn-light border" id="pf-reset">Batalkan</button>
                                    <button type="submit" class="btn btn-primary" id="pf-save"><i class="fas fa-save mr-1"></i>Simpan Perubahan</button>
                                </div>
                            </form>

                            <div class="tab-pane fade" id="tab-kepegawaian" role="tabpanel">
                                <p class="small text-muted"><i class="fas fa-info-circle mr-1"></i>Data ini dikelola HRD. Bila ada yang salah, hubungi HRD.</p>
                                <dl class="row pf-readonly mb-0">
                                    <div class="col-md-6"><dt>Status</dt><dd>{{ ucfirst($employee->status ?? '-') }}</dd></div>
                                    <div class="col-md-6"><dt>No Induk</dt><dd>{{ $employee->no_induk ?: '-' }}</dd></div>
                                    <div class="col-md-6"><dt>Posisi</dt><dd>{{ $positions->pluck('name')->implode(', ') ?: '-' }}</dd></div>
                                    <div class="col-md-6"><dt>Divisi</dt><dd>{{ $divisions ?: '-' }}</dd></div>
                                    <div class="col-md-6"><dt>Perusahaan</dt><dd>{{ implode(', ', $employee->daftarPerusahaan()) ?: '-' }}</dd></div>
                                    <div class="col-md-6"><dt>Tanggal masuk</dt><dd>{{ $fmt($employee->tanggal_masuk) }}{{ $employee->masaKerja() ? ' (' . $employee->masaKerja() . ')' : '' }}</dd></div>
                                    <div class="col-md-6"><dt>NPWP</dt><dd>{{ $employee->npwp ?: '-' }}</dd></div>
                                    <div class="col-md-6"><dt>Status pernikahan</dt><dd>{{ Employee::STATUS_PERNIKAHAN[$employee->status_pernikahan] ?? '-' }}{{ $employee->jumlah_tanggungan ? ', ' . $employee->jumlah_tanggungan . ' tanggungan' : '' }}</dd></div>
                                    <div class="col-md-6"><dt>No BPJS Kesehatan</dt><dd>{{ $employee->no_bpjs_kesehatan ?: '-' }}</dd></div>
                                    <div class="col-md-6"><dt>No BPJS Ketenagakerjaan</dt><dd>{{ $employee->no_bpjs_ketenagakerjaan ?: '-' }}</dd></div>
                                    <div class="col-md-6"><dt>Rekening gaji</dt><dd>{{ $employee->bank_no_rekening ? trim($employee->bank_nama . ' ' . $employee->bank_no_rekening) . ($employee->bank_atas_nama ? ' a.n. ' . $employee->bank_atas_nama : '') : '-' }}</dd></div>
                                    <div class="col-md-6"><dt>Finger ID</dt><dd>{{ $employee->finger_id ?: '-' }}</dd></div>
                                </dl>
                            </div>

                            @if($employee->status === 'kontrak' || $contracts->isNotEmpty())
                                {{-- Period + document only (HRD's notes stay internal) --}}
                                @php
                                    $kontrakLabel = ['active' => ['success', 'Aktif'], 'renewed' => ['info', 'Diperpanjang'], 'expired' => ['secondary', 'Selesai'], 'terminated' => ['danger', 'Berakhir']];
                                    $sisa = $employee->status === 'kontrak' && $employee->kontrak_berakhir ? (int) today()->diffInDays($employee->kontrak_berakhir, false) : null;
                                @endphp
                                <div class="tab-pane fade" id="tab-kontrak" role="tabpanel">
                                    @if($employee->status === 'kontrak')
                                        <div class="alert {{ $sisa !== null && $sisa <= 30 ? 'alert-warning' : 'alert-info' }} py-2 small">
                                            @if($employee->kontrak_berakhir)
                                                Kontrak berakhir <b>{{ $fmt($employee->kontrak_berakhir) }}</b>
                                                ({{ $sisa >= 0 ? 'sisa ' . $sisa . ' hari' : 'sudah lewat ' . (-$sisa) . ' hari, hubungi HRD' }})
                                            @else
                                                Periode kontrak belum dicatat HRD.
                                            @endif
                                        </div>
                                    @endif
                                    @if($contracts->isNotEmpty())
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered small mb-0">
                                                <thead><tr><th>Periode</th><th>Durasi</th><th>Status</th><th>Dokumen</th></tr></thead>
                                                <tbody>
                                                    @foreach($contracts as $c)
                                                        @php [$cBadge, $cLabel] = $kontrakLabel[$c->status] ?? ['secondary', ucfirst($c->status)]; @endphp
                                                        <tr>
                                                            <td>{{ $c->start_date->format('d/m/Y') }} – {{ $c->end_date->format('d/m/Y') }}</td>
                                                            <td>{{ $c->duration_months }} bln</td>
                                                            <td><span class="badge badge-{{ $cBadge }}">{{ $cLabel }}</span></td>
                                                            <td>
                                                                @if($c->contract_document)
                                                                    <a href="{{ route('hrd.employee.profile.contract-document', $c->id) }}" target="_blank"><i class="fas fa-paperclip mr-1"></i>Lihat</a>
                                                                @else
                                                                    -
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
            @endunless
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function () {
    var $form = $('#pf-form');
    var dirty = false;

    // Tab from the URL hash (#tab-dokumen, ...), remembered while switching
    // Panes sit in two parents (inside / outside the form): Bootstrap only hides siblings, so hide all first
    $('.nav-tabs .nav-link').on('show.bs.tab', function () {
        $('.tab-pane').removeClass('active show');
    });
    var first = $('.nav-tabs .nav-link').first().attr('href');
    var wanted = window.location.hash && $('.nav-tabs .nav-link[href="' + window.location.hash + '"]').length ? window.location.hash : first;
    $('.nav-tabs .nav-link[href="' + wanted + '"]').tab('show');
    $('.nav-tabs .nav-link').on('shown.bs.tab', function (e) {
        history.replaceState(null, '', $(e.target).attr('href'));
        // The save bar belongs to the form tabs only
        $('#pf-savebar').toggle(['#tab-data', '#tab-dokumen'].indexOf($(e.target).attr('href')) >= 0);
    });
    $('#pf-savebar').toggle(['#tab-data', '#tab-dokumen'].indexOf(wanted) >= 0);
    // The hash names a pane: keep the page at the top instead of jumping to it
    if (window.location.hash) {
        setTimeout(function () { window.scrollTo(0, 0); }, 0);
    }

    function setDirty(value) {
        dirty = value;
        $('#pf-dirty').toggle(value);
    }
    $form.on('input change', ':input', function () { setDirty(true); });
    $('#pf-photo').on('change', function () {
        var file = this.files[0];
        if (!file) return;
        setDirty(true);
        $('#pf-avatar').attr('src', URL.createObjectURL(file));
        $('#pf-photo-note').removeClass('d-none');
    });
    $form.on('change', '.custom-file-input', function () {
        var name = this.files[0] ? this.files[0].name : 'Pilih file…';
        $(this).siblings('.custom-file-label').text(name);
    });
    $('#pf-reset').on('click', function () {
        setDirty(false); // discarding on purpose: no "leave page?" prompt
        window.location.reload();
    });
    window.addEventListener('beforeunload', function (e) {
        if (!dirty) return;
        e.preventDefault();
        e.returnValue = '';
    });

    function errorText(xhr, fallback) {
        var r = xhr.responseJSON || {};
        if (r.errors) {
            return Object.keys(r.errors).map(function (k) { return r.errors[k][0]; }).join('\n');
        }
        if (xhr.status === 413) return 'File terlalu besar untuk diunggah.';
        if (xhr.status === 419) return 'Sesi habis. Muat ulang halaman lalu coba lagi.';
        return r.message || fallback;
    }

    function busy($btn, on, html) {
        if (on) {
            $btn.data('html', $btn.html()).prop('disabled', true).html('<span class="spinner-border spinner-border-sm mr-1"></span>Menyimpan...');
        } else {
            $btn.prop('disabled', false).html($btn.data('html') || html);
        }
    }

    $form.on('submit', function (e) {
        e.preventDefault();
        if (!$.trim($('#pf-nama').val())) {
            $('.nav-tabs .nav-link[href="#tab-data"]').tab('show');
            Swal.fire({ icon: 'warning', title: 'Nama wajib diisi' });
            return;
        }
        var $btn = $('#pf-save');
        busy($btn, true);
        $.ajax({ url: $form.attr('action'), type: 'POST', data: new FormData($form[0]), processData: false, contentType: false, dataType: 'json' })
            .done(function (res) {
                setDirty(false);
                Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 1500, timerProgressBar: true, showConfirmButton: false })
                    .then(function () { window.location.reload(); }); // topbar name / photo follow
            })
            .fail(function (xhr) {
                Swal.fire({ icon: 'error', title: 'Gagal menyimpan', text: errorText(xhr, 'Terjadi kesalahan saat menyimpan profil.') });
            })
            .always(function () { busy($btn, false); });
    });

});
</script>
@endsection
