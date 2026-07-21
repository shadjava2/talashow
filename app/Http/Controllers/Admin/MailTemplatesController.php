<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MailTemplate;
use App\Models\MailTemplateBinding;
use App\Services\MailTemplateService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MailTemplatesController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'active', 'adminapp']);
    }

    public static function events(): array
    {
        return [
            'auth.otp' => 'Auth — OTP (création de compte)',
            'auth.password_reset' => 'Auth — Mot de passe oublié',

            'billing.subscription_activated' => 'Facturation — Abonnement activé',
            'billing.subscription_cancelled' => 'Facturation — Abonnement annulé',
            'billing.invoice' => 'Facturation — Facture / reçu',

            'content.new_episode' => 'Contenu — Nouvel épisode',
            'content.new_series' => 'Contenu — Nouvelle série',

            'marketing.newsletter' => 'Marketing — Newsletter',

            'system.test_email' => 'Système — Email de test (SMTP)',
            'system.error_notification' => 'Système — Notification d’erreur',

            'account.deleted' => 'Compte — Suppression',
            'account.blocked' => 'Compte — Blocage',
            'account.unblocked' => 'Compte — Déblocage',
        ];
    }

    public static function sampleVars(string $eventKey): array
    {
        $common = [
            'app_name' => 'Talashow',
            'logo_url' => asset('logo.svg'),
            'year' => (string) now()->year,
            'now' => now()->format('Y-m-d H:i:s'),
        ];

        return match ($eventKey) {
            'auth.otp' => array_merge($common, [
                'name' => 'Utilisateur',
                'otp' => '123456',
                'expires_minutes' => 10,
            ]),
            'auth.password_reset' => array_merge($common, [
                'name' => 'Utilisateur',
                'email' => 'user@example.com',
                'reset_url' => url('/reset-password/EXEMPLE?email=user@example.com'),
            ]),
            default => $common,
        };
    }

    public function index()
    {
        $templates = MailTemplate::query()->orderBy('key')->get();
        $bindings = MailTemplateBinding::query()->with('template')->get()->keyBy('event_key');
        $events = self::events();

        return view('admin.mail_templates.index', compact('templates', 'bindings', 'events'));
    }

    public function create()
    {
        $events = self::events();
        return view('admin.mail_templates.create', compact('events'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'min:3', 'max:120', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('mail_templates', 'key')],
            'name' => 'required|string|min:2|max:255',
            'subject' => 'nullable|string|max:255',
            'html' => 'nullable|string|max:200000',
            'is_active' => 'boolean',
            'bind_event_key' => 'nullable|string|max:120',
        ]);

        $events = self::events();
        $eventKey = (string) ($validated['bind_event_key'] ?? '');
        if ($eventKey !== '' && !array_key_exists($eventKey, $events)) {
            return back()->withErrors([
                'bind_event_key' => 'Scénario invalide. Choisissez une valeur de la liste.',
            ])->withInput();
        }

        try {
            $tpl = MailTemplate::create([
                'key' => $validated['key'],
                'name' => $validated['name'],
                'subject' => $validated['subject'] ?? null,
                'html' => $validated['html'] ?? null,
                'is_active' => $request->boolean('is_active'),
            ]);

            $eventKeyToBind = $eventKey !== '' ? $eventKey : $tpl->key;
            MailTemplateBinding::query()->updateOrCreate(
                ['event_key' => $eventKeyToBind],
                ['mail_template_id' => $tpl->id]
            );
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors([
                'key' => 'Impossible de créer le template pour le moment. Vérifiez les champs puis réessayez.',
            ])->withInput();
        }

        return redirect()->route('admin.mail-templates.edit', $tpl->id)->with('success', 'Template créé.');
    }

    public function edit(int $id, MailTemplateService $svc)
    {
        $template = MailTemplate::findOrFail($id);
        $events = self::events();

        $boundEventKey = MailTemplateBinding::query()
            ->where('mail_template_id', $template->id)
            ->value('event_key');

        $eventKey = $boundEventKey ?: $template->key;
        try {
            $preview = $svc->render($eventKey, self::sampleVars($eventKey));
        } catch (\Throwable $e) {
            report($e);
            $preview = ['subject' => '', 'html' => ''];
        }

        return view('admin.mail_templates.edit', [
            'template' => $template,
            'events' => $events,
            'boundEventKey' => $boundEventKey,
            'eventKey' => $eventKey,
            'previewHtml' => $preview['html'] ?? '',
            'previewSubject' => $preview['subject'] ?? '',
        ]);
    }

    public function update(Request $request, int $id)
    {
        $template = MailTemplate::findOrFail($id);

        $validated = $request->validate([
            'key' => ['required', 'string', 'min:3', 'max:120', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('mail_templates', 'key')->ignore($template->id)],
            'name' => 'required|string|min:2|max:255',
            'subject' => 'nullable|string|max:255',
            'html' => 'nullable|string|max:200000',
            'is_active' => 'boolean',
            'bind_event_key' => 'nullable|string|max:120',
        ]);

        $events = self::events();
        $eventKey = (string) ($validated['bind_event_key'] ?? '');
        if ($eventKey !== '' && !array_key_exists($eventKey, $events)) {
            return back()->withErrors([
                'bind_event_key' => 'Scénario invalide. Choisissez une valeur de la liste.',
            ])->withInput();
        }

        try {
            $template->update([
                'key' => $validated['key'],
                'name' => $validated['name'],
                'subject' => $validated['subject'] ?? null,
                'html' => $validated['html'] ?? null,
                'is_active' => $request->boolean('is_active'),
            ]);

            $eventKeyToBind = $eventKey !== '' ? $eventKey : $template->key;
            MailTemplateBinding::query()->updateOrCreate(
                ['event_key' => $eventKeyToBind],
                ['mail_template_id' => $template->id]
            );
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors([
                'key' => 'Impossible de mettre à jour le template pour le moment. Vérifiez les champs puis réessayez.',
            ])->withInput();
        }

        return back()->with('success', 'Template mis à jour.');
    }

    public function destroy(int $id)
    {
        $template = MailTemplate::findOrFail($id);
        try {
            MailTemplateBinding::query()->where('mail_template_id', $template->id)->delete();
            $template->delete();
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors([
                'key' => "Impossible de supprimer ce template pour le moment. Réessayez plus tard.",
            ]);
        }

        return redirect()->route('admin.mail-templates.index')->with('success', 'Template supprimé.');
    }
}

