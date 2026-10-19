<!-- Modal: Daftarkan Pasien (Rawat Jalan) -->
<link rel="stylesheet" href="{{ asset('css/erm-rawatjalan-modal.css') }}?v={{ filemtime(public_path('css/erm-rawatjalan-modal.css')) }}">

<div class="modal fade" id="modalDaftarKunjunganRawatJalan" tabindex="-1" role="dialog" aria-labelledby="modalDaftarKunjunganRawatJalanLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form id="form-daftar-kunjungan-rawatjalan">
            @csrf
            <input type="hidden" name="jenis_kunjungan" id="rj_jenis_kunjungan" value="1">
            <input type="hidden" id="rj_mode" value="konsultasi">
            <input type="hidden" name="force_create_duplicate" id="rj_force_create_duplicate" value="0">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="modalDaftarKunjunganRawatJalanLabel">Daftarkan Kunjungan Pasien</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="la la-times"></i></span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="rj-visit-modal-layout">
                        <div class="rj-visit-modal-form">
                            <div class="form-group" id="rj_marketplace_patient_mode_group" style="display:none;">
                                <label>Tipe Pasien Marketplace</label>
                                <select id="rj_marketplace_patient_mode" class="form-control">
                                    <option value="existing">Pasien Lama</option>
                                    <option value="new">Pasien Baru</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label id="rj_pasien_label">Pasien</label>
                                <select id="rj_pasien_id" name="pasien_id" class="form-control select2" required></select>
                                <small class="form-text text-muted d-none" id="rj_marketplace_pasien_hint">Pilih pasien lama jika sudah ada. Kosongkan pasien untuk membuat pasien marketplace baru.</small>
                            </div>

                            <div id="rj_referral_section">
                                <div class="form-row">
                                <div class="col-sm form-group">
                                    <label for="rj_referral_type">Referral</label>
                                    <select id="rj_referral_type" name="referral_type" class="form-control rj-referral-select2" disabled>
                                        <option value="">Pilih Referral Kunjungan</option>
                                        <option value="{{ \App\Models\ERM\Pasien::REFERRAL_TYPE_WALK_IN }}">Walk-in</option>
                                        <option value="{{ \App\Models\ERM\Pasien::REFERRAL_TYPE_PASIEN }}">Pasien</option>
                                        <option value="{{ \App\Models\ERM\Pasien::REFERRAL_TYPE_DOKTER }}">Dokter</option>
                                        <option value="{{ \App\Models\ERM\Pasien::REFERRAL_TYPE_EMPLOYEE }}">Karyawan</option>
                                        <option value="{{ \App\Models\ERM\Pasien::REFERRAL_TYPE_SOCIAL_MEDIA }}">Social Media</option>
                                        <option value="{{ \App\Models\ERM\Pasien::REFERRAL_TYPE_MARKETPLACE }}">Marketplace</option>
                                        <option value="{{ \App\Models\ERM\Pasien::REFERRAL_TYPE_EVENT }}">Event</option>
                                        <option value="{{ \App\Models\ERM\Pasien::REFERRAL_TYPE_WEBSITE }}">Website</option>
                                        <option value="{{ \App\Models\ERM\Pasien::REFERRAL_TYPE_PARTNERSHIP }}">B2B Partnership</option>
                                        <option value="{{ \App\Models\ERM\Pasien::REFERRAL_TYPE_GOOGLE_MAPS }}">Google Maps</option>
                                        <option value="{{ \App\Models\ERM\Pasien::REFERRAL_TYPE_FOLLOW_UP }}">Follow Up CRM</option>
                                    </select>
                                </div>

                                <div class="col-sm form-group d-none" id="rj_referral_follow_up_wrapper">
                                    <label for="rj_referral_follow_up_id">Follow Up</label>
                                    <select class="form-control rj-referral-select2" id="rj_referral_follow_up_id" name="referral_follow_up_id" disabled></select>
                                </div>

                                <div class="col-sm form-group d-none" id="rj_referral_pasien_wrapper">
                                    <label for="rj_referral_target_pasien_id">Pasien</label>
                                    <select class="form-control" id="rj_referral_target_pasien_id" name="referral_target_pasien_id" disabled></select>
                                </div>

                                <div class="col-sm form-group d-none" id="rj_referral_employee_wrapper">
                                    <label for="rj_referral_employee_id">Karyawan</label>
                                    <select class="form-control rj-referral-select2" id="rj_referral_employee_id" name="referral_employee_id" disabled>
                                        <option value="">Pilih Karyawan Referral</option>
                                        @foreach($referralEmployees ?? [] as $employee)
                                            <option value="{{ $employee->id }}">{{ $employee->nama }}@if($employee->no_induk) ({{ $employee->no_induk }})@endif</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-sm form-group d-none" id="rj_referral_dokter_wrapper">
                                    <label for="rj_referral_dokter_id">Dokter</label>
                                    <select class="form-control rj-referral-select2" id="rj_referral_dokter_id" name="referral_dokter_id" disabled>
                                        <option value="">Pilih Dokter Referral</option>
                                        @foreach($referralDokters ?? [] as $referralDokter)
                                            <option value="{{ $referralDokter->id }}">{{ $referralDokter->user->name ?? ('Dokter ID ' . $referralDokter->id) }}@if($referralDokter->spesialisasi) ({{ $referralDokter->spesialisasi->nama }})@endif</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-sm form-group d-none" id="rj_referral_event_wrapper">
                                    <label for="rj_referral_event_id">Event</label>
                                    <select class="form-control rj-referral-select2" id="rj_referral_event_id" name="referral_event_id" disabled>
                                        <option value="">Pilih Event Referral</option>
                                        @foreach($referralEvents ?? [] as $referralEvent)
                                            <option value="{{ $referralEvent->id }}">{{ $referralEvent->nama_event }}@if($referralEvent->kode_event) ({{ $referralEvent->kode_event }})@endif</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-sm form-group d-none" id="rj_referral_detail_wrapper">
                                    <label for="rj_referral_detail_input">Detail</label>
                                    <select class="form-control" id="rj_referral_detail_select" name="referral_detail" disabled></select>
                                    <input type="text" class="form-control" id="rj_referral_detail_input" name="referral_detail" maxlength="255" placeholder="Isi detail referral" disabled>
                                </div>
                                </div>
                                <small class="rj-field-hint" id="rj_referral_hint">Pilih pasien terlebih dahulu.</small>
                            </div>

                            <div class="form-group" id="rj_marketplace_referral_group" style="display:none;">
                                <label>Referral Marketplace</label>
                                <select class="form-control" id="rj_marketplace_referral_detail" name="referral_detail">
                                    <option value="">Pilih Marketplace</option>
                                    <option value="shopee">Shopee</option>
                                    <option value="tiktokshop">Tiktokshop</option>
                                    <option value="tokopedia">Tokopedia</option>
                                    <option value="lazada">Lazada</option>
                                </select>
                                <small class="form-text text-muted">Untuk pasien lama, isi jika referral marketplace pasien belum ada.</small>
                            </div>

                            <div id="rj_marketplace_patient_section" style="display:none;">
                                <div class="border rounded p-3 mb-3 bg-light">
                                    <div class="form-group mb-2">
                                        <label>Nama Pasien Baru</label>
                                        <input type="text" class="form-control" id="rj_marketplace_nama" name="nama" maxlength="255">
                                    </div>

                                    <div class="form-group mb-2">
                                        <label>Jenis Kelamin</label>
                                        <select class="form-control" id="rj_marketplace_gender" name="gender">
                                            <option value="">Pilih Jenis Kelamin</option>
                                            <option value="Laki-laki">Laki-laki</option>
                                            <option value="Perempuan">Perempuan</option>
                                        </select>
                                    </div>

                                    <div class="form-group mb-2">
                                        <label>Alamat</label>
                                        <textarea class="form-control" id="rj_marketplace_alamat" name="alamat" rows="2"></textarea>
                                    </div>

                                    <div class="form-group mb-2">
                                        <label>No HP</label>
                                        <input type="text" class="form-control" id="rj_marketplace_no_hp" name="no_hp" maxlength="20">
                                    </div>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="col-sm form-group">
                                    <label for="rj_klinik_id">Klinik</label>
                                    <select id="rj_klinik_id" name="klinik_id" class="form-control select2" required>
                                        <option value="" disabled selected>Pilih Klinik</option>
                                        @foreach($kliniks as $klinik)
                                            <option value="{{ $klinik->id }}">{{ $klinik->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-sm form-group">
                                    <label for="rj_dokter_id">Dokter</label>
                                    <select id="rj_dokter_id" name="dokter_id" class="form-control select2" disabled>
                                        <option value="">Tanpa Dokter</option>
                                    </select>
                                </div>

                                {{-- Only when the dokter holds more than one spesialisasi in this klinik --}}
                                <div class="col-sm form-group" id="rj_spesialisasi_group" style="display:none;">
                                    <label for="rj_spesialisasi_id">Spesialisasi</label>
                                    <select id="rj_spesialisasi_id" name="spesialisasi_id" class="form-control" disabled></select>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="col-sm form-group">
                                    <label for="rj_tanggal_visitation">Tanggal</label>
                                    <input type="date" class="form-control" id="rj_tanggal_visitation" name="tanggal_visitation" required>
                                </div>

                                <div class="col-sm form-group" id="rj_waktu_group">
                                    <label for="rj_waktu_kunjungan">Waktu <span class="rj-label-optional">(opsional)</span></label>
                                    <input type="time" class="form-control" id="rj_waktu_kunjungan" name="waktu_kunjungan">
                                </div>

                                <div class="col-sm-3 form-group" id="rj_no_antrian_group">
                                    <label for="rj_no_antrian">Antrian</label>
                                    <input type="text" name="no_antrian" id="rj_no_antrian" class="form-control text-center" placeholder="-" readonly>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="col-sm-5 form-group mb-0">
                                    <label>Cara Bayar</label>
                                    <div class="btn-group btn-group-toggle d-flex rj-bayar-toggle" data-toggle="buttons">
                                        <label class="btn btn-outline-primary flex-fill active">
                                            <input type="radio" name="metode_bayar_group" value="umum" autocomplete="off" checked> Umum
                                        </label>
                                        <label class="btn btn-outline-primary flex-fill">
                                            <input type="radio" name="metode_bayar_group" value="asuransi" autocomplete="off"> Asuransi
                                        </label>
                                    </div>
                                </div>

                                <div class="col-sm form-group mb-0" id="rj_metode_bayar_wrapper">
                                    <label for="rj_metode_bayar_id" id="rj_metode_bayar_label">Metode Bayar</label>
                                    <select class="form-control" id="rj_metode_bayar_id" name="metode_bayar_id"></select>
                                </div>
                            </div>
                        </div>

                        <div class="rj-visit-modal-calendar">
                            <div class="rj-visit-modal-calendar-head">
                                <div>
                                    <div class="rj-visit-modal-calendar-title" id="rj-calendar-preview-month-label">Kalender Antrian</div>
                                    <small class="rj-visit-modal-calendar-subtitle">Mengikuti filter klinik, dokter, dan tanggal kunjungan pada form.</small>
                                </div>
                                <div class="rj-visit-modal-calendar-summary" id="rj-calendar-preview-summary">Pilih filter lalu cek kalender.</div>
                            </div>
                            <div class="rj-visit-modal-calendar-box" id="rj-calendar-preview-content">
                                <div class="rj-visit-modal-calendar-placeholder">Klik Cek Kalender untuk melihat kepadatan antrian pada bulan yang dipilih.</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-info mr-auto" id="btn-rj-cek-kalender">Cek Kalender</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>window.ErmRawatJalanConfig = {!! json_encode(['metodeBayar' => ($visitMetodeBayar ?? collect())->map(fn ($m) => ['id' => $m->id, 'nama' => $m->nama, 'is_asuransi' => (bool) $m->is_asuransi])->values(), 'routes' => ['erm.pasiens.select2' => route('erm.pasiens.select2'), 'erm.visitations.referral-context' => route('erm.visitations.referral-context'), 'erm.rawatjalans.queueCalendar' => route('erm.rawatjalans.queueCalendar'), 'erm.visitations.produk.store' => route('erm.visitations.produk.store'), 'erm.visitations.lab.store' => route('erm.visitations.lab.store'), 'erm.visitations.marketplace.store' => route('erm.visitations.marketplace.store'), 'erm.visitations.store' => route('erm.visitations.store'), 'erm.pasiens.marketplace.check-duplicate' => route('erm.pasiens.marketplace.check-duplicate'), 'erm.visitations.cekAntrian' => route('erm.visitations.cekAntrian')]], 15) !!};</script>
<script src="{{ asset('js/erm-rawatjalan-modal.js') }}?v={{ filemtime(public_path('js/erm-rawatjalan-modal.js')) }}"></script>
@endpush
