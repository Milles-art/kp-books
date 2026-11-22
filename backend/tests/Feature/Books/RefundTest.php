<?php

namespace Tests\Feature\Books;

use App\Enums\UserRole;
use App\Models\Book;
use App\Models\BookAccess;
use App\Models\BookPayment;
use App\Models\User;
use App\Services\Books\BookAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RefundTest extends TestCase
{
    use RefreshDatabase;

    private function pay(User $user, Book $book, int $amount): BookPayment
    {
        $payment = BookPayment::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'amount' => $amount,
            'currency' => 'TZS',
            'provider' => 'mpesa',
            'provider_ref' => Str::uuid()->toString(),
            'status' => BookPayment::PENDING,
            'confirmed_at' => null,
        ]);
        app(BookAccessService::class)->applyConfirmedPayment($payment);

        return $payment;
    }

    public function test_admin_refund_revokes_the_payment_and_recomputes_access(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $book = Book::factory()->create([
            'slug' => 'test-book',
            'status' => 'published',
            'published_at' => now(),
            'total_pages' => 100,
            'free_preview_pages' => 5,
            'price_full' => 8000,
            'unlock_block' => 5,
        ]);

        $payment = $this->pay($user, $book, 2000);
        $this->assertSame(25, BookAccess::where('user_id', $user->id)->firstOrFail()->unlocked_pages);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/payments/{$payment->id}/refund/request", ['reason' => 'Duplicate payment'])
            ->assertCreated()
            ->assertJsonPath('data.status', 'requested');

        $list = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/admin/refunds')->assertOk();
        $refundId = $list->json('data.0.id');

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/refunds/{$refundId}/process", ['approve' => true])
            ->assertOk()
            ->assertJsonPath('data.status', 'processed')
            ->assertJsonPath('data.payment_status', 'refunded');

        $this->assertSame(BookPayment::REFUNDED, $payment->refresh()->status);
        $access = BookAccess::where('user_id', $user->id)->firstOrFail();
        $this->assertSame(0, $access->credit_balance);
        $this->assertSame(5, $access->unlocked_pages);
    }

    public function test_refund_is_rejected_outside_the_window(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create([
            'slug' => 'test-book','status' => 'published', 'published_at' => now(), 'total_pages' => 50]);
        $payment = BookPayment::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'amount' => 1000,
            'provider' => 'mpesa',
            'provider_ref' => Str::uuid()->toString(),
            'status' => BookPayment::CONFIRMED,
            'confirmed_at' => now()->subDays(3),
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/payments/{$payment->id}/refund/request", ['reason' => 'Changed mind'])
            ->assertStatus(422);
    }
}
