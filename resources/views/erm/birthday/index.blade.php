@extends('layouts.erm.app')

@section('title', 'Ulang Tahun Pasien')

@section('navbar')
@include('layouts.erm.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <h4 class="page-title">Ulang Tahun Pasien</h4>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/erm">Beranda</a></li>
                            <li class="breadcrumb-item active">Ulang Tahun Pasien</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h4 class="card-title mb-0">Daftar Ulang Tahun Pasien</h4>
                        </div>
                        <div class="col-md-6">
                            <div class="row justify-content-end">
                                <div class="col-md-5 pr-0">
                                    <div class="form-group mb-0">
                                        <label class="mb-1">KLINIK:</label>
                                        <select id="klinik-filter" class="form-control form-control-sm">
                                            <option value="">Semua Klinik</option>
                                            @foreach($kliniks as $klinik)
                                                <option value="{{ $klinik->id }}">{{ $klinik->nama }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-7">
                                    <div class="form-group mb-0">
                                        <label class="mb-1">RENTANG TANGGAL:</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control form-control-sm" id="date-range">
                                            <div class="input-group-append">
                                                <span class="input-group-text"><i class="mdi mdi-calendar"></i></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="birthday-table" class="table table-bordered dt-responsive nowrap" 
                            style="border-collapse: collapse; width: 100%;">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nama</th>
                                    <th>Tanggal Lahir</th>
                                    <th>No. HP</th>
                                    <th>Usia</th>
                                    <th>Klinik</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Birthday Greeting Modal -->
<div class="modal fade" id="greetingModal" tabindex="-1" role="dialog" aria-labelledby="greetingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="greetingModalLabel">Ucapkan Selamat Ulang Tahun</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="greetingForm">
                    <input type="hidden" id="pasien-id">
                    <div class="form-group">
                        <label for="patient-name">Nama Pasien</label>
                        <input type="text" class="form-control" id="patient-name" readonly>
                    </div>
                    <div class="form-group">
                        <label for="greeting-prefix">Sapaan</label>
                        <select class="form-control" id="greeting-prefix">
                            <option value="Bapak">Bapak</option>
                            <option value="Ibu">Ibu</option>
                            <option value="Kakak">Kakak</option>
                            <option value="Adik">Adik</option>
                            <option value="">Tanpa Sapaan</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="phone-number">Nomor WhatsApp</label>
                        <input type="text" class="form-control" id="phone-number" placeholder="Contoh: 08123456789">
                        <small class="text-muted">Nomor akan diformat otomatis untuk WhatsApp</small>
                    </div>
                    <div class="form-group">
                        <label for="greeting-message">Pesan Ucapan</label>
                        <textarea class="form-control" id="greeting-message" rows="4"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-success" id="send-whatsapp">Kirim via WhatsApp</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Initialize date range picker
        var today = '{{ $today }}';
        $('#date-range').daterangepicker({
            startDate: moment(today),
            endDate: moment(today),
            locale: {
                format: 'DD/MM/YYYY'
            },
            opens: 'left'
        });

        // Initialize DataTable with today's birthdays
        let birthdayTable = $('#birthday-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('erm.birthday.data') }}",
                data: function(d) {
                    var dateRange = $('#date-range').val().split(' - ');
                    d.start_date = moment(dateRange[0], 'DD/MM/YYYY').format('YYYY-MM-DD');
                    d.end_date = moment(dateRange[1], 'DD/MM/YYYY').format('YYYY-MM-DD');
                    d.klinik_id = $('#klinik-filter').val();
                }
            },
            columns: [
                { data: 'id', name: 'id' },
                { data: 'nama', name: 'nama' },
                { data: 'tanggal', name: 'tanggal' },
                { data: 'no_hp', name: 'no_hp' },
                { data: 'usia', name: 'usia' },
                { data: 'klinik', name: 'klinik' },
                { data: 'status', name: 'status' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
            order: [[2, 'asc']], // Sort by tanggal_lahir
            pageLength: 10,
            language: {
                paginate: {
                    previous: "<i class='mdi mdi-chevron-left'>",
                    next: "<i class='mdi mdi-chevron-right'>"
                },
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                search: "Cari:",
                lengthMenu: "Show _MENU_ entries"
            },
            drawCallback: function() {
                $('.dataTables_paginate > .pagination').addClass('pagination-rounded');
            }
        });

        // Refresh data when date range or klinik changes
        $('#date-range').on('apply.daterangepicker', function(ev, picker) {
            birthdayTable.ajax.reload();
        });
        
        $('#klinik-filter').change(function() {
            birthdayTable.ajax.reload();
        });

        // Format phone number for WhatsApp (remove non-digits, ensure 62 prefix)
        function formatPhoneNumber(phoneNumber) {
            // Remove non-numeric characters
            phoneNumber = phoneNumber.replace(/\D/g, '');
            
            // Check if it starts with '0' and replace with Indonesia code '62'
            if (phoneNumber.startsWith('0')) {
                phoneNumber = '62' + phoneNumber.substring(1);
            }
            
            return phoneNumber;
        }

        // Handle click on "Ucapkan" button
        $(document).on('click', '.send-greeting', function() {
            let patientId = $(this).data('id');
            let patientName = $(this).data('name');
            let phoneNumber = $(this).data('phone') || '';
            let prefix = $(this).data('prefix');
            
            // Set hidden input
            $('#pasien-id').val(patientId);
            
            // Set prefix in dropdown
            $('#greeting-prefix').val(prefix);
            
            // Set patient name
            $('#patient-name').val(patientName);
            
            // Set phone number
            $('#phone-number').val(phoneNumber);
            
            // Update greeting message based on prefix and name
            updateGreetingMessage(prefix, patientName);
            
            // Show modal
            $('#greetingModal').modal('show');
        });

        // Update greeting message when prefix changes
        $('#greeting-prefix').change(function() {
            let prefix = $(this).val();
            let patientName = $('#patient-name').val();
            updateGreetingMessage(prefix, patientName);
        });

        // Function to update greeting message
        function updateGreetingMessage(prefix, patientName) {
            let greeting = "";
            
            if (prefix) {
                greeting = `Selamat Ulang Tahun, ${prefix} ${patientName}! Semoga panjang umur, sehat selalu, dan bahagia. Terima kasih telah menjadi bagian dari keluarga kami.`;
            } else {
                greeting = `Selamat Ulang Tahun, ${patientName}! Semoga panjang umur, sehat selalu, dan bahagia. Terima kasih telah menjadi bagian dari keluarga kami.`;
            }
            
            $('#greeting-message').val(greeting);
        }

        // Handle send via WhatsApp button
        $('#send-whatsapp').click(function() {
            let pasienId = $('#pasien-id').val();
            let phoneNumber = formatPhoneNumber($('#phone-number').val());
            let message = $('#greeting-message').val();
            
            if (!phoneNumber) {
                alert('Mohon masukkan nomor WhatsApp yang valid');
                return;
            }
            
            // Mark as sent in database
            $.ajax({
                url: "{{ route('erm.birthday.mark-sent') }}",
                type: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    pasien_id: pasienId,
                    message: message
                },
                success: function(response) {
                    if (response.success) {
                        // Create WhatsApp URL and open in new tab
                        let whatsappUrl = `https://wa.me/${phoneNumber}?text=${encodeURIComponent(message)}`;
                        window.open(whatsappUrl, '_blank');
                        
                        // Close modal and refresh table
                        $('#greetingModal').modal('hide');
                        birthdayTable.ajax.reload();
                    }
                },
                error: function(xhr) {
                    alert('Terjadi kesalahan saat mencatat ucapan ulang tahun');
                }
            });
        });
    });
</script>
@endsection