<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class QoeController extends Controller
{
    public function video(Request $request)
    {
        $validated = $request->validate([
            'episode_id' => 'required|integer|min:1',
            'is_hls' => 'nullable|boolean',
            'reason' => 'nullable|string|max:32',
            'ttfp_ms' => 'nullable|numeric|min:0|max:600000',
            'ttff_ms' => 'nullable|numeric|min:0|max:600000',
            'waiting_count' => 'nullable|integer|min:0|max:10000',
            'waiting_total_ms' => 'nullable|numeric|min:0|max:3600000',
            'error_count' => 'nullable|integer|min:0|max:1000',
            'played_seconds' => 'nullable|integer|min:0|max:86400',
        ]);

        logger()->info('TALASHOW_QOE_VIDEO', [
            ...$validated,
            'ip' => $request->ip(),
            'ua' => substr((string) $request->userAgent(), 0, 255),
        ]);

        return response()->noContent();
    }
}

