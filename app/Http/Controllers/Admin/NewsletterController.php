<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\TemplateMail;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class NewsletterController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $subscribers = NewsletterSubscriber::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where('email', 'like', '%' . $q . '%');
            })
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.newsletter.index', compact('subscribers', 'q'));
    }

    public function compose()
    {
        return view('admin.newsletter.compose');
    }

    public function sendTest(Request $request)
    {
        $validated = $request->validate([
            'test_email' => 'required|email|max:255',
            'headline' => 'required|string|max:120',
            'content_html' => 'required|string|max:20000',
        ]);

        try {
            Mail::to($validated['test_email'])->send(new TemplateMail('marketing.newsletter_broadcast', [
                // Compat templates
                'newsletter_title' => $validated['headline'],
                'newsletter_content' => $validated['content_html'],
                'headline' => $validated['headline'],
                'content_html' => $validated['content_html'],
                'unsubscribe_url' => route('home'),
            ]));
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors([
                'test_email' => "Impossible d'envoyer l'email de test pour le moment. Vérifiez la configuration SMTP puis réessayez.",
            ])->withInput();
        }

        return back()->with('success', "Email test envoyé à {$validated['test_email']}.");
    }

    public function sendNow(Request $request)
    {
        $validated = $request->validate([
            'headline' => 'required|string|max:120',
            'content_html' => 'required|string|max:20000',
        ]);

        // IMPORTANT: ne pas envoyer "à tous" dans une requête web (risque timeout => 500).
        // On planifie une campagne, envoyée par lots via cron: schedule:run.
        $hash = hash('sha256', $validated['headline'] . "\n" . $validated['content_html']);

        $existing = NewsletterCampaign::query()
            ->where('content_hash', $hash)
            ->whereIn('status', ['pending', 'sending'])
            ->where('created_at', '>=', now()->subMinutes(10))
            ->first();

        if ($existing) {
            return redirect()->route('admin.newsletter.index')->with('success', "Newsletter déjà planifiée (campagne #{$existing->id}).");
        }

        try {
            $campaign = NewsletterCampaign::create([
                'created_by' => auth()->id(),
                'headline' => $validated['headline'],
                'content_html' => $validated['content_html'],
                'content_hash' => $hash,
                'status' => 'pending',
            ]);
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors([
                'headline' => "Impossible de planifier la newsletter. Réessayez.",
            ])->withInput();
        }

        return redirect()->route('admin.newsletter.index')->with('success', "Newsletter planifiée (campagne #{$campaign->id}). L'envoi démarre automatiquement via cron.");
    }

    public function export(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $rows = NewsletterSubscriber::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where('email', 'like', '%' . $q . '%');
            })
            ->orderByDesc('id')
            ->get(['email', 'confirmed_at', 'unsubscribed_at', 'locale', 'source', 'created_at']);

        $filename = 'newsletter-subscribers-' . now()->format('Ymd-His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($rows) {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM pour Excel
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['email', 'confirmed_at', 'unsubscribed_at', 'locale', 'source', 'created_at']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->email,
                    optional($r->confirmed_at)->format('Y-m-d H:i:s'),
                    optional($r->unsubscribed_at)->format('Y-m-d H:i:s'),
                    $r->locale,
                    $r->source,
                    optional($r->created_at)->format('Y-m-d H:i:s'),
                ]);
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }
}

