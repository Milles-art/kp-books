<?php

namespace Tests\Feature\Books;

use App\Models\Book;
use App\Models\BookPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingSessionTest extends TestCase
{
    use RefreshDatabase;

    private function bookWithPage(): Book
    {
        $book = Book::factory()->create([
            'slug' => 'test-book',
            'status' => 'published',
            'published_at' => now(),
            'total_pages' => 10,
            'free_preview_pages' => 5,
        ]);
        BookPage::create(['book_id' => $book->id, 'page_no' => 1, 'content' => 'Page one']);

        return $book;
    }

    public function test_reading_a_page_registers_the_device_and_a_soft_limit_revokes_old_ones(): void
    {
        config(['kpbook.max_reading_devices' => 2]);
        $user = User::factory()->create();
        $book = $this->bookWithPage();

        foreach (['phone', 'tablet', 'laptop'] as $i => $device) {
            $this->actingAs($user, 'sanctum')
                ->withHeader('X-Device-Id', $device)
                ->getJson("/api/v1/books/{$book->slug}/pages/1")
                ->assertOk();
        }

        $this->assertSame(2, $user->readingSessions()->active()->count());
        $this->assertNotNull($user->readingSessions()->where('device_id', 'phone')->firstOrFail()->revoked_at);
        $this->assertNull($user->readingSessions()->where('device_id', 'laptop')->firstOrFail()->revoked_at);
    }

    public function test_a_reader_can_list_and_revoke_devices(): void
    {
        $user = User::factory()->create();
        $book = $this->bookWithPage();

        $this->actingAs($user, 'sanctum')
            ->withHeader('X-Device-Id', 'phone')
            ->getJson("/api/v1/books/{$book->slug}/pages/1")
            ->assertOk();

        $list = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/books/devices')
            ->assertOk()
            ->assertJsonPath('data.max_devices', 3);

        $id = $list->json('data.devices.0.id');

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/books/devices/{$id}")
            ->assertOk()
            ->assertJsonPath('data.ok', true);

        $this->assertSame(0, $user->readingSessions()->active()->count());
    }
}
