<?php

namespace Tests\Feature\Books;

use App\Enums\UserRole;
use App\Models\Author;
use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorTest extends TestCase
{
    use RefreshDatabase;

    public function test_reader_can_browse_authors_and_their_books(): void
    {
        $author = Author::create(['name' => 'Masoud Kipanya', 'slug' => 'masoud-kipanya']);
        $book = Book::factory()->create([
            'slug' => 'test-book',
            'author' => $author->name,
            'author_id' => $author->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->getJson('/api/v1/authors')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'masoud-kipanya')
            ->assertJsonPath('data.0.books_count', 1);

        $this->getJson('/api/v1/authors/masoud-kipanya')
            ->assertOk()
            ->assertJsonPath('data.name', 'Masoud Kipanya')
            ->assertJsonPath('data.books.0.slug', $book->slug);
    }

    public function test_admin_can_manage_authors(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/authors', [
                'name' => 'Zawadi Mrisho',
                'role' => 'Novelist',
                'bio' => 'Writes about the coast.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'zawadi-mrisho');

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/v1/admin/authors/zawadi-mrisho', ['role' => 'Novelist & teacher'])
            ->assertOk()
            ->assertJsonPath('data.role', 'Novelist & teacher');

        $this->getJson('/api/v1/admin/authors')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
