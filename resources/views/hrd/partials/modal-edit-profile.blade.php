{{-- filepath: c:\wamp64\www\belova\resources\views\hrd\employee\partials\edit-profile-modal.blade.php --}}
<div class="modal-header bg-primary text-white">
    <h5 class="modal-title" id="editProfileModalLabel">Edit Profil</h5>
    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<form id="profileUpdateForm" action="{{ route('hrd.employee.profile.update') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <div class="modal-body">
        <ul class="nav nav-tabs mb-3" id="profileTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="personal-tab" data-toggle="tab" href="#personal" role="tab">Data Pribadi</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="documents-tab" data-toggle="tab" href="#documents" role="tab">Dokumen</a>
            </li>
        </ul>
        
        <div class="tab-content" id="profileTabContent">
            <!-- Personal Information Tab -->
            <div class="tab-pane fade show active" id="personal" role="tabpanel">
                <div class="form-group">
                    <label>Foto Profil</label>
                    <div class="custom-file">
                        <input type="file" class="custom-file-input" id="photo" name="photo" accept="image/*">
                        <label class="custom-file-label" for="photo">Pilih foto...</label>
                    </div>
                    @if($employee->photo)
                        <div class="mt-2">
                            <img src="{{ asset('storage/'.$employee->photo) }}" alt="Current Photo" class="img-thumbnail" style="height: 100px;">
                        </div>
                    @endif
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Nama</label>
                        <input type="text" name="nama" class="form-control" value="{{ old('nama', $employee->nama) }}" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label>NIK</label>
                        <input type="text" name="nik" class="form-control" value="{{ old('nik', $employee->nik) }}">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Tempat Lahir</label>
                        <input type="text" name="tempat_lahir" class="form-control" value="{{ old('tempat_lahir', $employee->tempat_lahir) }}">
                    </div>
                    <div class="form-group col-md-6">
                        <label>Tanggal Lahir</label>
                        <input type="date" name="tanggal_lahir" class="form-control" value="{{ old('tanggal_lahir', $employee->tanggal_lahir ? $employee->tanggal_lahir->format('Y-m-d') : '') }}">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Pendidikan</label>
                        <input type="text" name="pendidikan" class="form-control" value="{{ old('pendidikan', $employee->pendidikan) }}">
                    </div>
                    <div class="form-group col-md-6">
                        <label>No. HP</label>
                        <input type="text" name="no_hp" class="form-control" value="{{ old('no_hp', $employee->no_hp) }}">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label>Alamat</label>
                        <input type="text" name="alamat" class="form-control" value="{{ old('alamat', $employee->alamat) }}">
                    </div>
                    <div class="form-group col-md-12">
                        <label>No Darurat</label>
                        <input type="text" name="no_darurat" class="form-control" value="{{ old('no_darurat', $employee->no_darurat) }}" placeholder="Nomor darurat (Emergency Contact)">
                    </div>
                   
                </div>
                
            </div>
            
            <!-- Documents Tab -->
            <div class="tab-pane fade" id="documents" role="tabpanel">
                <div class="form-group">
                    <label>CV</label>
                    <div class="custom-file">
                        <input type="file" class="custom-file-input" id="doc_cv" name="doc_cv">
                        <label class="custom-file-label" for="doc_cv">Pilih file...</label>
                    </div>
                    @if($employee->doc_cv)
                        <a href="{{ asset('storage/'.$employee->doc_cv) }}" target="_blank" class="d-block mt-1">
                            <i class="fas fa-file-pdf"></i> Lihat CV
                        </a>
                    @endif
                </div>
                
                <div class="form-group">
                    <label>KTP</label>
                    <div class="custom-file">
                        <input type="file" class="custom-file-input" id="doc_ktp" name="doc_ktp">
                        <label class="custom-file-label" for="doc_ktp">Pilih file...</label>
                    </div>
                    @if($employee->doc_ktp)
                        <a href="{{ asset('storage/'.$employee->doc_ktp) }}" target="_blank" class="d-block mt-1">
                            <i class="fas fa-file-pdf"></i> Lihat KTP
                        </a>
                    @endif
                </div>
                
                <div class="form-group">
                    <!-- Kontrak upload removed -->
                </div>
                
                <div class="form-group">
                    <label>Dokumen Pendukung</label>
                    <div class="custom-file">
                        <input type="file" class="custom-file-input" id="doc_pendukung" name="doc_pendukung">
                        <label class="custom-file-label" for="doc_pendukung">Pilih file...</label>
                    </div>
                    @if($employee->doc_pendukung)
                        <a href="{{ asset('storage/'.$employee->doc_pendukung) }}" target="_blank" class="d-block mt-1">
                            <i class="fas fa-file-pdf"></i> Lihat Dokumen Pendukung
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
    </div>
</form>