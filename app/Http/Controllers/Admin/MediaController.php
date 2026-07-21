<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BunnyStorageService;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    public function __construct(private BunnyStorageService $bunnyStorage)
    {
        $this->middleware(['auth', 'adminapp']);
    }

    public function uploadImage(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:poster,cover,thumb,logo',
            'file' => 'required|image|max:10240',
        ]);

        if (! $this->bunnyStorage->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Bunny Storage n’est pas configuré (BUNNY_STORAGE_* dans .env ou Paramètres admin).',
            ], 422);
        }

        $folder = match ($validated['type']) {
            'poster' => 'posters',
            'cover' => 'covers',
            'logo' => 'branding',
            default => 'thumbnails',
        };

        $meta = ['type' => $validated['type']];
        $uploaded = $this->bunnyStorage->upload($request->file('file'), $folder, $meta);

        if (! ($uploaded['success'] ?? false) || empty($uploaded['url'] ?? null)) {
            return response()->json([
                'success' => false,
                'message' => $uploaded['message'] ?? 'Upload Bunny Storage échoué. Vérifie la zone, la région et le mot de passe API.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'id' => $uploaded['id'],
            'url' => $uploaded['url'],
        ]);
    }
}
