@extends('layouts.admin.app')

@section('title', 'Referral Marketing')

@section('navbar')
    @include('layouts.admin.navbar')
@endsection

@section('content')
<style>
    .rm { --rm-line: #e2e8f0; --rm-muted: #64748b; --rm-soft: #f8fafc; --rm-yes: #16a34a; --rm-yes-soft: #dcfce7; --rm-no-soft: #f1f5f9; }
    html.theme-dark .rm { --rm-line: #3a4160; --rm-muted: #9aa3b8; --rm-soft: #262b3c; --rm-yes: #4ade80; --rm-yes-soft: rgba(74, 222, 128, .14); --rm-no-soft: rgba(148, 163, 184, .12); }
    .rm-type { border: 1px solid var(--rm-line); border-radius: 10px; margin-bottom: 10px; }
    .rm-type-head { display: flex; align-items: center; gap: 12px; padding: 12px 16px; flex-wrap: wrap; }
    .rm-type-name { font-weight: 600; font-size: 1rem; }
    .rm-meta { color: var(--rm-muted); font-size: .85rem; }
    .rm-grow { flex: 1 1 auto; }
    .rm-status { font-size: .78rem; font-weight: 600; border-radius: 999px; padding: 2px 10px; }
    .rm-status.is-yes { background: var(--rm-yes-soft); color: var(--rm-yes); }
    .rm-status.is-no { background: var(--rm-no-soft); color: var(--rm-muted); }
    .rm-details { border-top: 1px solid var(--rm-line); background: var(--rm-soft); padding: 8px 16px 12px; border-radius: 0 0 10px 10px; }
    .rm-detail { display: flex; align-items: center; gap: 12px; padding: 6px 0; border-bottom: 1px dashed var(--rm-line); flex-wrap: wrap; }
    .rm-detail:last-child { border-bottom: 0; }
    .rm-seg .btn { min-width: 64px; }
</style>

<div class="container rm">
    <div class="card shadow-sm">
        <div class="card-body">
            <h3 class="card-title mb-0">Referral Marketing</h3>
            <small class="text-muted d-block mb-3">
                Tentukan referral kunjungan mana yang dihitung sebagai aktivitas <strong>Marketing &amp; Sales</strong>.
                Dipakai widget dashboard "Omset Marketing &amp; Sales". Aturan per tipe berlaku untuk semua detailnya;
                detail bisa diatur sendiri (mis. Social Media → WhatsApp tidak dihitung). Angka = invoice 12 bulan terakhir.
            </small>

            @unless ($migrated)
                <div class="alert alert-warning">Tabel mapping belum dibuat. Jalankan <code>php artisan migrate</code> dulu.</div>
            @endunless

            @foreach ($types as $t)
                <div class="rm-type" data-type="{{ $t->type }}">
                    <div class="rm-type-head">
                        <div class="rm-grow">
                            <div class="rm-type-name">{{ $t->label }}</div>
                            <div class="rm-meta">{{ number_format($t->invoices, 0, ',', '.') }} invoice
                                @if ($t->has_details) · <span class="js-overrides">{{ $t->overrides }}</span> detail diatur sendiri @endif
                            </div>
                        </div>
                        <div class="btn-group btn-group-sm rm-seg" role="group" aria-label="Marketing & Sales">
                            <button type="button" class="btn js-set {{ $t->is_marketing ? 'btn-success' : 'btn-outline-success' }}" data-key="" data-value="1" @disabled(!$migrated)>Ya</button>
                            <button type="button" class="btn js-set {{ $t->is_marketing ? 'btn-outline-secondary' : 'btn-secondary' }}" data-key="" data-value="0" @disabled(!$migrated)>Tidak</button>
                        </div>
                        @if ($t->has_details && $t->details->isNotEmpty())
                            <button type="button" class="btn btn-sm btn-outline-primary js-toggle-details">
                                <i class="fas fa-list"></i> Atur detail ({{ $t->details->count() }})
                            </button>
                        @endif
                    </div>

                    @if ($t->has_details && $t->details->isNotEmpty())
                        <div class="rm-details d-none">
                            @foreach ($t->details as $d)
                                <div class="rm-detail" data-key="{{ $d->key }}" data-rule="{{ $d->rule }}">
                                    <div class="rm-grow">
                                        <div>{{ $d->label }}</div>
                                        <div class="rm-meta">{{ number_format($d->invoices, 0, ',', '.') }} invoice</div>
                                    </div>
                                    <span class="rm-status js-status {{ $d->effective ? 'is-yes' : 'is-no' }}">{{ $d->effective ? 'Dihitung' : 'Tidak dihitung' }}</span>
                                    <div class="btn-group btn-group-sm rm-seg" role="group">
                                        @foreach (['inherit' => 'Ikut tipe', '1' => 'Ya', '0' => 'Tidak'] as $value => $text)
                                            <button type="button" class="btn js-set {{ $d->rule === (string) $value ? 'btn-primary' : 'btn-outline-primary' }}"
                                                data-key="{{ $d->key }}" data-value="{{ $value }}" @disabled(!$migrated)>{{ $text }}</button>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function () {
    $(document).on('click', '.js-toggle-details', function () {
        $(this).closest('.rm-type').find('.rm-details').toggleClass('d-none');
    });

    // Detail status = its own rule, or the type's when it follows the type
    function refreshDetails($type, typeYes) {
        $type.find('.rm-detail').each(function () {
            var rule = String($(this).data('rule'));
            var yes = rule === 'inherit' ? typeYes : rule === '1';
            $(this).find('.js-status').toggleClass('is-yes', yes).toggleClass('is-no', !yes).text(yes ? 'Dihitung' : 'Tidak dihitung');
        });
    }

    $(document).on('click', '.js-set', function () {
        var $btn = $(this);
        var $type = $btn.closest('.rm-type');
        var key = String($btn.data('key'));
        var value = String($btn.data('value'));
        $btn.closest('.rm-seg').find('.btn').prop('disabled', true);

        $.post("{{ route('admin.referral_mapping.update') }}", {
            _token: "{{ csrf_token() }}", type: $type.data('type'), key: key, value: value
        }).done(function (res) {
            if (key === '') {
                var $seg = $btn.closest('.rm-seg');
                $seg.find('[data-value="1"]').toggleClass('btn-success', value === '1').toggleClass('btn-outline-success', value !== '1');
                $seg.find('[data-value="0"]').toggleClass('btn-secondary', value === '0').toggleClass('btn-outline-secondary', value !== '0');
            } else {
                var $row = $btn.closest('.rm-detail').data('rule', value);
                $row.find('.js-set').each(function () {
                    var on = String($(this).data('value')) === value;
                    $(this).toggleClass('btn-primary', on).toggleClass('btn-outline-primary', !on);
                });
                $type.find('.js-overrides').text($type.find('.rm-detail').filter(function () { return String($(this).data('rule')) !== 'inherit'; }).length);
            }
            refreshDetails($type, !!res.type_is_marketing);
            Swal.fire({ icon: 'success', title: 'Berhasil disimpan', text: res.message, timer: 1500, showConfirmButton: false });
        }).fail(function (xhr) {
            Swal.fire('Gagal', (xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menyimpan mapping.', 'error');
        }).always(function () {
            $btn.closest('.rm-seg').find('.btn').prop('disabled', false);
        });
    });
});
</script>
@endsection
