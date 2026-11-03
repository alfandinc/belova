@extends('layouts.admin.app')

@section('title', 'Akses Mutasi Stok')

@section('navbar')
    @include('layouts.admin.navbar')
@endsection

@section('content')
<style>
    .ma { --ma-line: #e2e8f0; --ma-muted: #64748b; --ma-soft: #f8fafc; }
    html.theme-dark .ma { --ma-line: #3a4160; --ma-muted: #9aa3b8; --ma-soft: #262b3c; }
    .ma-table th, .ma-table td { border-color: var(--ma-line) !important; vertical-align: middle; }
    .ma-table thead th { text-align: center; white-space: nowrap; }
    .ma-table td.ma-cell { text-align: center; width: 1%; padding: 6px 10px; }
    .ma-table .ma-role { background: var(--ma-soft); }
    .ma-table input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; }
    .ma-meta { color: var(--ma-muted); font-size: .85rem; }
    .ma-all { font-size: .75rem; color: var(--ma-muted); cursor: pointer; display: block; font-weight: 400; }
</style>

<div class="container ma">
    <div class="card shadow-sm">
        <div class="card-body">
            <h3 class="card-title mb-0">Akses Mutasi Stok</h3>
            <small class="text-muted d-block mb-3">
                Atur role mana yang boleh <strong>membuat</strong> dan <strong>meng-approve</strong> dokumen stok, per gudang.
                Admin selalu boleh semuanya. Gudang baru belum punya akses sampai dicentang di sini.
            </small>

            @unless ($migrated)
                <div class="alert alert-warning">Tabel akses belum dibuat. Jalankan <code>php artisan migrate</code> dulu. Sampai itu, aturan lama yang berlaku.</div>
            @endunless

            <ul class="nav nav-tabs mb-3" role="tablist">
                @foreach ($moduls as $m)
                    <li class="nav-item">
                        <a class="nav-link {{ $loop->first ? 'active' : '' }}" data-toggle="tab" href="#tab-{{ $m->key }}" role="tab">{{ $m->label }}</a>
                    </li>
                @endforeach
            </ul>

            <div class="tab-content">
                @foreach ($moduls as $m)
                    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="tab-{{ $m->key }}" role="tabpanel">
                        <form class="js-akses-form" data-modul="{{ $m->key }}">
                            <p class="ma-meta mb-2">
                                @if ($m->key === \App\Support\Stok\MutasiAkses::PENYESUAIAN)
                                    <strong>Buat</strong> dan <strong>Approve</strong> dicek pada gudang dokumen penyesuaian.
                                    Dokumen di bawah batas nilai (selain masuk) langsung memotong stok tanpa approval.
                                @else
                                    <strong>Buat</strong>: boleh membuat transfer bila gudang asal <em>atau</em> tujuan dicentang.
                                    <strong>Approve</strong>: dicek pada <em>gudang asal</em> (gudang yang stoknya keluar).
                                @endif
                            </p>

                            <div class="table-responsive">
                                <table class="table table-bordered table-sm ma-table">
                                    <thead>
                                        <tr>
                                            <th rowspan="2" class="text-left align-middle">Gudang</th>
                                            @foreach ($roles as $role)
                                                <th colspan="2" class="ma-role">{{ $role }}</th>
                                            @endforeach
                                        </tr>
                                        <tr>
                                            @foreach ($roles as $role)
                                                @foreach (['request' => 'Buat', 'approve' => 'Approve'] as $ability => $text)
                                                    <th class="ma-role">
                                                        {{ $text }}
                                                        <span class="ma-all js-all" data-col="{{ $role }}-{{ $ability }}" title="Centang / kosongkan semua gudang">semua</span>
                                                    </th>
                                                @endforeach
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($gudangs as $g)
                                            <tr>
                                                <td>{{ $g->nama }}</td>
                                                @foreach ($roles as $role)
                                                    @foreach (['request', 'approve'] as $ability)
                                                        <td class="ma-cell">
                                                            <input type="checkbox" name="akses[{{ $role }}][{{ $g->id }}][{{ $ability }}]" value="1"
                                                                data-col="{{ $role }}-{{ $ability }}"
                                                                @checked($m->rules[$role][$g->id][$ability] ?? false) @disabled(!$migrated)>
                                                        </td>
                                                    @endforeach
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex flex-wrap align-items-center justify-content-between">
                                <div class="custom-control custom-switch mb-2">
                                    <input type="checkbox" class="custom-control-input js-sendiri" id="sendiri-{{ $m->key }}"
                                        @checked($m->boleh_approve_sendiri) @disabled(!$migrated)>
                                    <label class="custom-control-label" for="sendiri-{{ $m->key }}">
                                        Approver boleh meng-approve dokumen buatannya sendiri
                                    </label>
                                </div>
                                <button type="submit" class="btn btn-primary mb-2" @disabled(!$migrated)>
                                    <i class="fas fa-save"></i> Simpan {{ $m->label }}
                                </button>
                            </div>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function () {
    // "semua" toggles a whole column: ticks all when any is empty, otherwise clears all
    $(document).on('click', '.js-all', function () {
        var $boxes = $(this).closest('form').find('input[data-col="' + $(this).data('col') + '"]:not(:disabled)');
        $boxes.prop('checked', $boxes.filter(':not(:checked)').length > 0);
    });

    $(document).on('submit', '.js-akses-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $form.find('button[type="submit"]').prop('disabled', true);
        var data = $form.serializeArray();
        data.push({ name: '_token', value: "{{ csrf_token() }}" });
        data.push({ name: 'modul', value: $form.data('modul') });
        data.push({ name: 'boleh_approve_sendiri', value: $form.find('.js-sendiri').is(':checked') ? 1 : 0 });

        $.post("{{ route('admin.mutasi_akses.update') }}", $.param(data))
            .done(function (res) {
                Swal.fire({ icon: 'success', title: 'Berhasil disimpan', text: res.message, timer: 1500, showConfirmButton: false });
            })
            .fail(function (xhr) {
                Swal.fire('Gagal', (xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menyimpan akses.', 'error');
            })
            .always(function () { $btn.prop('disabled', false); });
    });
});
</script>
@endsection
