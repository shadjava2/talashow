<?php

namespace App\Services;

use App\Models\Episode;
use App\Models\User;
use App\Models\UserEpisode;
use Illuminate\Support\Facades\DB;

class EpisodeCoinUnlockService
{
    /**
     * Débloque l’épisode avec les pièces si l’utilisateur en a assez et que l’épisode l’exige.
     *
     * @return array{unlocked: bool, already: bool, message: ?string}
     */
    public function unlockWithCoinsIfNeeded(User $user, Episode $episode): array
    {
        if ($episode->is_free) {
            return ['unlocked' => true, 'already' => true, 'message' => null];
        }

        if ($episode->isUnlockedForUser($user)) {
            return ['unlocked' => true, 'already' => true, 'message' => null];
        }

        $coinsNeeded = $episode->coinUnlockCost();
        if ($coinsNeeded <= 0) {
            return ['unlocked' => false, 'already' => false, 'message' => null];
        }

        if ($user->total_coins < $coinsNeeded) {
            return ['unlocked' => false, 'already' => false, 'message' => null];
        }

        return DB::transaction(function () use ($user, $episode, $coinsNeeded) {
            $locked = User::query()->whereKey($user->id)->lockForUpdate()->first();
            if (! $locked) {
                return ['unlocked' => false, 'already' => false, 'message' => null];
            }

            if ($episode->isUnlockedForUser($locked)) {
                return ['unlocked' => true, 'already' => true, 'message' => null];
            }

            if ($locked->total_coins < $coinsNeeded) {
                return ['unlocked' => false, 'already' => false, 'message' => null];
            }

            if ($locked->coins >= $coinsNeeded) {
                $locked->decrement('coins', $coinsNeeded);
            } else {
                $remaining = $coinsNeeded - $locked->coins;
                $locked->coins = 0;
                $locked->reward_coins = max(0, (int) $locked->reward_coins - $remaining);
                $locked->save();
            }

            UserEpisode::updateOrCreate(
                [
                    'user_id' => $locked->id,
                    'episode_id' => $episode->id,
                ],
                [
                    'is_unlocked' => true,
                    'unlock_method' => 'coins',
                    'unlocked_until' => now()->addDays(7),
                ]
            );

            return [
                'unlocked' => true,
                'already' => false,
                'message' => null,
            ];
        });
    }

    /**
     * Déblocage manuel (bouton) — même logique que l’auto, avec messages d’erreur.
     *
     * @return array{success: bool, message: string, coins_remaining?: int}
     */
    public function unlockManually(User $user, Episode $episode): array
    {
        if ($episode->isUnlockedForUser($user)) {
            return ['success' => false, 'message' => 'Cet épisode est déjà débloqué.'];
        }

        $coinsNeeded = $episode->coinUnlockCost();

        if ($coinsNeeded > 0) {
            if ($user->total_coins < $coinsNeeded) {
                return [
                    'success' => false,
                    'message' => 'Pièces insuffisantes. Il vous faut '.$coinsNeeded.' pièces.',
                ];
            }

            $result = $this->unlockWithCoinsIfNeeded($user, $episode);
            if ($result['unlocked']) {
                return [
                    'success' => true,
                    'message' => 'Épisode débloqué avec succès!',
                    'coins_remaining' => $user->fresh()->total_coins,
                ];
            }

            return ['success' => false, 'message' => 'Impossible de débloquer cet épisode.'];
        }

        if ($episode->is_premium_only) {
            if (! $user->hasActiveSubscription()) {
                return [
                    'success' => false,
                    'message' => 'Cet épisode nécessite un abonnement actif.',
                ];
            }

            UserEpisode::updateOrCreate(
                ['user_id' => $user->id, 'episode_id' => $episode->id],
                ['is_unlocked' => true, 'unlock_method' => 'subscription', 'unlocked_until' => null]
            );

            return ['success' => true, 'message' => 'Épisode débloqué avec succès!', 'coins_remaining' => $user->fresh()->total_coins];
        }

        return ['success' => false, 'message' => 'Cet épisode ne peut pas être débloqué.'];
    }
}
