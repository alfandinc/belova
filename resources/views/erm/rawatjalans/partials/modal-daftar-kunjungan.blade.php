<!-- Modal: Daftarkan Pasien (Rawat Jalan) -->
<style>
    #modalDaftarKunjunganRawatJalan .modal-dialog {
        max-width: 560px;
        transition: max-width 0.24s ease;
    }

    #modalDaftarKunjunganRawatJalan.is-calendar-expanded .modal-dialog {
        max-width: 1360px;
    }

    /* Compact form */
    #modalDaftarKunjunganRawatJalan .modal-header {
        padding: 12px 20px;
    }

    #modalDaftarKunjunganRawatJalan .modal-body {
        padding: 16px 20px;
    }

    #modalDaftarKunjunganRawatJalan .modal-footer {
        padding: 10px 20px;
    }

    #modalDaftarKunjunganRawatJalan .rj-visit-modal-form .form-group {
        margin-bottom: 12px;
    }

    #modalDaftarKunjunganRawatJalan .rj-visit-modal-form label {
        margin-bottom: 4px;
        font-size: 0.72rem;
        font-weight: 600;
        letter-spacing: 0.03em;
        color: #5f6c8c;
    }

    #modalDaftarKunjunganRawatJalan .rj-label-optional {
        font-weight: 400;
        text-transform: none;
        letter-spacing: 0;
        color: #9aa5b8;
    }

    #modalDaftarKunjunganRawatJalan #rj_referral_section .form-group {
        margin-bottom: 4px;
    }

    /* Explicit colors: the theme's outline-primary active state keeps grey text on blue. */
    #modalDaftarKunjunganRawatJalan .rj-bayar-toggle .btn {
        padding: 0.375rem 0.5rem;
        font-size: 0.85rem;
        font-weight: 600;
        text-transform: none;
        letter-spacing: 0;
        color: #1d4ed8;
        background-color: #fff;
        border-color: #93b4f5;
        box-shadow: none;
    }

    #modalDaftarKunjunganRawatJalan .rj-bayar-toggle .btn:hover {
        color: #1d4ed8;
        background-color: #eef4ff;
    }

    #modalDaftarKunjunganRawatJalan .rj-bayar-toggle .btn.active,
    #modalDaftarKunjunganRawatJalan .rj-bayar-toggle .btn.active:hover {
        color: #fff;
        background-color: #1d63ed;
        border-color: #1d63ed;
    }

    #modalDaftarKunjunganRawatJalan .rj-bayar-toggle .btn.focus,
    #modalDaftarKunjunganRawatJalan .rj-bayar-toggle .btn:focus-within {
        box-shadow: 0 0 0 0.15rem rgba(29, 99, 237, 0.25);
    }

    /* Locked patient (opened from a patient/visit context): readable, no clear button. */
    #modalDaftarKunjunganRawatJalan #rj_pasien_id + .select2-container--disabled .select2-selection__clear {
        display: none;
    }

    #modalDaftarKunjunganRawatJalan #rj_pasien_id + .select2-container--disabled .select2-selection {
        background-color: #f1f5f9;
        cursor: default;
    }

    #modalDaftarKunjunganRawatJalan #rj_pasien_id + .select2-container--disabled .select2-selection__rendered {
        color: #22324a;
        font-weight: 600;
    }

    #modalDaftarKunjunganRawatJalan .rj-field-hint {
        display: block;
        margin-bottom: 12px;
        font-size: 0.72rem;
        line-height: 1.35;
        color: #8a96ab;
    }

    .rj-visit-modal-layout {
        display: flex;
        gap: 24px;
        align-items: stretch;
    }

    .rj-visit-modal-form {
        flex: 0 0 100%;
        min-width: 0;
    }

    .rj-visit-modal-calendar {
        display: none;
        flex: 1 1 0;
        min-width: 760px;
        border-left: 1px solid #e9eef5;
        padding-left: 24px;
    }

    #modalDaftarKunjunganRawatJalan.is-calendar-expanded .rj-visit-modal-form {
        flex: 0 0 430px;
    }

    #modalDaftarKunjunganRawatJalan.is-calendar-expanded .rj-visit-modal-calendar {
        display: flex;
        flex-direction: column;
    }

    .rj-visit-modal-calendar-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 14px;
    }

    .rj-visit-modal-calendar-title {
        font-size: 1rem;
        font-weight: 700;
        color: #22324a;
    }

    .rj-visit-modal-calendar-subtitle {
        display: block;
        margin-top: 2px;
        font-size: 0.74rem;
        color: #7b8aa0;
    }

    .rj-visit-modal-calendar-summary {
        font-size: 0.75rem;
        color: #5f6c8c;
        text-align: right;
    }

    .rj-visit-modal-calendar-box {
        min-height: 420px;
        border: 1px solid #e4ebf4;
        border-radius: 14px;
        background: linear-gradient(180deg, #fbfdff 0%, #f4f8fd 100%);
        padding: 16px;
        width: 100%;
    }

    .rj-visit-modal-calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: 8px;
    }

    .rj-visit-modal-calendar-weekday {
        text-align: center;
        font-size: 0.71rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #687a96;
    }

    .rj-visit-modal-calendar-day {
        min-height: 104px;
        border: 1px solid #dbe4f0;
        border-radius: 12px;
        background: #fff;
        padding: 10px;
        display: flex;
        flex-direction: column;
        gap: 8px;
        box-shadow: 0 6px 18px rgba(15, 23, 42, 0.05);
    }

    .rj-visit-modal-calendar-day.is-empty {
        background: #f5f7fb;
        border-style: dashed;
        box-shadow: none;
    }

    .rj-visit-modal-calendar-day.is-busy {
        background: linear-gradient(180deg, #effcf5 0%, #dbf7e8 100%);
        border-color: #86efac;
    }

    .rj-visit-modal-calendar-day.is-today {
        background: linear-gradient(180deg, #fff8db 0%, #ffefad 100%);
        border-color: #facc15;
    }

    .rj-visit-modal-calendar-day.is-past {
        background: linear-gradient(180deg, #ffffff 0%, #eef4ff 100%);
        border-color: #c8d7ff;
    }

    .rj-visit-modal-calendar-day.is-selected {
        box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.22), 0 10px 22px rgba(37, 99, 235, 0.16);
        border-color: #2563eb;
    }

    .rj-visit-modal-calendar-day-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 8px;
    }

    .rj-visit-modal-calendar-day-number {
        font-size: 1.35rem;
        font-weight: 800;
        line-height: 1;
        color: #1f3b64;
    }

    .rj-visit-modal-calendar-day-name {
        margin-top: 2px;
        font-size: 0.72rem;
        font-weight: 700;
        color: #8696af;
    }

    .rj-visit-modal-calendar-count {
        min-width: 34px;
        padding: 5px 8px;
        border-radius: 10px;
        background: #2563eb;
        color: #fff;
        text-align: center;
        font-weight: 800;
        font-size: 0.95rem;
        line-height: 1;
    }

    .rj-visit-modal-calendar-day.is-busy .rj-visit-modal-calendar-count {
        background: #22c55e;
    }

    .rj-visit-modal-calendar-day.is-today .rj-visit-modal-calendar-count {
        background: #f4b400;
    }

    .rj-visit-modal-calendar-day.is-past .rj-visit-modal-calendar-count {
        background: #3b82f6;
    }

    .rj-visit-modal-calendar-breakdown {
        margin-top: auto;
        display: grid;
        gap: 2px;
        font-size: 0.69rem;
        line-height: 1.18;
        color: #5f6c8c;
    }

    .rj-visit-modal-calendar-breakdown small {
        display: block;
    }

    .rj-visit-modal-calendar-placeholder {
        height: 100%;
        min-height: 390px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        color: #7b8aa0;
        font-size: 0.88rem;
    }

    #btn-rj-cek-kalender.active {
        background: #eef4ff;
        border-color: #9bb8ff;
        color: #1d4ed8;
    }

    @media (max-width: 991.98px) {
        #modalDaftarKunjunganRawatJalan .modal-dialog,
        #modalDaftarKunjunganRawatJalan.is-calendar-expanded .modal-dialog {
            max-width: calc(100vw - 24px);
        }

        .rj-visit-modal-layout {
            flex-direction: column;
        }

        .rj-visit-modal-calendar {
            border-left: 0;
            border-top: 1px solid #e9eef5;
            min-width: 0;
            padding-left: 0;
            padding-top: 18px;
        }

        .rj-visit-modal-calendar-box {
            min-height: 0;
        }

        .rj-visit-modal-calendar-day {
            min-height: 96px;
        }
    }
</style>

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
                                    </select>
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
<script>
$(document).ready(function(){
    let rjFixedPasienContext = null;
    let rjCalendarPreviewVisible = false;
    let rjSubmitting = false;
    let rjPendingDokterId = null;

    // init select2 inside modal
    $('#modalDaftarKunjunganRawatJalan select.select2:not(#rj_pasien_id)').select2({ width: '100%' });

    // pasien select2 ajax
    $('#rj_pasien_id').select2({
        width: '100%',
        placeholder: 'Cari pasien (nama / RM / identitas)',
        allowClear: true,
        ajax: {
            url: "{{ route('erm.pasiens.select2') }}",
            dataType: 'json',
            delay: 250,
            data: function(params){
                return { q: params.term || '' };
            },
            processResults: function(data){
                return data;
            },
            cache: true
        }
    });

    // ===== Referral kunjungan =====
    // First visit: locked to the patient's source referral. Later visits: staff must pick one.
    const RJ_REFERRAL_LABELS = {
        walk_in: 'Walk-in',
        pasien: 'Pasien',
        dokter: 'Dokter',
        employee: 'Karyawan',
        social_media: 'Social Media',
        marketplace: 'Marketplace',
        event: 'Event',
        website: 'Website',
        partnership: 'B2B Partnership',
        google_maps: 'Google Maps'
    };
    const RJ_REFERRAL_DETAIL_OPTIONS = {
        social_media: [
            { value: 'instagram', label: 'Instagram' },
            { value: 'tiktok', label: 'TikTok' },
            { value: 'facebook', label: 'Facebook' },
            { value: 'threads', label: 'Threads' },
            { value: 'twitter', label: 'Twitter / X' },
            { value: 'whatsapp', label: 'WhatsApp' }
        ],
        marketplace: [
            { value: 'shopee', label: 'Shopee' },
            { value: 'tiktokshop', label: 'TikTok Shop' },
            { value: 'tokopedia', label: 'Tokopedia' },
            { value: 'lazada', label: 'Lazada' }
        ]
    };
    const RJ_REFERRAL_TYPES_WITH_DETAIL = ['social_media', 'marketplace', 'partnership', 'google_maps'];
    let rjReferralContext = null;

    $('#modalDaftarKunjunganRawatJalan .rj-referral-select2').select2({ width: '100%' });
    $('#rj_referral_target_pasien_id').select2({
        width: '100%',
        placeholder: 'Cari pasien referral',
        allowClear: true,
        ajax: {
            url: "{{ route('erm.pasiens.select2') }}",
            dataType: 'json',
            delay: 250,
            data: function(params){
                return { q: params.term || '' };
            },
            processResults: function(data){
                return data;
            }
        }
    });

    function currentRjPasienId() {
        return (rjFixedPasienContext && rjFixedPasienContext.id) || $('#rj_pasien_id').val() || '';
    }

    // Repeat visit registered by a dokter: referral is that dokter (set server-side too).
    function isRjReferralLockedToDokter() {
        return !!rjReferralContext
            && !rjReferralContext.is_first_visit
            && !!rjReferralContext.registrar_dokter;
    }

    function isRjReferralEditable() {
        return !isMarketplaceMode()
            && !!currentRjPasienId()
            && !!rjReferralContext
            && !rjReferralContext.is_first_visit
            && !isRjReferralLockedToDokter();
    }

    function rjReferralSourceLabel(source) {
        if (!source) {
            return '-';
        }

        const typeLabel = RJ_REFERRAL_LABELS[source.referral_type] || source.referral_type || 'Walk-in';
        const detail = source.referralable_label || source.referral_detail || '';

        return detail ? typeLabel + ': ' + detail : typeLabel;
    }

    function resetRjReferralInputs() {
        $('#rj_referral_type').val('').trigger('change.select2');
        $('#rj_referral_employee_id, #rj_referral_dokter_id, #rj_referral_event_id').val('').trigger('change.select2');
        $('#rj_referral_target_pasien_id').val(null).trigger('change.select2');
        $('#rj_referral_detail_select').empty();
        $('#rj_referral_detail_input').val('');
    }

    function syncRjReferralFields() {
        const editable = isRjReferralEditable();
        const type = $('#rj_referral_type').val() || '';
        const detailOptions = RJ_REFERRAL_DETAIL_OPTIONS[type] || null;
        const needsDetail = RJ_REFERRAL_TYPES_WITH_DETAIL.includes(type);
        const detailSelect = $('#rj_referral_detail_select');

        $('#rj_referral_type').prop('disabled', !editable);

        // Sub-fields only matter while the staff is choosing a referral.
        $('#rj_referral_pasien_wrapper').toggleClass('d-none', !editable || type !== 'pasien');
        $('#rj_referral_employee_wrapper').toggleClass('d-none', !editable || type !== 'employee');
        $('#rj_referral_dokter_wrapper').toggleClass('d-none', !editable || type !== 'dokter');
        $('#rj_referral_event_wrapper').toggleClass('d-none', !editable || type !== 'event');
        $('#rj_referral_detail_wrapper').toggleClass('d-none', !editable || !needsDetail);

        $('#rj_referral_target_pasien_id').prop('disabled', !editable || type !== 'pasien');
        $('#rj_referral_employee_id').prop('disabled', !editable || type !== 'employee');
        $('#rj_referral_dokter_id').prop('disabled', !editable || type !== 'dokter');
        $('#rj_referral_event_id').prop('disabled', !editable || type !== 'event');

        if (detailOptions) {
            const currentValue = detailSelect.val() || '';
            let optionsHtml = '<option value="">Pilih Detail Referral</option>';
            detailOptions.forEach(function(option) {
                optionsHtml += '<option value="' + option.value + '">' + option.label + '</option>';
            });
            detailSelect.html(optionsHtml).val(currentValue);
        } else {
            detailSelect.empty();
        }

        detailSelect.toggleClass('d-none', !detailOptions).prop('disabled', !editable || !detailOptions);
        $('#rj_referral_detail_input')
            .toggleClass('d-none', !needsDetail || !!detailOptions)
            .prop('disabled', !editable || !needsDetail || !!detailOptions);
    }

    function loadRjReferralContext() {
        const pasienId = currentRjPasienId();

        rjReferralContext = null;
        resetRjReferralInputs();
        $('#rj_referral_section').toggle(!isMarketplaceMode());

        if (isMarketplaceMode() || !pasienId) {
            $('#rj_referral_hint').text('Pilih pasien terlebih dahulu.');
            syncRjReferralFields();
            return;
        }

        $('#rj_referral_hint').text('Memuat referral pasien...');
        syncRjReferralFields();

        $.get("{{ route('erm.visitations.referral-context') }}", { pasien_id: pasienId })
            .done(function(res) {
                if (currentRjPasienId() !== pasienId) {
                    return;
                }

                rjReferralContext = res || { is_first_visit: false, source: null };
                const sourceLabel = rjReferralSourceLabel(rjReferralContext.source);

                if (rjReferralContext.is_first_visit) {
                    $('#rj_referral_type').val(rjReferralContext.source.referral_type).trigger('change.select2');
                    $('#rj_referral_hint').text('Kunjungan pertama — otomatis mengikuti sumber pasien (' + sourceLabel + ').');
                } else if (isRjReferralLockedToDokter()) {
                    $('#rj_referral_type').val('dokter').trigger('change.select2');
                    $('#rj_referral_hint').text('Didaftarkan oleh dokter — referral otomatis: Dokter (' + rjReferralContext.registrar_dokter.label + '). Sumber awal pasien: ' + sourceLabel + '.');
                } else {
                    $('#rj_referral_hint').text('Sumber awal pasien: ' + sourceLabel + '. Pilih alasan datang kunjungan ini.');
                }

                syncRjReferralFields();
            })
            .fail(function() {
                if (currentRjPasienId() !== pasienId) {
                    return;
                }

                rjReferralContext = { is_first_visit: false, source: null };
                $('#rj_referral_hint').text('Gagal memuat sumber pasien. Pilih referral kunjungan.');
                syncRjReferralFields();
            });
    }

    function validateRjReferral() {
        if (!isRjReferralEditable()) {
            return null;
        }

        const type = $('#rj_referral_type').val() || '';

        if (!type) return 'Referral kunjungan wajib dipilih.';
        if (type === 'pasien' && !$('#rj_referral_target_pasien_id').val()) return 'Pasien referral wajib dipilih.';
        if (type === 'employee' && !$('#rj_referral_employee_id').val()) return 'Karyawan referral wajib dipilih.';
        if (type === 'dokter' && !$('#rj_referral_dokter_id').val()) return 'Dokter referral wajib dipilih.';
        if (type === 'event' && !$('#rj_referral_event_id').val()) return 'Event referral wajib dipilih.';
        if (RJ_REFERRAL_DETAIL_OPTIONS[type] && !$('#rj_referral_detail_select').val()) return 'Detail referral wajib dipilih.';
        if (['partnership', 'google_maps'].includes(type) && !($('#rj_referral_detail_input').val() || '').trim()) return 'Detail referral wajib diisi.';

        return null;
    }

    // ===== Cara bayar: Umum / Asuransi =====
    const RJ_METODE_BAYAR = @json(($visitMetodeBayar ?? collect())->map(fn ($m) => ['id' => $m->id, 'nama' => $m->nama, 'is_asuransi' => (bool) $m->is_asuransi])->values());

    $('#rj_metode_bayar_id').select2({ width: '100%' });

    function rjMetodeBayarGroup() {
        return $('input[name="metode_bayar_group"]:checked').val() || 'umum';
    }

    function setRjMetodeBayarGroup(group, selectedId) {
        const isAsuransi = group === 'asuransi';
        const options = RJ_METODE_BAYAR.filter(function(m) { return m.is_asuransi === isAsuransi; });
        const $select = $('#rj_metode_bayar_id').empty();

        $('input[name="metode_bayar_group"]').each(function() {
            const selected = this.value === group;
            this.checked = selected;
            $(this).closest('label').toggleClass('active', selected);
        });

        if (options.length !== 1) {
            $select.append(new Option(isAsuransi ? 'Pilih Asuransi' : 'Pilih Metode Bayar', '', true, true));
        }
        options.forEach(function(m) {
            $select.append(new Option(m.nama, m.id, false, false));
        });
        if (selectedId && options.some(function(m) { return String(m.id) === String(selectedId); })) {
            $select.val(String(selectedId));
        } else if (options.length === 1) {
            $select.val(String(options[0].id));
        }
        $select.trigger('change.select2');

        // Umum usually has a single option ("Umum"): no picker needed.
        $('#rj_metode_bayar_wrapper').toggleClass('d-none', !isAsuransi && options.length === 1);
        $('#rj_metode_bayar_label').text(isAsuransi ? 'Penjamin Asuransi' : 'Metode Bayar');
    }

    $(document).on('change', 'input[name="metode_bayar_group"]', function() {
        setRjMetodeBayarGroup(this.value);
    });

    $('#rj_referral_type').on('change', function() {
        $('#rj_referral_employee_id, #rj_referral_dokter_id, #rj_referral_event_id').val('').trigger('change.select2');
        $('#rj_referral_target_pasien_id').val(null).trigger('change.select2');
        $('#rj_referral_detail_select').val('');
        $('#rj_referral_detail_input').val('');
        syncRjReferralFields();
    });

    function isMarketplaceMode() {
        return $('#rj_mode').val() === 'marketplace';
    }

    function isEventMode() {
        return $('#rj_mode').val() === 'event';
    }

    function marketplacePatientMode() {
        return $('#rj_marketplace_patient_mode').val() || 'existing';
    }

    function isMarketplaceNewPatient() {
        return isMarketplaceMode() && marketplacePatientMode() === 'new';
    }

    function syncMarketplaceState() {
        const marketplaceMode = isMarketplaceMode();
        const newMarketplacePatient = isMarketplaceNewPatient();
        const hasFixedPasien = !!(rjFixedPasienContext && rjFixedPasienContext.id);
        const existingMarketplacePatient = marketplaceMode && !newMarketplacePatient;
        // A fixed patient (opened from a patient/visit context) stays visible as a locked, read-only field.
        const shouldShowPatientSelector = hasFixedPasien ? true : (!marketplaceMode || existingMarketplacePatient);
        const shouldShowPatientModeGroup = marketplaceMode && !hasFixedPasien;
        const shouldShowMarketplacePatientSection = newMarketplacePatient && !hasFixedPasien;

        $('#rj_marketplace_patient_mode_group').toggle(shouldShowPatientModeGroup);
        $('#rj_marketplace_referral_group').toggle(marketplaceMode);
        $('#rj_marketplace_patient_section').toggle(shouldShowMarketplacePatientSection);
        $('#rj_marketplace_pasien_hint').toggleClass('d-none', !marketplaceMode || hasFixedPasien);
        $('#rj_pasien_label').text(marketplaceMode ? 'Pasien Lama' : 'Pasien');
        $('#rj_pasien_id').closest('.form-group').toggle(shouldShowPatientSelector);
        $('#rj_pasien_id').prop('required', hasFixedPasien ? false : shouldShowPatientSelector);
        $('#rj_pasien_id').prop('disabled', hasFixedPasien);
        $('#rj_force_create_duplicate').val('0');

        $('#rj_marketplace_nama, #rj_marketplace_gender, #rj_marketplace_alamat, #rj_marketplace_no_hp')
            .prop('required', shouldShowMarketplacePatientSection);
        $('#rj_marketplace_referral_detail').prop('required', shouldShowMarketplacePatientSection);
        // Shares the referral_detail name with the visit referral fields; only submit it in marketplace mode.
        $('#rj_marketplace_referral_detail').prop('disabled', !marketplaceMode);
    }

    function setFixedPasienContext(pasienId, pasienNama) {
        if (!pasienId) {
            rjFixedPasienContext = null;
            $('#rj_pasien_id').prop('disabled', false).val(null).trigger('change');
            return;
        }

        rjFixedPasienContext = {
            id: pasienId.toString(),
            nama: (pasienNama || '').toString()
        };

        const selectedText = rjFixedPasienContext.nama
            ? rjFixedPasienContext.nama + ' (RM: ' + rjFixedPasienContext.id + ')'
            : 'RM: ' + rjFixedPasienContext.id;

        if ($('#rj_pasien_id').find("option[value='" + rjFixedPasienContext.id + "']").length === 0) {
            const option = new Option(selectedText, rjFixedPasienContext.id, true, true);
            $('#rj_pasien_id').append(option);
        }

        $('#rj_pasien_id').val(rjFixedPasienContext.id).trigger('change');
        $('#rj_pasien_id').prop('disabled', true);
    }

    function marketplaceDuplicatePayload() {
        return {
            nama: ($('#rj_marketplace_nama').val() || '').trim(),
            referral_detail: ($('#rj_marketplace_referral_detail').val() || '').trim()
        };
    }

    function marketplaceDuplicateHtml(pasien) {
        pasien = pasien || {};
        const lines = [
            '<div>Sudah ada pasien marketplace dengan nama dan referral yang sama.</div>',
            '<div class="mt-2 text-left">',
            '<div><strong>No RM:</strong> ' + $('<div>').text(pasien.id || '-').html() + '</div>',
            '<div><strong>Nama:</strong> ' + $('<div>').text(pasien.nama || '-').html() + '</div>',
            '<div><strong>No HP:</strong> ' + $('<div>').text(pasien.no_hp || '-').html() + '</div>',
            '<div><strong>Referral:</strong> ' + $('<div>').text(pasien.referral_detail || '-').html() + '</div>',
            '</div>',
            '<div class="mt-3">Lanjutkan membuat pasien baru?</div>'
        ];

        return lines.join('');
    }

    function resetRawatJalanMarketplaceFields() {
        $('#rj_force_create_duplicate').val('0');
        $('#rj_marketplace_patient_mode').val('existing');
        $('#rj_marketplace_nama').val('');
        $('#rj_marketplace_gender').val('');
        $('#rj_marketplace_alamat').val('');
        $('#rj_marketplace_no_hp').val('');
        $('#rj_marketplace_referral_detail').val('');
    }

    function escapeRjCalendarHtml(value) {
        if (value === null || typeof value === 'undefined') {
            return '';
        }

        return $('<div>').text(value).html();
    }

    function renderRjCalendarBreakdownItem(label, value) {
        var parsedValue = parseInt(value || 0, 10);

        if (parsedValue <= 0) {
            return '';
        }

        return '<small>' + escapeRjCalendarHtml(label) + ': ' + escapeRjCalendarHtml(parsedValue) + '</small>';
    }

    function renderRjCalendarPreview(response, selectedDate) {
        var weekdays = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
        var month = response && response.month ? response.month : {};
        var summary = response && response.summary ? response.summary : {};
        var days = Array.isArray(response && response.days) ? response.days : [];
        var startsOn = parseInt(month.starts_on || 1, 10);
        var html = '<div class="rj-visit-modal-calendar-grid">';

        weekdays.forEach(function(label) {
            html += '<div class="rj-visit-modal-calendar-weekday">' + escapeRjCalendarHtml(label) + '</div>';
        });

        for (var blank = 1; blank < startsOn; blank++) {
            html += '<div class="rj-visit-modal-calendar-day is-empty" aria-hidden="true"></div>';
        }

        days.forEach(function(day) {
            var count = parseInt(day.count || 0, 10);
            var breakdown = day && day.breakdown ? day.breakdown : {};
            var classes = ['rj-visit-modal-calendar-day'];
            var breakdownItems = [
                renderRjCalendarBreakdownItem('Konsul', breakdown.konsultasi),
                renderRjCalendarBreakdownItem('Produk', breakdown.produk),
                renderRjCalendarBreakdownItem('Lab', breakdown.lab),
                renderRjCalendarBreakdownItem('Event', breakdown.event),
                renderRjCalendarBreakdownItem('Marketplace', breakdown.marketplace)
            ].join('');

            if (day.is_past) {
                classes.push('is-past');
            }

            if (day.is_today) {
                classes.push('is-today');
            }

            if (count > 0) {
                classes.push('is-busy');
            }

            if (selectedDate && day.date === selectedDate) {
                classes.push('is-selected');
            }

            html += '<div class="' + classes.join(' ') + '">'
                + '<div class="rj-visit-modal-calendar-day-head">'
                + '<div>'
                + '<div class="rj-visit-modal-calendar-day-number">' + escapeRjCalendarHtml(day.day) + '</div>'
                + '<div class="rj-visit-modal-calendar-day-name">' + escapeRjCalendarHtml(day.weekday || '') + '</div>'
                + '</div>'
                + '<div class="rj-visit-modal-calendar-count">' + escapeRjCalendarHtml(count) + '</div>'
                + '</div>'
                + (breakdownItems ? '<div class="rj-visit-modal-calendar-breakdown">' + breakdownItems + '</div>' : '')
                + '</div>';
        });

        html += '</div>';

        $('#rj-calendar-preview-month-label').text(month.label || 'Kalender Antrian');
        $('#rj-calendar-preview-summary').text(
            'Total visit: ' + (summary.total_visits || 0)
            + ' | Hari terisi: ' + (summary.active_days || 0)
            + ' | Puncak harian: ' + (summary.max_visits || 0)
        );
        $('#rj-calendar-preview-content').html(html);
    }

    function setRjCalendarPreviewLoading(message) {
        $('#rj-calendar-preview-month-label').text('Kalender Antrian');
        $('#rj-calendar-preview-summary').text(message || 'Memuat data antrian...');
        $('#rj-calendar-preview-content').html('<div class="rj-visit-modal-calendar-placeholder"><span class="spinner-border spinner-border-sm mr-2"></span>Memuat kalender antrian...</div>');
    }

    function loadRjCalendarPreview() {
        if (!rjCalendarPreviewVisible) {
            return;
        }

        var selectedDate = $('#rj_tanggal_visitation').val() || '';
        var selectedMonth = selectedDate && window.moment
            ? moment(selectedDate).format('YYYY-MM')
            : (window.moment ? moment().format('YYYY-MM') : '');

        setRjCalendarPreviewLoading('Memuat data antrian...');

        $.get('{{ route("erm.rawatjalans.queueCalendar") }}', {
            month: selectedMonth,
            dokter_id: $('#rj_dokter_id').val() || '',
            klinik_id: $('#rj_klinik_id').val() || ''
        }).done(function(response) {
            renderRjCalendarPreview(response || {}, selectedDate);
        }).fail(function(xhr) {
            $('#rj-calendar-preview-summary').text('Gagal memuat data antrian.');
            $('#rj-calendar-preview-content').html('<div class="rj-visit-modal-calendar-placeholder text-danger">Tidak dapat memuat kalender antrian.</div>');
            console.error('Failed to load modal queue calendar', xhr);
        });
    }

    function toggleRjCalendarPreview(forceVisible) {
        rjCalendarPreviewVisible = typeof forceVisible === 'boolean' ? forceVisible : !rjCalendarPreviewVisible;
        $('#modalDaftarKunjunganRawatJalan').toggleClass('is-calendar-expanded', rjCalendarPreviewVisible);
        $('#btn-rj-cek-kalender')
            .toggleClass('active', rjCalendarPreviewVisible)
            .text(rjCalendarPreviewVisible ? 'Sembunyikan Kalender' : 'Cek Kalender');

        if (rjCalendarPreviewVisible) {
            loadRjCalendarPreview();
            return;
        }

        $('#rj-calendar-preview-month-label').text('Kalender Antrian');
        $('#rj-calendar-preview-summary').text('Pilih filter lalu cek kalender.');
        $('#rj-calendar-preview-content').html('<div class="rj-visit-modal-calendar-placeholder">Klik Cek Kalender untuk melihat kepadatan antrian pada bulan yang dipilih.</div>');
    }

    function resolveSubmitUrl() {
        const mode = $('#rj_mode').val();
        if (mode === 'produk') {
            return "{{ route('erm.visitations.produk.store') }}";
        }
        if (mode === 'lab') {
            return "{{ route('erm.visitations.lab.store') }}";
        }
        if (mode === 'marketplace') {
            return "{{ route('erm.visitations.marketplace.store') }}";
        }

        return "{{ route('erm.visitations.store') }}";
    }

    function showMarketplaceDuplicateWarning(onConfirm, pasien) {
        Swal.fire({
            icon: 'warning',
            title: 'Data Mirip Ditemukan',
            html: marketplaceDuplicateHtml(pasien),
            showCancelButton: true,
            confirmButtonText: 'Lanjut Buat Pasien Baru',
            cancelButtonText: 'Batal'
        }).then(function(result) {
            if (result.isConfirmed) {
                $('#rj_force_create_duplicate').val('1');
                onConfirm();
            }
        });
    }

    function submitRawatJalanForm() {
        const formData = $('#form-daftar-kunjungan-rawatjalan').serializeArray();

        if (rjFixedPasienContext && rjFixedPasienContext.id && !formData.some(function(field) {
            return field.name === 'pasien_id';
        })) {
            formData.push({
                name: 'pasien_id',
                value: rjFixedPasienContext.id
            });
        }

        // Guard against double submit (double click / Enter while the request is in flight).
        if (rjSubmitting) {
            return;
        }
        rjSubmitting = true;
        const $submitBtn = $('#form-daftar-kunjungan-rawatjalan button[type="submit"]').prop('disabled', true);

        $.ajax({
            url: resolveSubmitUrl(),
            type: 'POST',
            data: $.param(formData)
        }).always(function(){
            rjSubmitting = false;
            $submitBtn.prop('disabled', false);
        }).done(function(res){
            // Lets the host page (rawat jalan, pasien, billing) refresh its own tables.
            $(document).trigger('rj:visitation-registered', [res]);

            $('#modalDaftarKunjunganRawatJalan').modal('hide');
            $('#form-daftar-kunjungan-rawatjalan')[0].reset();
            $('#rj_pasien_id').val(null).trigger('change');
            $('#rj_dokter_id').empty().append('<option value="">Tanpa Dokter</option>').prop('disabled', true).trigger('change.select2');
            $('#rj_no_antrian').val('');
            resetRawatJalanMarketplaceFields();

            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: (res && res.message) ? res.message : 'Kunjungan berhasil disimpan.',
                confirmButtonText: 'OK'
            }).then(function(){
                try {
                    $('#rawatjalan-table').DataTable().ajax.reload(null, false);
                } catch(e) {}
                try {
                    if (typeof updateStats === 'function') updateStats();
                } catch(e) {}
                try {
                    if (window.pasiensTable && typeof window.pasiensTable.ajax === 'object') window.pasiensTable.ajax.reload(null, false);
                } catch(e) {}
                // After the success popup closes (e.g. pasien create page reloads itself).
                $(document).trigger('rj:visitation-success-closed', [res]);
            });
        }).fail(function(xhr){
            if (xhr && xhr.status === 409 && xhr.responseJSON && xhr.responseJSON.duplicate) {
                showMarketplaceDuplicateWarning(submitRawatJalanForm, xhr.responseJSON.pasien || {});
                return;
            }

            let msg = 'Terjadi kesalahan. Pastikan semua data valid.';
            if (xhr && xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.message) {
                msg = xhr.responseJSON.message;
            }
            Swal.fire({ icon: 'error', title: 'Gagal', text: msg, confirmButtonText: 'OK' });
        });
    }

    function checkMarketplaceDuplicateThenSubmit() {
        $.get("{{ route('erm.pasiens.marketplace.check-duplicate') }}", marketplaceDuplicatePayload())
            .done(function(res) {
                if (res && res.exists) {
                    showMarketplaceDuplicateWarning(submitRawatJalanForm, res.pasien || {});
                    return;
                }

                submitRawatJalanForm();
            })
            .fail(function() {
                submitRawatJalanForm();
            });
    }

    function applyMode(mode){
        mode = (mode || 'konsultasi').toString();
        $('#rj_mode').val(mode);

        if (mode === 'produk') {
            $('#modalDaftarKunjunganRawatJalanLabel').text('Daftarkan Kunjungan Beli Produk Pasien');
            $('#rj_jenis_kunjungan').val('2');
            $('#rj_waktu_group').hide();
            $('#rj_no_antrian_group').hide();
            $('#rj_waktu_kunjungan').val('');
            $('#rj_no_antrian').val('');
        } else if (mode === 'lab') {
            $('#modalDaftarKunjunganRawatJalanLabel').text('Daftarkan Kunjungan Laboratorium Pasien');
            $('#rj_jenis_kunjungan').val('3');
            $('#rj_waktu_group').hide();
            $('#rj_no_antrian_group').hide();
            $('#rj_waktu_kunjungan').val('');
            $('#rj_no_antrian').val('');
        } else if (mode === 'marketplace') {
            $('#modalDaftarKunjunganRawatJalanLabel').text('Daftarkan Kunjungan Marketplace Pasien');
            $('#rj_jenis_kunjungan').val('5');
            $('#rj_waktu_group').hide();
            $('#rj_no_antrian_group').hide();
            $('#rj_waktu_kunjungan').val('');
            $('#rj_no_antrian').val('');
        } else if (mode === 'event') {
            $('#modalDaftarKunjunganRawatJalanLabel').text('Daftarkan Kunjungan Event Pasien');
            $('#rj_jenis_kunjungan').val('4');
            $('#rj_waktu_group').hide();
            $('#rj_no_antrian_group').hide();
            $('#rj_waktu_kunjungan').val('');
            $('#rj_no_antrian').val('');
        } else {
            $('#modalDaftarKunjunganRawatJalanLabel').text('Daftarkan Kunjungan Pasien');
            $('#rj_jenis_kunjungan').val('1');
            $('#rj_waktu_group').show();
            $('#rj_no_antrian_group').show();
        }

        syncMarketplaceState();
        loadRjReferralContext();
        setRjMetodeBayarGroup('umum');
    }

    function todayYmd() {
        const d = new Date();
        const pad = function(n) { return n < 10 ? '0' + n : String(n); };
        return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
    }

    /**
     * Single entry point for every "Daftarkan Kunjungan" flow.
     * opts: { jenis, pasienId, pasienNama, klinikId, dokterId, metodeBayarId } (all optional)
     */
    function openRjDaftarKunjungan(opts) {
        opts = opts || {};
        applyMode(opts.jenis || 'konsultasi');
        setFixedPasienContext(opts.pasienId || '', opts.pasienNama || '');
        $('#rj_tanggal_visitation').val(todayYmd());

        // Pre-fill from a current visit (e.g. ERM sidebar "Reservasi Kunjungan").
        if (opts.klinikId && $('#rj_klinik_id option[value="' + opts.klinikId + '"]').length) {
            rjPendingDokterId = opts.dokterId ? String(opts.dokterId) : null;
            $('#rj_klinik_id').val(String(opts.klinikId)).trigger('change');
        }

        const metode = RJ_METODE_BAYAR.find(function(m) { return String(m.id) === String(opts.metodeBayarId || ''); });
        if (metode) {
            setRjMetodeBayarGroup(metode.is_asuransi ? 'asuransi' : 'umum', metode.id);
        }

        $('#modalDaftarKunjunganRawatJalan').modal('show');
    }

    window.openDaftarKunjunganModal = openRjDaftarKunjungan;

    // open modal (from dropdown / any trigger with this class)
    $(document).on('click', '.btn-daftarkan-pasien-rawatjalan', function(e){
        e.preventDefault();
        const $btn = $(this);
        openRjDaftarKunjungan({
            jenis: $btn.data('jenis') || 'konsultasi',
            pasienId: $btn.data('id') || '',
            pasienNama: $btn.data('nama') || '',
            klinikId: $btn.data('klinik') || '',
            dokterId: $btn.data('dokter') || '',
            metodeBayarId: $btn.data('metodebayar') || ''
        });
    });

    function cekAntrianRJ(){
        let dokterId = $('#rj_dokter_id').val();
        let tanggal = $('#rj_tanggal_visitation').val();
        if (!dokterId || !tanggal) {
            $('#rj_no_antrian').val('');
            return;
        }
        if ($('#rj_mode').val() !== 'konsultasi') return;

        $.get("{{ route('erm.visitations.cekAntrian') }}", { dokter_id: dokterId, tanggal: tanggal }, function(res){
            $('#rj_no_antrian').val(res.no_antrian || '');
        }).fail(function(){
            $('#rj_no_antrian').val('');
        });
    }

    // klinik => load doctors
    $('#rj_klinik_id').on('change', function(){
        let klinikId = $(this).val();
        let dokterSelect = $('#rj_dokter_id');

        dokterSelect.empty().append('<option value="">Loading...</option>').prop('disabled', true);
        if (!klinikId) {
            dokterSelect.empty().append('<option value="">Tanpa Dokter</option>').prop('disabled', true).trigger('change.select2');
            $('#rj_no_antrian').val('');
            return;
        }

        $.ajax({
            url: `/get-dokters/${klinikId}`,
            type: 'GET'
        }).done(function(data){
            dokterSelect.empty().append('<option value="">Tanpa Dokter</option>');
            if (data && data.length) {
                $.each(data, function(_, dokter){
                    let dokterName = (dokter.user && dokter.user.name) ? dokter.user.name : 'Unknown Doctor';
                    let spesialis = (dokter.spesialisasi && dokter.spesialisasi.nama) ? ` (${dokter.spesialisasi.nama})` : '';
                    dokterSelect.append(`<option value="${dokter.id}">${dokterName}${spesialis}</option>`);
                });
            }
            dokterSelect.prop('disabled', false).trigger('change.select2');

            // Pre-filled dokter (openRjDaftarKunjungan): select it once the klinik's list is in.
            if (rjPendingDokterId && dokterSelect.find('option[value="' + rjPendingDokterId + '"]').length) {
                dokterSelect.val(rjPendingDokterId).trigger('change');
            }
            rjPendingDokterId = null;
        }).fail(function(){
            dokterSelect.empty().append('<option value="">Tanpa Dokter</option>').prop('disabled', false).trigger('change.select2');
            Swal.fire({ icon: 'error', title: 'Error', text: 'Gagal mengambil data dokter' });
        });
    });

    // dokter/date => cek antrian
    $('#rj_dokter_id, #rj_tanggal_visitation').on('change', function(){
        cekAntrianRJ();
        loadRjCalendarPreview();
    });

    $('#rj_klinik_id').on('change', function(){
        loadRjCalendarPreview();
    });

    $('#btn-rj-cek-kalender').on('click', function() {
        toggleRjCalendarPreview();
    });

    $('#rj_marketplace_patient_mode').on('change', function() {
        $('#rj_force_create_duplicate').val('0');
        if (rjFixedPasienContext && rjFixedPasienContext.id) {
            $(this).val('existing');
            syncMarketplaceState();
            return;
        }
        if (marketplacePatientMode() === 'new') {
            $('#rj_pasien_id').val(null).trigger('change');
        }
        syncMarketplaceState();
    });

    $('#rj_pasien_id').on('change', function(){
        syncMarketplaceState();
        loadRjReferralContext();
    });

    // submit
    $('#form-daftar-kunjungan-rawatjalan').on('submit', function(e){
        e.preventDefault();

        if (!$('#rj_metode_bayar_id').val()) {
            const metodeMessage = rjMetodeBayarGroup() === 'asuransi' ? 'Penjamin asuransi wajib dipilih.' : 'Metode bayar wajib dipilih.';
            Swal.fire({ icon: 'warning', title: 'Cara Bayar', text: metodeMessage, confirmButtonText: 'OK' });
            return;
        }

        const referralError = validateRjReferral();
        if (referralError) {
            Swal.fire({ icon: 'warning', title: 'Referral Kunjungan', text: referralError, confirmButtonText: 'OK' });
            return;
        }

        if (!isMarketplaceMode() && currentRjPasienId() && !rjReferralContext) {
            Swal.fire({ icon: 'info', title: 'Mohon tunggu', text: 'Referral pasien masih dimuat.', confirmButtonText: 'OK' });
            return;
        }

        if (isMarketplaceNewPatient() && $('#rj_force_create_duplicate').val() !== '1') {
            checkMarketplaceDuplicateThenSubmit();
            return;
        }

        submitRawatJalanForm();
    });

    // cleanup on close
    $('#modalDaftarKunjunganRawatJalan').on('hidden.bs.modal', function(){
        try { $('#form-daftar-kunjungan-rawatjalan')[0].reset(); } catch(e) {}
        try { $('#rj_pasien_id').val(null).trigger('change'); } catch(e) {}
        try { $('#rj_dokter_id').empty().append('<option value="">Tanpa Dokter</option>').prop('disabled', true).trigger('change.select2'); } catch(e) {}
        $('#rj_no_antrian').val('');
        rjFixedPasienContext = null;
        $('#rj_pasien_id').prop('disabled', false);
        resetRawatJalanMarketplaceFields();
        toggleRjCalendarPreview(false);
        applyMode('konsultasi');
    });

    // Default mode
    applyMode('konsultasi');
});
</script>
@endpush
