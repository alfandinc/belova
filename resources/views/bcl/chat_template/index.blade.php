@extends('layouts.bcl.app')

@section('content')
<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box">
            <div class="row align-items-center">
                <div class="col">
                    <h4 class="page-title">Templating Chat</h4>
                    <span>Simpan template pesan yang bisa langsung dicopy untuk penyewa.</span>
                </div>
                <div class="col-auto align-self-center">
                    <button class="btn btn-sm btn-dark waves-effect waves-light" id="btn-add-template">
                        <i class="mdi mdi-plus"></i> Tambah Template
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0" id="chat-template-table">
                        <thead>
                            <tr>
                                <th>Nama Template</th>
                                <th>Context</th>
                                <th>Isi Pesan</th>
                                <th>Update Terakhir</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">Contoh Context</h5>
                <p class="text-muted mb-3">Gunakan context untuk membedakan kebutuhan pesan, misalnya pengingat masa sewa atau informasi fasilitas kamar.</p>
                <div class="mb-4">
                    @foreach($contextSuggestions as $contextSuggestion)
                        <span class="badge badge-soft-primary mb-2 mr-1 px-3 py-2">{{ $contextSuggestion }}</span>
                    @endforeach
                </div>

                <h5 class="card-title">Placeholder Siap Pakai</h5>
                <p class="text-muted mb-3">Template tetap sederhana. Kalau perlu data spesifik, sisipkan placeholder lalu ganti manual saat akan dikirim.</p>
                <div class="template-placeholder-list">
                    @foreach($placeholders as $placeholder)
                        <button type="button" class="btn btn-outline-secondary btn-sm mb-2 btn-placeholder" data-placeholder="{{ $placeholder }}">{{ $placeholder }}</button>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="chatTemplateModal" tabindex="-1" role="dialog" aria-labelledby="chatTemplateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark">
                <h6 class="modal-title m-0 text-white" id="chatTemplateModalLabel">Tambah Template Chat</h6>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="chatTemplateForm">
                    @csrf
                    <input type="hidden" name="id" id="template-id">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="template-name">Nama Template</label>
                            <input type="text" class="form-control" id="template-name" name="name" placeholder="Contoh: Reminder masa sewa" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="template-context">Context</label>
                            <input type="text" class="form-control" id="template-context" name="context" list="template-context-suggestions" placeholder="Contoh: Masa Sewa Hampir Habis">
                            <datalist id="template-context-suggestions">
                                @foreach($contextSuggestions as $contextSuggestion)
                                    <option value="{{ $contextSuggestion }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                    </div>
                    <div class="form-group mb-0">
                        <label for="template-content">Isi Template</label>
                        <textarea class="form-control" id="template-content" name="content" rows="12" placeholder="Tulis pesan yang ingin dicopy admin saat chat ke penyewa." required></textarea>
                        <small class="form-text text-muted">Bisa isi teks normal atau placeholder seperti @{{nama_penyewa}} dan @{{nomor_kamar}}.</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary btn-sm" id="save-template-btn">Simpan</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('pagescript')
<script>
    $(function () {
        var table = $('#chat-template-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{!! route("bcl.chat_template.data") !!}',
            order: [[3, 'desc']],
            columns: [
                { data: 'name', name: 'name' },
                { data: 'context', name: 'context' },
                { data: 'content_preview', name: 'content', orderable: false },
                { data: 'updated_at', name: 'updated_at' },
                {
                    data: null,
                    name: 'actions',
                    orderable: false,
                    searchable: false,
                    render: function (data, type, row) {
                        var encodedContent = encodeURIComponent(row.content || '');

                        return '' +
                            '<button type="button" class="btn btn-sm btn-success btn-copy mr-1" data-content="' + encodedContent + '">Copy</button>' +
                            '<button type="button" class="btn btn-sm btn-primary btn-edit mr-1" data-id="' + row.id + '">Edit</button>' +
                            '<button type="button" class="btn btn-sm btn-danger btn-delete" data-id="' + row.id + '">Delete</button>';
                    }
                }
            ]
        });

        function resetForm() {
            $('#chatTemplateForm')[0].reset();
            $('#template-id').val('');
            $('#chatTemplateModalLabel').text('Tambah Template Chat');
        }

        function showToast(icon, text) {
            $.toast({
                heading: 'Result',
                text: text,
                position: 'top-center',
                hideAfter: 2500,
                icon: icon
            });
        }

        async function copyText(text) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                await navigator.clipboard.writeText(text);
                return;
            }

            var textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.setAttribute('readonly', 'readonly');
            textarea.style.position = 'absolute';
            textarea.style.left = '-9999px';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
        }

        $('#btn-add-template').on('click', function () {
            resetForm();
            $('#chatTemplateModal').modal('show');
        });

        $('#save-template-btn').on('click', function () {
            var id = $('#template-id').val();
            var url = id ? '{!! url("bcl/chat-template/update") !!}/' + id : '{!! route("bcl.chat_template.store") !!}';

            $.post(url, $('#chatTemplateForm').serialize())
                .done(function (response) {
                    $('#chatTemplateModal').modal('hide');
                    table.ajax.reload(null, false);
                    showToast('success', response.message || 'Template chat berhasil disimpan.');
                })
                .fail(function (xhr) {
                    var message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Gagal menyimpan template chat.';
                    showToast('error', message);
                });
        });

        $('#chat-template-table').on('click', '.btn-edit', function () {
            var id = $(this).data('id');

            $.get('{!! url("bcl/chat-template/edit") !!}/' + id)
                .done(function (response) {
                    var data = response.data;
                    $('#template-id').val(data.id);
                    $('#template-name').val(data.name);
                    $('#template-context').val(data.context);
                    $('#template-content').val(data.content);
                    $('#chatTemplateModalLabel').text('Edit Template Chat');
                    $('#chatTemplateModal').modal('show');
                })
                .fail(function () {
                    showToast('error', 'Gagal mengambil data template.');
                });
        });

        $('#chat-template-table').on('click', '.btn-delete', function () {
            var id = $(this).data('id');

            if (!confirm('Hapus template ini?')) {
                return;
            }

            $.get('{!! url("bcl/chat-template/delete") !!}/' + id)
                .done(function (response) {
                    table.ajax.reload(null, false);
                    showToast('success', response.message || 'Template chat berhasil dihapus.');
                })
                .fail(function () {
                    showToast('error', 'Gagal menghapus template chat.');
                });
        });

        $('#chat-template-table').on('click', '.btn-copy', async function () {
            var text = decodeURIComponent($(this).data('content') || '');

            try {
                await copyText(text);
                showToast('success', 'Template chat berhasil dicopy.');
            } catch (error) {
                showToast('error', 'Gagal copy template chat. Silakan copy manual lewat edit.');
            }
        });

        $('.btn-placeholder').on('click', function () {
            var placeholder = $(this).data('placeholder');
            var textarea = $('#template-content');
            var currentValue = textarea.val();
            var spacer = currentValue && !currentValue.endsWith(' ') && !currentValue.endsWith('\n') ? ' ' : '';

            textarea.val(currentValue + spacer + placeholder).trigger('focus');
        });

        $('#chatTemplateModal').on('hidden.bs.modal', function () {
            resetForm();
        });
    });
</script>
@stop