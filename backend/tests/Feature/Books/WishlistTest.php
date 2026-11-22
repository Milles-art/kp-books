<?php

namespace Tests\Feature\Books;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WishlistTest extends TestCase
{
    use RefreshDatabase;

    private function book(): Book
    {
        return Book::factory()->create([
            'slug' => 'test-book',
            'status' => 'published',
            'published_at' => now(),
            'total_pages' => 100,
            'price_full' => 8000,
        ]);
    }

    public function test_a_reader_can_save_and_unsave_a_book(): void
    {
        $user = User::factory()->create();
        $book = $this->book();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/books/{$book->slug}/save")
            ->assertOk()
            ->assertJsonPath('data.saved', true);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/books/saved')
            ->assertOk()
            ->assertJsonCount(1, 'data.saved')
            ->assertJsonPath('data.saved.0.slug', $book->slug);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/books/{$book->slug}/save")
            ->assertOk()
            ->assertJsonPath('data.saved', false);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/books/saved')
            ->assertOk()
            ->assertJsonCount(0, 'data.saved');
    }

    public function test_a_reader_can_manage_collections(): void
    {
        $user = User::factory()->create();
        $book = $this->book();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/books/collections', ['name' => '  Weekend  reads '])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Weekend reads');

        $collection = $user->collections()->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/books/collections/{$collection->id}/books/{$book->slug}")
            ->assertOk()
            ->assertJsonPath('data.added', true);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/books/saved')
            ->assertOk()
            ->assertJsonPath('data.collections.0.name', 'Weekend reads')
            ->assertJsonPath('data.collections.0.count', 1);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/books/collections/{$collection->id}/books/{$book->slug}")
            ->assertOk()
            ->assertJsonPath('data.removed', true);

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/books/collections/{$collection->id}", ['name' => 'Favourites'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Favourites');

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/books/collections/{$collection->id}")
            ->assertOk()
            ->assertJsonPath('data.ok', true);
    }

    public function test_a_reader_cannot_edit_another_readers_collection(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($owner, 'sanctum')->postJson('/api/v1/books/collections', ['name' => 'Mine'])->assertCreated();
        $collection = $owner->collections()->firstOrFail();

        $this->actingAs($other, 'sanctum')
            ->patchJson("/api/v1/books/collections/{$collection->id}", ['name' => 'Stolen'])
            ->assertNotFound();
    }

    public function test_saved_books_require_login(): void
    {
        $this->getJson('/api/v1/books/saved')->assertUnauthorized();
    }
}
