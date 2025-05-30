function format(t) {
    return '<table cellpadding="5" cellspacing="0" border="0" style="padding-left:50px;"><tr><td>Full name:</td><td>' + t.name + "</td></tr><tr><td>Extension number:</td><td>" + t.extn + "</td></tr><tr><td>Extra info:</td><td>And any further details here (images etc)...</td></tr></table>"
}
$(document).ready(function() {
    $("#datatable").DataTable(), $(document).ready(function() {
        $("#datatable2").DataTable()
    }), $("#datatable-buttons").DataTable({
        lengthChange: !1,
        buttons: ["copy", "excel", "pdf", "colvis"]
    }).buttons().container().appendTo("#datatable-buttons_wrapper .col-md-6:eq(0)"), $("#row_callback").DataTable({
        createdRow: function(t, a, e) {
            15e4 < 1 * a[5].replace(/[\$,]/g, "") && $("td", t).eq(5).addClass("highlight")
        }
    })
}), $(document).ready(function() {
    var e = $("#child_rows").DataTable({
        data: testdata.data,
        select: "single",
        columns: [{
            className: "details-control",
            orderable: !1,
            data: null,
            defaultContent: ""
        }, {
            data: "name"
        }, {
            data: "position"
        }, {
            data: "office"
        }, {
            data: "salary"
        }],
        order: [
            [1, "asc"]
        ]
    });
    $("#child_rows tbody").on("click", "td.details-control", function() {
        var t = $(this).closest("tr"),
            a = e.row(t);
        a.child.isShown() ? (a.child.hide(), t.removeClass("shown")) : (a.child(format(a.data())).show(), t.addClass("shown"))
    })
});
var testdata = {
    data: [{
        name: "Tiger Nixon",
        position: "System Architect",
        salary: "$320,800",
        start_date: "2011/04/25",
        office: "Edinburgh",
        extn: "5421"
    }, {
        name: "Garrett Winters",
        position: "Accountant",
        salary: "$170,750",
        start_date: "2011/07/25",
        office: "Tokyo",
        extn: "8422"
    }, {
        name: "Ashton Cox",
        position: "Junior Technical Author",
        salary: "$86,000",
        start_date: "2009/01/12",
        office: "San Francisco",
        extn: "1562"
    }]
};