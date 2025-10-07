@extends('layouts.marketing.app')
@section('title', 'Hari Penting | Marketing')
@section('navbar')
@include('layouts.marketing.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="page-title mb-0">Hari Penting</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item active">Daftar hari penting / event internal & marketing</li>
                    </ol>
                </div>
                <div>
                    <button class="btn btn-primary" id="addEventBtn"><i data-feather="plus" class="icon-xs me-1"></i> Tambah Hari Penting</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div id="calendar"></div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
#calendar { max-width: 100%; margin: 0 auto; }
.fc .fc-toolbar-title { font-size: 1.25rem; }
.event-popover-title { font-weight:bold; margin-bottom:4px; }
</style>
@endpush

@push('scripts')
<script src='{{ asset('fullcalendar/dist/index.global.js') }}'></script>
<script>
$(function(){
    feather.replace();
    const calendarEl = document.getElementById('calendar');
    let calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        height: 650,
        headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,listWeek' },
        events: {
            url: '{{ url('/marketing/hari-penting/events') }}',
        },
        eventClick: function(info){
            // Close existing popover if any
            if(window.__hpPopoverEl){
                $(window.__hpPopoverEl).popover('dispose');
                window.__hpPopoverEl = null;
            }

            const desc = info.event.extendedProps.description || '(Tidak ada deskripsi)';
            const range = info.event.extendedProps.range || '';
            const html = `\
<div style=\"max-width:260px;\">\
  <div class=\"d-flex justify-content-between align-items-start\">\
    <div class=\"event-popover-title\">${info.event.title}</div>\
    <button type=\"button\" class=\"close ml-2 hp-popover-close\" aria-label=\"Close\">\
      <span aria-hidden=\"true\">&times;</span>\
    </button>\
  </div>\
  <div class=\"text-muted small mb-1\">${range}</div>\
  <div>${desc}</div>\
</div>`;

            $(info.el).popover({
                html: true,
                content: html,
                trigger: 'manual',
                placement: 'top',
                container: 'body'
            }).popover('show');
            window.__hpPopoverEl = info.el;

            // Close handlers
            function closePopover(){
                if(window.__hpPopoverEl){
                    $(window.__hpPopoverEl).popover('dispose');
                    window.__hpPopoverEl = null;
                    $(document).off('mousedown.hpPopover keyup.hpPopover');
                }
            }
            // Outside click
            setTimeout(()=>{ // allow DOM insertion
                $(document).on('mousedown.hpPopover', function(e){
                    const $tip = $('.popover');
                    if(!$tip.is(e.target) && $tip.has(e.target).length===0 && !$(info.el).is(e.target)){
                        closePopover();
                    }
                });
                $(document).on('keyup.hpPopover', function(e){ if(e.key==='Escape') closePopover(); });
                $('.hp-popover-close').on('click', closePopover);
            }, 10);
        }
    });
    calendar.render();

    $('#addEventBtn').on('click', function(){
        $('#modalHariPenting').modal('show');
    });

    $('#formHariPenting').on('submit', function(e){
        e.preventDefault();
        const form = this;
        const data = $(form).serialize();
        $.post('{{ url('/marketing/hari-penting/store') }}', data)
            .done(function(res){
                if(res.success){
                    $('#modalHariPenting').modal('hide');
                    form.reset();
                    calendar.refetchEvents();
                    Swal.fire('Berhasil','Hari penting ditambahkan','success');
                }
            })
            .fail(function(xhr){
                let msg = 'Gagal menyimpan';
                if(xhr.responseJSON && xhr.responseJSON.errors){
                    msg = Object.values(xhr.responseJSON.errors).map(a=>a.join ? a.join(',') : a).join('<br>');
                }
                Swal.fire('Error', msg, 'error');
            });
    });
});
</script>

<!-- Modal -->
<div class="modal fade" id="modalHariPenting" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Hari Penting</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <form id="formHariPenting">
            <div class="modal-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Judul <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                    <input type="date" name="start_date" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tanggal Selesai</label>
                    <input type="date" name="end_date" class="form-control" placeholder="(opsional)">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Warna</label>
                    <input type="color" name="color" class="form-control form-control-color" value="#4e73df">
                </div>
                <div class="col-md-4 d-flex align-items-center">
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="all_day" id="allDayCheck" checked value="1">
                        <label class="form-check-label" for="allDayCheck">All Day</label>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Deskripsi singkat"></textarea>
                </div>
            </div>
      </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">Tutup</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
      </form>
    </div>
  </div>
</div>
@endpush