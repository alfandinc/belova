<div class="modal-header">
    <h5 class="modal-title">Ganti Password</h5>
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>

<div class="modal-body">
    <form id="passwordChangeForm" action="{{ route('hrd.employee.password.update') }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="form-group">
            <label for="current_password">Password Saat Ini <span class="text-danger">*</span></label>
            <input type="password" class="form-control" id="current_password" name="current_password" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password Baru <span class="text-danger">*</span></label>
            <input type="password" class="form-control" id="password" name="password" required minlength="8">
            <small class="form-text text-muted">Minimal 8 karakter</small>
        </div>
        
        <div class="form-group">
            <label for="password_confirmation">Konfirmasi Password Baru <span class="text-danger">*</span></label>
            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required minlength="8">
        </div>
        
        <div class="text-right">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        </div>
    </form>
</div>