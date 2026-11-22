<?php

namespace App\Services\Books;

use App\Models\ReadingSession;
use App\Models\User;

/**
 * Soft, revocable device sessions for anti-sharing. We never hard-delete a
 * session: going over the limit revokes the least recently used ones so a
 * reader who changes device is not locked out permanently.
 */
class ReadingSessionService
{
    public function register(User $user, string $deviceId, ?string $name = null): ReadingSession
    {
        $session = ReadingSession::updateOrCreate(
            ['user_id' => $user->id, 'device_id' => $deviceId],
            [
                'name' => $name ?: 'This device',
                'last_seen' => now(),
                'revoked_at' => null,
            ]
        );

        $limit = max(1, (int) config('kpbook.max_reading_devices', 3));
        $active = ReadingSession::where('user_id', $user->id)
            ->active()
            ->orderByDesc('last_seen')
            ->get();

        if ($active->count() > $limit) {
            $active->slice($limit)->each(function (ReadingSession $old) {
                $old->update(['revoked_at' => now()]);
            });
        }

        return $session->refresh();
    }

    public function forUser(User $user)
    {
        return ReadingSession::where('user_id', $user->id)
            ->orderByDesc('last_seen')
            ->get();
    }

    public function revoke(User $user, ReadingSession $session): void
    {
        abort_unless($session->user_id === $user->id, 403);
        $session->update(['revoked_at' => now()]);
    }
}
