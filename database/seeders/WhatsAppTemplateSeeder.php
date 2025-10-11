<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WhatsAppTemplate;

class WhatsAppTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'key' => 'visitation',
                'name' => 'Visitation Notification',
                'description' => 'Sent when patient registers for a visit',
                'content' => "🏥 *KONFIRMASI KUNJUNGAN*\n\n" .
                           "Halo {pasien_nama},\n\n" .
                           "Terima kasih telah mendaftarkan kunjungan di Belova Clinic!\n\n" .
                           "📋 Detail Kunjungan:\n" .
                           "• Jenis: {jenis_kunjungan}\n" .
                           "• Tanggal: {tanggal_visitation}\n" .
                           "• Waktu: {waktu_kunjungan}\n" .
                           "• No. Antrian: {no_antrian}\n" .
                           "• Dokter: {dokter_nama}\n" .
                           "• Klinik: {klinik_nama}\n\n" .
                           "📍 Status: TERDAFTAR\n\n" .
                           "Mohon datang 15 menit sebelum waktu kunjungan.\n\n" .
                           "Jika ada perubahan atau pembatalan, silakan hubungi kami.\n\n" .
                           "_Terima kasih telah mempercayai Belova Clinic! 🙏_",
                'variables' => [
                    'pasien_nama' => 'Patient name',
                    'jenis_kunjungan' => 'Visit type',
                    'tanggal_visitation' => 'Visit date',
                    'waktu_kunjungan' => 'Visit time',
                    'no_antrian' => 'Queue number',
                    'dokter_nama' => 'Doctor name',
                    'klinik_nama' => 'Clinic name'
                ],
                'is_active' => true
            ],
            [
                'key' => 'appointment_reminder',
                'name' => 'Appointment Reminder',
                'description' => 'Sent as a reminder for upcoming appointments',
                'content' => "🔔 *PENGINGAT JANJI TEMU*\n\n" .
                           "Halo {pasien_nama},\n\n" .
                           "Mengingatkan janji temu Anda besok:\n" .
                           "📅 Tanggal: {tanggal_visitation}\n" .
                           "⏰ Waktu: {waktu_kunjungan}\n" .
                           "👨‍⚕️ Dokter: {dokter_nama}\n" .
                           "🏢 Lokasi: {klinik_nama}\n\n" .
                           "Mohon datang tepat waktu.\n" .
                           "Terima kasih! 🙏",
                'variables' => [
                    'pasien_nama' => 'Patient name',
                    'tanggal_visitation' => 'Visit date',
                    'waktu_kunjungan' => 'Visit time',
                    'dokter_nama' => 'Doctor name',
                    'klinik_nama' => 'Clinic name'
                ],
                'is_active' => true
            ],
            [
                'key' => 'payment_reminder',
                'name' => 'Payment Reminder',
                'description' => 'Sent for outstanding payments',
                'content' => "💳 *PENGINGAT PEMBAYARAN*\n\n" .
                           "Halo {pasien_nama},\n\n" .
                           "Kami ingin mengingatkan tentang tagihan yang belum dibayar:\n\n" .
                           "📄 No. Invoice: {invoice_number}\n" .
                           "💰 Jumlah: Rp {amount}\n" .
                           "📅 Jatuh Tempo: {due_date}\n\n" .
                           "Mohon segera melakukan pembayaran untuk menghindari keterlambatan.\n\n" .
                           "Terima kasih atas perhatiannya! 🙏",
                'variables' => [
                    'pasien_nama' => 'Patient name',
                    'invoice_number' => 'Invoice number',
                    'amount' => 'Outstanding amount',
                    'due_date' => 'Payment due date'
                ],
                'is_active' => true
            ],
            [
                'key' => 'lab_results',
                'name' => 'Lab Results Notification',
                'description' => 'Sent when lab results are ready',
                'content' => "🔬 *HASIL LABORATORIUM SIAP*\n\n" .
                           "Halo {pasien_nama},\n\n" .
                           "Hasil pemeriksaan laboratorium Anda sudah siap:\n\n" .
                           "📋 Jenis Pemeriksaan: {test_type}\n" .
                           "📅 Tanggal Pemeriksaan: {test_date}\n" .
                           "👨‍⚕️ Dokter: {dokter_nama}\n\n" .
                           "Silakan datang ke klinik untuk mengambil hasil atau konsultasi lebih lanjut.\n\n" .
                           "Terima kasih! 🙏",
                'variables' => [
                    'pasien_nama' => 'Patient name',
                    'test_type' => 'Type of test',
                    'test_date' => 'Test date',
                    'dokter_nama' => 'Doctor name'
                ],
                'is_active' => true
            ],
            [
                'key' => 'birthday_greeting',
                'name' => 'Birthday Greeting',
                'description' => 'Sent on patient birthdays',
                'content' => "🎉 *SELAMAT ULANG TAHUN*\n\n" .
                           "Halo {pasien_nama},\n\n" .
                           "Selamat ulang tahun yang ke-{age}! 🎂\n\n" .
                           "Semoga sehat selalu dan panjang umur.\n" .
                           "Terima kasih telah mempercayai Belova Clinic! 🙏\n\n" .
                           "_Salam hangat dari keluarga besar Belova_",
                'variables' => [
                    'pasien_nama' => 'Patient name',
                    'age' => 'Patient age'
                ],
                'is_active' => true
            ],
            [
                'key' => 'visitation_interactive',
                'name' => 'Interactive Visitation Confirmation',
                'description' => 'Sent when patient registers for a visit with confirmation options',
                'content' => "🏥 *KONFIRMASI KUNJUNGAN*\n\n" .
                           "Halo {pasien_nama},\n\n" .
                           "Terima kasih telah mendaftarkan kunjungan di Belova Clinic!\n\n" .
                           "📋 Detail Kunjungan:\n" .
                           "• Jenis: {jenis_kunjungan}\n" .
                           "• Tanggal: {tanggal_visitation}\n" .
                           "• Waktu: {waktu_kunjungan}\n" .
                           "• No. Antrian: {no_antrian}\n" .
                           "• Dokter: {dokter_nama}\n" .
                           "• Klinik: {klinik_nama}\n\n" .
                           "📍 Status: TERDAFTAR\n\n" .
                           "Silakan konfirmasi kehadiran Anda:\n" .
                           "*1* - Konfirmasi Kehadiran ✅\n" .
                           "*2* - Batalkan Kunjungan ❌\n\n" .
                           "Balas dengan angka *1* atau *2*\n\n" .
                           "_Konfirmasi akan expired dalam 24 jam_",
                'variables' => [
                    'pasien_nama' => 'Patient name',
                    'jenis_kunjungan' => 'Visit type',
                    'tanggal_visitation' => 'Visit date',
                    'waktu_kunjungan' => 'Visit time',
                    'no_antrian' => 'Queue number',
                    'dokter_nama' => 'Doctor name',
                    'klinik_nama' => 'Clinic name'
                ],
                'is_active' => true
            ],
            [
                'key' => 'confirmation_confirmed',
                'name' => 'Visit Confirmation Response',
                'description' => 'Sent when patient confirms their visit (option 1)',
                'content' => "✅ *KUNJUNGAN DIKONFIRMASI*\n\n" .
                           "Terima kasih {pasien_nama}!\n\n" .
                           "Kunjungan Anda telah dikonfirmasi:\n" .
                           "📅 {tanggal_visitation} - {waktu_kunjungan}\n" .
                           "👨‍⚕️ {dokter_nama}\n" .
                           "🎫 No. Antrian: {no_antrian}\n\n" .
                           "📍 Mohon datang 15 menit sebelum waktu kunjungan.\n\n" .
                           "Jika ada perubahan mendadak, silakan hubungi kami.\n\n" .
                           "_Terima kasih telah mempercayai Belova Clinic! 🙏_",
                'variables' => [
                    'pasien_nama' => 'Patient name',
                    'tanggal_visitation' => 'Visit date',
                    'waktu_kunjungan' => 'Visit time',
                    'dokter_nama' => 'Doctor name',
                    'no_antrian' => 'Queue number'
                ],
                'is_active' => true
            ],
            [
                'key' => 'confirmation_cancelled',
                'name' => 'Visit Cancellation Response',
                'description' => 'Sent when patient cancels their visit (option 2)',
                'content' => "❌ *KUNJUNGAN DIBATALKAN*\n\n" .
                           "Baik {pasien_nama},\n\n" .
                           "Kunjungan Anda telah dibatalkan:\n" .
                           "📅 {tanggal_visitation} - {waktu_kunjungan}\n" .
                           "👨‍⚕️ {dokter_nama}\n\n" .
                           "Jika Anda ingin membuat janji temu lagi, silakan hubungi kami atau daftar melalui sistem.\n\n" .
                           "📞 Kontak: [Nomor Klinik]\n" .
                           "🌐 Website: [Website Klinik]\n\n" .
                           "_Terima kasih atas pengertiannya. Semoga sehat selalu! 🙏_",
                'variables' => [
                    'pasien_nama' => 'Patient name',
                    'tanggal_visitation' => 'Visit date',
                    'waktu_kunjungan' => 'Visit time',
                    'dokter_nama' => 'Doctor name'
                ],
                'is_active' => true
            ],
            [
                'key' => 'invalid_response',
                'name' => 'Invalid Response Message',
                'description' => 'Sent when patient sends invalid response',
                'content' => "❓ *RESPONS TIDAK DIKENALI*\n\n" .
                           "Maaf, saya tidak memahami respons Anda.\n\n" .
                           "Silakan balas dengan:\n" .
                           "*1* - Untuk konfirmasi kehadiran ✅\n" .
                           "*2* - Untuk membatalkan kunjungan ❌\n\n" .
                           "_Cukup ketik angka 1 atau 2_",
                'variables' => [],
                'is_active' => true
            ]
        ];

        foreach ($templates as $template) {
            WhatsAppTemplate::updateOrCreate(
                ['key' => $template['key']],
                $template
            );
        }
    }
}