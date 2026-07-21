<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SecurityAuditService;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UsersController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'adminapp', 'perm:users.manage']);
    }

    public function index(Request $request)
    {
        $q = User::query()->with('role')->orderByDesc('id');
        if ($request->filled('search')) {
            $s = $request->string('search');
            $q->where(function ($qq) use ($s) {
                $qq->where('email', 'like', "%{$s}%")
                   ->orWhere('name', 'like', "%{$s}%");
            });
        }

        $users = $q->paginate(30)->withQueryString();
        $roles = Role::orderBy('id')->get();

        return view('admin.users.index', compact('users', 'roles'));
    }

    public function updateRole(Request $request, $id)
    {
        $validated = $request->validate([
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')],
        ]);

        $user = User::findOrFail($id);
        if (Auth::id() === $user->id) {
            return back()->with('error', 'Vous ne pouvez pas modifier votre propre rôle ici.');
        }
        try {
            $user->role_id = (int) $validated['role_id'];
            // legacy: garder compat admin flag si rôle admin
            $role = Role::find($user->role_id);
            $user->is_admin = $role?->key === 'admin';
            $user->save();
            Cache::forget('talashow.perms.user.' . $user->id);
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', "Impossible de modifier le rôle pour le moment. Réessayez.")->withInput();
        }

        SecurityAuditService::adminActivity('users.role_updated', [
            'target_user_id' => $user->id,
            'role_id' => (int) $validated['role_id'],
        ], $request);

        return back()->with('success', 'Rôle mis à jour.');
    }

    public function toggleActive(Request $request, $id)
    {
        $user = User::findOrFail($id);
        if (Auth::id() === $user->id) {
            return back()->with('error', 'Vous ne pouvez pas bloquer/débloquer votre propre compte.');
        }

        try {
            $user->is_active = !$user->is_active;
            // Révoque le "remember me" si on bloque l’utilisateur
            if ($user->is_active === false) {
                $user->setRememberToken(null);
            }
            $user->save();
            Cache::forget('talashow.perms.user.' . $user->id);
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', "Impossible de modifier le statut du compte pour le moment. Réessayez.");
        }

        SecurityAuditService::adminActivity('users.active_toggled', [
            'target_user_id' => $user->id,
            'is_active' => $user->is_active,
        ], $request);

        return back()->with('success', $user->is_active ? 'Compte débloqué.' : 'Compte bloqué.');
    }

    public function destroy(Request $request, $id)
    {
        $user = User::findOrFail($id);
        if (Auth::id() === $user->id) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        try {
            // Révoque le "remember me" + soft delete
            $user->setRememberToken(null);
            $user->save();
            $user->delete();
            Cache::forget('talashow.perms.user.' . $user->id);
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', "Impossible de supprimer ce compte pour le moment. Réessayez plus tard.");
        }

        SecurityAuditService::adminActivity('users.deleted', [
            'target_user_id' => $user->id,
        ], $request);

        return back()->with('success', 'Compte supprimé.');
    }
}

