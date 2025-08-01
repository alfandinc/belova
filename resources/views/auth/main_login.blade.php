<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Login - SIM Klinik Belova</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="{{ asset('dastone/default/assets/css/bootstrap-dark.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('dastone/default/assets/css/app-dark.min.css') }}" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(45deg, #3a4b5c, #1e2430);
            color: #fff;
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: #232a36;
            padding: 40px 30px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
            transition: background 0.3s;
        }
        .login-container.skin {
            background: linear-gradient(135deg,#b8004c,#e83e8c);
        }
        .login-container.premiere {
            background: linear-gradient(135deg,#003366,#007bff);
        }
        .login-title {
            text-align: center;
            font-weight: 700;
            margin-bottom: 30px;
        }
        .form-group label {
            color: #fff;
        }
        .btn-login {
            width: 100%;
            background: #00B4DB;
            color: #fff;
            font-weight: 600;
            border-radius: 6px;
        }
        .logo {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo img {
            height: 50px;
        }
        .error-message {
            color: #ff6b6b;
            text-align: center;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="login-container" id="login-container">
        <div class="logo">
            <img id="login-logo" src="{{ asset('img/logo-belovacorp-bw.png') }}" alt="Belova Logo">
        </div>
        <h2 class="login-title" id="login-title">Login SIM Klinik Belova</h2>
        @if ($errors->any())
            <div class="error-message">
                {{ $errors->first() }}
            </div>
        @endif
        <form method="POST" action="{{ route('login') }}" id="login-form">
            @csrf
            <div class="form-group mb-3">
                <label for="email">Email</label>
                <input type="email" class="form-control" id="email" name="email" required autofocus>
            </div>
            <div class="form-group mb-3">
                <label for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <input type="hidden" name="clinic_choice" id="clinic_choice_input">
            <button type="submit" class="btn btn-login" id="login-btn" disabled>Login</button>
        </form>
    </div>

    <!-- Clinic Choice Modal -->
    <div class="modal fade" id="clinicChoiceModal" tabindex="-1" aria-labelledby="clinicChoiceModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="background:#232a36; color:#fff; border-radius:20px;">
          {{-- <div class="modal-header border-0">
            <h4 class="modal-title w-100 text-center" id="clinicChoiceModalLabel" style="font-weight:700;">Pilih Klinik</h4>
          </div> --}}
          <div class="modal-body text-center" style="padding:50px 20px;">
            <div class="d-flex flex-row align-items-center justify-content-center gap-3">
              <button class="btn btn-lg btn-block d-flex flex-column align-items-center justify-content-center mx-2 p-0" id="choose-skin" style="width:340px; height:340px; border-radius:20px; border-width:4px; background:linear-gradient(135deg,#b8004c,#e83e8c); color:#232a36; border:4px solid #e83e8c; overflow:hidden;">
                <img src="{{ asset('img/logo-belovaskin-bw.png') }}" alt="Belova Skin" style="width:100%; height:100%; object-fit:contain; padding:32px; display:block;">
              </button>
              <button class="btn btn-lg btn-block d-flex flex-column align-items-center justify-content-center mx-2 p-0" id="choose-premiere" style="width:340px; height:340px; border-radius:20px; border-width:4px; background:linear-gradient(135deg,#003366,#007bff); color:#232a36; border:4px solid #007bff; overflow:hidden;">
                <img src="{{ asset('img/logo-premiere-bw.png') }}" alt="Premiere Belova" style="width:100%; height:100%; object-fit:contain; padding:32px; display:block;">
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    $(document).ready(function() {
        // Show modal on page load
        $('#clinicChoiceModal').modal({backdrop: 'static', keyboard: false});
        $('#clinicChoiceModal').modal('show');

        // Disable login until choice is made
        $('#login-btn').prop('disabled', true);

        $('#choose-skin').on('click', function() {
            setClinicChoice('skin');
        });
        $('#choose-premiere').on('click', function() {
            setClinicChoice('premiere');
        });

        function setClinicChoice(choice) {
            // Set hidden input for form submit
            $('#clinic_choice_input').val(choice);
            // Change logo
            if (choice === 'skin') {
                $('#login-logo').attr('src', '{{ asset('img/logo-belovaskin-bw.png') }}');
                $('#login-title').text('Login SIM Klinik Belova Skin');
                $('#login-container').removeClass('premiere').addClass('skin');
            } else if (choice === 'premiere') {
                $('#login-logo').attr('src', '{{ asset('img/logo-premiere-bw.png') }}');
                $('#login-title').text('Login SIM Klinik Premiere Belova');
                $('#login-container').removeClass('skin').addClass('premiere');
            }
            // Enable login button
            $('#login-btn').prop('disabled', false);
            // Hide modal
            $('#clinicChoiceModal').modal('hide');
        }
    });
    </script>
</body>
</html>
