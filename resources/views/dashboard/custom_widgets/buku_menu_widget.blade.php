@php
    $widgetKey = 'buku-menu-widget-' . ($widget->id ?? 'default');
    $obatTableId = $widgetKey . '-obat-table';
    $tindakanTableId = $widgetKey . '-tindakan-table';
    $labtestTableId = $widgetKey . '-labtest-table';
    $searchInputId = $widgetKey . '-search';
    $clearButtonId = $widgetKey . '-search-clear';
    $tabNavId = $widgetKey . '-tabs';
    $tabContentId = $widgetKey . '-content';
@endphp

<div class="card h-100 border-0 shadow-sm dashboard-widget-card" style="border-radius: 18px; overflow: hidden;">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap mb-2">
            <div>
                <h5 class="card-title mb-1">{{ $widget->widget_name ?? 'Buku Menu' }}</h5>
            </div>
            <a href="{{ route('buku-menu.index') }}" class="btn btn-outline-secondary btn-sm mt-2 mt-md-0">Buka Halaman</a>
        </div>

        <div class="mb-2">
            <div class="input-group input-group-sm" style="max-width: 420px;">
                <input id="{{ $searchInputId }}" type="search" class="form-control" placeholder="Search Obat, Tindakan, Lab Test">
                <div class="input-group-append">
                    <button id="{{ $clearButtonId }}" class="btn btn-outline-secondary" type="button">Clear</button>
                </div>
            </div>
        </div>

        <ul class="nav nav-tabs" id="{{ $tabNavId }}" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link active" id="{{ $widgetKey }}-obat-tab" data-toggle="tab" href="#{{ $widgetKey }}-tab-obat" role="tab" aria-controls="{{ $widgetKey }}-tab-obat" aria-selected="true">Obat</a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" id="{{ $widgetKey }}-tindakan-tab" data-toggle="tab" href="#{{ $widgetKey }}-tab-tindakan" role="tab" aria-controls="{{ $widgetKey }}-tab-tindakan" aria-selected="false">Tindakan</a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" id="{{ $widgetKey }}-labtest-tab" data-toggle="tab" href="#{{ $widgetKey }}-tab-labtest" role="tab" aria-controls="{{ $widgetKey }}-tab-labtest" aria-selected="false">Lab Test</a>
            </li>
        </ul>

        <div class="tab-content mt-2" id="{{ $tabContentId }}">
            <div class="tab-pane fade show active" id="{{ $widgetKey }}-tab-obat" role="tabpanel" aria-labelledby="{{ $widgetKey }}-obat-tab">
                <div class="table-responsive">
                    <table id="{{ $obatTableId }}" class="table table-bordered table-striped w-100 buku-menu-widget-table">
                        <thead>
                            <tr>
                                <th style="width: 60px;">No</th>
                                <th>Nama Obat</th>
                                <th style="width: 120px;">Satuan</th>
                                <th style="width: 140px;">Stok Total</th>
                                <th style="width: 180px;">Harga Non Fornas</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="{{ $widgetKey }}-tab-tindakan" role="tabpanel" aria-labelledby="{{ $widgetKey }}-tindakan-tab">
                <div class="table-responsive">
                    <table id="{{ $tindakanTableId }}" class="table table-bordered table-striped w-100 buku-menu-widget-table">
                        <thead>
                            <tr>
                                <th style="width: 60px;">No</th>
                                <th>Nama Tindakan</th>
                                <th style="width: 220px;">Kode Tindakan</th>
                                <th style="width: 160px;">Tipe</th>
                                <th style="width: 220px;">List Harga</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="{{ $widgetKey }}-tab-labtest" role="tabpanel" aria-labelledby="{{ $widgetKey }}-labtest-tab">
                <div class="table-responsive">
                    <table id="{{ $labtestTableId }}" class="table table-bordered table-striped w-100 buku-menu-widget-table">
                        <thead>
                            <tr>
                                <th style="width: 60px;">No</th>
                                <th>Nama Lab Test</th>
                                <th style="width: 200px;">Kategori</th>
                                <th style="width: 160px;">Harga</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .dashboard-widget-card .buku-menu-widget-subtitle {
        line-height: 1.3;
    }

    .dashboard-widget-card .nav-tabs .nav-link {
        padding: 0.45rem 0.8rem;
        font-size: 0.9rem;
    }

    .dashboard-widget-card .dataTables_wrapper .dataTables_length,
    .dashboard-widget-card .dataTables_wrapper .dataTables_filter,
    .dashboard-widget-card .dataTables_wrapper .dataTables_info,
    .dashboard-widget-card .dataTables_wrapper .dataTables_paginate {
        font-size: 12px;
        margin-top: 0.35rem;
        margin-bottom: 0;
    }

    .dashboard-widget-card .dataTables_wrapper .dataTables_length select {
        height: calc(1.5em + 0.35rem + 2px);
        padding: 0.1rem 1.4rem 0.1rem 0.4rem;
        font-size: 12px;
    }

    .dashboard-widget-card .dataTables_wrapper .paginate_button {
        padding: 0.2rem 0.45rem !important;
        font-size: 12px;
    }

    .buku-menu-widget-table th,
    .buku-menu-widget-table td {
        vertical-align: middle;
        padding: 0.4rem 0.45rem;
        font-size: 12px;
    }

    .buku-menu-widget-table thead th {
        white-space: nowrap;
    }

    .buku-menu-widget-table tbody td {
        line-height: 1.35;
    }

    .buku-menu-widget-table .price-block {
        width: 100%;
    }

    .buku-menu-widget-table .price-block .row {
        display: block;
        padding: 2px 8px;
        border-top: 1px solid #f1f3f5;
    }

    .buku-menu-widget-table .price-block .row:first-child {
        border-top: none;
    }

    .buku-menu-widget-table .price-block .label {
        color: #6c757d;
        display: block;
    }

    .buku-menu-widget-table .list-harga .price-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 2px 8px;
        border-top: 1px solid #f1f3f5;
    }

    .buku-menu-widget-table .list-harga .price-row:first-child {
        border-top: none;
    }

    .buku-menu-widget-table .list-harga .original-price {
        color: #d9534f;
        text-decoration: line-through;
        margin-right: 8px;
    }

    .buku-menu-widget-table .list-harga .active-price {
        font-weight: 700;
        text-align: right;
    }

    .buku-menu-widget-table .kode-list {
        margin: 0;
        padding-left: 18px;
    }

    .buku-menu-widget-table .kode-list li {
        list-style-type: disc;
        margin: 0;
        padding: 0;
        color: #333;
    }

    .dashboard-widget-card .table-responsive {
        margin-bottom: 0;
    }
</style>
@endpush

@push('scripts')
<script>
    $(function () {
        var widgetKey = @json($widgetKey);
        var obatTableSelector = '#{{ $obatTableId }}';
        var tindakanTableSelector = '#{{ $tindakanTableId }}';
        var labtestTableSelector = '#{{ $labtestTableId }}';
        var searchInputSelector = '#{{ $searchInputId }}';
        var clearButtonSelector = '#{{ $clearButtonId }}';
        var tabNavSelector = '#{{ $tabNavId }} a[data-toggle="tab"]';

        if (!$(obatTableSelector).length || $.fn.dataTable.isDataTable(obatTableSelector)) {
            return;
        }

        var obatTable = $(obatTableSelector).DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ route('buku-menu.data') }}',
            dom: 'rtip',
            pageLength: 5,
            order: [[1, 'asc']],
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'nama', name: 'nama' },
                { data: 'satuan', name: 'satuan' },
                { data: 'total_stok', name: 'total_stok', searchable: false },
                { data: 'harga_nonfornas', name: 'harga_nonfornas', searchable: false }
            ]
        });

        var tindakanTable = $(tindakanTableSelector).DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ route('buku-menu.tindakan-data') }}',
            dom: 'rtip',
            pageLength: 5,
            order: [[1, 'asc']],
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'nama', name: 'nama' },
                { data: 'kode_tindakan_names', name: 'kode_tindakan_names', orderable: false, searchable: false },
                { data: 'jenis_harga', name: 'jenis_harga', orderable: false, searchable: false },
                { data: 'list_harga', name: 'list_harga', orderable: false, searchable: false }
            ],
            columnDefs: [
                { targets: 2, className: 'text-left' },
                { targets: 3, className: 'text-left' },
                { targets: 1, className: 'text-left' }
            ]
        });

        var labtestTable = $(labtestTableSelector).DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ route('buku-menu.labtest-data') }}',
            dom: 'rtip',
            pageLength: 5,
            order: [[1, 'asc']],
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'nama', name: 'nama' },
                { data: 'kategori', name: 'kategori', orderable: false, searchable: false },
                { data: 'harga', name: 'harga', orderable: false, searchable: false }
            ],
            columnDefs: [
                { targets: 3, className: 'text-left' },
                { targets: 1, className: 'text-left' }
            ]
        });

        $(tabNavSelector).on('shown.bs.tab', function (e) {
            obatTable.columns.adjust();
            tindakanTable.columns.adjust();
            labtestTable.columns.adjust();
        });

        var currentTable = obatTable;
        $(tabNavSelector).on('shown.bs.tab', function (e) {
            var target = $(e.target).attr('href');
            if (target === '#{{ $widgetKey }}-tab-obat') {
                currentTable = obatTable;
            } else if (target === '#{{ $widgetKey }}-tab-tindakan') {
                currentTable = tindakanTable;
            } else if (target === '#{{ $widgetKey }}-tab-labtest') {
                currentTable = labtestTable;
            }

            var currentValue = $(searchInputSelector).val();
            currentTable.search(currentValue).draw();
        });

        function debounce(fn, delay) {
            var timer = null;
            return function () {
                var context = this;
                var args = arguments;
                clearTimeout(timer);
                timer = setTimeout(function () {
                    fn.apply(context, args);
                }, delay);
            };
        }

        $(searchInputSelector).on('input', debounce(function () {
            var value = $(this).val();
            if (currentTable) {
                currentTable.search(value).draw();
            }
        }, 300));

        $(clearButtonSelector).on('click', function () {
            $(searchInputSelector).val('');
            if (currentTable) {
                currentTable.search('').draw();
            }
        });
    });
</script>
@endpush