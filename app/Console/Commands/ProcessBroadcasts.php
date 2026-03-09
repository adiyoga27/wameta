<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Broadcast;
use App\Models\BroadcastContact;
use App\Models\ChatMessage;
use App\Models\Device;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;

class ProcessBroadcasts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'broadcast:process {--limit=100 : Maximum number of messages to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process pending broadcasts in the background';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = $this->option('limit');
        
        $this->info("Starting broadcast processing. Limit: {$limit}");
        
        // Find broadcasts that are in 'sending' status
        $broadcasts = Broadcast::where('status', 'sending')
                               ->with(['device', 'messageTemplate'])
                               ->get();
                               
        if ($broadcasts->isEmpty()) {
            $this->info('No active broadcasts found.');
            return;
        }

        $processedCount = 0;

        foreach ($broadcasts as $broadcast) {
            if ($processedCount >= $limit) {
                break;
            }

            $device = $broadcast->device;
            $template = $broadcast->messageTemplate;
            
            // Re-check balance just in case
            if ($device->balance <= 0) {
                $broadcast->update([
                    'status' => 'failed',
                ]);
                Log::warning("Broadcast ID {$broadcast->id} failed due to insufficient balance on device {$device->name}");
                continue;
            }

            $waService = new WhatsAppService($device);
            
            // Prepare header for media template if needed
            $headerData = [];
            if (in_array($template->header_type, ['IMAGE', 'VIDEO', 'DOCUMENT'])) {
                if ($template->header_media_path) {
                    $filePath = storage_path('app/public/' . $template->header_media_path);
                    if (file_exists($filePath)) {
                        $mimeType = mime_content_type($filePath);
                        // We use direct media_id caching to avoid re-uploading the same file every minute
                        $cacheKey = "broadcast_{$broadcast->id}_media_id";
                        $mediaId = cache($cacheKey);
                        
                        if (!$mediaId) {
                            $uploadResult = $waService->uploadMedia($filePath, $mimeType);
                            if ($uploadResult['success']) {
                                $mediaId = $uploadResult['media_id'];
                                cache([$cacheKey => $mediaId], now()->addHours(24));
                            } else {
                                $broadcast->update(['status' => 'failed']);
                                Log::error("Failed uploading media for Broadcast {$broadcast->id}: " . ($uploadResult['error'] ?? 'Unknown'));
                                continue;
                            }
                        }

                        $typeKey = strtolower($template->header_type);
                        $headerData = [
                            'type' => $typeKey,
                            $typeKey => ['id' => $mediaId]
                        ];
                    }
                }
            }

            // Get pending contacts for this broadcast
            $pendingContacts = $broadcast->broadcastContacts()
                ->whereIn('status', ['pending', 'failed'])
                ->whereNull('wa_message_id')
                ->limit($limit - $processedCount)
                ->get();
                
            if ($pendingContacts->isEmpty()) {
                // If there are no pending contacts but status is sending, mark it as completed
                $this->updateBroadcastCounts($broadcast, true);
                continue;
            }

            $this->info("Processing {$pendingContacts->count()} contacts for broadcast ID: {$broadcast->id}");

            foreach ($pendingContacts as $bc) {
                $contact = $bc->contact;
                
                $result = $waService->sendTemplateMessage(
                    $contact->phone,
                    $template->name,
                    $template->language,
                    [], 
                    $headerData
                );

                if ($result['success']) {
                    $bc->update([
                        'status' => 'sent',
                        'wa_message_id' => $result['message_id'] ?? null,
                    ]);

                    ChatMessage::create([
                        'device_id' => $device->id,
                        'contact_number' => $contact->phone,
                        'contact_name' => $contact->name ?? $contact->phone,
                        'direction' => 'out',
                        'message_type' => 'template',
                        'message_body' => "[Template: {$template->name}]\n" . $template->body,
                        'wa_message_id' => $result['message_id'] ?? null,
                        'wa_timestamp' => now(),
                        'status' => 'sent',
                    ]);
                } else {
                    $bc->update([
                        'status' => 'failed',
                        'error_message' => $result['error'] ?? 'Unknown error',
                    ]);
                }
                
                $processedCount++;
                
                // Small delay to avoid API rate limiting
                usleep(150000); // 150ms
            }

            // Update stats after processing batch
            $this->updateBroadcastCounts($broadcast);
        }

        $this->info("Processed batch of {$processedCount} messages.");
    }
    
    protected function updateBroadcastCounts(Broadcast $broadcast, $forceComplete = false)
    {
        $sentCount = $broadcast->broadcastContacts()->whereIn('status', ['sent', 'delivered', 'read'])->count();
        $failedCount = $broadcast->broadcastContacts()->where('status', 'failed')->whereNotNull('error_message')->count();
        $deliveredCount = $broadcast->broadcastContacts()->whereIn('status', ['delivered', 'read'])->count();
        $readCount = $broadcast->broadcastContacts()->where('status', 'read')->count();
        
        $pendingCount = $broadcast->broadcastContacts()->where('status', 'pending')->count();
        
        $newStatus = ($pendingCount === 0 || $forceComplete) ? 'completed' : 'sending';

        $broadcast->update([
            'status' => $newStatus,
            'sent' => $sentCount,
            'failed' => $failedCount,
            'delivered' => $deliveredCount,
            'read' => $readCount,
        ]);
        
        if ($newStatus === 'completed') {
            $this->info("Broadcast ID {$broadcast->id} fully completed.");
        }
    }
}
