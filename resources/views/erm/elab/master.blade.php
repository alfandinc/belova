@extends('layouts.erm.app')
@section('title','ERM | Master Lab Test')
@section('navbar')
    @include('layouts.erm.navbar-lab')
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">ERM</a></li>
                            <li class="breadcrumb-item">Laboratorium</li>
                            <li class="breadcrumb-item active">Master</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Lab Tests</h5>
                    <button class="btn btn-sm btn-primary" id="btn-add-test">Tambah Lab Test</button>
                </div>
                <div class="card-body">
                    <table class="table table-bordered w-100" id="labtests-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nama</th>
                                <th>Kategori</th>
                                <th>Harga</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-5">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Kategori</h5>
                    <button class="btn btn-sm btn-secondary" id="btn-add-kategori">Tambah Kategori</button>
                </div>
                <div class="card-body">
                    <table class="table table-bordered w-100" id="labkategories-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nama</th>
                                <th>Jumlah Test</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Lab Test -->
<div class="modal fade" id="modalLabTest" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="labTestModalTitle">Tambah Lab Test</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form id="form-lab-test">
        <div class="modal-body">
            <input type="hidden" id="lab_test_id">
            <div class="form-group">
                <label>Nama</label>
                <input type="text" class="form-control" id="lab_nama" required>
                <div class="invalid-feedback" id="err_lab_nama"></div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-8">
                    <label>Kategori</label>
                    <select id="lab_kategori_id" class="form-control select2" style="width:100%" required></select>
                    <div class="invalid-feedback" id="err_lab_kategori_id"></div>
                </div>
                <div class="form-group col-md-4">
                    <label>Harga</label>
                    <input type="number" min="0" class="form-control" id="lab_harga">
                    <div class="invalid-feedback" id="err_lab_harga"></div>
                </div>
            </div>
            <hr>
            <div class="form-group">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="mb-0">Obat yang dipakai</label>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-obat">Tambah Obat</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm" id="lab-obat-table">
                        <thead>
                            <tr>
                                <th style="width:60%">Obat</th>
                                <th style="width:20%">Dosis</th>
                                <th style="width:15%">Satuan</th>
                                <th style="width:5%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="lab-obat-list"></tbody>
                    </table>
                </div>
                <small class="form-text text-muted">Pilih obat dan masukkan dosis (mis. 0.50). Satuan diambil dari data obat.</small>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-dismiss="modal">Tutup</button>
          <button type="submit" class="btn btn-primary" id="btn-save-labtest">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Kategori -->
<div class="modal fade" id="modalKategori" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="kategoriModalTitle">Tambah Kategori</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form id="form-kategori">
        <div class="modal-body">
            <input type="hidden" id="kategori_id">
            <div class="form-group">
                <label>Nama</label>
                <input type="text" class="form-control" id="kategori_nama" required>
                <div class="invalid-feedback" id="err_kategori_nama"></div>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-dismiss="modal">Tutup</button>
          <button type="submit" class="btn btn-primary" id="btn-save-kategori">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
$(function(){
    let csrf = $('meta[name="csrf-token"]').attr('content');

    // --- DataTables Init ---
    let testTable = $('#labtests-table').DataTable({
        processing:true, serverSide:true, responsive:true,
        ajax: '{!! route('erm.labtests.data') !!}',
        columns:[
            {data:'DT_RowIndex', orderable:false, searchable:false},
            {data:'nama'},
            {data:'kategori', name:'labKategori.nama'},
            {data:'harga', render:function(d){ if(!d) return '0'; return Number(d).toLocaleString('id-ID'); }},
            {data:'actions', orderable:false, searchable:false}
        ]
    });

    let kategoriTable = $('#labkategories-table').DataTable({
        processing:true, serverSide:true, responsive:true,
        ajax: '{!! route('erm.labkategories.data') !!}',
        columns:[
            {data:'DT_RowIndex', orderable:false, searchable:false},
            {data:'nama'},
            {data:'lab_tests_count', name:'lab_tests_count', orderable:false, searchable:false},
            {data:'actions', orderable:false, searchable:false}
        ]
    });

    // Populate kategori select2
    function loadKategoriSelect(selected=null){
        $.getJSON('{!! route('erm.labkategories.data') !!}?length=1000', function(resp){
            let select = $('#lab_kategori_id');
            select.empty();
            if(resp.data){
                resp.data.forEach(r=>{
                    select.append(`<option value="${r.id}">${r.nama}</option>`);
                });
            }
            if(selected) select.val(selected);
        });
    }

    // Reset helpers
    function resetLabTestForm(){
        $('#lab_test_id').val('');
        $('#lab_nama').val('');
        $('#lab_kategori_id').val('').trigger('change');
        $('#lab_harga').val('');
        $('#lab-obat-list').empty();
        clearErrors('#form-lab-test');
        $('#labTestModalTitle').text('Tambah Lab Test');
    }
    function resetKategoriForm(){
        $('#kategori_id').val('');
        $('#kategori_nama').val('');
        clearErrors('#form-kategori');
        $('#kategoriModalTitle').text('Tambah Kategori');
    }
    function clearErrors(form){
        $(form+' .is-invalid').removeClass('is-invalid');
        $(form+' .invalid-feedback').text('');
    }
    function showErrors(form, errors){
        for(let k in errors){
            let field = errors[k][0];
            if(k === 'nama' && form === '#form-lab-test') k = 'lab_'+k; // adapt naming
            let input = $(form+' [id$="'+k+'"]');
            input.addClass('is-invalid');
            $('#err_'+k).text(errors[k][0]);
        }
    }

    // Open modals
    $('#btn-add-test').on('click', function(){
        resetLabTestForm();
        loadKategoriSelect();
        $('#modalLabTest').modal('show');
    });
    // Add obat row
    function initObatSelect($el){
        $el.select2({
            placeholder: 'Cari obat...',
            allowClear: true,
            width: '100%',
            ajax: {
                url: '{!! route('obat.search') !!}',
                dataType: 'json',
                delay: 250,
                data: function(params){ return { q: params.term }; },
                processResults: function(data){ return { results: data.results || [] }; }
            }
        });

        // when an obat is selected, update the satuan cell for the row
        $el.on('select2:select', function(e){
            let d = e.params.data || {};
            let satuan = d.satuan ? String(d.satuan).toLowerCase() : '';
            $(this).closest('tr').find('.obat-satuan').text(satuan);
        });
        $el.on('select2:clear', function(){ $(this).closest('tr').find('.obat-satuan').text(''); });
    }

    function addObatRow(data = {}){
        let id = Date.now();
        let html = `
            <tr class="obat-row" data-row="${id}">
                <td><select class="form-control obat-select"></select></td>
                <td><input type="number" step="0.01" min="0" class="form-control obat-dosis" placeholder="Dosis" value="${data.dosis ?? ''}"></td>
                <td class="obat-satuan">${data.satuan ? data.satuan : ''}</td>
                <td><button class="btn btn-sm btn-danger btn-remove-obat" type="button">&times;</button></td>
            </tr>`;
        let $row = $(html);
        $('#lab-obat-list').append($row);
        let $select = $row.find('.obat-select');
        initObatSelect($select);
        if(data.obat_id){
            // create and select option with additional data
            let option = new Option(data.nama || data.text || data.obat_id, data.obat_id, true, true);
            // attach extra properties so select2 selection has them (satuan in lowercase)
            let satuanVal = data.satuan ? String(data.satuan).toLowerCase() : '';
            $(option).data('data', { id: data.obat_id, text: data.nama || data.text, nama: data.nama || data.text, satuan: satuanVal });
            $select.append(option).trigger('change');
            $row.find('.obat-satuan').text(satuanVal);
        }
    }

    $(document).on('click', '#btn-add-obat', function(){ addObatRow(); });
    $(document).on('click', '.btn-remove-obat', function(){ $(this).closest('.obat-row').remove(); });
    $('#btn-add-kategori').on('click', function(){
        resetKategoriForm();
        $('#modalKategori').modal('show');
    });

    // Edit actions
    $(document).on('click','.edit-test', function(){
        resetLabTestForm();
        let id = $(this).data('id');
        $('#lab_test_id').val(id);
        // fetch full record including obats, then load kategori options with selected value
        $.getJSON(`/erm/lab-tests/${id}`, function(resp){
            $('#lab_nama').val(resp.nama);
            $('#lab_harga').val(resp.harga);
            // ensure kategori options are loaded before selecting
            loadKategoriSelect(resp.lab_kategori_id || null);
            // populate obat rows
            $('#lab-obat-list').empty();
            if(resp.obats && resp.obats.length){
                resp.obats.forEach(function(o){ addObatRow({obat_id: o.id, nama: o.nama, dosis: o.pivot?.dosis ?? '', satuan: o.satuan}); });
            }
            $('#labTestModalTitle').text('Edit Lab Test');
            $('#modalLabTest').modal('show');
        }).fail(function(){ alert('Gagal mengambil data'); });
    });
    $(document).on('click','.edit-kategori', function(){
        resetKategoriForm();
        $('#kategori_id').val($(this).data('id'));
        $('#kategori_nama').val($(this).data('nama'));
        $('#kategoriModalTitle').text('Edit Kategori');
        $('#modalKategori').modal('show');
    });

    // Delete actions
    $(document).on('click','.delete-test', function(){
        if(!confirm('Hapus lab test ini?')) return;
        let id = $(this).data('id');
        $.ajax({
            url: `/erm/lab-tests/${id}`,
            type:'DELETE',
            headers:{'X-CSRF-TOKEN':csrf},
            success: res=>{ testTable.ajax.reload(null,false); },
            error: xhr=>{ alert(xhr.responseJSON?.message || 'Error'); }
        });
    });
    $(document).on('click','.delete-kategori', function(){
        if(!confirm('Hapus kategori ini?')) return;
        let id = $(this).data('id');
        $.ajax({
            url: `/erm/lab-kategories/${id}`,
            type:'DELETE',
            headers:{'X-CSRF-TOKEN':csrf},
            success: res=>{ kategoriTable.ajax.reload(null,false); testTable.ajax.reload(); },
            error: xhr=>{ alert(xhr.responseJSON?.message || 'Error'); }
        });
    });

    // Submit lab test
    $('#form-lab-test').on('submit', function(e){
        e.preventDefault();
        clearErrors('#form-lab-test');
        let id = $('#lab_test_id').val();
        let method = id ? 'PUT' : 'POST';
        let url = id ? `/erm/lab-tests/${id}` : `/erm/lab-tests`;
        // build obat payload
        let obatPayload = [];
        $('#lab-obat-list .obat-row').each(function(){
            let obatId = $(this).find('.obat-select').val();
            let dosis = $(this).find('.obat-dosis').val();
            if(obatId){ obatPayload.push({ obat_id: obatId, dosis: dosis || 0 }); }
        });

        $.ajax({
            url, type: method,
            headers:{'X-CSRF-TOKEN':csrf},
            data:{
                nama: $('#lab_nama').val(),
                lab_kategori_id: $('#lab_kategori_id').val(),
                harga: $('#lab_harga').val(),
                obat: obatPayload
            },
            success: res=>{
                $('#modalLabTest').modal('hide');
                testTable.ajax.reload();
                kategoriTable.ajax.reload(null,false);
            },
            error: xhr=>{
                if(xhr.status === 422){
                    showErrors('#form-lab-test', xhr.responseJSON.errors || {nama:[xhr.responseJSON.message]});
                } else { alert(xhr.responseJSON?.message || 'Error'); }
            }
        });
    });

    // Submit kategori
    $('#form-kategori').on('submit', function(e){
        e.preventDefault();
        clearErrors('#form-kategori');
        let id = $('#kategori_id').val();
        let method = id ? 'PUT' : 'POST';
        let url = id ? `/erm/lab-kategories/${id}` : `/erm/lab-kategories`;
        $.ajax({
            url, type: method,
            headers:{'X-CSRF-TOKEN':csrf},
            data:{ nama: $('#kategori_nama').val() },
            success: res=>{
                $('#modalKategori').modal('hide');
                kategoriTable.ajax.reload();
                testTable.ajax.reload(null,false);
            },
            error: xhr=>{
                if(xhr.status === 422){
                    showErrors('#form-kategori', xhr.responseJSON.errors || {nama:[xhr.responseJSON.message]});
                } else { alert(xhr.responseJSON?.message || 'Error'); }
            }
        });
    });

});
</script>
@endsection