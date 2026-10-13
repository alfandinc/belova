{{-- Password check before opening payroll pages (My Payroll / Gaji Dokter items in the sidebar). --}}
<!-- Password Verification Modal -->
<div class="modal fade" id="passwordVerificationModal" tabindex="-1" role="dialog" aria-labelledby="passwordVerificationModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="passwordVerificationModalLabel">Verifikasi Password</h5>
                <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="passwordVerificationForm">
                    <div class="form-group">
                        <label for="payrollVerifyPassword">Masukkan password Anda untuk melihat slip gaji:</label>
                        <input type="password" class="form-control" id="payrollVerifyPassword" name="password" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" onclick="verifyPasswordAndGetSlip()">Verifikasi</button>
            </div>
        </div>
    </div>
</div>

<script>
// nextRedirect can be set by callers to override where to go after successful verification
window.nextRedirect = null;

function getPasswordVerificationModal() {
    const modalElement = document.getElementById('passwordVerificationModal');

    if (!modalElement) {
        return null;
    }

    if (window.bootstrap && window.bootstrap.Modal) {
        if (typeof window.bootstrap.Modal.getOrCreateInstance === 'function') {
            return window.bootstrap.Modal.getOrCreateInstance(modalElement);
        }

        if (typeof window.bootstrap.Modal === 'function') {
            if (!modalElement._passwordVerificationModalInstance) {
                modalElement._passwordVerificationModalInstance = new window.bootstrap.Modal(modalElement);
            }

            return modalElement._passwordVerificationModalInstance;
        }
    }

    return null;
}

function showPasswordVerificationModal() {
    const modal = getPasswordVerificationModal();

    if (modal) {
        modal.show();
        return;
    }

    if (window.jQuery && typeof window.jQuery.fn.modal === 'function') {
        window.jQuery('#passwordVerificationModal').modal('show');
    }
}

function hidePasswordVerificationModal() {
    const modal = getPasswordVerificationModal();

    if (modal) {
        modal.hide();
        return;
    }

    if (window.jQuery && typeof window.jQuery.fn.modal === 'function') {
        window.jQuery('#passwordVerificationModal').modal('hide');
    }
}

function checkSlipGaji(e) {
    e.preventDefault();
    // reset redirect to default behavior (use response.url)
    window.nextRedirect = null;
    // Show password verification modal
    showPasswordVerificationModal();
}

function checkGajiDokter(e) {
    e.preventDefault();
    // Set redirect to the Gaji Dokter index page after successful verification
    window.nextRedirect = '{{ route('hrd.payroll.slip_gaji_dokter.index') }}';
    showPasswordVerificationModal();
}

function verifyPasswordAndGetSlip() {
    const password = $("#payrollVerifyPassword").val();
    
    if (!password) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Password harus diisi'
        });
        return;
    }

    $.ajax({
        url: '{{ route('hrd.payroll.slip_gaji.my_slip') }}',
        type: 'POST',
        data: {
            password: password,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            hidePasswordVerificationModal();

            if (!response.success) {
                Swal.fire({
                    icon: response.type,
                    title: response.title,
                    text: response.message
                });
            } else {
                // Password is correct and slip is available
                hidePasswordVerificationModal();
                // If a custom redirect was requested (e.g. Gaji Dokter), use it.
                if (window.nextRedirect) {
                    var redirect = window.nextRedirect;
                    // reset for next time
                    window.nextRedirect = null;
                    window.location.href = redirect;
                } else if (response.url) {
                    // Default: redirect to URL returned by server (slip karyawan or slip dokter)
                    window.location.href = response.url;
                } else {
                    Swal.fire({
                        icon: response.type || 'error',
                        title: response.title || 'Error',
                        text: response.message || 'Data slip gaji tidak ditemukan.'
                    });
                }
            }

            // Clear the password field
            $("#payrollVerifyPassword").val('');
        },
        error: function(xhr) {
            if (xhr.status === 401) {
                Swal.fire({
                    icon: 'error',
                    title: 'Password Salah',
                    text: 'Password yang Anda masukkan tidak sesuai.'
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Terjadi kesalahan saat mengambil slip gaji.'
                });
            }
            // Clear the password field
            $("#payrollVerifyPassword").val('');
        }
    });
}

// Handle enter key in password field (jQuery is loaded at the end of the layout)
document.addEventListener('DOMContentLoaded', function () {
    var input = document.getElementById('payrollVerifyPassword');
    if (!input) return;
    input.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            verifyPasswordAndGetSlip();
        }
    });
});
</script>
