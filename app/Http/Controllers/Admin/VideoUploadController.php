<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Episode;
use App\Services\Video\BunnyStreamProvider;
use Illuminate\Http\Request;

class VideoUploadController extends Controller
{
    public function __construct(
        protected BunnyStreamProvider $bunnyProvider,
    ) {
        $this->middleware('auth');
        $this->middleware('adminapp');
    }

    /**
     * Upload une vidéo pour un épisode
     */
    public function upload(Request $request, $episodeId)
    {
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');
        @ignore_user_abort(true);

        $request->validate([
            'video' => 'required|file|mimes:mp4,mov,mkv,webm,avi|max:512000',
        ]);

        $episode = Episode::findOrFail($episodeId);

        try {
            $result = $this->bunnyProvider->uploadFromFile(
                $request->file('video'),
                [
                    'title' => $episode->titleForLocale(),
                    'meta' => [
                        'episode_id' => $episode->id,
                        'series_id' => $episode->series_id,
                    ],
                    'bunny' => [],
                ]
            );

            if ($result['success']) {
                $episode->update(array_filter([
                    'video_url' => $result['playback_url'] ?? null,
                    'video_type' => 'bunny',
                    'video_provider' => 'bunny',
                    'external_video_id' => $result['video_id'] ?? null,
                    'hls_url' => $result['playback_url'] ?? null,
                    'playback_url' => $result['playback_url'] ?? null,
                    'video_status' => $result['status'] ?? 'processing',
                    'thumbnail' => $result['thumbnail'] ?? $episode->thumbnail,
                ], fn ($v) => $v !== null));

                return response()->json([
                    'success' => true,
                    'message' => 'Vidéo uploadée avec succès!',
                    'video_id' => $result['video_id'] ?? null,
                    'playback_url' => $result['playback_url'] ?? null,
                    'status' => $result['status'] ?? null,
                    'provider' => 'bunny',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'Erreur lors de l\'upload',
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload depuis une URL
     */
    public function uploadFromUrl(Request $request, $episodeId)
    {
        $request->validate([
            'video_url' => 'required|url',
        ]);

        $episode = Episode::findOrFail($episodeId);

        try {
            $result = $this->bunnyProvider->uploadFromRemoteUrl(
                $request->video_url,
                [
                    'title' => $episode->titleForLocale(),
                    'meta' => [
                        'episode_id' => $episode->id,
                        'series_id' => $episode->series_id,
                    ],
                    'bunny' => [],
                ]
            );

            if ($result['success']) {
                $episode->update(array_filter([
                    'video_url' => $result['playback_url'] ?? null,
                    'video_type' => 'bunny',
                    'video_provider' => 'bunny',
                    'external_video_id' => $result['video_id'] ?? null,
                    'hls_url' => $result['playback_url'] ?? null,
                    'playback_url' => $result['playback_url'] ?? null,
                    'video_status' => $result['status'] ?? 'processing',
                    'thumbnail' => $result['thumbnail'] ?? $episode->thumbnail,
                ], fn ($v) => $v !== null));

                return response()->json([
                    'success' => true,
                    'message' => 'Vidéo importée avec succès!',
                    'video_id' => $result['video_id'] ?? null,
                    'provider' => 'bunny',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'Erreur lors de l\'import',
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Vérifier le statut d'une vidéo
     */
    public function checkStatus($episodeId)
    {
        $episode = Episode::findOrFail($episodeId);

        if (! in_array($episode->video_type, ['bunny', 'bunny_stream'], true)
            && $episode->video_provider !== 'bunny') {
            return response()->json([
                'success' => false,
                'message' => 'Le suivi de statut côté API est pris en charge pour les vidéos Bunny Stream. Migrez ou ré-uploadez l’épisode vers Bunny.',
            ], 400);
        }

        $guid = $episode->external_video_id;
        if (! $guid) {
            return response()->json([
                'success' => false,
                'message' => 'GUID Bunny introuvable',
            ], 400);
        }

        try {
            $payload = $this->bunnyProvider->getVideo($guid);
            $isReady = $this->bunnyProvider->isReady($payload);
            $status = $this->bunnyProvider->getStatus($payload);

            return response()->json([
                'success' => true,
                'status' => $status,
                'is_ready' => $isReady,
                'provider' => 'bunny',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
