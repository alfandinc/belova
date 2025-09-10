@if(!empty($riwayatTindakan->kodeTindakans) && is_iterable($riwayatTindakan->kodeTindakans))
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Kode Tindakan</th>
                <th>Obat yang digunakan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($riwayatTindakan->kodeTindakans as $kodeTindakan)
            <tr>
                <td>{{ $kodeTindakan->nama }}</td>
                <td>
                    <table class="table table-bordered table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Obat</th>
                                <th style="width:60px">Qty</th>
                                <th style="width:60px">Dosis</th>
                                <th>Satuan Dosis</th>
                                <th>Edit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(isset($riwayatObat[$kodeTindakan->id]))
                                @foreach($riwayatObat[$kodeTindakan->id] as $obatId)
                                    @php
                                        $obat = \App\Models\ERM\Obat::find($obatId);
                                        $pivot = \DB::table('erm_riwayat_tindakan_obat')
                                            ->where('riwayat_tindakan_id', $riwayatTindakan->id)
                                            ->where('kode_tindakan_id', $kodeTindakan->id)
                                            ->where('obat_id', $obatId)
                                            ->first();
                                    @endphp
                                    @if($obat)
                                    <tr>
                                        <td class="obat-name-cell" data-obat-id="{{ $obat->id }}" data-kode-tindakan-id="{{ $kodeTindakan->id }}" style="width:220px">{{ $obat->nama }}</td>
                                        <td><input type="number" class="form-control qty-input" name="qty[{{ $kodeTindakan->id }}][{{ $obat->id }}]" value="{{ $pivot->qty ?? 1 }}" min="1" style="width:50px" disabled></td>
                                        <td><input type="text" class="form-control dosis-input" name="dosis[{{ $kodeTindakan->id }}][{{ $obat->id }}]" value="{{ $pivot->dosis ?? '' }}" style="width:50px" disabled></td>
                                        <td style="min-width:100px">
                                            @php
                                                $satuanOptions = [];
                                                if (!empty($obat->satuan)) {
                                                    $satuanOptions = explode(',', $obat->satuan);
                                                }
                                            @endphp
                                            @php
                                                $defaultSatuan = ['mL', 'Mg', 'Vial', 'Gram', 'Tablet', 'Botol', 'Ampul', 'Tube', 'IU', 'Unit', 'pcs', 'dll'];
                                                $allSatuan = array_unique(array_merge($satuanOptions, $defaultSatuan));
                                            @endphp
                                            <select class="form-control satuan-dosis-input" name="satuan_dosis[{{ $kodeTindakan->id }}][{{ $obat->id }}]" disabled>
                                                @foreach($allSatuan as $opt)
                                                    <option value="{{ trim($opt) }}" @if(($pivot->satuan_dosis ?? '') == trim($opt)) selected @endif>{{ trim($opt) }}</option>
                                                @endforeach
                                                @if(empty($allSatuan))
                                                    <option value="{{ $pivot->satuan_dosis ?? '' }}" selected>{{ $pivot->satuan_dosis ?? '' }}</option>
                                                @endif
                                            </select>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-warning edit-obat-btn" data-obat-id="{{ $obat->id }}" data-kode-tindakan-id="{{ $kodeTindakan->id }}">Edit</button>
                                        </td>
                                    </tr>
                                    @endif
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
        <div class="alert alert-warning">Tidak ada kode tindakan untuk riwayat ini.</div>
    @endif

<button type="button" class="btn btn-primary" id="saveRiwayatObatBtn">Simpan Perubahan</button>
<script>
    $(document).ready(function() {
        // Save logic
        $('#saveRiwayatObatBtn').on('click', function() {
            var data = {};
            var qtyData = {};
            var dosisData = {};
            var satuanDosisData = {};
            // Collect obat IDs
            $('.obat-name-cell').each(function() {
                var $row = $(this).closest('tr');
                var kodeTindakanId = $(this).data('kode-tindakan-id');
                var obatId = $(this).data('obat-id');
                if (!data[kodeTindakanId]) data[kodeTindakanId] = [];
                data[kodeTindakanId].push(obatId);
                // Collect qty, dosis, satuan_dosis
                var qty = $row.find('.qty-input').val();
                var dosis = $row.find('.dosis-input').val();
                var satuanDosis = $row.find('.satuan-dosis-input').val();
                if (!qtyData[kodeTindakanId]) qtyData[kodeTindakanId] = {};
                if (!dosisData[kodeTindakanId]) dosisData[kodeTindakanId] = {};
                if (!satuanDosisData[kodeTindakanId]) satuanDosisData[kodeTindakanId] = {};
                qtyData[kodeTindakanId][obatId] = qty;
                dosisData[kodeTindakanId][obatId] = dosis;
                satuanDosisData[kodeTindakanId][obatId] = satuanDosis;
            });
            $.ajax({
                url: '/erm/riwayat-tindakan/{{ $riwayatTindakan->id }}/obat',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    obats: data,
                    qty: qtyData,
                    dosis: dosisData,
                    satuan_dosis: satuanDosisData
                },
                success: function(res) {
                    alert('Obat berhasil disimpan!');
                    // Reload modal content to show updated obat
                    var riwayatId = {{ $riwayatTindakan->id }};
                    $.get('/erm/riwayat-tindakan/' + riwayatId + '/detail', function(response) {
                        $('#riwayatDetailContent').html(response.html);
                    });
                },
                error: function() {
                    alert('Gagal menyimpan obat.');
                }
            });
        });

        // Edit button logic
        $(document).on('click', '.edit-obat-btn', function() {
            var $btn = $(this);
            var obatId = $btn.data('obat-id');
            var kodeTindakanId = $btn.data('kode-tindakan-id');
            var $row = $btn.closest('tr');
            var $cell = $row.find('.obat-name-cell');
            var currentObatName = $cell.text();
            // Enable qty, dosis, satuan_dosis fields in this row
            $row.find('.qty-input, .dosis-input, .satuan-dosis-input').prop('disabled', false);
            // Replace cell with select2 input
            $cell.html('<select class="form-control obat-substitute-select" style="width:100%"></select>');
            var $select = $cell.find('select');
            $select.select2({
                placeholder: 'Cari obat pengganti...',
                ajax: {
                    url: '/obat/search',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return { q: params.term };
                    },
                    processResults: function(data) {
                        return {
                            results: data.results.map(function(item) {
                                return { id: item.id, text: item.nama };
                            })
                        };
                    },
                    cache: true
                },
                minimumInputLength: 2
            });
            // On select, update cell
            $select.on('select2:select', function(e) {
                var obatData = e.params.data;
                // Update cell with new obat name and update data-obat-id
                $cell.text(obatData.text);
                $cell.attr('data-obat-id', obatData.id);
            });
            // Change Edit button to Cancel
            $btn.text('Cancel').removeClass('btn-warning').addClass('btn-secondary').addClass('cancel-obat-btn').removeClass('edit-obat-btn');
            // Store original name and id for cancel
            $row.data('original-obat-name', currentObatName);
            $row.data('original-obat-id', obatId);
        });

        // Cancel button logic
        $(document).on('click', '.cancel-obat-btn', function() {
            var $btn = $(this);
            var $row = $btn.closest('tr');
            var $cell = $row.find('.obat-name-cell');
            var originalName = $row.data('original-obat-name');
            var originalId = $row.data('original-obat-id');
            // Restore cell
            $cell.text(originalName);
            $cell.attr('data-obat-id', originalId);
            // Disable qty, dosis, satuan_dosis fields in this row
            $row.find('.qty-input, .dosis-input, .satuan-dosis-input').prop('disabled', true);
            // Restore button
            $btn.text('Edit').removeClass('btn-secondary').addClass('btn-warning').addClass('edit-obat-btn').removeClass('cancel-obat-btn');
        });
    });
</script>
