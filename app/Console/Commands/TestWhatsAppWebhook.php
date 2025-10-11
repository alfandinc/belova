<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WhatsAppConversation;
use App\Services\WhatsAppChatbotService;
use App\Services\WhatsAppService;

class TestWhatsAppWebhook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:test-webhook {phone} {message}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test WhatsApp webhook functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $phoneNumber = $this->argument('phone');
        $message = $this->argument('message');

        $this->info("Testing webhook for phone: {$phoneNumber}, message: {$message}");

        // Check if there's an active conversation
        $conversation = WhatsAppConversation::findActiveConversation($phoneNumber);
        
        if ($conversation) {
            $this->info("Found active conversation: ID {$conversation->id}, Type: {$conversation->conversation_type}");
        } else {
            $this->warn("No active conversation found. Creating test conversation...");
            
            // Create a test conversation
            $conversation = WhatsAppConversation::createConversation(
                $phoneNumber,
                WhatsAppConversation::TYPE_VISITATION_CONFIRMATION,
                ['visitation_id' => 1, 'patient_id' => 1],
                24
            );
            
            $this->info("Created test conversation: ID {$conversation->id}");
        }

        // Test the chatbot service
        $chatbotService = app(WhatsAppChatbotService::class);
        $result = $chatbotService->processIncomingMessage($phoneNumber, $message);

        $this->info("Chatbot response: " . json_encode($result, JSON_PRETTY_PRINT));

        // Check conversation state after processing
        $conversation->refresh();
        $this->info("Conversation state after processing: {$conversation->conversation_state}");

        return Command::SUCCESS;
    }
}
