<?php

return [
    'service_url' => env('WHATSAPP_SERVICE_URL', 'http://localhost:3000'),
    'enabled' => env('WHATSAPP_ENABLED', false),
    'timeout' => env('WHATSAPP_TIMEOUT', 30),
    'retry_attempts' => env('WHATSAPP_RETRY_ATTEMPTS', 3),
    'retry_delay' => env('WHATSAPP_RETRY_DELAY', 5),
    
    // Message Templates
    'templates' => [
        'visitation' => env('WHATSAPP_TEMPLATE_VISITATION', 
            "🏥 *BELOVA CLINIC - KONFIRMASI PENDAFTARAN*\n\n" .
            "Halo *{pasien_nama}*,\n\n" .
            "Pendaftaran kunjungan Anda telah berhasil!\n\n" .
            "📋 *Detail Kunjungan:*\n" .
            "• No. RM: *{pasien_id}*\n" .
            "• Jenis: {jenis_kunjungan}\n" .
            "• Tanggal: {tanggal_visitation}\n" .
            "• Waktu: {waktu_kunjungan}\n" .
            "• No. Antrian: *{no_antrian}*\n" .
            "• Dokter: {dokter_nama}\n" .
            "• Klinik: {klinik_nama}\n\n" .
            "✅ *Status: TERDAFTAR*\n\n" .
            "Mohon datang 15 menit sebelum waktu kunjungan.\n\n" .
            "Jika ada perubahan atau pembatalan, silakan hubungi kami.\n\n" .
            "Terima kasih telah mempercayai layanan kami! 🙏\n\n" .
            "_Pesan otomatis dari Sistem Belova_"
        )
    ]
];