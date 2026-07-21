<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;

class PagesController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'adminapp', 'perm:settings.manage']);
    }

    public function index()
    {
        $pages = Page::orderBy('title')->get();
        return view('admin.pages.index', compact('pages'));
    }

    public function edit($id)
    {
        $page = Page::findOrFail($id);
        return view('admin.pages.edit', compact('page'));
    }

    public function update(Request $request, $id)
    {
        $page = Page::findOrFail($id);
        $validated = $request->validate([
            'title_fr' => 'required|string|min:2|max:120',
            'title_en' => 'nullable|string|min:2|max:120',
            'content_fr' => 'nullable|string|max:200000',
            'content_en' => 'nullable|string|max:200000',
            'is_active' => 'boolean',
        ]);

        try {
            $titleFr = trim((string) $validated['title_fr']);
            $titleEn = trim((string) ($validated['title_en'] ?? ''));
            $contentFr = $validated['content_fr'] ?? null;
            $contentEn = $validated['content_en'] ?? null;

            // Stockage i18n
            $page->title_fr = $titleFr;
            $page->title_en = $titleEn !== '' ? $titleEn : null;
            $page->content_fr = $contentFr;
            $page->content_en = $contentEn;

            // Fallbacks legacy (utiles si certains endroits utilisent encore title/content)
            $page->title = $titleFr;
            $page->content = $contentFr;
            $page->is_active = $request->boolean('is_active');
            $page->save();
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors([
                'title' => "Impossible d'enregistrer la page pour le moment. Vérifiez les champs puis réessayez.",
            ])->withInput();
        }

        return redirect()->route('admin.pages.index')->with('success', 'Page enregistrée.');
    }
}

