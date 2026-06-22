<div class="card" id="kpiAssessmentFillSection">
    <div class="card-body">
        <div class="mb-3">
            <h4 class="mb-2">KPI Assessment - {{ optional($assessment->period)->period_name ?: (optional(optional($assessment->period)->month) ? \DateTime::createFromFormat('!m', optional($assessment->period)->month)->format('F') . ' ' . optional($assessment->period)->year : '') }}</h4>
            <p class="text-muted mb-0">Menilai: {{ optional($assessment->evaluateeEmployee)->nama ?? optional($assessment->evaluateeEmployee)->name ?? '-' }} | {{ optional($assessment->evaluateePosition)->name ?? '-' }}</p>
        </div>

        @if($indicators->isEmpty())
            <div class="alert alert-warning">Belum ada indikator yang cocok untuk assessment ini.</div>
        @else
            <form method="POST" action="{{ route('kpi.kpi_assessments.submit', $assessment) }}" id="kpiAssessmentFillForm">
                @csrf
                <div class="mb-4">
                    <table class="table table-striped table-sm">
                        <thead>
                            <tr>
                                <th style="width:48px">No</th>
                                <th>Indicator</th>
                                <th style="width:120px">Weight</th>
                                <th style="width:240px">Jawaban</th>
                                <th style="width:320px">Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $i = 1; @endphp
                            @foreach($indicators->groupBy(function($m){ return $m->category_name ?? optional(optional($m->indicator)->category)->category_name ?? 'Uncategorized'; }) as $categoryName => $group)
                                <tr class="table-light">
                                    <td colspan="5"><strong>{{ $categoryName }}</strong></td>
                                </tr>
                                @foreach($group as $mapping)
                                    @php($existingScore = $scores->get(optional($mapping->indicator)->id))
                                    <tr>
                                        <td class="align-middle">{{ $i++ }}</td>
                                        <td>
                                            <div class="font-weight-semibold">{{ optional($mapping->indicator)->indicator_name }}</div>
                                            @if(optional($mapping->indicator)->notes)
                                                <div class="text-muted small">{{ optional($mapping->indicator)->notes }}</div>
                                            @endif
                                        </td>
                                        <td class="align-middle">{{ number_format((float) $mapping->weight_percentage, 0) }}%</td>
                                        <td class="align-middle">
                                            <div class="kpi-star-rating" data-indicator-id="{{ optional($mapping->indicator)->id }}">
                                                @for($s = 1; $s <= 5; $s++)
                                                    <span class="star" data-value="{{ $s }}">&#9733;</span>
                                                @endfor
                                                <input type="hidden" name="scores[{{ optional($mapping->indicator)->id }}]" class="star-value" value="{{ old('scores.' . optional($mapping->indicator)->id, optional($existingScore)->score ?: 0) }}" required>
                                            </div>
                                        </td>
                                        <td>
                                            <textarea name="notes[{{ optional($mapping->indicator)->id }}]" rows="2" class="form-control form-control-sm">{{ old('notes.' . optional($mapping->indicator)->id, optional($existingScore)->notes) }}</textarea>
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <button type="submit" class="btn btn-primary">Submit Assessment</button>
            </form>
        @endif
    </div>
</div>

<style>
    .kpi-star-rating { display:inline-block; }
    .kpi-star-rating .star { font-size:22px; color:#ddd; cursor:pointer; padding:0 4px; }
    .kpi-star-rating .star.selected { color:#f5b301; }
</style>

<script>
    (function(){
        function initStars($container){
            $container.find('.star').each(function(){
                var $s = $(this);
                var v = parseInt($s.data('value'));
                var cur = parseInt($container.find('.star-value').val() || 0);
                if (v <= cur) $s.addClass('selected'); else $s.removeClass('selected');
            });
        }

        $(document).on('click', '.kpi-star-rating .star', function(){
            var $s = $(this);
            var val = parseInt($s.data('value'));
            var $container = $s.closest('.kpi-star-rating');
            $container.find('.star').each(function(){
                var $x = $(this);
                var v = parseInt($x.data('value'));
                $x.toggleClass('selected', v <= val);
            });
            $container.find('.star-value').val(val).trigger('change');
        });

        // initialize existing values on load
        $(document).ready(function(){
            $('.kpi-star-rating').each(function(){ initStars($(this)); });
        });
    })();
</script>
