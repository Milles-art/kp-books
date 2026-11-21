<?php

namespace Tests\Feature\Books;

use App\Models\Book;
use App\Models\BookPage;
use App\Models\BookPayment;
use App\Models\User;
use App\Services\Books\BookAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookAccessTest extends TestCase
{
    use RefreshDatabase;

    private function pay(User $user, Book $book, int $amount): BookPayment
    {
        $payment = BookPayment::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'amount' => $amount,
            'provider' => 'mpesa',
            'provider_ref' => (string) \Illuminate\Support\Str::uuid(),
        ]);
        app(BookAccessService::class)->applyConfirmedPayment($payment);

        return $payment;
    }

    public function test_payment_opens_pages_in_blocks(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(); // 8000 TZS, 100 pages, block 5

        $access = $this->pay($user, $book, 1000)->fresh();
        $this->assertSame(10, app(BookAccessService::class)->accessFor($user, $book)->unlocked_pages); // 12 raw, block of 5
    }

    public function test_top_up_adds_credit_and_never_loses_pages(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $this->pay($user, $book, 1000);
        $this->pay($user, $book, 1000);

        $access = app(BookAccessService::class)->accessFor($user, $book);
        $this->assertSame(2000, $access->credit_balance);
        $this->assertSame(25, $access->unlocked_pages);
    }

    public function test_full_price_opens_whole_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $this->pay($user, $book, 8000);

        $this->assertSame(100, app(BookAccessService::class)->accessFor($user, $book)->unlocked_pages);
    }

    public function test_price_change_never_shrinks_unlocked_pages(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $this->pay($user, $book, 1000);              // 10 pages
        $book->update(['price_full' => 40000]);      // book becomes much more expensive
        $this->pay($user, $book, 1000);              // credit 2000 would only open 5 pages now

        $this->assertSame(10, app(BookAccessService::class)->accessFor($user, $book)->unlocked_pages);
    }

    public function test_duplicate_confirmation_does_not_double_credit(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $payment = $this->pay($user, $book, 1000);
        app(BookAccessService::class)->applyConfirmedPayment($payment);
        app(BookAccessService::class)->applyConfirmedPayment($payment);

        $this->assertSame(1000, app(BookAccessService::class)->accessFor($user, $book)->credit_balance);
    }

    public function test_quote_rejects_amounts_below_minimum_and_caps_overpayment(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $service = app(BookAccessService::class);

        $this->assertFalse($service->quote($book, null, 500)['valid']);
        $q = $service->quote($book, null, 50000);
        $this->assertTrue($q['valid']);
        $this->assertSame(8000, $q['amount']);
        $this->assertTrue($q['whole_book']);
    }

    public function test_guest_reads_preview_but_not_locked_pages(): void
    {
        $book = Book::factory()->create();
        foreach ([5, 6] as $n) {
            BookPage::create(['book_id' => $book->id, 'page_no' => $n, 'content' => "page {$n}"]);
        }

        $this->getJson("/api/v1/books/{$book->slug}/pages/5")->assertOk()->assertJsonPath('data.content', 'page 5');
        $this->getJson("/api/v1/books/{$book->slug}/pages/6")->assertStatus(403)->assertJsonPath('code', 'PAGE_LOCKED');
    }

    public function test_paid_reader_can_open_paid_pages_only(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        foreach ([10, 11] as $n) {
            BookPage::create(['book_id' => $book->id, 'page_no' => $n, 'content' => "page {$n}"]);
        }
        $this->pay($user, $book, 1000); // opens 1 to 10

        $this->actingAs($user, 'sanctum')->getJson("/api/v1/books/{$book->slug}/pages/10")->assertOk();
        $this->actingAs($user, 'sanctum')->getJson("/api/v1/books/{$book->slug}/pages/11")->assertStatus(403);
    }

    public function test_simulated_payment_flow_end_to_end(): void
    {
        config(['kpbook.simulate_payments' => true]);
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $res = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/books/{$book->slug}/payments", ['amount' => 1000, 'provider' => 'mpesa'])
            ->assertCreated();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/payments/' . $res->json('data.id') . '/simulate-confirm')
            ->assertOk()
            ->assertJsonPath('data.allowed_pages', 10);
    }

    public function test_payment_below_minimum_is_refused(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/books/{$book->slug}/payments", ['amount' => 500, 'provider' => 'mpesa'])
            ->assertStatus(422);
    }
}
