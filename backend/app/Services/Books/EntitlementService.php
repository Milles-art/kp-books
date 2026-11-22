<?php

namespace App\Services\Books;

use App\Models\Book;
use App\Models\BookAccess;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Short-lived signed proof of what a reader may access. The other Kipanya apps
 * can verify the signature with the shared secret instead of calling this
 * service on every request. It grants no download and contains no content.
 */
class EntitlementService
{
    public function issue(User $user, Book $book, ?BookAccess $access, ?string $deviceId = null): array
    {
        $ttl = max(1, (int) config('kpbook.entitlement_ttl_minutes', 15));
        $allowed = app(BookAccessService::class)->allowedPages($book, $access);
        $now = time();

        $payload = [
            'sub' => $user->id,
            'book' => $book->slug,
            'pages' => $allowed,
            'total' => (int) $book->total_pages,
            'credit' => (int) ($access->credit_balance ?? 0),
            'device' => $deviceId,
            'iat' => $now,
            'exp' => $now + ($ttl * 60),
            'nonce' => Str::random(10),
        ];

        $body = rtrim(strtr(base64_encode((string) json_encode($payload)), '+/', '-_'), '=');
        $token = $body.'.'.$this->sign($body);

        return [
            'token' => $token,
            'expires_at' => now()->addMinutes($ttl)->toISOString(),
            'allowed_pages' => $allowed,
            'total_pages' => (int) $book->total_pages,
            'whole_book' => $allowed >= (int) $book->total_pages,
        ];
    }

    /** Verify a token issued by any app sharing the secret. */
    public function verify(string $token): ?array
    {
        $parts = explode('.', $token, 2);
        if (count($parts) !== 2 || ! hash_equals($this->sign($parts[0]), $parts[1])) {
            return null;
        }

        $json = base64_decode(strtr($parts[0], '-_', '+/'), true);
        $payload = $json === false ? null : json_decode($json, true);
        if (! is_array($payload) || empty($payload['exp']) || $payload['exp'] < time()) {
            return null;
        }

        return $payload;
    }

    private function sign(string $body): string
    {
        $secret = config('kpbook.entitlement_secret') ?: config('app.key');

        return hash_hmac('sha256', $body, (string) $secret);
    }
}
