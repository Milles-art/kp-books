<?php

namespace Tests\Feature\Books;

use App\Models\Book;
use App\Models\User;
use App\Services\Books\EntitlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntitlementTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_reader_can_get_a_signed_entitlement_and_other_apps_can_verify_it(): void
    {
        config(['kpbook.entitlement_secret' => 'shared-secret-across-apps']);
        $user = User::factory()->create();
        $book = Book::factory()->create([
            'slug' => 'test-book',
            'status' => 'published',
            'published_at' => now(),
            'total_pages' => 100,
            'free_preview_pages' => 10,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeader('X-Device-Id', 'phone')
            ->getJson("/api/v1/books/{$book->slug}/entitlement")
            ->assertOk()
            ->assertJsonPath('data.allowed_pages', 10);

        $token = $response->json('data.token');
        $payload = app(EntitlementService::class)->verify($token);

        $this->assertNotNull($payload);
        $this->assertSame($user->id, $payload['sub']);
        $this->assertSame($book->slug, $payload['book']);
        $this->assertSame(10, $payload['pages']);
        $this->assertSame('phone', $payload['device']);
    }

    public function test_a_tampered_entitlement_is_rejected(): void
    {
        config(['kpbook.entitlement_secret' => 'shared-secret-across-apps']);
        $user = User::factory()->create();
        $book = Book::factory()->create([
            'slug' => 'test-book',
            'status' => 'published',
            'published_at' => now(),
            'total_pages' => 50,
        ]);

        $token = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/books/{$book->slug}/entitlement")
            ->assertOk()
            ->json('data.token');

        $parts = explode('.', $token);
        $payload = json_decode(base64_decode(strtr($parts[0], '-_', '+/'), true), true);
        $payload['pages'] = 50;
        $parts[0] = rtrim(strtr((string) json_encode($payload), '+/', '-_'), '=');

        $this->assertNull(app(EntitlementService::class)->verify(implode('.', $parts)));
    }
}
