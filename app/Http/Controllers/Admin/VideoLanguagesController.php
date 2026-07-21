<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VideoLanguage;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VideoLanguagesController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'adminapp', 'perm:settings.manage']);
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $langs = VideoLanguage::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where('code', 'like', "%{$q}%")
                    ->orWhere('name', 'like', "%{$q}%")
                    ->orWhere('native_name', 'like', "%{$q}%");
            })
            ->orderByDesc('is_active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(50)
            ->withQueryString();

        return view('admin.video_languages.index', compact('langs', 'q'));
    }

    public function create()
    {
        return view('admin.video_languages.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:12', 'regex:/^[a-zA-Z0-9_-]{2,12}$/', Rule::unique('video_languages', 'code')],
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'native_name' => ['nullable', 'string', 'max:80'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['boolean'],
        ]);

        $code = strtolower(trim((string) $validated['code']));
        $name = trim((string) $validated['name']);
        $native = trim((string) ($validated['native_name'] ?? ''));

        try {
            VideoLanguage::create([
                'code' => $code,
                'name' => $name,
                'native_name' => ($native !== '' ? $native : null),
                'sort_order' => (int) ($validated['sort_order'] ?? 0),
                'is_active' => (bool) ($validated['is_active'] ?? false),
            ]);
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors([
                'code' => "Impossible d'enregistrer la langue vidéo pour le moment.",
            ])->withInput();
        }

        return redirect()->route('admin.video-languages.index')->with('success', 'Langue vidéo créée.');
    }

    public function edit($id)
    {
        $lang = VideoLanguage::findOrFail($id);
        return view('admin.video_languages.edit', compact('lang'));
    }

    public function update(Request $request, $id)
    {
        $lang = VideoLanguage::findOrFail($id);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:12', 'regex:/^[a-zA-Z0-9_-]{2,12}$/', Rule::unique('video_languages', 'code')->ignore($lang->id)],
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'native_name' => ['nullable', 'string', 'max:80'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['boolean'],
        ]);

        $code = strtolower(trim((string) $validated['code']));
        $name = trim((string) $validated['name']);
        $native = trim((string) ($validated['native_name'] ?? ''));

        try {
            $lang->update([
                'code' => $code,
                'name' => $name,
                'native_name' => ($native !== '' ? $native : null),
                'sort_order' => (int) ($validated['sort_order'] ?? 0),
                'is_active' => (bool) ($validated['is_active'] ?? false),
            ]);
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors([
                'code' => "Impossible d'enregistrer la langue vidéo pour le moment.",
            ])->withInput();
        }

        return redirect()->route('admin.video-languages.index')->with('success', 'Langue vidéo mise à jour.');
    }

    public function destroy($id)
    {
        $lang = VideoLanguage::findOrFail($id);

        try {
            $lang->delete();
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', "Impossible de supprimer cette langue vidéo pour le moment.");
        }

        return redirect()->route('admin.video-languages.index')->with('success', 'Langue vidéo supprimée.');
    }
}
