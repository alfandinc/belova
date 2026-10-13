{{-- "Data yang perlu action": $actionCounts = [key => ['label', 'hint', 'count']]. Cards filter the page's table;
     nothing is deleted automatically. The page wires it up with ActionCards.init(reload, onActivate) and
     refreshes the numbers with ActionCards.update(json.action_counts). --}}
<div class="card border-warning mb-3 {{ collect($actionCounts)->sum('count') ? '' : 'd-none' }}" id="actionPanel">
    <div class="card-body py-2">
        <div class="d-flex flex-wrap align-items-center mb-2">
            <i class="fas fa-exclamation-triangle text-warning mr-2"></i>
            <strong>Data yang perlu action</strong>
            <span class="small text-muted ml-2">Klik kartu untuk menampilkan datanya di tabel. Tidak ada data yang dihapus otomatis.</span>
            <button type="button" class="btn btn-sm btn-link ml-auto d-none" id="actionReset">Tampilkan semua data</button>
        </div>
        <div class="d-flex flex-wrap" style="gap:8px">
            @foreach($actionCounts as $key => $a)
                <button type="button" class="btn btn-outline-warning text-left text-dark action-card {{ $a['count'] ? '' : 'd-none' }}" data-action="{{ $key }}" style="min-width:210px">
                    <span class="h5 mb-0 font-weight-bold" data-count>{{ $a['count'] }}</span>
                    <span class="font-weight-bold">{{ $a['label'] }}</span>
                    <div class="small text-muted">{{ $a['hint'] }}</div>
                </button>
            @endforeach
        </div>
    </div>
</div>
<script>
window.ActionCards = {
    active: null,
    init: function (reload, onActivate) {
        var self = this;
        document.querySelectorAll('#actionPanel .action-card').forEach(function (card) {
            card.addEventListener('click', function () {
                var key = card.getAttribute('data-action');
                self.active = self.active === key ? null : key;
                if (self.active && onActivate) onActivate(self.active);
                self.render();
                reload();
            });
        });
        document.getElementById('actionReset').addEventListener('click', function () {
            self.active = null;
            self.render();
            reload();
        });
    },
    render: function () {
        var self = this;
        document.querySelectorAll('#actionPanel .action-card').forEach(function (card) {
            var on = card.getAttribute('data-action') === self.active;
            card.classList.toggle('btn-warning', on);
            card.classList.toggle('btn-outline-warning', !on);
        });
        document.getElementById('actionReset').classList.toggle('d-none', !self.active);
    },
    update: function (counts) {
        if (!counts) return;
        var total = 0;
        Object.keys(counts).forEach(function (key) {
            var card = document.querySelector('#actionPanel .action-card[data-action="' + key + '"]');
            if (!card) return;
            var n = Number(counts[key].count) || 0;
            total += n;
            card.querySelector('[data-count]').textContent = n.toLocaleString('id-ID');
            // Keep the selected card visible even when it just dropped to 0, so the filter can be cleared
            card.classList.toggle('d-none', !n && key !== this.active);
        }, this);
        document.getElementById('actionPanel').classList.toggle('d-none', !total && !this.active);
    }
};
</script>
