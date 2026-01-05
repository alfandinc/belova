<div class="card">
    <div class="card-header">
        <div class="row">
            <div class="col-md-6">
                <h4>Daftar Pengajuan Cuti/Libur Saya</h4>
            </div>
            <div class="col-md-6 text-right">
                <div class="row">
                    <div class="col-md-6">
                        <div class="alert alert-info py-2 mb-0">
                            <p class="mb-0"><strong>Saldo Cuti Tahunan:</strong> {{ auth()->user()->employee->jatahLibur->jatah_cuti_tahunan ?? 0 }} hari</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-info py-2 mb-0">
                            <p class="mb-0"><strong>Saldo Ganti Libur:</strong> {{ auth()->user()->employee->jatahLibur->jatah_ganti_libur ?? 0 }} hari</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <table id="tableLiburKaryawan" class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Jenis Libur</th>
                    <th>Tanggal</th>
                    <th>Jumlah Hari</th>
                    <th>Status Manager</th>
                    <th>Status HRD</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <!-- Data will be loaded via Ajax -->
            </tbody>
        </table>
    </div>
</div>