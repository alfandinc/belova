{{-- Manage Pasien > Merchandise: shared by Data Pasien and Rawat Jalan. A patient can hold several gifts of one item. --}}
function getMerchandiseErrorMessage(xhr, fallbackMessage) {
    if (xhr && xhr.responseJSON) {
        return xhr.responseJSON.message || xhr.responseJSON.error || fallbackMessage;
    }

    return fallbackMessage;
}

function escapeMerchandiseText(value) {
    return $('<div>').text(value === null || value === undefined ? '' : String(value)).html();
}

function escapeMerchandiseAttr(value) {
    return escapeMerchandiseText(value).replace(/"/g, '&quot;');
}

function loadManagePasienMerchandise(pasienId) {
    $('#unifiedMerchChecklistContainer').html('<p class="text-muted">Memuat...</p>');

    $.when(
        $.get('{{ route("erm.merchandises.catalog") }}', { pasien_id: pasienId }),
        $.get('/erm/pasiens/' + pasienId + '/merchandises')
    ).done(function(masterResp, pasienResp){
        renderMerchChecklist(masterResp[0].data || [], pasienResp[0].data || [], masterResp[0].can_give !== false);
    }).fail(function(){
        $('#unifiedMerchChecklistContainer').html('<p class="text-danger">Gagal memuat data.</p>');
    });
}

function renderMerchRecord(record, stock, canGive) {
    let info = `Diberikan ${escapeMerchandiseText(String(record.given_at || '-').substring(0, 16))} oleh ${escapeMerchandiseText(record.given_by_name || '-')}`;
    let qty = parseInt(record.quantity || 1, 10);

    if (!canGive) {
        return `<div class="d-flex justify-content-between mt-2 pt-2 border-top small"><span>${info}</span><strong>Qty ${qty}</strong></div>`;
    }

    return `<div class="d-flex flex-wrap justify-content-between align-items-center mt-2 pt-2 border-top small">
        <span class="pr-2">${info}</span>
        <span class="d-inline-flex align-items-center">
            <input type="number" min="1" max="${stock + qty}" value="${qty}" class="form-control form-control-sm text-center merch-record-qty mr-1" data-pm-id="${record.id}" style="width:70px;" aria-label="Qty">
            <button type="button" class="btn btn-sm btn-primary merch-record-save mr-1" data-pm-id="${record.id}">Ubah</button>
            <button type="button" class="btn btn-sm btn-outline-danger merch-remove" data-pm-id="${record.id}">Batalkan</button>
        </span>
    </div>`;
}

function renderMerchChecklist(masterList, pasienReceipts, canGive) {
    let $container = $('#unifiedMerchChecklistContainer');
    $container.empty();

    if (!masterList.length) {
        $container.html('<p class="text-muted">Belum ada merchandise.</p>');
        return;
    }

    let $list = $('<div class="list-group"></div>');

    masterList.forEach(item => {
        let records = (pasienReceipts || []).filter(record => String(record.merchandise_id) === String(item.id));
        let stock = parseInt(item.current_stock || 0, 10);
        let stockBadge = stock > 0
            ? `<small class="text-muted ml-2">Stok: ${stock}</small>`
            : '<small class="text-danger ml-2">Stok habis</small>';
        let inactiveBadge = item.is_active === false ? '<span class="badge badge-secondary ml-2">Nonaktif</span>' : '';
        let klinikBadge = item.klinik_label ? `<span class="badge badge-light ml-2">${escapeMerchandiseText(item.klinik_label)}</span>` : '';
        let thumb = item.image_url
            ? `<img src="${escapeMerchandiseAttr(item.image_url)}" alt="" class="mr-2" style="width:40px;height:40px;flex:0 0 40px;object-fit:cover;border-radius:6px;">`
            : '';
        let giveDisabled = stock <= 0 ? 'disabled' : '';
        let giveForm = (canGive && item.is_active !== false)
            ? `<div class="input-group input-group-sm flex-nowrap" style="width:130px;flex:0 0 130px;">
                    <input type="number" min="1" max="${Math.max(stock, 1)}" value="1" class="form-control text-center merch-qty" data-id="${item.id}" aria-label="Qty" ${giveDisabled}>
                    <div class="input-group-append">
                        <button type="button" class="btn btn-primary merch-save" data-id="${item.id}" ${giveDisabled}>Beri</button>
                    </div>
                </div>`
            : '';

        $list.append(`<div class="list-group-item merch-item-row" data-id="${item.id}">
            <div class="d-flex align-items-start justify-content-between">
                <div class="pr-3 d-flex align-items-start" style="min-width:0;">
                    ${thumb}
                    <div>
                        <div><strong>${escapeMerchandiseText(item.name)}</strong> <small class="text-muted">${escapeMerchandiseText(item.kode)}</small> ${stockBadge} ${klinikBadge} ${inactiveBadge}</div>
                        <div class="small text-muted">${escapeMerchandiseText(item.description)}</div>
                    </div>
                </div>
                ${giveForm}
            </div>
            ${records.map(record => renderMerchRecord(record, stock, canGive)).join('')}
        </div>`);
    });

    $container.append($list);
}

function afterMerchandiseChange(pasienId) {
    loadManagePasienMerchandise(pasienId);
    if ($('#rawatjalan-table').length) {
        $('#rawatjalan-table').DataTable().ajax.reload(null, false);
    }
}

function isMerchandiseQtyValid(qty, maxQty) {
    if (!qty || qty < 1) {
        Swal.fire({ target: document.getElementById('modalManagePasien'), icon: 'warning', title: 'Qty tidak valid', text: 'Qty minimal 1.' });
        return false;
    }

    if (qty > maxQty) {
        Swal.fire({ target: document.getElementById('modalManagePasien'), icon: 'warning', title: 'Melebihi stok', text: `Qty (${qty}) melebihi yang tersedia (${maxQty}).` });
        return false;
    }

    return true;
}

function sendMerchandiseChange($button, request, pasienId) {
    $button.prop('disabled', true);
    request.done(function(){
        afterMerchandiseChange(pasienId);
    }).fail(function(xhr){
        Swal.fire({ target: document.getElementById('modalManagePasien'), icon: 'error', title: 'Gagal', text: getMerchandiseErrorMessage(xhr, 'Gagal menyimpan merchandise') });
    }).always(function(){
        $button.prop('disabled', false);
    });
}

$(document).on('click', '.merch-save', function(){
    let $button = $(this);
    let merchId = $button.data('id');
    let pasienId = $('#modalManagePasien').data('pasien-id');
    let $input = $('.merch-qty[data-id="' + merchId + '"]');
    let qty = parseInt($input.val(), 10);

    if (!pasienId || !isMerchandiseQtyValid(qty, parseInt($input.attr('max'), 10))) return;

    sendMerchandiseChange($button, $.post('/erm/pasiens/' + pasienId + '/merchandises', {
        _token: $('meta[name="csrf-token"]').attr('content'),
        merchandise_id: merchId,
        quantity: qty
    }), pasienId);
});

$(document).on('click', '.merch-record-save', function(){
    let $button = $(this);
    let pmId = $button.data('pm-id');
    let pasienId = $('#modalManagePasien').data('pasien-id');
    let $input = $('.merch-record-qty[data-pm-id="' + pmId + '"]');
    let qty = parseInt($input.val(), 10);

    if (!pasienId || !isMerchandiseQtyValid(qty, parseInt($input.attr('max'), 10))) return;

    sendMerchandiseChange($button, $.ajax({
        url: '/erm/pasiens/' + pasienId + '/merchandises/' + pmId,
        type: 'PUT',
        data: { _token: $('meta[name="csrf-token"]').attr('content'), quantity: qty }
    }), pasienId);
});

$(document).on('click', '.merch-remove', function(){
    let pmId = $(this).data('pm-id');
    let pasienId = $('#modalManagePasien').data('pasien-id');
    if (!pasienId) return;

    Swal.fire({
        target: document.getElementById('modalManagePasien'),
        icon: 'warning',
        title: 'Batalkan pemberian merchandise?',
        html: '<select id="merch-cancel-return" class="form-control mb-2">'
            + '<option value="">Apakah barangnya kembali?</option>'
            + '<option value="1">Ya, barang ada: stok dikembalikan</option>'
            + '<option value="0">Tidak, barang tidak kembali: stok tetap</option>'
            + '</select>'
            + '<input type="text" id="merch-cancel-reason" class="form-control" maxlength="255" placeholder="Alasan pembatalan">',
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'Batalkan pemberian',
        cancelButtonText: 'Kembali',
        preConfirm: function(){
            let returnStock = $('#merch-cancel-return').val();
            let reason = $.trim($('#merch-cancel-reason').val());
            if (returnStock === '') {
                Swal.showValidationMessage('Pilih apakah barangnya kembali');
                return false;
            }
            if (!reason) {
                Swal.showValidationMessage('Alasan pembatalan wajib diisi');
                return false;
            }
            return { returnStock: returnStock, reason: reason };
        }
    }).then(function(result){
        if (!result.value) return;
        $.ajax({
            url: '/erm/pasiens/' + pasienId + '/merchandises/' + pmId,
            type: 'DELETE',
            data: { _token: $('meta[name="csrf-token"]').attr('content'), reason: result.value.reason, return_stock: result.value.returnStock }
        }).done(function(response){
            afterMerchandiseChange(pasienId);
            Swal.fire({ target: document.getElementById('modalManagePasien'), icon: 'success', title: 'Berhasil', text: response.message });
        }).fail(function(xhr){
            Swal.fire({ target: document.getElementById('modalManagePasien'), icon: 'error', title: 'Gagal', text: getMerchandiseErrorMessage(xhr, 'Gagal membatalkan merchandise') });
        });
    });
});
