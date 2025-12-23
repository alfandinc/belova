@extends('layouts.marketing.app')

@section('title', 'Kunjungan Marketing')

@section('navbar')
    @include('layouts.marketing.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Kunjungan Marketing</h4>
                    <style>
                        .badge-pink{ background:#e83e8c; color:#fff; }
                        .badge-orange{ background:#fd7e14; color:#fff; }
                    </style>
                    <div class="mb-3 d-flex align-items-center">
                        <button id="btn-add" class="btn btn-primary mr-3">Tambah Kunjungan</button>
                    </div>

                    <table id="kunjungan-table" class="table table-striped table-bordered" style="width:100%">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Instansi Tujuan</th>
                                <th>Status</th>
                                <th>Hasil</th>
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
<div class="modal fade" id="kunjunganModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Kunjungan Marketing</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form id="kunjungan-form" enctype="multipart/form-data">
      <div class="modal-body">
            <input type="hidden" name="id" id="kunjungan-id">
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Instansi</label>
                    <select name="instansi" id="instansi" class="form-control">
                        <option value="">-- Pilih instansi --</option>
                        <option value="Premiere Belova">Premiere Belova</option>
                        <option value="Belova Skincare">Belova Skincare</option>
                        <option value="BCL">BCL</option>
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label>Instansi Tujuan</label>
                    <input type="text" name="instansi_tujuan" id="instansi_tujuan" class="form-control">
                </div>
                <div class="form-group col-md-4">
                    <label>Tanggal Kunjungan</label>
                    <input type="date" name="tanggal_kunjungan" id="tanggal_kunjungan" class="form-control">
                </div>
            </div>

            <div class="form-row mt-2">
                <div class="form-group col-md-6">
                    <label>PIC</label>
                    <input type="text" name="pic" id="pic" class="form-control">
                </div>
                <div class="form-group col-md-6">
                    <label>No HP</label>
                    <input type="text" name="no_hp" id="no_hp" class="form-control">
                </div>
            </div>

            <div class="form-row mt-2">
                <div class="form-group col-md-6">
                    <label>Status</label>
                    <select name="status" id="status" class="form-control">
                        <option value="Planned">Planned</option>
                        <option value="On Going">On Going</option>
                        <option value="Done">Done</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label>Bukti Kunjungan (image)</label>
                    <div class="custom-file">
                        <input type="file" name="bukti_kunjungan" id="bukti_kunjungan" class="custom-file-input" accept="image/*">
                        <label class="custom-file-label" for="bukti_kunjungan">Pilih file</label>
                    </div>
                </div>
            </div>
            <div class="form-group mt-2">
                <label>Hasil Kunjungan</label>
                <textarea name="hasil_kunjungan" id="hasil_kunjungan" class="form-control" rows="4"></textarea>
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
<script>
    $(function(){
        const table = $('#kunjungan-table').DataTable({
            ajax: {
                url: '{!! url('marketing/kunjungan/data') !!}',
            },
            order: [[0,'asc']],
            columns: [
                { data: null, render: function(data,type,row,meta){ return meta.row + meta.settings._iDisplayStart + 1; } },
                { data: 'tanggal_kunjungan', render: function(d,type,row){
                        // format date
                        let dateStr = '';
                        if(d){
                            const parts = d.split('-');
                            if(parts.length===3){
                                const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                                dateStr = parts[2] + ' ' + months[parseInt(parts[1],10)-1] + ' ' + parts[0];
                            } else {
                                dateStr = d;
                            }
                        }
                        // instansi badge below the date
                        const inst = (row.instansi || '').toString();
                        let badgeHtml = '';
                        if(inst){
                            const key = inst.toLowerCase();
                            const map = {'premiere belova':'badge-primary','belova skincare':'badge-pink','bcl':'badge-orange'};
                            const cls = map[key] || 'badge-secondary';
                            badgeHtml = `<div class="mt-1"><span class="badge ${cls}">${inst}</span></div>`;
                        }
                        return (dateStr ? `<div>${dateStr}</div>` : '') + badgeHtml;
                    }
                },
                { data: null, render: function(data,type,row){
                        // instansi tujuan plus PIC and phone shown underneath
                        const tujuan = row.instansi_tujuan ? `<div>${row.instansi_tujuan}</div>` : '';
                        const pic = row.pic ? `<div class="text-muted" style="font-size:0.85rem;margin-top:6px">${row.pic}${row.no_hp?(' — ' + row.no_hp):''}</div>` : (row.no_hp?`<div class="text-muted" style="font-size:0.85rem;margin-top:6px">${row.no_hp}</div>` : '');
                        return tujuan + pic;
                    }
                },
                { data: 'status', render: function(s){
                        if(!s) return '';
                        const key = (s||'').toString().toLowerCase();
                        const map = {
                            'planned':'badge-primary',
                            'on going':'badge-warning',
                            'ongoing':'badge-warning',
                            'done':'badge-success',
                            'cancelled':'badge-danger',
                            'canceled':'badge-danger'
                        };
                        const cls = map[key] || 'badge-secondary';
                        // display with capitalized text
                        const txt = s.charAt(0).toUpperCase() + s.slice(1);
                        return `<span class="badge ${cls}">${txt}</span>`;
                    }
                },
                { data: 'hasil_kunjungan', render: function(d){ return d ? d : ''; } },
                { data: null, orderable:false, searchable:false, render: function(data,type,row){
                    const imgBtn = row.bukti_kunjungan ? ` <a class="btn btn-sm btn-primary" href="{{ asset('storage') }}/${row.bukti_kunjungan}" target="_blank">Bukti</a> ` : '';
                    const editBtn = ` <button class="btn btn-sm btn-info btn-edit" data-id="${row.id}">Edit</button> `;
                    const delBtn = ` <button class="btn btn-sm btn-danger btn-delete" data-id="${row.id}">Delete</button>`;
                    return `<div class="btn-group" role="group">${imgBtn}${editBtn}${delBtn}</div>`;
                }}
            ]
        });

        $('#btn-add').on('click', function(){
            $('#kunjungan-form')[0].reset();
            $('#kunjungan-id').val('');
            $('#instansi').val('');
            $('#tanggal_kunjungan').val('');
            $('#bukti_kunjungan').next('.custom-file-label').html('Pilih file');
            $('#kunjunganModal').modal('show');
        });

        $('#kunjungan-table').on('click', '.btn-edit', function(){
            const id = $(this).data('id');
            $.get(`{!! url('marketing/kunjungan') !!}/${id}`, function(res){
                const d = res.item;
                $('#kunjungan-id').val(d.id);
                $('#instansi').val(d.instansi);
                $('#instansi_tujuan').val(d.instansi_tujuan);
                $('#tanggal_kunjungan').val(d.tanggal_kunjungan);
                $('#pic').val(d.pic);
                $('#no_hp').val(d.no_hp);
                $('#status').val(d.status);
                $('#hasil_kunjungan').val(d.hasil_kunjungan);
                if(d.bukti_kunjungan){
                    const fname = d.bukti_kunjungan.split('/').pop();
                    $('#bukti_kunjungan').next('.custom-file-label').html(fname);
                } else {
                    $('#bukti_kunjungan').next('.custom-file-label').html('Pilih file');
                }
                $('#kunjunganModal').modal('show');
            });
        });

        $(document).on('change', '#bukti_kunjungan', function(){
            const fileName = $(this).val().split('\\').pop();
            $(this).next('.custom-file-label').html(fileName || 'Pilih file');
        });

        $('#kunjungan-form').on('submit', function(e){
            e.preventDefault();
            const id = $('#kunjungan-id').val();
            const url = id ? `{!! url('marketing/kunjungan') !!}/${id}` : `{!! url('marketing/kunjungan') !!}`;
            const form = document.getElementById('kunjungan-form');
            const formData = new FormData(form);
            if(id) formData.append('_method','PUT');

            $.ajax({
                url: url,
                method: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(){
                    Swal.fire({icon:'success', title:'Berhasil', text: 'Data disimpan', timer:1200, showConfirmButton:false});
                    $('#kunjunganModal').modal('hide');
                    table.ajax.reload();
                },
                error: function(xhr){
                    Swal.fire({icon:'error', title:'Error', text: xhr.responseJSON?.message || 'Validation failed'});
                }
            });
        });

        $('#kunjungan-table').on('click', '.btn-delete', function(){
            if(!confirm('Delete this item?')) return;
            const id = $(this).data('id');
            $.ajax({url:`{!! url('marketing/kunjungan') !!}/${id}`, method:'DELETE', success:function(){
                Swal.fire({icon:'success', title:'Berhasil', text:'Data dihapus', timer:1000, showConfirmButton:false});
                table.ajax.reload();
            }, error:function(){ Swal.fire({icon:'error', title:'Error', text:'Gagal'}); }});
        });

    });
</script>
@endsection
