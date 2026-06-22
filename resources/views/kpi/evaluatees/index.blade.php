@extends('layouts.hrd.app')

@section('title', 'KPI | My Evaluations')

@section('navbar')
    @include('layouts.kpi.navbar')
@endsection

@section('content')
<div class="container-fluid px-2">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-1">My Pending Evaluations</h4>
                <div class="text-muted small">List of evaluatees you need to assess.</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-2">
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            <div id="noOpenBanner" class="alert alert-warning" style="display: none;">No open KPI period. Evaluations are unavailable.</div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped w-100" id="evaluateesTable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Evaluatee</th>
                            <th>Position</th>
                            <th>Category</th>
                            <th>Period</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function(){
    var hasOpen = {{ isset($hasOpenPeriod) && $hasOpenPeriod ? 'true' : 'false' }};
    if (!hasOpen) {
        $('#noOpenBanner').show();
        return;
    }

    var table = $('#evaluateesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('kpi.evaluatees.data') }}",
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'evaluatee', name: 'evaluatee' },
            { data: 'position', name: 'position' },
                { data: 'categories', name: 'categories' },
            { data: 'period', name: 'period' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]
    });

    // Modal loading: large modal
    $('body').append('\n    <div class="modal fade" id="fillAssessmentModal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">\n      <div class="modal-dialog modal-xl" role="document">\n        <div class="modal-content">\n          <div class="modal-header">\n            <h5 class="modal-title">Fill Assessment</h5>\n            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>\n          </div>\n          <div class="modal-body" id="fillAssessmentModalBody">\n          </div>\n        </div>\n      </div>\n    </div>\n    ');

    // open fill modal when Fill button clicked
    $(document).on('click', '.btn-fill-assessment', function(){
        var url = $(this).data('url');
        if (!url) return;
        $('#fillAssessmentModalBody').html('<div class="text-center py-5">Loading...</div>');
        $('#fillAssessmentModal').modal({ backdrop: 'static', keyboard: false });
        $.get(url).done(function(html){
            try {
                var $doc = $(html);
                var body = $doc.find('#kpiAssessmentFillSection').length ? $doc.find('#kpiAssessmentFillSection').html() : html;
                $('#fillAssessmentModalBody').html(body);
                // bind AJAX submit for the form inside modal
                bindFillFormAjax();
            } catch(e){
                $('#fillAssessmentModalBody').html(html);
            }
        }).fail(function(xhr){
            var status = xhr.status;
            var txt = xhr.responseText || xhr.statusText || 'Unknown error';
            var html = '<div class="alert alert-danger">Failed to load assessment. HTTP ' + status + '</div>';
            html += '<pre style="max-height:200px;overflow:auto;background:#f8f9fa;padding:8px;margin-top:8px;border:1px solid #eee;">' + $('<div>').text(txt).html() + '</pre>';
            $('#fillAssessmentModalBody').html(html);
            console.error('Failed to load assessment', status, txt);
        });
    });

    function bindFillFormAjax(){
        var $form = $('#fillAssessmentModalBody').find('form#kpiAssessmentFillForm');
        if (!$form.length) return;
        $form.off('submit').on('submit', function(e){
            e.preventDefault();
            var $f = $(this);

            Swal.fire({
                title: 'Confirm submit',
                text: 'This action cannot be undone. Once submitted, the assessment cannot be changed. Proceed?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, submit',
                cancelButtonText: 'Cancel'
            }).then(function(result){
                if (!result.value) return;

                $.ajax({
                    url: $f.attr('action'),
                    method: 'POST',
                    data: $f.serialize(),
                    headers: { 'Accept': 'application/json' }
                }).done(function(res){
                    $('#fillAssessmentModal').modal('hide');
                    table.ajax.reload(null,false);
                    Swal.fire('Success', res.message || 'Assessment saved', 'success');
                }).fail(function(xhr){
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        var msgs = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                        Swal.fire('Validation failed', msgs, 'warning');
                        return;
                    }
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to save assessment';
                    Swal.fire('Error', msg, 'error');
                });
            });
        });
    }
});
</script>
@endsection
