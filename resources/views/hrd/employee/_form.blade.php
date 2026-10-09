{{-- Tambah / edit karyawan: modal content loaded into #employeeFormModal on the karyawan list --}}
@php
    $isEdit = isset($employee);
    $e = $employee ?? null;
    $val = fn($field) => $e->{$field} ?? '';
    $date = fn($field) => optional($e->{$field} ?? null)->format('Y-m-d');
    $selectedPositions = $isEdit ? $employee->positions->pluck('id')->all() : [];
    // "S1 Ilmu Komunikasi" -> jenjang + jurusan (text without a known jenjang lands in jurusan)
    [$jenjang, $jurusan] = \App\Models\HRD\Employee::splitPendidikan($val('pendidikan'));
    $req = '<span class="text-danger">*</span>';
    // Card per group: icon, title, what it is used for
    $block = fn($icon, $title, $desc) => '<div class="ef-block-head"><span class="ef-block-icon"><i class="fas ' . $icon . '"></i></span>'
        . '<div><div class="ef-block-title">' . e($title) . '</div><div class="ef-block-desc">' . e($desc) . '</div></div></div>';
@endphp
<form id="employee-form" action="{{ $isEdit ? route('hrd.employee.update', $employee->id) : route('hrd.employee.store') }}" method="POST" enctype="multipart/form-data" data-id="{{ $e->id ?? '' }}" novalidate>
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">
            <i class="fas {{ $isEdit ? 'fa-user-edit' : 'fa-user-plus' }} mr-2"></i>{{ $isEdit ? 'Edit Karyawan · ' . $employee->nama : 'Tambah Karyawan' }}
        </h5>
        <button type="button" class="close text-white ef-close" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    </div>

    <div class="modal-body ef-body">
        {{-- Kelengkapan: filled by the list page script from the fields marked data-penting / required --}}
        <div class="ef-progress mb-3">
            <div class="d-flex justify-content-between small mb-1">
                <span class="text-muted"><i class="fas fa-tasks mr-1"></i>Kelengkapan data</span>
                <span id="ef-progress-text" class="font-weight-bold"></span>
            </div>
            <div class="progress" style="height: 6px;"><div class="progress-bar" id="ef-progress-bar" role="progressbar" style="width: 0%"></div></div>
        </div>

        <div class="alert alert-danger py-2 small" id="employee-form-errors" style="display:none;"></div>

        <ul class="nav nav-tabs ef-tabs mb-3" role="tablist">
            <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#ef-pribadi" role="tab"><i class="fas fa-user mr-1"></i>Data Pribadi <span class="ef-tab-badge"></span></a></li>
            <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#ef-kepegawaian" role="tab"><i class="fas fa-briefcase mr-1"></i>Kepegawaian <span class="ef-tab-badge"></span></a></li>
            <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#ef-payroll" role="tab"><i class="fas fa-money-check-alt mr-1"></i>Payroll <span class="ef-tab-badge"></span></a></li>
            <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#ef-dokumen" role="tab"><i class="fas fa-folder-open mr-1"></i>Dokumen <span class="ef-tab-badge"></span></a></li>
        </ul>

        <div class="tab-content">
            {{-- ===== Data Pribadi ===== --}}
            <div class="tab-pane fade show active" id="ef-pribadi" role="tabpanel">
                <div class="ef-block">
                    {!! $block('fa-id-card', 'Identitas', 'Isi sesuai KTP') !!}
                    <div class="ef-block-body form-row">
                        <div class="form-group col-md-6">
                            <label for="ef-nama">Nama lengkap {!! $req !!}</label>
                            <input type="text" id="ef-nama" name="nama" class="form-control" value="{{ $val('nama') }}" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="ef-nik">NIK</label>
                            <input type="text" id="ef-nik" name="nik" class="form-control" data-penting value="{{ $val('nik') }}" inputmode="numeric" maxlength="20">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="ef-jenis_kelamin">Jenis kelamin {!! $req !!}</label>
                            <select name="jenis_kelamin" id="ef-jenis_kelamin" class="form-control" required>
                                <option value="">Pilih jenis kelamin</option>
                                <option value="L" {{ strtoupper($val('jenis_kelamin')) === 'L' ? 'selected' : '' }}>Laki-laki</option>
                                <option value="P" {{ strtoupper($val('jenis_kelamin')) === 'P' ? 'selected' : '' }}>Perempuan</option>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="ef-gol_darah">Golongan darah</label>
                            <select name="gol_darah" id="ef-gol_darah" class="form-control">
                                <option value="">Tidak diketahui</option>
                                @foreach(['A', 'B', 'AB', 'O'] as $gol)
                                    <option value="{{ $gol }}" {{ $val('gol_darah') === $gol ? 'selected' : '' }}>{{ $gol }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="ef-tempat_lahir">Tempat lahir</label>
                            <input type="text" id="ef-tempat_lahir" name="tempat_lahir" class="form-control" value="{{ $val('tempat_lahir') }}">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="ef-tanggal_lahir">Tanggal lahir</label>
                            <input type="date" id="ef-tanggal_lahir" name="tanggal_lahir" class="form-control" value="{{ $date('tanggal_lahir') }}" data-penting>
                            <small class="form-text text-muted" id="ef-umur-hint"></small>
                        </div>
                    </div>
                </div>

                <div class="ef-block">
                    {!! $block('fa-graduation-cap', 'Pendidikan terakhir', 'Jenjang dan jurusan / program studi') !!}
                    <div class="ef-block-body form-row">
                        <div class="form-group col-md-4">
                            <label for="ef-pendidikan_jenjang">Jenjang</label>
                            <select name="pendidikan_jenjang" id="ef-pendidikan_jenjang" class="form-control">
                                <option value="">Pilih jenjang</option>
                                @foreach(\App\Models\HRD\Employee::PENDIDIKAN as $option)
                                    <option value="{{ $option }}" {{ $jenjang === $option ? 'selected' : '' }}>{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-8">
                            <label for="ef-pendidikan_jurusan">Jurusan / program studi</label>
                            <input type="text" id="ef-pendidikan_jurusan" name="pendidikan_jurusan" class="form-control" maxlength="80" value="{{ $jurusan }}" list="ef-jurusan-list" autocomplete="off">
                            <datalist id="ef-jurusan-list">
                                @foreach($jurusanList as $item)
                                    <option value="{{ $item }}">
                                @endforeach
                            </datalist>
                            <small class="form-text text-muted" id="ef-pendidikan-preview"></small>
                        </div>
                    </div>
                </div>

                <div class="ef-block">
                    {!! $block('fa-address-book', 'Kontak', 'Untuk dihubungi dan notifikasi WhatsApp') !!}
                    <div class="ef-block-body form-row">
                        <div class="form-group col-md-6">
                            <label for="ef-no_hp">No HP / WhatsApp</label>
                            <input type="tel" id="ef-no_hp" name="no_hp" class="form-control" data-penting value="{{ $val('no_hp') }}">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="ef-email">Email</label>
                            <input type="email" id="ef-email" name="email" class="form-control" value="{{ $val('email') }}">
                        </div>
                        <div class="form-group col-12">
                            <label for="ef-alamat">Alamat tinggal</label>
                            <textarea id="ef-alamat" name="alamat" class="form-control" rows="2">{{ $val('alamat') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="ef-block">
                    {!! $block('fa-first-aid', 'Kontak darurat', 'Dihubungi bila terjadi sesuatu pada karyawan') !!}
                    <div class="ef-block-body form-row">
                        <div class="form-group col-md-6">
                            <label for="ef-darurat_nama">Nama</label>
                            <input type="text" id="ef-darurat_nama" name="darurat_nama" class="form-control" maxlength="100" value="{{ $val('darurat_nama') }}">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="ef-darurat_hubungan">Hubungan</label>
                            <input type="text" id="ef-darurat_hubungan" name="darurat_hubungan" class="form-control" maxlength="50" value="{{ $val('darurat_hubungan') }}" list="ef-hubungan-list" autocomplete="off">
                            <datalist id="ef-hubungan-list">
                                @foreach(['Orang tua', 'Suami', 'Istri', 'Saudara kandung', 'Anak', 'Kerabat', 'Teman'] as $hubungan)
                                    <option value="{{ $hubungan }}">
                                @endforeach
                            </datalist>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="ef-no_darurat">No HP</label>
                            <input type="tel" id="ef-no_darurat" name="no_darurat" class="form-control" maxlength="50" value="{{ $val('no_darurat') }}">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== Kepegawaian ===== --}}
            <div class="tab-pane fade" id="ef-kepegawaian" role="tabpanel">
                @php $selectedCompanies = $isEdit ? $employee->daftarPerusahaan() : []; @endphp
                <div class="ef-block">
                    {!! $block('fa-sitemap', 'Penempatan', 'Perusahaan dan posisi; divisi & atasan mengikuti posisi utama') !!}
                    <div class="ef-block-body form-row">
                        <div class="form-group col-md-6">
                            <label for="ef-perusahaan_list">Perusahaan {!! $req !!}</label>
                            <select id="ef-perusahaan_list" name="perusahaan_list[]" class="form-control ef-select2" multiple required data-placeholder="Pilih satu atau lebih">
                                @foreach(\App\Models\HRD\Employee::PERUSAHAAN as $company)
                                    <option value="{{ $company }}" {{ in_array($company, $selectedCompanies, true) ? 'selected' : '' }}>{{ $company }}</option>
                                @endforeach
                            </select>
                            <div id="ef-perusahaan-utama-group" class="ef-sub" style="display:none;">
                                <label for="ef-perusahaan" class="ef-sublabel">Perusahaan utama</label>
                                <select id="ef-perusahaan" name="perusahaan" class="form-control form-control-sm" data-initial="{{ $val('perusahaan') }}"></select>
                            </div>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="ef-position_ids">Posisi {!! $req !!}</label>
                            <select name="position_ids[]" id="ef-position_ids" class="form-control ef-select2" multiple required data-placeholder="Pilih satu atau lebih">
                                @foreach($positions as $position)
                                    <option value="{{ $position->id }}" {{ in_array($position->id, $selectedPositions) ? 'selected' : '' }}>{{ $position->name }}{{ $position->is_active ? '' : ' (nonaktif)' }}</option>
                                @endforeach
                            </select>
                            <div id="ef-primary-group" class="ef-sub">
                                <label for="ef-primary_position" class="ef-sublabel">Posisi utama</label>
                                <select name="primary_position" id="ef-primary_position" class="form-control ef-select2" data-initial="{{ $primaryPositionId ?? '' }}"></select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ef-block">
                    {!! $block('fa-user-check', 'Status kerja', 'Tanggal masuk menentukan jatah cuti & masa kerja') !!}
                    <div class="ef-block-body form-row">
                        <div class="form-group col-md-6">
                            <label for="ef-status">Status {!! $req !!}</label>
                            <select name="status" id="ef-status" class="form-control" required>
                                <option value="">Pilih status</option>
                                @foreach(['tetap' => 'Tetap', 'kontrak' => 'Kontrak', 'freelance' => 'Freelance', 'tidak aktif' => 'Tidak Aktif'] as $value => $label)
                                    <option value="{{ $value }}" {{ $val('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">
                                @if($isEdit && $employee->status === 'kontrak')
                                    Mengganti status dari Kontrak mengakhiri kontrak yang berjalan.
                                @else
                                    Kontrak: periode kontrak diisi setelah simpan.
                                @endif
                            </small>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="ef-tanggal_masuk">Tanggal masuk {!! $req !!}</label>
                            <input type="date" id="ef-tanggal_masuk" name="tanggal_masuk" class="form-control" value="{{ $date('tanggal_masuk') }}" required>
                            <small class="form-text text-muted" id="ef-masa-hint"></small>
                        </div>
                        {{-- Shown only for status Tidak Aktif (toggled by the list page script) --}}
                        <div class="col-12" id="ef-nonaktif-group" style="display:none;">
                            <div class="form-row ef-nonaktif mx-0 mb-3">
                                <div class="form-group col-md-6">
                                    <label for="ef-nonaktif_alasan">Alasan nonaktif {!! $req !!}</label>
                                    <select name="nonaktif_alasan" id="ef-nonaktif_alasan" class="form-control">
                                        <option value="">Pilih alasan</option>
                                        @foreach(\App\Models\HRD\Employee::NONAKTIF_ALASAN as $value => $label)
                                            <option value="{{ $value }}" {{ $val('nonaktif_alasan') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="ef-nonaktif_tanggal">Berlaku sejak {!! $req !!}</label>
                                    <input type="date" id="ef-nonaktif_tanggal" name="nonaktif_tanggal" class="form-control" value="{{ $date('nonaktif_tanggal') ?? now()->format('Y-m-d') }}">
                                </div>
                                <div class="form-group col-12">
                                    <label for="ef-nonaktif_keterangan">Keterangan</label>
                                    <input type="text" id="ef-nonaktif_keterangan" name="nonaktif_keterangan" class="form-control" maxlength="1000" value="{{ $val('nonaktif_keterangan') }}">
                                </div>
                            </div>
                        </div>
                        <div class="form-group col-12">
                            <input type="hidden" name="kategori_pegawai" value="normal">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="ef-kategori_khusus" name="kategori_pegawai" value="khusus" {{ $val('kategori_pegawai') === 'khusus' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="ef-kategori_khusus">Pegawai khusus <span class="text-muted">— tidak mendapat poin KPI awal di slip gaji</span></label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ef-block">
                    {!! $block('fa-fingerprint', 'Absensi & akun', 'No induk, ID mesin absensi, dan akun untuk login aplikasi') !!}
                    <div class="ef-block-body form-row">
                        <div class="form-group col-md-6">
                            <label for="ef-no_induk">No induk</label>
                            <input type="text" id="ef-no_induk" name="no_induk" class="form-control" value="{{ $e->no_induk ?? ($nextNoInduk ?? '') }}">
                            @unless($isEdit)<small class="form-text text-muted">Terisi otomatis, boleh diubah.</small>@endunless
                        </div>
                        <div class="form-group col-md-6">
                            <label for="ef-finger_id">Finger ID</label>
                            <input type="text" id="ef-finger_id" name="finger_id" class="form-control" data-penting maxlength="20" value="{{ $val('finger_id') }}">
                            <small class="form-text text-muted">ID karyawan di mesin absensi.</small>
                        </div>
                        <div class="form-group col-12">
                            <label for="ef-user_id">Akun login</label>
                            {{-- Empty value first: with no option selected the select sends nothing, which would keep the old link --}}
                            <input type="hidden" name="user_id" value="">
                            <select name="user_id" id="ef-user_id" class="form-control" data-url="{{ route('hrd.employee.users-search', ['employee_id' => $e->id ?? null]) }}">
                                @if($isEdit && $employee->user)
                                    <option value="{{ $employee->user->id }}" selected>{{ $employee->user->name }} — {{ $employee->user->email }}</option>
                                @endif
                            </select>
                            <small class="form-text text-muted">Cari nama / email user. Kosongkan jika belum punya akun.</small>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== Payroll ===== --}}
            <div class="tab-pane fade" id="ef-payroll" role="tabpanel">
                <div class="ef-block">
                    {!! $block('fa-coins', 'Gaji', 'Dasar perhitungan slip gaji') !!}
                    <div class="ef-block-body form-row">
                        <div class="form-group col-md-6">
                            <label for="ef-gol_gaji_pokok_id">Gaji pokok</label>
                            <select name="gol_gaji_pokok_id" id="ef-gol_gaji_pokok_id" class="form-control ef-select2" data-penting>
                                <option value="">Belum diatur (Rp0)</option>
                                @foreach($gajiPokokList as $gaji)
                                    <option value="{{ $gaji->id }}" {{ $val('gol_gaji_pokok_id') == $gaji->id ? 'selected' : '' }}>{{ $gaji->golongan }} - Rp{{ number_format($gaji->nominal, 0, ',', '.') }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="ef-gol_tunjangan_jabatan_id">Tunjangan jabatan</label>
                            <select name="gol_tunjangan_jabatan_id" id="ef-gol_tunjangan_jabatan_id" class="form-control ef-select2">
                                <option value="">Tanpa tunjangan</option>
                                @foreach($tunjanganJabatanList as $tunjangan)
                                    <option value="{{ $tunjangan->id }}" {{ $val('gol_tunjangan_jabatan_id') == $tunjangan->id ? 'selected' : '' }}>{{ $tunjangan->golongan }} - Rp{{ number_format($tunjangan->nominal, 0, ',', '.') }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="ef-block">
                    {!! $block('fa-receipt', 'Pajak (PPh 21)', 'Status pernikahan & tanggungan menentukan PTKP') !!}
                    <div class="ef-block-body form-row">
                        <div class="form-group col-md-6">
                            <label for="ef-status_pernikahan">Status pernikahan</label>
                            <select name="status_pernikahan" id="ef-status_pernikahan" class="form-control">
                                <option value="">Pilih status</option>
                                @foreach(\App\Models\HRD\Employee::STATUS_PERNIKAHAN as $value => $label)
                                    <option value="{{ $value }}" {{ $val('status_pernikahan') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="ef-jumlah_tanggungan">Jumlah tanggungan</label>
                            <input type="number" id="ef-jumlah_tanggungan" name="jumlah_tanggungan" class="form-control" min="0" max="20" value="{{ $val('jumlah_tanggungan') }}">
                            <small class="form-text text-muted">PTKP: <b id="ef-ptkp-text">-</b></small>
                            <input type="hidden" id="ef-ptkp">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="ef-npwp">NPWP</label>
                            <input type="text" id="ef-npwp" name="npwp" class="form-control" maxlength="30" value="{{ $val('npwp') }}">
                        </div>
                    </div>
                </div>

                <div class="ef-block">
                    {!! $block('fa-shield-alt', 'BPJS', 'Nomor kepesertaan') !!}
                    <div class="ef-block-body form-row">
                        <div class="form-group col-md-6">
                            <label for="ef-no_bpjs_kesehatan">BPJS Kesehatan</label>
                            <input type="text" id="ef-no_bpjs_kesehatan" name="no_bpjs_kesehatan" class="form-control" maxlength="30" value="{{ $val('no_bpjs_kesehatan') }}" inputmode="numeric">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="ef-no_bpjs_ketenagakerjaan">BPJS Ketenagakerjaan</label>
                            <input type="text" id="ef-no_bpjs_ketenagakerjaan" name="no_bpjs_ketenagakerjaan" class="form-control" maxlength="30" value="{{ $val('no_bpjs_ketenagakerjaan') }}" inputmode="numeric">
                        </div>
                    </div>
                </div>

                <div class="ef-block">
                    {!! $block('fa-university', 'Rekening gaji', 'Tujuan transfer gaji') !!}
                    <div class="ef-block-body form-row">
                        <div class="form-group col-md-6">
                            <label for="ef-bank_nama">Bank</label>
                            <input type="text" id="ef-bank_nama" name="bank_nama" class="form-control" maxlength="50" value="{{ $val('bank_nama') }}" list="ef-bank-list" autocomplete="off">
                            <datalist id="ef-bank-list">
                                @foreach(['BCA', 'BRI', 'BNI', 'Mandiri', 'BSI', 'BTN', 'CIMB Niaga', 'Bank Jateng'] as $bank)
                                    <option value="{{ $bank }}">
                                @endforeach
                            </datalist>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="ef-bank_no_rekening">No rekening</label>
                            <input type="text" id="ef-bank_no_rekening" name="bank_no_rekening" class="form-control" maxlength="40" value="{{ $val('bank_no_rekening') }}" inputmode="numeric">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="ef-bank_atas_nama">Atas nama</label>
                            <input type="text" id="ef-bank_atas_nama" name="bank_atas_nama" class="form-control" maxlength="100" value="{{ $val('bank_atas_nama') }}">
                            <small class="form-text text-muted">Kosongkan bila sama dengan nama karyawan.</small>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== Dokumen ===== --}}
            <div class="tab-pane fade" id="ef-dokumen" role="tabpanel">
                <div class="ef-block">
                    {!! $block('fa-camera', 'Foto', 'Tampil di daftar dan detail karyawan') !!}
                    <div class="ef-block-body form-row">
                        <div class="form-group col-12 d-flex align-items-center">
                            <img id="ef-photo-preview" src="{{ $isEdit && $employee->photo ? asset('storage/' . $employee->photo) : '' }}" alt="" class="rounded-circle mr-3" style="width:64px;height:64px;object-fit:cover;flex:none;{{ $isEdit && $employee->photo ? '' : 'display:none;' }}">
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="ef-photo" name="photo" accept="image/*">
                                <label class="custom-file-label text-truncate" for="ef-photo" data-default="Pilih foto (JPG / PNG, maks. 2MB)">{{ $isEdit && $employee->photo ? 'Ganti foto' : 'Pilih foto (JPG / PNG, maks. 2MB)' }}</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ef-block">
                    {!! $block('fa-folder-open', 'Berkas', 'Maks. 2MB per file; file baru menggantikan yang lama') !!}
                    <div class="ef-block-body form-row">
                        @foreach(['doc_ktp' => 'KTP', 'doc_cv' => 'CV', 'doc_pendukung' => 'Dokumen pendukung'] as $field => $label)
                            <div class="form-group col-md-6">
                                <label for="ef-{{ $field }}">
                                    {{ $label }}
                                    @if($isEdit && $employee->{$field})
                                        <a href="{{ asset('storage/' . $employee->{$field}) }}" class="ml-1" target="_blank"><i class="fas fa-external-link-alt"></i> lihat</a>
                                    @endif
                                </label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="ef-{{ $field }}" name="{{ $field }}">
                                    <label class="custom-file-label text-truncate" for="ef-{{ $field }}" data-default="Pilih file">{{ $isEdit && $employee->{$field} ? basename($employee->{$field}) : 'Pilih file' }}</label>
                                </div>
                            </div>
                        @endforeach
                        <div class="form-group col-md-6">
                            <label>Kontrak</label>
                            <div class="small text-muted pt-1">Diunggah per kontrak lewat tombol <b>Kontrak</b> di daftar karyawan.
                                @if($isEdit && $employee->doc_kontrak)
                                    <a href="{{ asset('storage/' . $employee->doc_kontrak) }}" target="_blank">Lihat dokumen lama</a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-footer justify-content-between">
        <div>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="ef-prev"><i class="fas fa-chevron-left mr-1"></i>Sebelumnya</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="ef-next">Lanjut<i class="fas fa-chevron-right ml-1"></i></button>
        </div>
        <div>
            <button type="button" class="btn btn-outline-secondary mr-1 ef-close">Batal</button>
            <button type="submit" class="btn btn-primary" id="employee-form-save" title="Simpan (Ctrl+S)"><i class="fas fa-save mr-1"></i>{{ $isEdit ? 'Simpan Perubahan' : 'Simpan' }}</button>
        </div>
    </div>
</form>
