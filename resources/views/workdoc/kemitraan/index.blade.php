@extends('layouts.workdoc.app')

@section('title', 'Kemitraan')

@section('navbar')
    @include('layouts.workdoc.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Kemitraan</h4>
                    <div class="mb-3 d-flex align-items-center">
                        <button id="btn-add" class="btn btn-primary mr-3">Tambah Kemitraan</button>
                        <label class="mr-2 mb-0">Filter Kategori:</label>
                        <select id="filter-category" class="form-control mr-3" style="width:220px">
                            <option value="">Semua Kategori</option>
                            <option value="asuransi">Asuransi</option>
                            <option value="operasional">Operasional</option>
                            <option value="marketing">Marketing</option>
                        </select>

                        <label class="mr-2 mb-0">Filter Instansi:</label>
                        <select id="filter-instansi" class="form-control" style="width:200px">
                            <option value="">Semua Instansi</option>
                            <option value="Premiere Belova">Premiere Belova</option>
                            <option value="Belova Skincare">Belova Skincare</option>
                            <option value="BCL">BCL</option>
                        </select>
                    </div>

                    <table id="kemitraan-table" class="table table-striped table-bordered" style="width:100%">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Mitra</th>
                                <th>Instansi</th>
                                <th>Perihal</th>
                                <th style="display:none">End Date</th>
                                <th>Periode</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="kemitraanModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Kemitraan</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
            <form id="kemitraan-form" enctype="multipart/form-data">
      <div class="modal-body">
            <input type="hidden" name="id" id="kemitraan-id">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Nama Mitra</label>
                    <input type="text" name="partner_name" id="partner_name" class="form-control" required style="text-transform:uppercase">
                </div>
                <div class="form-group col-md-6">
                    <label>Kategori</label>
                    <select name="category" id="category" class="form-control">
                        <option value="">-- Pilih kategori --</option>
                        <option value="asuransi">Asuransi</option>
                        <option value="operasional">Operasional</option>
                        <option value="marketing">Marketing</option>
                    </select>
                </div>
            </div>

            <div class="form-row mt-2">
                <div class="form-group col-md-6">
                    <label>Status</label>
                    <select name="status" id="status" class="form-control">
                        <option value="on going" selected>On Going</option>
                        <option value="terminated">Terminated</option>
                        <option value="posponed">Posponed</option>
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label>Instansi</label>
                    <select name="instansi" id="instansi" class="form-control">
                        <option value="">-- Pilih instansi --</option>
                        <option value="Premiere Belova">Premiere Belova</option>
                        <option value="Belova Skincare">Belova Skincare</option>
                        <option value="BCL">BCL</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Perihal</label>
                <textarea name="perihal" id="perihal" class="form-control" rows="4" placeholder="Deskripsi perihal..."></textarea>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Periode Mulai</label>
                    <input type="date" name="start_date" id="start_date" class="form-control">
                </div>
                <div class="form-group col-md-6">
                    <label>Periode Selesai</label>
                    <input type="date" name="end_date" id="end_date" class="form-control">
                </div>
            </div>
            
            <!-- notes removed as requested -->
            <div class="form-group">
                <label>Dokumen (PDF, max 25MB)</label>
                <div class="custom-file">
                    <input type="file" name="dokumen_pks" id="dokumen_pks" class="custom-file-input" accept="application/pdf">
                    <label class="custom-file-label" for="dokumen_pks">Pilih file</label>
                </div>
            </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save</button>
      </div>
      </form>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<style>
    .blink-warning{
        display:inline-block;
        margin-left:8px;
        color:#856404;
        background:#fff3cd;
        padding:2px 6px;
        border-radius:4px;
        font-weight:600;
        animation: blink-warning 1.6s ease-in-out infinite;
    }
    .blink-danger{
        display:inline-block;
        margin-left:8px;
        color:#721c24;
        background:#f8d7da;
        padding:2px 6px;
        border-radius:4px;
        font-weight:700;
        animation: blink-danger 1.2s ease-in-out infinite;
    }
    @keyframes blink-warning{0%{opacity:1;transform:scale(1)}50%{opacity:0.35;transform:scale(0.98)}100%{opacity:1;transform:scale(1)}}
    @keyframes blink-danger{0%{opacity:1;transform:scale(1)}50%{opacity:0.25;transform:scale(0.97)}100%{opacity:1;transform:scale(1)}}
    /* Instansi badge colors */
    .badge-pink{
        background:#e83e8c;
        color:#fff;
    }
    .badge-orange{
        background:#fd7e14;
        color:#fff;
    }
</style>
<script>
    $(function(){
            const table = $('#kemitraan-table').DataTable({
                ajax: {
                    url: '{!! route('workdoc.kemitraan.data') !!}',
                    data: function(d){
                        d.category = $('#filter-category').val();
                        d.instansi = $('#filter-instansi').val();
                    }
                },
                // order by the hidden end_date column (ascending = nearest/earliest end_date first)
                order: [[3, 'asc']],
                order: [[4, 'asc']],
                // highlight rows whose end_date is today or in the past
                rowCallback: function(row, data){
                    try{
                        if(!data || !data.end_date){
                            $(row).removeClass('table-danger');
                            return;
                        }
                        const end = new Date(data.end_date + 'T00:00:00');
                        const today = new Date();
                        // normalize both to midnight for date-only comparison
                        end.setHours(0,0,0,0);
                        today.setHours(0,0,0,0);
                        if(end.getTime() <= today.getTime()){
                            $(row).addClass('table-danger');
                        } else {
                            $(row).removeClass('table-danger');
                        }
                    }catch(e){
                        // on parse error, ensure row isn't marked
                        $(row).removeClass('table-danger');
                    }
                },
                columns: [
                    { data: null, render: function(data,type,row,meta){ return meta.row + meta.settings._iDisplayStart + 1; } },
                    { data: null, render: function(data,type,row){
                        // Nama Mitra + kategori badge
                        const name = (row.partner_name || '').toUpperCase();
                        const cat = row.category || '';
                        const map = {'asuransi':'badge-success','operasional':'badge-warning','marketing':'badge-info'};
                        const labelMap = {'asuransi':'Asuransi','operasional':'Operasional','marketing':'Marketing'};
                        const cls = map[cat] || 'badge-secondary';
                        const labelText = labelMap[cat] || (cat ? (cat.charAt(0).toUpperCase()+cat.slice(1)) : '');
                        const label = cat ? `<div class="mt-1"><span class="badge ${cls}">${labelText}</span></div>` : '';
                        return `<div>${name}${label}</div>`;
                    } },
                    { data: 'instansi', render: function(data){
                        if(!data) return '';
                        const key = (data || '').toString().toLowerCase();
                        const map = {
                            'premiere belova': 'badge-primary',
                            'belova skincare': 'badge-pink',
                            'bcl': 'badge-orange'
                        };
                        const cls = map[key] || 'badge-secondary';
                        return `<div><span class="badge ${cls}">${data}</span></div>`;
                    } },
                    { data: 'perihal' },
                    // hidden raw end_date for correct ordering
                    { data: 'end_date', visible: false },
                    { data: null, render: function(data,type,row){
                        function formatDateIndo(d){
                            if(!d) return '';
                            const parts = d.split('-');
                            if(parts.length < 3) return d;
                            const y = parts[0];
                            const m = parseInt(parts[1],10);
                            const day = parseInt(parts[2],10);
                            const months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                            return `${day} ${months[m-1]} ${y}`;
                        }
                        const s = formatDateIndo(row.start_date);
                        const e = formatDateIndo(row.end_date);
                        // determine warning/danger based on remaining time until end_date
                        let marker = '';
                        if(row.end_date){
                            const end = new Date(row.end_date + 'T23:59:59');
                            const now = new Date();
                            const diffMs = end - now;
                            const diffDays = Math.ceil(diffMs / (1000*60*60*24));
                            if(diffDays <= 0){
                                // already passed or today -> danger
                                marker = `<span class="blink-danger" title="Periode selesai pada ${e}">!</span>`;
                            } else if(diffDays < 30){
                                // less than 1 month
                                marker = `<span class="blink-danger" title="Periode selesai pada ${e} (tersisa ${diffDays} hari)">!</span>`;
                            } else if(diffDays < 183){
                                // less than ~6 months (approx 183 days)
                                marker = `<span class="blink-warning" title="Periode selesai pada ${e} (tersisa ${diffDays} hari)">⚠</span>`;
                            }
                        }
                        if(s && e) return `${s} s/d ${e} ${marker}`;
                        return (s || e || '') + ` ${marker}`;
                    } },
                    { data: 'status', render: function(data){
                        if(!data) return '';
                        const map = {'on going':'badge-success','terminated':'badge-danger','posponed':'badge-warning'};
                        const labelMap = {'on going':'On Going','terminated':'Terminated','posponed':'Posponed'};
                        const cls = map[data] || 'badge-secondary';
                        const txt = labelMap[data] || data;
                        return `<span class="badge ${cls}">${txt}</span>`;
                    } },
                    { data: null, orderable: false, searchable: false, render: function (data,type,row) {
                        const viewBtn = row.dokumen_pks ? ` <a class="btn btn-sm btn-primary" href="{{ asset('storage') }}/${row.dokumen_pks}" target="_blank">PKS</a> ` : '';
                        const editBtn = ` <button class="btn btn-sm btn-info btn-edit" data-id="${row.id}">Edit</button> `;
                        const delBtn = ` <button class="btn btn-sm btn-danger btn-delete" data-id="${row.id}">Delete</button>`;
                        return `<div class="btn-group" role="group">${viewBtn}${editBtn}${delBtn}</div>`;
                    }}
                ]
            });

        $('#btn-add').on('click', function(){
            $('#kemitraan-form')[0].reset();
            $('#kemitraan-id').val('');
            // ensure default status when adding
            $('#status').val('on going');
            // reset instansi
            $('#instansi').val('');
            // reset custom file label
            $('#dokumen_pks').next('.custom-file-label').html('Pilih file');
            $('#kemitraanModal').modal('show');
        });

        // reload table when category filter changes
        $(document).on('change', '#filter-category, #filter-instansi', function(){
            table.ajax.reload();
        });

        $('#kemitraan-table').on('click', '.btn-edit', function(){
            const id = $(this).data('id');
            $.get(`{!! url('workdoc/kemitraan') !!}/${id}`, function(res){
                const d = res.item;
                $('#kemitraan-id').val(d.id);
                $('#partner_name').val(d.partner_name);
                $('#category').val(d.category);
                $('#instansi').val(d.instansi);
                $('#perihal').val(d.perihal);
                $('#start_date').val(d.start_date);
                $('#end_date').val(d.end_date);
                $('#status').val(d.status);
                // set custom file label to existing filename if present
                if(d.dokumen_pks){
                    const fname = d.dokumen_pks.split('/').pop();
                    $('#dokumen_pks').next('.custom-file-label').html(fname);
                } else {
                    $('#dokumen_pks').next('.custom-file-label').html('Pilih file');
                }
                $('#kemitraanModal').modal('show');
            });
        });

        // Force uppercase on Nama Mitra input while typing and ensure when populating
        $(document).on('input', '#partner_name', function(){
            const pos = this.selectionStart;
            this.value = this.value.toUpperCase();
            this.setSelectionRange(pos, pos);
        });

        $('#kemitraan-form').on('submit', function(e){
            e.preventDefault();
            const id = $('#kemitraan-id').val();
            const url = id ? `{!! url('workdoc/kemitraan') !!}/${id}` : `{!! route('workdoc.kemitraan.store') !!}`;

            // client-side file size guard: 25 MB
            const maxBytes = 25 * 1024 * 1024;
            const fileInputEl = document.getElementById('dokumen_pks');
            if(fileInputEl && fileInputEl.files && fileInputEl.files.length){
                const f = fileInputEl.files[0];
                if(f.size > maxBytes){
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'File terlalu besar. Maksimum 25 MB.'
                    });
                    return;
                }
            }

            const form = document.getElementById('kemitraan-form');
            const formData = new FormData(form);
            if(id){
                formData.append('_method','PUT');
            }

            $.ajax({
                url: url,
                method: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(){
                    const message = id ? 'Data berhasil diperbarui' : 'Data berhasil disimpan';
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    $('#kemitraanModal').modal('hide');
                    table.ajax.reload();
                },
                error: function(xhr){
                    const msg = xhr.responseJSON?.message || 'Validation failed';
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: msg
                    });
                }
            });
        });

        $('#kemitraan-table').on('click', '.btn-delete', function(){
            if(!confirm('Delete this item?')) return;
            const id = $(this).data('id');
            $.ajax({
                url: `{!! url('workdoc/kemitraan') !!}/${id}`,
                method: 'DELETE',
                success: function(){
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: 'Data berhasil dihapus',
                        timer: 1200,
                        showConfirmButton: false
                    });
                    table.ajax.reload();
                },
                error: function(xhr){
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: xhr.responseJSON?.message || 'Gagal menghapus data'
                    });
                }
            });
        });
        // show selected filename for bootstrap custom-file and check size (25 MB)
        $(document).on('change', '#dokumen_pks', function(){
            const input = this;
            const maxBytes = 25 * 1024 * 1024;
            const file = input.files && input.files[0] ? input.files[0] : null;
            if(file && file.size > maxBytes){
                Swal.fire({
                    icon: 'error',
                    title: 'File terlalu besar',
                    text: 'Dokumen PKS maksimal 25 MB. Silakan pilih file lain.'
                });
                input.value = '';
                $(input).next('.custom-file-label').html('Pilih file');
                return;
            }
            const fileName = $(this).val().split('\\').pop();
            $(this).next('.custom-file-label').html(fileName || 'Pilih file');
        });
    });
</script>
@endsection
