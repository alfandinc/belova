<script>
document.addEventListener('DOMContentLoaded', function(){
  // Use jQuery to ensure we remove any existing handlers to avoid duplicate popups
  if (typeof $ !== 'undefined') {
    $('#btnGenerateUangKpi').off('click').on('click', function(e){
      e.preventDefault(); e.stopImmediatePropagation();
      const bulan = document.getElementById('filterBulan') ? document.getElementById('filterBulan').value : null;
      $('#simulateKpiModal').modal('show');
      document.getElementById('simulateKpiContent').innerHTML = '<p class="text-muted">Loading preview...</p>';
      fetch("{{ route('hrd.payroll.slip_gaji.simulate_kpi') }}", {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ bulan: bulan })
      }).then(r => r.json()).then(data => {
        if (!data.success) {
          document.getElementById('simulateKpiContent').innerHTML = '<div class="alert alert-danger">'+(data.message||'Error')+'</div>';
          return;
        }
      let html = '<h6>Omset Rows</h6>';
      html += '<table class="table table-sm table-bordered"><thead><tr><th>ID</th><th>Insentif ID</th><th>Nominal</th><th>Insentif%</th><th>Mode</th><th>Kontribusi</th></tr></thead><tbody>';
      data.rows.forEach(r => {
        html += `<tr><td>${r.id}</td><td>${r.insentif_omset_id}</td><td>${r.nominal}</td><td>${r.insentif_pct}</td><td>${r.mode}</td><td>${r.kontribusi}</td></tr>`;
      });
      html += '</tbody></table>';
      html += '<h6>Employee Distribution</h6>';
      html += '<table class="table table-sm table-bordered"><thead><tr><th>ID</th><th>Nama</th><th>KPI Poin</th><th>Uang KPI</th></tr></thead><tbody>';
      data.employees.forEach(e => {
        html += `<tr><td>${e.id}</td><td>${e.nama}</td><td>${e.kpi_poin}</td><td>${e.uang_kpi}</td></tr>`;
      });
      html += `</tbody></table><p><strong>Total Kontribusi:</strong> ${data.total}</p>`;
      document.getElementById('simulateKpiContent').innerHTML = html;

      // hook confirm - unbind previous handlers first to avoid duplicates
      if (typeof $ !== 'undefined') {
        $('#confirmGenerateKpi').off('click').on('click', function(ev){
          ev.preventDefault(); ev.stopImmediatePropagation();
          // call generation (confirm=true)
          fetch("{{ route('hrd.payroll.slip_gaji.simulate_kpi') }}", {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ bulan: bulan, confirm: true })
          }).then(res => res.json()).then(resp => {
            if (resp.success) {
              $('#simulateKpiModal').modal('hide');
              // optional: show nicer toast if available
              alert('Generate berhasil.');
              location.reload();
            } else {
              alert('Generate gagal: ' + (resp.message || 'error'));
            }
          });
        });
      } else {
        document.getElementById('confirmGenerateKpi').onclick = function(){ /* fallback */ };
      }

      }).catch(err => {
        document.getElementById('simulateKpiContent').innerHTML = '<div class="alert alert-danger">'+err+'</div>';
      });
    });
  } else {
    // fallback to previous plain DOM behavior
    const btn = document.getElementById('btnGenerateUangKpi');
    if (!btn) return;
    btn.addEventListener('click', function(){
      const bulan = document.getElementById('filterBulan') ? document.getElementById('filterBulan').value : null;
      $('#simulateKpiModal').modal('show');
      document.getElementById('simulateKpiContent').innerHTML = '<p class="text-muted">Loading preview...</p>';
      fetch("{{ route('hrd.payroll.slip_gaji.simulate_kpi') }}", {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ bulan: bulan })
      }).then(r => r.json()).then(data => {
        if (!data.success) {
          document.getElementById('simulateKpiContent').innerHTML = '<div class="alert alert-danger">'+(data.message||'Error')+'</div>';
          return;
        }
        // ...same rendering handled above (omitted for brevity)
      });
    });
  }
});
</script>
