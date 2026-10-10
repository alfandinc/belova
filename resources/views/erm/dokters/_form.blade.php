{{-- Tambah / edit dokter: modal content loaded into #dokterFormModal on the dokter list --}}
@php
    $isEdit = isset($dokter);
    $d = $dokter ?? null;
    $val = fn($field) => $d->{$field} ?? '';
    $date = fn($field) => ($d && $d->{$field}) ? \Carbon\Carbon::parse($d->{$field})->format('Y-m-d') : '';
    $selectedKlinikIds = $isEdit ? $dokter->kliniks->pluck('id')->map(fn ($id) => (int) $id)->all() : [];
    $klinikSpesialisasiIds = $isEdit ? $dokter->kliniks->pluck('pivot.spesialisasi_id', 'id')->all() : [];
    $req = '<span class="text-danger">*</span>';
    // Card per group: icon, title, what it is used for
    $block = fn($icon, $title, $desc) => '<div class="df-block-head"><span class="df-block-icon"><i class="fas ' . $icon . '"></i></span>'
        . '<div><div class="df-block-title">' . e($title) . '</div><div class="df-block-desc">' . e($desc) . '</div></div></div>';
@endphp
<form id="dokter-form" action="{{ route('hrd.dokters.store') }}" method="POST" enctype="multipart/form-data" data-id="{{ $d->id ?? '' }}" novalidate>
    @csrf
    @if($isEdit)
        <input type="hidden" name="id" value="{{ $dokter->id }}">
    @endif

    <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">
            <i class="fas {{ $isEdit ? 'fa-user-edit' : 'fa-user-md' }} mr-2"></i>{{ $isEdit ? 'Edit Dokter · ' . optional($dokter->user)->name : 'Tambah Dokter' }}
        </h5>
        <button type="button" class="close text-white df-close" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    </div>

    <div class="modal-body df-body">
        <div class="alert alert-danger py-2 small" id="dokter-form-errors" style="display:none;"></div>

        <ul class="nav nav-tabs df-tabs mb-3" role="tablist">
            <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#df-pribadi" role="tab"><i class="fas fa-user mr-1"></i>Data Pribadi <span class="df-tab-badge"></span></a></li>
            <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#df-profesional" role="tab"><i class="fas fa-id-badge mr-1"></i>Izin Praktik <span class="df-tab-badge"></span></a></li>
            <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#df-klinik" role="tab"><i class="fas fa-hospital mr-1"></i>Klinik <span class="df-tab-badge"></span></a></li>
        </ul>

        <div class="tab-content">
            {{-- ===== Data Pribadi ===== --}}
            <div class="tab-pane fade show active" id="df-pribadi" role="tabpanel">
                <div class="df-block">
                    {!! $block('fa-user-lock', 'Akun', 'Akun login dengan role dokter yang belum terhubung ke data dokter lain') !!}
                    <div class="df-block-body form-row">
                        <div class="form-group col-md-12">
                            <label for="df-user_id">Akun user {!! $req !!}</label>
                            <select name="user_id" id="df-user_id" class="form-control df-select2" required>
                                <option value="">-- Pilih User --</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ $isEdit && (int) $dokter->user_id === (int) $user->id ? 'selected' : '' }}>{{ $user->name }} ({{ $user->email }})</option>
                                @endforeach
                            </select>
                            @if($users->isEmpty())
                                <small class="form-text text-warning">Belum ada user dengan role dokter yang belum terhubung. Buat akun / beri role dokter terlebih dahulu.</small>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="df-block">
                    {!! $block('fa-id-card', 'Identitas', 'Data pribadi dokter') !!}
                    <div class="df-block-body form-row">
                        <div class="form-group col-md-6">
                            <label for="df-nik">NIK</label>
                            <input type="text" id="df-nik" name="nik" class="form-control" value="{{ $val('nik') }}" inputmode="numeric" maxlength="30">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="df-no_hp">No HP</label>
                            <input type="text" id="df-no_hp" name="no_hp" class="form-control" value="{{ $val('no_hp') }}" inputmode="tel" maxlength="20" placeholder="08xxxxxxxxxx">
                        </div>
                        <div class="form-group col-md-12">
                            <label for="df-alamat">Alamat</label>
                            <textarea id="df-alamat" name="alamat" class="form-control" rows="2">{{ $val('alamat') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="df-block">
                    {!! $block('fa-image', 'Foto & Tanda Tangan', 'TTD dipakai pada hasil lab / surat yang ditandatangani dokter') !!}
                    <div class="df-block-body form-row">
                        <div class="form-group col-md-6">
                            <label for="df-photo">Foto</label>
                            <div class="d-flex align-items-center">
                                <img id="df-photo-preview" src="{{ $isEdit && $dokter->photo ? asset('storage/' . $dokter->photo) : '' }}" alt="" class="df-preview mr-2" style="{{ $isEdit && $dokter->photo ? '' : 'display:none;' }}">
                                <div class="custom-file">
                                    <input type="file" name="photo" class="custom-file-input df-image" id="df-photo" accept="image/*" data-preview="#df-photo-preview">
                                    <label class="custom-file-label text-truncate" for="df-photo" data-default="Pilih foto">Pilih foto</label>
                                </div>
                            </div>
                            @if($isEdit && $dokter->photo)
                                <div class="custom-control custom-checkbox mt-2">
                                    <input type="checkbox" class="custom-control-input df-remove" id="df-remove_photo" name="remove_photo" value="1" data-file="#df-photo" data-preview="#df-photo-preview">
                                    <label class="custom-control-label text-danger small" for="df-remove_photo">Hapus foto</label>
                                </div>
                            @endif
                        </div>
                        <div class="form-group col-md-6">
                            <label for="df-ttd">Tanda tangan (gambar)</label>
                            <div class="d-flex align-items-center">
                                <img id="df-ttd-preview" src="{{ $isEdit && $dokter->ttd ? asset('img/qr/' . $dokter->ttd) : '' }}" alt="" class="df-preview df-preview-ttd mr-2" style="{{ $isEdit && $dokter->ttd ? '' : 'display:none;' }}">
                                <div class="custom-file">
                                    <input type="file" name="ttd" class="custom-file-input df-image" id="df-ttd" accept="image/*" data-preview="#df-ttd-preview">
                                    <label class="custom-file-label text-truncate" for="df-ttd" data-default="Pilih gambar TTD">Pilih gambar TTD</label>
                                </div>
                            </div>
                            @if($isEdit && $dokter->ttd)
                                <div class="custom-control custom-checkbox mt-2">
                                    <input type="checkbox" class="custom-control-input df-remove" id="df-remove_ttd" name="remove_ttd" value="1" data-file="#df-ttd" data-preview="#df-ttd-preview">
                                    <label class="custom-control-label text-danger small" for="df-remove_ttd">Hapus TTD</label>
                                </div>
                            @endif
                        </div>
                        <div class="col-12"><small class="form-text text-muted mt-0 mb-2">Maksimal 5 MB. Kosongkan bila tidak ingin mengganti. File lama dihapus saat diganti.</small></div>
                    </div>
                </div>
            </div>

            {{-- ===== Izin Praktik ===== --}}
            <div class="tab-pane fade" id="df-profesional" role="tabpanel">
                <div class="df-block">
                    {!! $block('fa-id-badge', 'SIP', 'Surat Izin Praktik') !!}
                    <div class="df-block-body form-row">
                        <div class="form-group col-md-6">
                            <label for="df-sip">Nomor SIP {!! $req !!}</label>
                            <input type="text" id="df-sip" name="sip" class="form-control" value="{{ $val('sip') }}" maxlength="255" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="df-due_date_sip">Berlaku sampai</label>
                            <input type="date" id="df-due_date_sip" name="due_date_sip" class="form-control df-due" value="{{ $date('due_date_sip') }}">
                            <small class="form-text df-due-hint"></small>
                        </div>
                    </div>
                </div>
                <div class="df-block">
                    {!! $block('fa-certificate', 'STR', 'Surat Tanda Registrasi') !!}
                    <div class="df-block-body form-row">
                        <div class="form-group col-md-6">
                            <label for="df-str">Nomor STR</label>
                            <input type="text" id="df-str" name="str" class="form-control" value="{{ $val('str') }}" maxlength="255">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="df-due_date_str">Berlaku sampai</label>
                            <input type="date" id="df-due_date_str" name="due_date_str" class="form-control df-due" value="{{ $date('due_date_str') }}">
                            <small class="form-text df-due-hint"></small>
                        </div>
                    </div>
                </div>
                <div class="df-block">
                    {!! $block('fa-user-md', 'Spesialisasi & Status', 'Spesialisasi default dipakai bila klinik tidak menentukan spesialisasi sendiri') !!}
                    <div class="df-block-body form-row">
                        <div class="form-group col-md-6">
                            <label for="df-spesialisasi_id">Spesialisasi {!! $req !!}</label>
                            <select name="spesialisasi_id" id="df-spesialisasi_id" class="form-control df-select2" required>
                                <option value="">-- Pilih Spesialisasi --</option>
                                @foreach($spesialisasis as $s)
                                    <option value="{{ $s->id }}" {{ $isEdit && (int) $dokter->spesialisasi_id === (int) $s->id ? 'selected' : '' }}>{{ $s->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="df-status">Status</label>
                            <select name="status" id="df-status" class="form-control">
                                <option value="">-- Pilih Status --</option>
                                @foreach(['Kontrak', 'Tetap'] as $status)
                                    <option value="{{ $status }}" {{ $val('status') === $status ? 'selected' : '' }}>{{ $status }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== Klinik ===== --}}
            <div class="tab-pane fade" id="df-klinik" role="tabpanel">
                <div class="df-block">
                    {!! $block('fa-hospital', 'Klinik Utama', 'Klinik tempat dokter terutama praktik') !!}
                    <div class="df-block-body form-row">
                        <div class="form-group col-md-12">
                            <label for="df-klinik_id">Klinik utama {!! $req !!}</label>
                            <select name="klinik_id" id="df-klinik_id" class="form-control" required>
                                <option value="">-- Pilih Klinik --</option>
                                @foreach($kliniks as $klinik)
                                    <option value="{{ $klinik->id }}" {{ $isEdit && (int) $dokter->klinik_id === (int) $klinik->id ? 'selected' : '' }}>{{ $klinik->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="df-block">
                    {!! $block('fa-clinic-medical', 'Klinik Praktik', 'Centang semua klinik tempat dokter praktik. Spesialisasi per klinik menentukan form asesmen.') !!}
                    <div class="df-block-body pb-2">
                        @foreach($kliniks as $klinik)
                            @php $checked = in_array((int) $klinik->id, $selectedKlinikIds, true); @endphp
                            <div class="df-klinik-row d-flex flex-wrap align-items-center justify-content-between" data-klinik="{{ $klinik->id }}">
                                <div class="custom-control custom-checkbox my-1">
                                    <input type="checkbox" class="custom-control-input df-klinik-check" id="df-klinik-{{ $klinik->id }}" name="klinik_ids[]" value="{{ $klinik->id }}" {{ $checked ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="df-klinik-{{ $klinik->id }}">{{ $klinik->nama }} <span class="badge badge-primary ml-1 df-utama-badge" style="display:none;">utama</span></label>
                                </div>
                                <select name="klinik_spesialisasi[{{ $klinik->id }}]" class="form-control form-control-sm df-klinik-spesialisasi my-1" style="max-width: 240px;">
                                    <option value="">Spesialisasi default</option>
                                    @foreach($spesialisasis as $s)
                                        <option value="{{ $s->id }}" {{ (string) ($klinikSpesialisasiIds[$klinik->id] ?? '') === (string) $s->id ? 'selected' : '' }}>{{ $s->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-footer justify-content-between">
        <div>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="df-prev"><i class="fas fa-chevron-left mr-1"></i>Sebelumnya</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="df-next">Lanjut<i class="fas fa-chevron-right ml-1"></i></button>
        </div>
        <div>
            <button type="button" class="btn btn-outline-secondary mr-1 df-close">Batal</button>
            <button type="submit" class="btn btn-primary" id="dokter-form-save" title="Simpan (Ctrl+S)"><i class="fas fa-save mr-1"></i>{{ $isEdit ? 'Simpan Perubahan' : 'Simpan' }}</button>
        </div>
    </div>
</form>
