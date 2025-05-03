@extends('layouts.erm.app')
@section('title', 'ERM | Rawat Jalan')
@section('navbar')
    @include('layouts.erm.navbar')
@endsection  

@section('content')

<style>
.popover {
    background: transparent !important;
    border: none;
    max-width: 240px;
    padding: 0;
    z-index: 9999;
}

.popover-dark-wrapper {
    background-color: #2a2f3a;
    border-radius: 10px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
    overflow: hidden;
    font-family: sans-serif;
}

.popover-dark-header {
    background-color: #1f232d;
    color: #ffffff;
    padding: 8px 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 13px;
    font-weight: 600;
    border-bottom: 1px solid #333;
}

.popover-dark-title {
    flex: 1;
    margin-right: 8px;
}

.close-btn {
    background: transparent;
    border: none;
    color: #aaa;
    font-size: 16px;
    cursor: pointer;
    line-height: 1;
}

.popover-dark-body {
    padding: 10px 12px;
    max-height: 180px;
    overflow-y: auto;
}

.popover-dark-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.popover-dark-list li {
    display: flex;
    align-items: center;
    color: #f1f1f1;
    font-size: 13px;
    margin-bottom: 6px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.popover-dark-list .dot {
    display: inline-block;
    width: 8px;
    height: 8px;
    background-color: #3399ff;
    border-radius: 50%;
    margin-right: 8px;
}
/* Mengurangi tinggi tiap kotak hari */

</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">ERM</a></li>
                            <li class="breadcrumb-item active">Rawat Jalan</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-primary">
            <h4 class="card-title text-white">List Antrian Pasien</h4>
        </div>
        <div class="card-body">
            <div id='calendar'></div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src='{{ asset('fullcalendar/dist/index.global.js') }}'></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');

    var calendar = new FullCalendar.Calendar(calendarEl, {
        // initialDate: '2025-05-03',
        // editable: false,
        // selectable: true,
        dayMaxEvents: true,
        // eventDisplay: 'block',
        height: 500,
        events: @json($events),

        eventContent: function(info) {
            const count = info.event.title;
            const wrapper = document.createElement('div');
            wrapper.innerHTML = `<div style="font-size: 10px; text-align: center;">${count}</div>`;
            wrapper.setAttribute('tabindex', '0'); // Required for Bootstrap popover focus trigger
            return { domNodes: [wrapper] };
        },

        eventClick: function(info) {
            const list = info.event.extendedProps.antrian_list;
            if (list && list.length) {
                const dateLabel = info.event.start.toLocaleDateString('id-ID', {
                    weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
                });

                const popoverContent = `
<div class="popover-dark-wrapper">
    <div class="popover-dark-header">
        <span class="popover-dark-title">${dateLabel}</span>
        <button class="close-btn" onclick="this.closest('.popover').remove();">&times;</button>
    </div>
    <div class="popover-dark-body">
        <ul class="popover-dark-list">
            ${list.map(item => `<li><span class="dot"></span>${item}</li>`).join('')}
        </ul>
    </div>
</div>
                `;

                const popover = new bootstrap.Popover(info.el, {
                    content: popoverContent,
                    placement: 'top',
                    trigger: 'focus',
                    html: true,
                    sanitize: false,
                });

                popover.show();
            }
        }
    });

    calendar.render();
});
</script>
@endsection
