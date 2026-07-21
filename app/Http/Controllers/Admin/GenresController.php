<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Genre;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class GenresController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'adminapp']);
    }

    public function index()
    {
        $genres = Genre::orderBy('sort_order')->orderBy('name')->paginate(50);
        return view('admin.genres.index', compact('genres'));
    }

    public function create()
    {
        return view('admin.genres.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_fr' => ['required', 'string', 'min:2', 'max:80'],
            'name_en' => ['required', 'string', 'min:2', 'max:80'],
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'is_active' => 'boolean',
        ]);

        $nameFr = trim((string) $validated['name_fr']);
        $nameEn = trim((string) $validated['name_en']);

        $slugFr = Str::slug($nameFr);
        $slugFr = $slugFr !== '' ? $slugFr : ('genre-fr-' . Str::lower(Str::random(6)));

        $slugEn = Str::slug($nameEn);
        $slugEn = $slugEn !== '' ? $slugEn : $slugFr;

        // slug canonique (interne) = FR
        $slug = $slugFr;

        // Unicité: slug (canonique), slug_fr, slug_en
        if (Genre::where('slug', $slug)->exists()) {
            return back()->withErrors(['name_fr' => "Ce nom génère un slug déjà utilisé (“{$slug}”). Modifiez légèrement le nom FR."])->withInput();
        }
        if (Genre::where('slug_fr', $slugFr)->exists()) {
            return back()->withErrors(['name_fr' => "Slug FR déjà utilisé (“{$slugFr}”). Modifiez légèrement le nom FR."])->withInput();
        }
        if (Genre::where('slug_en', $slugEn)->exists()) {
            $slugEn .= '-' . Str::lower(Str::random(4));
        }

        try {
            Genre::create([
                'name' => $nameFr,
                'name_fr' => $nameFr,
                'name_en' => $nameEn,
                'slug' => $slug,
                'slug_fr' => $slugFr,
                'slug_en' => $slugEn,
                'sort_order' => $validated['sort_order'] ?? 0,
                'is_active' => (bool) ($validated['is_active'] ?? false),
            ]);
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors([
                'name_fr' => "Impossible d'enregistrer le genre. Vérifiez les champs puis réessayez.",
            ])->withInput();
        }

        return redirect()->route('admin.genres.index')->with('success', 'Genre créé.');
    }

    public function edit($id)
    {
        $genre = Genre::findOrFail($id);
        return view('admin.genres.edit', compact('genre'));
    }

    public function update(Request $request, $id)
    {
        $genre = Genre::findOrFail($id);

        $validated = $request->validate([
            'name_fr' => ['required', 'string', 'min:2', 'max:80'],
            'name_en' => ['required', 'string', 'min:2', 'max:80'],
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'is_active' => 'boolean',
        ]);

        $nameFr = trim((string) $validated['name_fr']);
        $nameEn = trim((string) $validated['name_en']);

        $slugFr = Str::slug($nameFr);
        $slugFr = $slugFr !== '' ? $slugFr : ('genre-fr-' . Str::lower(Str::random(6)));

        $slugEn = Str::slug($nameEn);
        $slugEn = $slugEn !== '' ? $slugEn : $slugFr;

        $slug = $slugFr; // canonique = FR

        if (Genre::where('slug', $slug)->where('id', '!=', $genre->id)->exists()) {
            return back()->withErrors(['name_fr' => "Ce nom génère un slug déjà utilisé (“{$slug}”). Modifiez légèrement le nom FR."])->withInput();
        }
        if (Genre::where('slug_fr', $slugFr)->where('id', '!=', $genre->id)->exists()) {
            return back()->withErrors(['name_fr' => "Slug FR déjà utilisé (“{$slugFr}”). Modifiez légèrement le nom FR."])->withInput();
        }
        if (Genre::where('slug_en', $slugEn)->where('id', '!=', $genre->id)->exists()) {
            $slugEn .= '-' . Str::lower(Str::random(4));
        }

        try {
            $genre->update([
                'name' => $nameFr,
                'name_fr' => $nameFr,
                'name_en' => $nameEn,
                'slug' => $slug,
                'slug_fr' => $slugFr,
                'slug_en' => $slugEn,
                'sort_order' => $validated['sort_order'] ?? 0,
                'is_active' => (bool) ($validated['is_active'] ?? false),
            ]);
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors([
                'name_fr' => "Impossible d'enregistrer le genre. Vérifiez les champs puis réessayez.",
            ])->withInput();
        }

        return redirect()->route('admin.genres.index')->with('success', 'Genre mis à jour.');
    }

    public function destroy($id)
    {
        $genre = Genre::findOrFail($id);
        $genre->delete();
        return redirect()->route('admin.genres.index')->with('success', 'Genre supprimé.');
    }
}

