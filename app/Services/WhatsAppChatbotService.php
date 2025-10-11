<?php

namespace App\Services;

use App\Models\WhatsAppConversation;
use App\Models\WhatsAppTemplate;
use App\Models\ERM\Visitation;
use Illuminate\Support\Facades\Log;

class WhatsAppChatbotService
{
    protected $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Process incoming message from patient
     */
    public function processIncomingMessage($phoneNumber, $messageText)
    {
        Log::info('Processing chatbot message', [
            'phone' => $phoneNumber,
            'message' => $messageText
        ]);

        // Find active conversation for this phone number
        $conversation = WhatsAppConversation::findActiveConversation($phoneNumber);

        Log::info('Active conversation found', [
            'conversation_id' => $conversation ? $conversation->id : null,
            'has_valid_visitation' => $conversation ? ($conversation->getVisitation() ? 'yes' : 'no') : 'n/a'
        ]);

        // If no active conversation OR active conversation has no valid visitation,
        // try to find the most recent one (within last 24 hours) that has a valid visitation
        if (!$conversation || !$conversation->getVisitation()) {
            $recentConversations = WhatsAppConversation::where('phone_number', $phoneNumber)
                ->where('conversation_type', WhatsAppConversation::TYPE_VISITATION_CONFIRMATION)
                ->where('created_at', '>=', now()->subHours(24))
                ->orderBy('created_at', 'desc')
                ->get();
                
            // Find the first conversation that has a valid visitation
            foreach ($recentConversations as $conv) {
                Log::info('Checking recent conversation', [
                    'conversation_id' => $conv->id,
                    'has_valid_visitation' => $conv->getVisitation() ? 'yes' : 'no'
                ]);
                
                if ($conv->getVisitation()) {
                    $conversation = $conv;
                    break;
                }
            }
        }

        Log::info('Final conversation selected', [
            'conversation_id' => $conversation ? $conversation->id : null
        ]);

        if (!$conversation) {
            // No recent conversation with valid visitation, ignore message or send general response
            return $this->handleNoActiveConversation($phoneNumber, $messageText);
        }

        // Process based on conversation type
        switch ($conversation->conversation_type) {
            case WhatsAppConversation::TYPE_VISITATION_CONFIRMATION:
                return $this->handleVisitationConfirmation($conversation, $messageText);
            
            case WhatsAppConversation::TYPE_APPOINTMENT_REMINDER:
                return $this->handleAppointmentResponse($conversation, $messageText);
            
            default:
                return $this->handleGenericResponse($conversation, $messageText);
        }
    }

    /**
     * Handle visitation confirmation responses
     */
    private function handleVisitationConfirmation($conversation, $messageText)
    {
        $response = trim(strtolower($messageText));
        
        Log::info('Handling visitation confirmation', [
            'conversation_id' => $conversation->id,
            'message' => $messageText,
            'response' => $response,
            'conversation_type' => $conversation->conversation_type,
            'context_data' => $conversation->context_data
        ]);
        
        // Get the visitation data
        $visitation = $conversation->getVisitation();
        
        Log::info('Visitation lookup result', [
            'conversation_id' => $conversation->id,
            'visitation_found' => $visitation ? true : false,
            'visitation_id' => $visitation ? $visitation->id : null
        ]);
        
        if (!$visitation) {
            Log::error('Visitation not found for conversation', ['conversation_id' => $conversation->id]);
            return ['status' => 'error', 'message' => 'Data kunjungan tidak ditemukan'];
        }

        switch ($response) {
            case '1':
            case 'ya':
            case 'iya':
            case 'konfirmasi':
                return $this->confirmVisitation($conversation, $visitation);
                
            case '2':
            case 'tidak':
            case 'batal':
            case 'batalkan':
                return $this->cancelVisitation($conversation, $visitation);
                
            default:
                return $this->sendInvalidResponseMessage($conversation->phone_number);
        }
    }

    /**
     * Confirm visitation
     */
    private function confirmVisitation($conversation, $visitation)
    {
        try {
            // Update conversation status
            $conversation->confirm();
            
            // Update visitation status if needed
            // $visitation->update(['status' => 'confirmed']);
            
            // Send confirmation message
            $template = WhatsAppTemplate::getByKey('confirmation_confirmed');
            
            if ($template) {
                $replacements = [
                    'pasien_nama' => $visitation->pasien->nama ?? '',
                    'tanggal_visitation' => $visitation->tanggal_visitation ? date('d/m/Y', strtotime($visitation->tanggal_visitation)) : '',
                    'waktu_kunjungan' => $visitation->waktu_kunjungan ? date('H:i', strtotime($visitation->waktu_kunjungan)) : 'Tidak ditentukan',
                    'dokter_nama' => $visitation->dokter && $visitation->dokter->user ? 'Dr. ' . $visitation->dokter->user->name : 'Tidak ditentukan',
                    'no_antrian' => $visitation->no_antrian ?? 'Tidak ada'
                ];
                
                $message = $template->processContent($replacements);
                $this->whatsappService->sendMessage($conversation->phone_number, $message);
            }
            
            Log::info('Visitation confirmed', [
                'conversation_id' => $conversation->id,
                'visitation_id' => $visitation->id
            ]);
            
            return ['status' => 'confirmed', 'message' => 'Kunjungan dikonfirmasi'];
            
        } catch (\Exception $e) {
            Log::error('Error confirming visitation', [
                'error' => $e->getMessage(),
                'conversation_id' => $conversation->id
            ]);
            
            return ['status' => 'error', 'message' => 'Gagal mengkonfirmasi kunjungan'];
        }
    }

    /**
     * Cancel visitation
     */
    private function cancelVisitation($conversation, $visitation)
    {
        try {
            // Update conversation status
            $conversation->cancel();
            
            // Update visitation status if needed
            // $visitation->update(['status' => 'cancelled']);
            
            // Send cancellation message
            $template = WhatsAppTemplate::getByKey('confirmation_cancelled');
            
            if ($template) {
                $replacements = [
                    'pasien_nama' => $visitation->pasien->nama ?? '',
                    'tanggal_visitation' => $visitation->tanggal_visitation ? date('d/m/Y', strtotime($visitation->tanggal_visitation)) : '',
                    'waktu_kunjungan' => $visitation->waktu_kunjungan ? date('H:i', strtotime($visitation->waktu_kunjungan)) : 'Tidak ditentukan',
                    'dokter_nama' => $visitation->dokter && $visitation->dokter->user ? 'Dr. ' . $visitation->dokter->user->name : 'Tidak ditentukan'
                ];
                
                $message = $template->processContent($replacements);
                $this->whatsappService->sendMessage($conversation->phone_number, $message);
            }
            
            Log::info('Visitation cancelled', [
                'conversation_id' => $conversation->id,
                'visitation_id' => $visitation->id
            ]);
            
            return ['status' => 'cancelled', 'message' => 'Kunjungan dibatalkan'];
            
        } catch (\Exception $e) {
            Log::error('Error cancelling visitation', [
                'error' => $e->getMessage(),
                'conversation_id' => $conversation->id
            ]);
            
            return ['status' => 'error', 'message' => 'Gagal membatalkan kunjungan'];
        }
    }

    /**
     * Send invalid response message
     */
    private function sendInvalidResponseMessage($phoneNumber)
    {
        $template = WhatsAppTemplate::getByKey('invalid_response');
        
        if ($template) {
            $message = $template->content;
            $this->whatsappService->sendMessage($phoneNumber, $message);
        }
        
        return ['status' => 'invalid', 'message' => 'Respons tidak valid'];
    }

    /**
     * Handle when no active conversation exists
     */
    private function handleNoActiveConversation($phoneNumber, $messageText)
    {
        Log::info('No active conversation found', [
            'phone' => $phoneNumber,
            'message' => $messageText
        ]);
        
        // Could send a general response or ignore
        return ['status' => 'no_conversation', 'message' => 'Tidak ada percakapan aktif'];
    }

    /**
     * Handle appointment response
     */
    private function handleAppointmentResponse($conversation, $messageText)
    {
        // Similar to visitation confirmation
        return $this->handleVisitationConfirmation($conversation, $messageText);
    }

    /**
     * Handle generic response
     */
    private function handleGenericResponse($conversation, $messageText)
    {
        Log::info('Generic response handler', [
            'conversation_type' => $conversation->conversation_type,
            'message' => $messageText
        ]);
        
        return ['status' => 'handled', 'message' => 'Pesan diproses'];
    }

    /**
     * Send interactive visitation message and create conversation
     */
    public function sendInteractiveVisitationMessage($visitation)
    {
        try {
            $phoneNumber = $visitation->pasien->no_hp ?? '';
            
            if (!$phoneNumber) {
                Log::warning('No phone number for patient', ['visitation_id' => $visitation->id]);
                return ['success' => false, 'error' => 'No phone number'];
            }

            // Clean phone number
            $cleanPhone = $this->cleanPhoneNumber($phoneNumber);
            
            // Check if conversation already exists for this visitation
            $existingConversation = WhatsAppConversation::findByVisitationId($visitation->id);
            
            if ($existingConversation) {
                Log::info('Conversation already exists for visitation', [
                    'visitation_id' => $visitation->id,
                    'conversation_id' => $existingConversation->id
                ]);
                
                // Use existing conversation
                $conversation = $existingConversation;
            } else {
                // Create conversation record
                $conversation = WhatsAppConversation::createConversation(
                    $cleanPhone,
                    WhatsAppConversation::TYPE_VISITATION_CONFIRMATION,
                    [
                        'visitation_id' => $visitation->id,
                        'patient_id' => $visitation->pasien->id
                    ],
                    24 // expires in 24 hours
                );
            }

            // Get interactive template
            $template = WhatsAppTemplate::getByKey('visitation_interactive');
            
            if (!$template) {
                Log::error('Interactive visitation template not found');
                return ['success' => false, 'error' => 'Template not found'];
            }

            // Prepare replacement values
            $jenisKunjungan = '';
            switch ($visitation->jenis_kunjungan) {
                case 1: $jenisKunjungan = 'Konsultasi Dokter'; break;
                case 2: $jenisKunjungan = 'Pembelian Produk'; break;
                case 3: $jenisKunjungan = 'Laboratorium'; break;
                default: $jenisKunjungan = 'Kunjungan'; break;
            }

            $replacements = [
                'pasien_nama' => $visitation->pasien->nama ?? '',
                'jenis_kunjungan' => $jenisKunjungan,
                'tanggal_visitation' => $visitation->tanggal_visitation ? date('d/m/Y', strtotime($visitation->tanggal_visitation)) : '',
                'waktu_kunjungan' => $visitation->waktu_kunjungan ? date('H:i', strtotime($visitation->waktu_kunjungan)) : 'Tidak ditentukan',
                'no_antrian' => $visitation->no_antrian ?? 'Tidak ada',
                'dokter_nama' => $visitation->dokter && $visitation->dokter->user ? 'Dr. ' . $visitation->dokter->user->name : 'Tidak ditentukan',
                'klinik_nama' => $visitation->klinik ? $visitation->klinik->nama_klinik : 'Tidak ditentukan'
            ];

            $message = $template->processContent($replacements);
            
            // Send the message
            $result = $this->whatsappService->sendMessage($cleanPhone, $message);
            
            Log::info('Interactive visitation message sent', [
                'visitation_id' => $visitation->id,
                'conversation_id' => $conversation->id,
                'phone' => $cleanPhone,
                'result' => $result
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('Error sending interactive visitation message', [
                'error' => $e->getMessage(),
                'visitation_id' => $visitation->id
            ]);
            
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Clean phone number format
     */
    private function cleanPhoneNumber($number)
    {
        // Remove all non-digit characters
        $clean = preg_replace('/[^0-9]/', '', $number);
        
        // Add country code if not present (assuming Indonesia +62)
        if (strlen($clean) > 0) {
            if (substr($clean, 0, 1) === '0') {
                $clean = '62' . substr($clean, 1);
            } elseif (substr($clean, 0, 2) !== '62') {
                $clean = '62' . $clean;
            }
        }
        
        return $clean;
    }
}