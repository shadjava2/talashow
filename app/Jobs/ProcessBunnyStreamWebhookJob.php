<?php

namespace App\Jobs;

use App\Models\VideoWebhookLog;
use App\Services\Video\VideoMigrationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ProcessBunnyStreamWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $videoGuid,
        public ?int $webhookLogId = null
    ) {}

    public function handle(VideoMigrationService $migration): void
    {
        try {
            $migration->syncAllFromBunnyGuid($this->videoGuid);
            $this->markWebhookLog(true, null);
        } catch (\Throwable $e) {
            Log::channel('video_migration')->error('bunny_webhook_job', [
                'phase' => 'job',
                'status' => 'error',
                'target_video_guid' => $this->videoGuid,
                'error' => $e->getMessage(),
            ]);
            $this->markWebhookLog(false, $e->getMessage());
            throw $e;
        }
    }

    protected function markWebhookLog(bool $ok, ?string $error): void
    {
        if ($this->webhookLogId === null || ! Schema::hasTable('video_webhook_logs')) {
            return;
        }

        VideoWebhookLog::query()->whereKey($this->webhookLogId)->update([
            'processed' => $ok,
            'processed_at' => now(),
            'error_message' => $error,
        ]);
    }
}
