@extends('layouts.erm.app')

@section('navbar')
    @include('layouts.satusehat.navbar')
@endsection

@section('title', 'Mapping Dokter')

@section('content')
    <div class="container-fluid">
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Mapping Dokter</h4>
                        <p class="card-text">List of dokter. Edit mapping code inline.</p>

                        <div class="table-responsive">
                            <div class="form-row mb-3">
                                <div class="form-group col-md-4">
                                    <label for="filterKlinik">Klinik</label>
                                    <select id="filterKlinik" class="form-control">
                                        <option value="">-- Semua --</option>
                                        @foreach($klinikList as $k)
                                            <option value="{{ $k->id }}">{{ $k->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <table id="dokterMappingTable" class="table table-striped table-bordered" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Nama</th>
                                        <th>NIK</th>
                                        <th>Spesialisasi</th>
                                        <th>Klinik</th>
                                        <th>Mapping Code</th>
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
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

        var table = $('#dokterMappingTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{!! route("satusehat.dokter_mapping.data") !!}',
            type: 'POST'
                ,
                data: function(d){
                    d.klinik = $('#filterKlinik').val() || '';
                }
        },
        pageLength: 100,
            columns: [
            { data: 'id', name: 'id' },
            { data: 'nama', name: 'nama' },
            { data: 'nik', name: 'nik' },
            { data: 'spesialisasi', name: 'spesialisasi', orderable: false, searchable: false },
            { data: 'klinik', name: 'klinik', orderable: false, searchable: false },
            { data: 'mapping', name: 'mapping', orderable: false, searchable: false, render: function(data, type, row){
                const val = data || '';
                return `
                    <div class="input-group">
                        <input type="text" class="form-control mapping-input" data-dokter-id="${row.id}" value="${val}">
                        <div class="input-group-append">
                            <span class="input-group-text mapping-status" data-dokter-id="${row.id}"></span>
                        </div>
                    </div>`;
            }}
        ],
        order: [[1,'asc']],
        drawCallback: function(){
            $('.mapping-input').off('change').on('change', function(){
                var $input = $(this);
                var dokterId = $input.data('dokter-id');
                var code = $input.val();
                var $status = $input.closest('.input-group').find('.mapping-status');

                $input.prop('disabled', true).removeClass('is-invalid is-valid');
                $status.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');

                $.ajax({
                    url: '{!! route("satusehat.dokter_mapping.store") !!}',
                    method: 'POST',
                    data: { dokter_id: dokterId, mapping_code: code },
                    success: function(resp){
                        if(resp.ok){
                            $status.html('<i class="fa fa-check text-success"></i>');
                            $input.addClass('is-valid');
                        } else {
                            $status.html('<i class="fa fa-times text-danger"></i>');
                            $input.addClass('is-invalid');
                        }
                    },
                    error: function(){
                        $status.html('<i class="fa fa-times text-danger"></i>');
                        $input.addClass('is-invalid');
                    },
                    complete: function(){
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

    // reload table on klinik filter change
    $('#filterKlinik').off('change').on('change', function(){
        table.ajax.reload();
    });
});
</script>
@endpush
