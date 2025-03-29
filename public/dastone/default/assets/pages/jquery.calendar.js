document.addEventListener("DOMContentLoaded", function () {
    var e = document.getElementById("calendar"),
        n = new FullCalendar.Calendar(e, {
            themeSystem: 'bootstrap',
            plugins: ["interaction", "dayGrid", "timeGrid"],
            header: {
                left: "prev,next today",
                center: "title",
                right: "dayGridMonth,timeGridWeek,timeGridDay",
            },
            buttonsIcons: {
                close: 'fa-times',
                prev: 'fa-chevron-left',
                next: 'fa-chevron-right',
                prevYear: 'fa-angle-double-left',
                nextYear: 'fa-angle-double-right'
            },
            // defaultDate: "2020-06-12",

            navLinks: !0,
            selectable: !0,
            selectMirror: !0,
            select: function (e) {
                var t = prompt("Event Title:");
                t && n.addEvent({ title: t, start: e.start, end: e.end, allDay: e.allDay }), n.unselect();
            },
            editable: !0,
            eventLimit: !0,
            events: [
                { title: "AD 8857 OB", start: "2024-12-17 08:00:00", end: "2024-12-17 10:00:00", className: "bg-soft-success" }
                // { title: "Business Lunch", start: "2020-06-03T13:00:00", constraint: "businessHours", className: "bg-soft-warning" },
                // { title: "Meeting", start: "2020-06-13T11:00:00", constraint: "availableForMeeting", className: "bg-soft-purple", textColor: "white" },
                // { title: "Conference", start: "2020-06-27", end: "2020-06-29", className: "bg-soft-primary" },
                // { groupId: "availableForMeeting", start: "2020-06-11T10:00:00", end: "2020-06-11T16:00:00", title: "Repeating Event", className: "bg-soft-purple" },
                // { groupId: "availableForMeeting", start: "2020-06-15T10:00:00", end: "2020-06-15T16:00:00", title: "holiday", className: "bg-soft-success" },
                // { start: "2020-06-06", end: "2020-06-08", overlap: !1, title: "New Event", className: "bg-soft-pink" },
            ],
            eventClick: function (e) {
                confirm("delete event?") && e.event.remove();
            },
        });
    n.render();
}),
    new Lightpick({ field: document.getElementById("light_datepick"), inline: !0 });
