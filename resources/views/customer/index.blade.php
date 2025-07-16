<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Satisfaction Survey</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #f8bbd0 0%, #90caf9 100%) !important;
            font-family: 'Montserrat', Arial, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        body.klinik-select-bg {
            background: linear-gradient(120deg, #f8bbd0 0%, #90caf9 100%);
        }
        body.blue-theme {
            background: linear-gradient(120deg, #e3f2fd 0%, #90caf9 100%);
        }
        .survey-container {
            max-width: 700px;
            margin: 48px auto;
            background: rgba(255,255,255,0.95);
            border-radius: 28px;
            box-shadow: 0 8px 32px rgba(206,147,216,0.18);
            padding: 54px 36px 36px 36px;
            border: 2px solid #f06292;
            position: relative;
        }
        .survey-container.blue-theme {
            box-shadow: 0 8px 32px rgba(33,150,243,0.13);
            border: 2px solid #1976d2;
        }
        .survey-title-outer {
            width: 100%;
            text-align: center;
            margin-top: 48px;
            margin-bottom: 0;
        }
        .survey-title {
            display: inline-block;
            text-align: center;
            font-size: 2.7rem;
            color: #ad1457;
            margin-bottom: 14px;
            letter-spacing: 2px;
            font-family: 'Montserrat', cursive, Arial, sans-serif;
        }
        .blue-theme .survey-title {
            color: #1976d2;
            font-family: 'Montserrat', Arial, sans-serif;
            letter-spacing: 1px;
        }
        .survey-desc {
            text-align: center;
            color: #7b1fa2;
            margin-bottom: 40px;
            font-size: 1.35rem;
        }
        .survey-desc.blue-theme {
            color: #1976d2;
        }
        .question-block {
            display: none;
            margin-bottom: 40px;
            padding-bottom: 24px;
            border-bottom: 1px solid #f8bbd0;
            animation: fadeIn 0.4s;
        }
        .question-block.active {
            display: block;
        }
        .question-block.blue-theme {
            border-bottom: 1px solid #90caf9;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .question-text {
            font-size: 1.6rem;
            color: #8e24aa;
            margin-bottom: 22px;
            font-weight: 700;
            letter-spacing: 1px;
        }
        .question-text.blue-theme {
            color: #1976d2;
        }
        .emoji-survey {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 44px;
        }
        .emoji-option {
            display: flex;
            flex-direction: column;
            align-items: center;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .emoji-option input[type="radio"] {
            display: none;
        }
        .emoji {
            font-size: 70px;
            transition: transform 0.2s, filter 0.2s;
            filter: grayscale(0.3) brightness(1.1) drop-shadow(0 2px 8px #f8bbd0);
        }
        .blue-theme .emoji {
            filter: grayscale(0.1) brightness(1.1) drop-shadow(0 2px 8px #90caf9);
        }
        .emoji-option input[type="radio"]:checked + .emoji {
            transform: scale(1.35) rotate(-5deg);
            filter: none;
            border-bottom: 4px solid #d500f9;
        }
        .blue-theme .emoji-option input[type="radio"]:checked + .emoji {
            border-bottom: 4px solid #1976d2;
        }
        .label {
            margin-top: 12px;
            font-size: 1.1rem;
            color: #ad1457;
            font-weight: 600;
        }
        .label.blue-theme {
            color: #1976d2;
        }
        .step-btn, .submit-btn {
            display: inline-block;
            margin: 36px 12px 0 12px;
            padding: 18px 54px;
            font-size: 1.35rem;
            background: linear-gradient(90deg, #f06292 0%, #ba68c8 100%);
            color: #fff;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: bold;
            box-shadow: 0 2px 8px rgba(186,104,200,0.12);
            transition: background 0.2s;
        }
        .blue-theme .step-btn, .blue-theme .submit-btn {
            background: linear-gradient(90deg, #1976d2 0%, #64b5f6 100%);
            box-shadow: 0 2px 8px rgba(33,150,243,0.10);
        }
        .step-btn:hover, .submit-btn:hover {
            background: linear-gradient(90deg, #ba68c8 0%, #f06292 100%);
        }
        .blue-theme .step-btn:hover, .blue-theme .submit-btn:hover {
            background: linear-gradient(90deg, #64b5f6 0%, #1976d2 100%);
        }
        .step-btn[disabled] {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .step-indicator {
            text-align: center;
            margin-bottom: 24px;
            color: #d500f9;
            font-weight: 700;
            letter-spacing: 1px;
            font-size: 1.2rem;
        }
        .step-indicator.blue-theme {
            color: #1976d2;
        }
        @media (max-width: 900px) {
            .survey-container {
                padding: 18px 8px;
                max-width: 99vw;
            }
            .survey-title {
                font-size: 2rem;
            }
            .survey-desc {
                font-size: 1.1rem;
            }
            .question-text {
                font-size: 1.2rem;
            }
            .emoji {
                font-size: 48px;
            }
            .step-btn, .submit-btn {
                font-size: 1.1rem;
                padding: 14px 30px;
            }
        }
        @media (max-width: 600px) {
            .survey-container {
                padding: 10px 2px;
                max-width: 100vw;
            }
            .emoji-survey {
                gap: 4px;
                flex-wrap: wrap;
            }
            .emoji-option {
                min-width: 48px;
            }
            .emoji {
                font-size: 32px;
            }
            .question-block {
                margin-bottom: 18px;
                padding-bottom: 10px;
            }
            .step-btn, .submit-btn {
                width: 100%;
                font-size: 1rem;
                padding: 12px 0;
            }
            .survey-title {
                font-size: 1.3rem;
            }
            .survey-desc {
                font-size: 1rem;
            }
            .question-text {
                font-size: 1rem;
            }
        }
        @media (max-width: 400px) {
            .survey-title {
                font-size: 1.1rem;
            }
            .emoji {
                font-size: 20px;
            }
        }
        /* Butterfly decorations */
        .butterfly {
            position: absolute;
            z-index: 2;
            pointer-events: none;
            opacity: 0.7;
        }
        .butterfly.b1 { top: -30px; left: -30px; width: 70px; transform: rotate(-15deg); }
        .butterfly.b2 { top: -40px; right: -30px; width: 60px; transform: rotate(10deg); }
        .butterfly.b3 { bottom: -30px; left: 10px; width: 50px; transform: rotate(8deg); }
        .butterfly.b4 { bottom: -25px; right: 0px; width: 40px; transform: rotate(-12deg); }
        .blue-theme .butterfly { display: none; }
        /* Klinik selection */
        .klinik-select-container {
            max-width: 420px;
            margin: 80px auto 0 auto;
            background: rgba(255,255,255,0.97);
            border-radius: 22px;
            box-shadow: 0 8px 32px rgba(206,147,216,0.18);
            padding: 48px 24px 36px 24px;
            border: 2px solid #f06292;
            text-align: center;
            position: relative;
        }
        .klinik-select-title {
            font-size: 2rem;
            color: #ad1457;
            margin-bottom: 32px;
            font-family: 'Montserrat', cursive, Arial, sans-serif;
            letter-spacing: 1px;
        }
        .klinik-btn {
            display: block;
            width: 100%;
            margin: 18px 0;
            padding: 22px 0;
            font-size: 1.25rem;
            font-weight: bold;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            background: linear-gradient(90deg, #f06292 0%, #ba68c8 100%);
            color: #fff;
            box-shadow: 0 2px 8px rgba(186,104,200,0.10);
            transition: background 0.2s, transform 0.2s;
        }
        .klinik-btn:hover {
            background: linear-gradient(90deg, #ba68c8 0%, #f06292 100%);
            transform: scale(1.03);
        }
        .klinik-btn.blue {
            background: linear-gradient(90deg, #1976d2 0%, #64b5f6 100%);
        }
        .klinik-btn.blue:hover {
            background: linear-gradient(90deg, #64b5f6 0%, #1976d2 100%);
        }
        .source-option {
            display: flex;
            align-items: center;
            gap: 14px;
            font-size: 1.15rem;
            cursor: pointer;
            margin-bottom: 2px;
            font-weight: 600;
        }
        .source-option input[type="radio"] {
            margin-right: 4px;
        }
        .icon {
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .icon img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            filter: drop-shadow(0 2px 6px #f8bbd0);
        }
        .source-label {
            font-size: 1.08rem;
            font-weight: 600;
            color: #8e24aa;
        }
        .blue-theme .source-label {
            color: #1976d2;
        }
        .source-survey {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 44px;
            margin-top: 24px;
        }
        .source-option {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            font-size: 1.15rem;
            cursor: pointer;
            font-weight: 600;
        }
        .source-option input[type="radio"] {
            margin-bottom: 4px;
        }
        .icon {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .icon img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            filter: drop-shadow(0 2px 6px #f8bbd0);
        }
        .source-label {
            font-size: 1.08rem;
            font-weight: 600;
            color: #8e24aa;
        }
        .blue-theme .source-label {
            color: #1976d2;
        }
        @media (max-width: 900px) {
            .source-survey {
                gap: 18px;
            }
            .icon {
                width: 24px;
                height: 24px;
            }
        }
        @media (max-width: 600px) {
            .source-survey {
                gap: 4px;
                flex-wrap: wrap;
            }
            .source-option {
                min-width: 48px;
            }
            .icon {
                width: 20px;
                height: 20px;
            }
        }
        .survey-btn-row {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 16px;
            margin-top: 24px;
        }
        .step-btn, .submit-btn {
            margin: 0 8px;
        }
        @media (max-width: 600px) {
            .survey-btn-row {
                flex-direction: column;
                gap: 8px;
            }
            .step-btn, .submit-btn {
                width: 100%;
                margin: 0;
            }
        }
        .center-question {
            text-align: center;
            font-size: 2.1rem;
            font-weight: 800;
            margin-bottom: 38px;
            margin-top: 32px;
            letter-spacing: 1px;
            line-height: 1.3;
        }
        @media (max-width: 900px) {
            .center-question {
                font-size: 1.4rem;
                margin-bottom: 24px;
                margin-top: 18px;
            }
        }
        @media (max-width: 600px) {
            .center-question {
                font-size: 1.1rem;
                margin-bottom: 14px;
                margin-top: 10px;
            }
        }
    </style>
</head>
<body>
    @if (!isset($klinik))
        <div class="survey-title-outer">
            <div class="survey-title">Survei Kepuasan Pelanggan</div>
        </div>
        <div class="klinik-select-container">
            <div class="klinik-select-title">Pilih Klinik</div>
            <form method="GET" action="{{ route('customer.survey') }}">
                <button type="submit" name="klinik" value="Klinik Pratama Belova Skin" class="klinik-btn">Klinik Pratama Belova Skin</button>
                <button type="submit" name="klinik" value="Klinik Utama Premiere Belova" class="klinik-btn blue">Klinik Utama Premiere Belova</button>
            </form>
        </div>
    @else
        @if(session('success'))
            <div id="thankYouMessage" style="position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:999;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#f8bbd0 0%,#90caf9 100%);">
                <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;">
                    <div style="font-size:2.5rem;font-weight:900;color:#ad1457;letter-spacing:1px;margin-bottom:32px;text-align:center;box-shadow:0 4px 24px #f8bbd0;padding:40px 32px 32px 32px;background:rgba(255,255,255,0.92);border-radius:24px;max-width:480px;">
                        {{ session('success') }}
                    </div>
                    <button onclick="window.location='{{ route('customer.survey') }}'" style="margin-top:24px;padding:20px 54px;font-size:1.35rem;font-weight:bold;border-radius:16px;border:none;background:linear-gradient(90deg,#f06292 0%,#ba68c8 100%);color:#fff;box-shadow:0 2px 12px rgba(186,104,200,0.13);cursor:pointer;transition:background 0.2s;">Kembali ke Pilih Klinik</button>
                </div>
            </div>
        @else
            <div id="thankYouMessage" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:999;align-items:center;justify-content:center;background:linear-gradient(135deg,#f8bbd0 0%,#90caf9 100%);">
                <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;">
                    <div style="font-size:2.5rem;font-weight:900;color:#ad1457;letter-spacing:1px;margin-bottom:32px;text-align:center;box-shadow:0 4px 24px #f8bbd0;padding:40px 32px 32px 32px;background:rgba(255,255,255,0.92);border-radius:24px;max-width:480px;">
                        Terima kasih atas partisipasi Anda!
                    </div>
                    <button onclick="window.location='{{ route('customer.survey') }}'" style="margin-top:24px;padding:20px 54px;font-size:1.35rem;font-weight:bold;border-radius:16px;border:none;background:linear-gradient(90deg,#f06292 0%,#ba68c8 100%);color:#fff;box-shadow:0 2px 12px rgba(186,104,200,0.13);cursor:pointer;transition:background 0.2s;">Kembali ke Pilih Klinik</button>
                </div>
            </div>
        @endif
        @if(!session('success') && isset($klinik))
            <div class="survey-title-outer">
                <div class="survey-title" id="surveyTitle">Survei Kepuasan Pelanggan</div>
            </div>
            <div id="surveyContainer" class="survey-container">
                <!-- Butterfly SVGs -->
                <svg class="butterfly b1" viewBox="0 0 64 64"><g><ellipse cx="16" cy="32" rx="16" ry="24" fill="#f06292"/><ellipse cx="48" cy="32" rx="16" ry="24" fill="#ba68c8"/><ellipse cx="32" cy="32" rx="8" ry="16" fill="#fff"/><ellipse cx="32" cy="32" rx="4" ry="12" fill="#ce93d8"/></g></svg>
                <svg class="butterfly b2" viewBox="0 0 64 64"><g><ellipse cx="16" cy="32" rx="16" ry="24" fill="#ba68c8"/><ellipse cx="48" cy="32" rx="16" ry="24" fill="#f06292"/><ellipse cx="32" cy="32" rx="8" ry="16" fill="#fff"/><ellipse cx="32" cy="32" rx="4" ry="12" fill="#f8bbd0"/></g></svg>
                <svg class="butterfly b3" viewBox="0 0 64 64"><g><ellipse cx="16" cy="32" rx="16" ry="24" fill="#f8bbd0"/><ellipse cx="48" cy="32" rx="16" ry="24" fill="#ce93d8"/><ellipse cx="32" cy="32" rx="8" ry="16" fill="#fff"/><ellipse cx="32" cy="32" rx="4" ry="12" fill="#ba68c8"/></g></svg>
                <svg class="butterfly b4" viewBox="0 0 64 64"><g><ellipse cx="16" cy="32" rx="16" ry="24" fill="#ce93d8"/><ellipse cx="48" cy="32" rx="16" ry="24" fill="#f8bbd0"/><ellipse cx="32" cy="32" rx="8" ry="16" fill="#fff"/><ellipse cx="32" cy="32" rx="4" ry="12" fill="#f06292"/></g></svg>
                <form id="surveyForm" method="POST" action="{{ route('customer.survey') }}">
                    @csrf
                    <input type="hidden" name="klinik" value="{{ $klinik }}">
                    <div class="step-indicator" id="stepIndicator"><span id="stepText"></span></div>
                    @foreach($questions as $i => $question)
                        <div class="question-block{{ $i === 0 ? ' active' : '' }}" data-step="{{ $i+1 }}" data-qid="{{ $question->id }}">
                            <div class="question-text center-question">{{ $question->question_text }}</div>
                            @if($question->question_type === 'emoji_scale')
                                <div class="emoji-survey">
                                    @php
                                        $emojis = [
                                            1 => ['emoji' => '😟', 'label' => 'Sangat Buruk'],
                                            2 => ['emoji' => '😞', 'label' => 'Buruk'],
                                            3 => ['emoji' => '😐', 'label' => 'Biasa Saja'],
                                            4 => ['emoji' => '😊', 'label' => 'Baik'],
                                            5 => ['emoji' => '😄', 'label' => 'Sangat Baik'],
                                        ];
                                    @endphp
                                    @foreach($emojis as $val => $data)
                                        <label class="emoji-option">
                                            <input type="radio" name="q{{ $question->id }}" value="{{ $val }}" {{ $i === 0 ? 'required' : '' }}>
                                            <span class="emoji">{{ $data['emoji'] }}</span>
                                            <span class="label">{{ $data['label'] }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            @elseif($question->question_type === 'multiple_choice')
                                <div class="source-survey">
                                    @foreach($question->options as $opt)
                                        @php
                                            $icon = '';
                                            if ($opt === 'Tiktok') $icon = 'https://cdn.jsdelivr.net/gh/simple-icons/simple-icons/icons/tiktok.svg';
                                            elseif ($opt === 'Instagram') $icon = 'https://cdn.jsdelivr.net/gh/simple-icons/simple-icons/icons/instagram.svg';
                                            elseif ($opt === 'Google') $icon = 'https://cdn.jsdelivr.net/gh/simple-icons/simple-icons/icons/google.svg';
                                            elseif ($opt === 'Teman') $icon = 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/icons/people-fill.svg';
                                            elseif ($opt === 'Lainnya') $icon = 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/icons/question-circle-fill.svg';
                                        @endphp
                                        <label class="source-option">
                                            <input type="radio" name="q{{ $question->id }}" value="{{ $opt }}">
                                            @if($icon)
                                                <span class="icon"><img src="{{ $icon }}" alt="{{ $opt }}"></span>
                                            @endif
                                            <span class="source-label">{{ $opt }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                    <div class="survey-btn-row">
                        <button type="button" class="step-btn" id="prevBtn">Sebelumnya</button>
                        <button type="button" class="step-btn" id="nextBtn">Selanjutnya</button>
                        <button type="submit" class="submit-btn" id="submitBtn" style="display:none;">Kirim</button>
                    </div>
                </form>
            </div>
        @endif
    @endif
<script>
$(function() {
    var totalSteps = $('.question-block').length;
    var currentStep = 1;
    function showStep(step) {
        $('.question-block').removeClass('active');
        $('.question-block[data-step="'+step+'"]').addClass('active');
        $('#stepText').text('');
        $('#prevBtn').prop('disabled', step === 1);
        if (step === totalSteps) {
            $('#nextBtn').hide();
            $('#submitBtn').show();
        } else {
            $('#nextBtn').show();
            $('#submitBtn').hide();
        }
    }
    function validateStep(step) {
        var qid = $('.question-block[data-step="'+step+'"]').data('qid');
        return $('input[name="q'+qid+'"]:checked').length > 0;
    }
    $('#nextBtn').click(function() {
        if (!validateStep(currentStep)) {
            alert('Silakan pilih jawaban untuk melanjutkan.');
            return;
        }
        if (currentStep < totalSteps) {
            currentStep++;
            showStep(currentStep);
        }
    });
    $('#prevBtn').click(function() {
        if (currentStep > 1) {
            currentStep--;
            showStep(currentStep);
        }
    });
    $('.emoji-option input[type="radio"]').on('change', function() {
        var step = $(this).closest('.question-block').data('step');
        if (step < totalSteps) {
            setTimeout(function() {
                $('#nextBtn').click();
            }, 200);
        }
    });
    $('#surveyForm').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var formData = form.serialize();
        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': $('input[name="_token"]').val()
            },
            success: function(res) {
                $('#surveyContainer').hide();
                $('#thankYouMessage').fadeIn(300);
            },
            error: function(xhr) {
                alert('Terjadi kesalahan. Mohon cek kembali isian Anda.');
            }
        });
    });
    showStep(currentStep);
});
</script>
</body>
</html>