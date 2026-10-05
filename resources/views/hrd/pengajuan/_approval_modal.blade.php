{{--
    Shared approval modal for pengajuan pages.
    Params: $modalId, $title, $commentName, $extra (optional HTML rendered under the note field),
            $adjust (optional: 'date' or 'time') to let the approver approve fewer days / hours
--}}
<div class="modal fade" id="{{ $modalId }}" tabindex="-1" role="dialog" aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="{{ $modalId }}Label">{{ $title }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form onsubmit="return false;">
                <div class="modal-body">
                    <div class="approval-summary border rounded p-2 mb-3 small"></div>
                    @if(!empty($adjust))
                    <div class="approval-adjust border rounded p-2 mb-3" data-adjust="{{ $adjust }}">
                        <label class="font-weight-bold mb-1">{{ $adjust === 'time' ? 'Jam yang disetujui' : 'Tanggal yang disetujui' }}</label>
                        <div class="form-row">
                            <div class="col-6">
                                <small class="text-muted">{{ $adjust === 'time' ? 'Jam mulai' : 'Tanggal mulai' }}</small>
                                <input type="{{ $adjust }}" class="form-control form-control-sm" name="{{ $adjust === 'time' ? 'jam_mulai_disetujui' : 'tanggal_mulai_disetujui' }}">
                            </div>
                            <div class="col-6">
                                <small class="text-muted">{{ $adjust === 'time' ? 'Jam selesai' : 'Tanggal selesai' }}</small>
                                <input type="{{ $adjust }}" class="form-control form-control-sm" name="{{ $adjust === 'time' ? 'jam_selesai_disetujui' : 'tanggal_selesai_disetujui' }}">
                            </div>
                        </div>
                        <div class="adjust-info small mt-2"></div>
                    </div>
                    @endif
                    <div class="form-group mb-2">
                        <label for="{{ $modalId }}Comment">Catatan</label>
                        <textarea class="form-control" name="{{ $commentName }}" id="{{ $modalId }}Comment" rows="3" maxlength="1000" placeholder="Opsional. Sebaiknya diisi bila menolak."></textarea>
                    </div>
                    {!! $extra ?? '' !!}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light mr-auto" data-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger" data-status="ditolak"><i class="fas fa-times-circle mr-1"></i>Tolak</button>
                    <button type="button" class="btn btn-success" data-status="disetujui"><i class="fas fa-check-circle mr-1"></i>Setujui</button>
                </div>
            </form>
        </div>
    </div>
</div>
