@extends('layouts.erm.app')

@section('navbar')
    @include('layouts.satusehat.navbar')
@endsection

@section('title', 'Obat - KFA Mapping')

@section('content')
    <div class="container-fluid">
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Obat - KFA Mapping</h4>
                        <p class="card-text">List of active obat. Edit KFA code inline.</p>

                        <div class="table-responsive">
                            <div class="form-row mb-3">
                                <div class="form-group col-md-3">
                                    <label for="filterMetode">Metode Bayar</label>
                                    <select id="filterMetode" class="form-control">
                                        <option value="">-- Semua --</option>
                                        @foreach($metodeBayarList as $m)
                                            <option value="{{ $m->id }}" {{ strtolower($m->nama) === 'umum' ? 'selected' : '' }}>{{ $m->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="filterKategori">Kategori</label>
                                    <select id="filterKategori" class="form-control">
                                        <option value="">-- Semua --</option>
                                        @foreach($kategoriList as $k)
                                            <option value="{{ $k }}" {{ strtolower($k) === 'obat' ? 'selected' : '' }}>{{ $k }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="filterHasKfa">KFA</label>
                                    <select id="filterHasKfa" class="form-control">
                                        <option value="">-- Semua --</option>
                                        <option value="with">Dengan KFA</option>
                                        <option value="without">Tanpa KFA</option>
                                    </select>
                                </div>
                            </div>
                            <table id="obatKfaTable" class="table table-striped table-bordered" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Nama</th>
                                        <th>Metode Bayar</th>
                                        <th>Kategori</th>
                                        <th>KFA Code</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(function(){
    // CSRF for AJAX
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

        var table = $('#obatKfaTable').DataTable({
        processing: true,
        serverSide: true,
            ajax: {
                url: '{!! route("erm.obat_kfa.data") !!}',
                type: 'POST',
                data: function(d){
                    // read values explicitly and send as strings; fallback to empty string
                    var mb = $('#filterMetode').val();
                    var kat = $('#filterKategori').val();
                        var hasKfa = $('#filterHasKfa').val();
                    // debug in browser console (include has_kfa)
                    var hasKfa = $('#filterHasKfa').val();
                    if(window.console && console.log) console.log('ObatKfa filters sending', {metode_bayar: mb, kategori: kat, has_kfa: hasKfa});
                    d.metode_bayar = mb === null ? '' : mb;
                    d.kategori = kat === null ? '' : kat;
                    d.has_kfa = hasKfa === null ? '' : hasKfa;
                }
            },
        pageLength: 100,
        lengthMenu: [[10,25,50,100], [10,25,50,100]],
        columns: [
            { data: 'id', name: 'id' },
            { data: 'nama', name: 'nama' },
            { data: 'metode_bayar', name: 'metode_bayar', orderable: false, searchable: false },
            { data: 'kategori', name: 'kategori', orderable: false, searchable: false },
            { data: 'kfa', name: 'kfa', orderable: false, searchable: false, render: function(data, type, row){
                const val = data || '';
                // input with inline status indicator (spinner / check / x)
                return `
                    <div class="input-group">
                        <input type="text" class="form-control kfa-input" data-obat-id="${row.id}" value="${val}">
                        <div class="input-group-append">
                            <span class="input-group-text kfa-status" data-obat-id="${row.id}"></span>
                        </div>
                    </div>`;
            }}
        ],
        order: [[0,'asc']],
        drawCallback: function(){
            // bind change handler for KFA inputs
            $('.kfa-input').off('change').on('change', function(){
                var $input = $(this);
                var obatId = $input.data('obat-id');
                var kfa = $input.val();
                var $status = $input.closest('.input-group').find('.kfa-status');

                // show spinner, disable input
                $input.prop('disabled', true).removeClass('is-invalid is-valid');
                $status.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');

                $.ajax({
                    url: '{!! route("erm.obat_kfa.store") !!}',
                    method: 'POST',
                    data: { obat_id: obatId, kfa_code: kfa },
                    success: function(resp){
                        if(resp.ok){
                            $status.html('<i class="fa fa-check text-success"></i>');
                            $input.addClass('is-valid');
                            if(window.toastr) toastr.success('KFA code saved');
                        } else {
                            $status.html('<i class="fa fa-times text-danger"></i>');
                            $input.addClass('is-invalid');
                            if(window.toastr) toastr.error('Failed to save KFA code');
                        }
                    },
                    error: function(xhr){
                        $status.html('<i class="fa fa-times text-danger"></i>');
                        $input.addClass('is-invalid');
                        if(window.toastr) toastr.error('Error saving KFA code');
                    },
                    complete: function(){
                        // re-enable and clear status after short delay
                        $input.prop('disabled', false);
                        setTimeout(function(){
                            $status.fadeOut(200, function(){ $(this).empty().show(); });
                            $input.removeClass('is-valid');
                        }, 1500);
                    }
                });
            });
        }
    });

    // reload table on filter change (outside drawCallback)
    $('#filterMetode, #filterKategori, #filterHasKfa').off('change').on('change', function(){
        table.ajax.reload();
    });
});
</script>
@endpush
