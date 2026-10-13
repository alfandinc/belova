@extends('layouts.marketing.app')

@section('title', 'Kunjungan Instansi')

@section('navbar')
    @include('layouts.marketing.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card mt-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Kunjungan Instansi</h4>
                    <button id="btn-add" class="btn btn-success"><i class="fas fa-plus mr-1"></i>Tambah Kunjungan</button>
                </div>
                <div class="card-body">
                    <style>
                        .badge-pink{ background:#e83e8c; color:#fff; }
                        .badge-orange{ background:#fd7e14; color:#fff; }
                        #kunjungan-table td { vertical-align: middle; }
                        #kunjungan-table .hasil-text { white-space: normal; min-width: 200px; max-width: 380px; }
                    </style>
                    <div class="form-row mb-3">
                        <div class="col-md-3 col-6">
                            <label for="filter-instansi" class="small font-weight-bold text-uppercase mb-1" style="opacity:.65;">Instansi</label>
                            <select id="filter-instansi" class="form-control">
                                <option value="">Semua</option>
                                <option value="Premiere Belova">Premiere Belova</option>
                                <option value="Belova Skincare">Belova Skincare</option>
                                <option value="BCL">BCL</option>
                            </select>
                        </div>
                        <div class="col-md-3 col-6">
                            <label for="filter-status" class="small font-weight-bold text-uppercase mb-1" style="opacity:.65;">Status</label>
                            <select id="filter-status" class="form-control">
                                <option value="">Semua</option>
                                <option value="Planned">Planned</option>
                                <option value="On Going">On Going</option>
                                <option value="Done">Done</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>

                    <table id="kunjungan-table" class="table table-hover table-bordered w-100">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Instansi Tujuan / PIC</th>
                                <th>Status</th>
                                <th>Hasil</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="kunjunganModal" tabindex="-1" role="dialog" aria-labelledby="kunjunganModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="kunjunganModalLabel">Tambah Kunjungan</h5>
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
                    <label>Bukti Kunjungan (gambar)</label>
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
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary" id="kunjungan-submit">Simpan</button>
      </div>
      </form>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script>
    $(function(){
        const baseUrl = @json(url('marketing/kunjungan'));
        const storageUrl = @json(asset('storage'));
        const esc = (value) => $('<div>').text(value === null || value === undefined ? '' : String(value)).html();
        const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];

        // Client-side filters (the endpoint returns every row)
        $.fn.dataTable.ext.search.push(function(settings, data, index, row){
            if (settings.nTable.id !== 'kunjungan-table') return true;
            const instansi = $('#filter-instansi').val();
            const status = $('#filter-status').val();
            if (instansi && (row.instansi || '') !== instansi) return false;
            if (status && (row.status || '').toLowerCase() !== status.toLowerCase()) return false;
            return true;
        });

        const table = $('#kunjungan-table').DataTable({
            ajax: { url: baseUrl + '/data' },
            order: [[0, 'desc']],
            language: { search: 'Search:', searchPlaceholder: 'Instansi / PIC / hasil', emptyTable: 'Belum ada kunjungan', zeroRecords: 'Kunjungan tidak ditemukan' },
            columns: [
                { data: 'tanggal_kunjungan', className: 'text-nowrap', render: function(d, type, row){
                        // sort/search on the raw date
                        if (type !== 'display') return d || '';
                        let dateStr = '<span class="text-muted">-</span>';
                        if (d) {
                            const parts = d.split('-');
                            dateStr = parts.length === 3 ? parseInt(parts[2], 10) + ' ' + months[parseInt(parts[1], 10) - 1] + ' ' + parts[0] : esc(d);
                        }
                        const inst = (row.instansi || '').toString();
                        const map = {'premiere belova':'badge-primary','belova skincare':'badge-pink','bcl':'badge-orange'};
                        const badgeHtml = inst ? `<div class="mt-1"><span class="badge ${map[inst.toLowerCase()] || 'badge-secondary'}">${esc(inst)}</span></div>` : '';
                        return `<div>${dateStr}</div>${badgeHtml}`;
                    }
                },
                { data: 'instansi_tujuan', render: function(d, type, row){
                        if (type !== 'display') return [d, row.pic, row.no_hp].filter(Boolean).join(' ');
                        const tujuan = `<div class="font-weight-bold">${d ? esc(d) : '-'}</div>`;
                        const contact = [row.pic, row.no_hp].filter(Boolean).map(esc).join(' · ');
                        return tujuan + (contact ? `<div class="small text-muted mt-1">${contact}</div>` : '');
                    }
                },
                { data: 'status', render: function(s, type){
                        if (type !== 'display') return s || '';
                        if (!s) return '<span class="text-muted">-</span>';
                        const map = {'planned':'badge-primary','on going':'badge-warning','ongoing':'badge-warning','done':'badge-success','cancelled':'badge-danger','canceled':'badge-danger'};
                        return `<span class="badge ${map[s.toLowerCase()] || 'badge-secondary'}">${esc(s.charAt(0).toUpperCase() + s.slice(1))}</span>`;
                    }
                },
                { data: 'hasil_kunjungan', className: 'hasil-text', render: function(d, type){
                        if (type !== 'display') return d || '';
                        return d ? esc(d).replace(/\n/g, '<br>') : '<span class="text-muted">-</span>';
                    }
                },
                { data: null, orderable: false, searchable: false, className: 'text-nowrap', render: function(data, type, row){
                        const bukti = row.bukti_kunjungan
                            ? `<a class="btn btn-sm btn-outline-secondary" href="${storageUrl}/${encodeURI(row.bukti_kunjungan)}" target="_blank"><i class="fas fa-image mr-1"></i>Bukti</a> `
                            : '';
                        return bukti
                            + `<button class="btn btn-sm btn-outline-primary btn-edit" data-id="${row.id}"><i class="fas fa-pen mr-1"></i>Edit</button> `
                            + `<button class="btn btn-sm btn-outline-danger btn-delete" data-id="${row.id}"><i class="fas fa-trash mr-1"></i>Hapus</button>`;
                    }
                }
            ]
        });

        $('#filter-instansi, #filter-status').on('change', function(){ table.draw(); });

        $('#btn-add').on('click', function(){
            $('#kunjungan-form')[0].reset();
            $('#kunjungan-id').val('');
            $('#bukti_kunjungan').next('.custom-file-label').text('Pilih file');
            $('#kunjunganModalLabel').text('Tambah Kunjungan');
            $('#kunjunganModal').modal('show');
        });

        $('#kunjungan-table').on('click', '.btn-edit', function(){
            const id = $(this).data('id');
            $.get(`${baseUrl}/${id}`, function(res){
                const d = res.item;
                $('#kunjungan-form')[0].reset();
                $('#kunjungan-id').val(d.id);
                $('#instansi').val(d.instansi || '');
                $('#instansi_tujuan').val(d.instansi_tujuan);
                // date input needs YYYY-MM-DD
                $('#tanggal_kunjungan').val(d.tanggal_kunjungan ? String(d.tanggal_kunjungan).substring(0, 10) : '');
                $('#pic').val(d.pic);
                $('#no_hp').val(d.no_hp);
                $('#status').val(d.status || 'Planned');
                $('#hasil_kunjungan').val(d.hasil_kunjungan);
                $('#bukti_kunjungan').next('.custom-file-label').text(d.bukti_kunjungan ? d.bukti_kunjungan.split('/').pop() : 'Pilih file');
                $('#kunjunganModalLabel').text('Edit Kunjungan');
                $('#kunjunganModal').modal('show');
            }).fail(function(){
                Swal.fire({icon:'error', title:'Gagal memuat data'});
            });
        });

        $(document).on('change', '#bukti_kunjungan', function(){
            const fileName = $(this).val().split('\\').pop();
            $(this).next('.custom-file-label').text(fileName || 'Pilih file');
        });

        $('#kunjungan-form').on('submit', function(e){
            e.preventDefault();
            const id = $('#kunjungan-id').val();
            const formData = new FormData(this);
            if (id) formData.append('_method', 'PUT');
            const $submit = $('#kunjungan-submit').prop('disabled', true).text('Menyimpan...');

            $.ajax({
                url: id ? `${baseUrl}/${id}` : baseUrl,
                method: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(){
                    Swal.fire({icon:'success', title:'Tersimpan', timer:1200, showConfirmButton:false});
                    $('#kunjunganModal').modal('hide');
                    table.ajax.reload(null, false);
                },
                error: function(xhr){
                    const errors = xhr.responseJSON && xhr.responseJSON.errors;
                    Swal.fire({icon:'error', title:'Gagal menyimpan', html: errors ? Object.values(errors).map(m => esc(m)).join('<br>') : esc(xhr.responseJSON?.message || 'Terjadi kesalahan')});
                },
                complete: function(){
                    $submit.prop('disabled', false).text('Simpan');
                }
            });
        });

        $('#kunjungan-table').on('click', '.btn-delete', function(){
            const id = $(this).data('id');
            Swal.fire({
                title: 'Hapus kunjungan ini?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal'
            }).then(function(result){
                if (!result.value) return;
                $.ajax({url: `${baseUrl}/${id}`, method: 'DELETE', success: function(){
                    Swal.fire({icon:'success', title:'Terhapus', timer:1000, showConfirmButton:false});
                    table.ajax.reload(null, false);
                }, error: function(){ Swal.fire({icon:'error', title:'Gagal menghapus'}); }});
            });
        });
    });
</script>
@endsection
