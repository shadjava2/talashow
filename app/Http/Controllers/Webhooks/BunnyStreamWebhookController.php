<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessBunnyStreamWebhookJob;
use App\Models\Episode;
use App\Models\VideoAsset;
use App\Models\VideoProviderMapping;
use App\Models\VideoWebhookLog;
use App\Services\SecurityAuditService;
use App\Services\Video\BunnyStreamService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class BunnyStreamWebhookController extends Controller
{
    public function __construct(
        protected BunnyStreamService $bunnyStream
    ) {}

    public function __invoke(Request $request)
    {
        $raw = $request->getContent();

        if (! $this->bunnyStream->verifyWebhook($request)) {
            SecurityAuditService::securityEvent('bunny_webhook_invalid_signature', 'high', [], $request);

            return response()->json(['ok' => false, 'message' => 'signature_invalide'], 401);
        }

        $payload = $request->json()->all();
        if (! is_array($payload)) {
            $payload = [];
        }

        Log::channel('video_migration')->info('bunny_webhook', [
            'phase' => 'webhook',
            'status' => 'received',
            'payload_keys' => array_keys($payload),
        ]);

        $logId = null;
        if (Schema::hasTable('video_webhook_logs')) {
            $log = VideoWebhookLog::query()->create([
                'provider' => 'bunny_stream',
                'event_name' => is_string($payload['EventType'] ?? null) ? $payload['EventType'] : (is_string($payload['Event'] ?? null) ? $payload['Event'] : null),
                'payload_json' => $payload,
                'headers_json' => $request->headers->all(),
                'processed' => false,
            ]);
            $logId = $log->id;
        }

        $guid = $this->extractGuid($payload);
        if ($guid === null) {
            if ($logId) {
                VideoWebhookLog::query()->whereKey($logId)->update([
                    'processed' => true,
                    'processed_at' => now(),
                    'error_message' => 'ignored_no_guid',
                ]);
            }

            return response()->json(['ok' => true, 'ignored' => true]);
        }

        $hasWork = VideoProviderMapping::query()->where('target_video_guid', $guid)->exists()
            || Episode::query()->where('external_video_id', $guid)->exists()
            || (Schema::hasTable('video_assets') && VideoAsset::query()->where('bunny_video_guid', $guid)->exists());

        if (! $hasWork) {
            if ($logId) {
                VideoWebhookLog::query()->whereKey($logId)->update([
                    'processed' => true,
                    'processed_at' => now(),
                    'error_message' => null,
                ]);
            }

            return response()->json(['ok' => true, 'mapped' => false]);
        }

        ProcessBunnyStreamWebhookJob::dispatch($guid, $logId);

        return response()->json(['ok' => true, 'queued' => true, 'guid' => $guid]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function extractGuid(array $payload): ?string
    {
        $candidates = [
            $payload['VideoGuid'] ?? null,
            $payload['videoGuid'] ?? null,
            $payload['guid'] ?? null,
            $payload['Video']['guid'] ?? null,
        ];
        foreach ($candidates as $c) {
            if (is_string($c) && $c !== '') {
                return $c;
            }
        }

        return null;
    }
}
