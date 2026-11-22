<?php

namespace Tests\Feature\Books;

use App\Models\Book;
use App\Models\BookPage;
use App\Models\BookPageView;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_locked_pages_are_never_sent_and_allowed_pages_are_watermarked_and_audited(): void
    {
        $user = User::factory()->create(['email' => 'amina@example.com']);
        $book = Book::factory()->create([
            'slug' => 'test-book',
            'status' => 'published',
            'published_at' => now(),
            'total_pages' => 20,
            'free_preview_pages' => 3,
        ]);
        BookPage::create(['book_id' => $book->id, 'page_no' => 1, 'content' => 'Secret page one']);
        BookPage::create(['book_id' => $book->id, 'page_no' => 9, 'content' => 'Secret page nine']);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/books/{$book->slug}/pages/9")
            ->assertStatus(403)
            ->assertJsonPath('code', 'PAGE_LOCKED')
            ->assertJsonMissingPath('data.content');

        $response = $this->actingAs($user, 'sanctum')
            ->withHeader('X-Device-Id', 'phone')
            ->withHeader('X-Request-Id', 'ABC123')
            ->getJson("/api/v1/books/{$book->slug}/pages/1")
            ->assertOk()
            ->assertJsonPath('data.content', 'Secret page one');

        $watermark = $response->json('data.watermark');
        $this->assertStringContainsString('ABC123', $watermark);
        $this->assertStringNotContainsString('amina@example.com', $watermark);
        $response->assertHeader('Cache-Control', 'no-store, private');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'no-referrer');

        $this->assertSame(1, BookPageView::count());
        $this->assertSame('phone', BookPageView::firstOrFail()->device_id);
    }
}
