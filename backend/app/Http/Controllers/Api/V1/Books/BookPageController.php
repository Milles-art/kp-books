<?php

namespace App\Http\Controllers\Api\V1\Books;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookPage;
use App\Models\BookPageView;
use App\Models\BookReadingProgress;
use App\Services\Books\BookAccessService;
use App\Services\Books\ReadingSessionService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BookPageController extends Controller
{
    /**
     * One page at a time. Locked pages are refused here and never leave the server.
     * Guests can read the free preview pages.
     */
    public function show(
        Request $request,
        Book $book,
        int $page,
        BookAccessService $service,
        ReadingSessionService $sessions
    ) {
        abort_unless($book->status === 'published', 404);
        abort_if($page < 1 || $page > $book->total_pages, 404);

        $user = $request->user('sanctum');
        $access = $service->accessFor($user, $book);
        $allowed = $service->allowedPages($book, $access);

        if ($page > $allowed) {
            return response()->json([
                'message' => 'This page is locked.',
                'code' => 'PAGE_LOCKED',
                'allowed_pages' => $allowed,
                'total_pages' => $book->total_pages,
                'min_payment' => min($book->min_payment, $service->remaining($book, $access)),
            ], 403);
        }

        $content = BookPage::where('book_id', $book->id)->where('page_no', $page)->value('content');
        abort_if($content === null, 404);

        $deviceId = $request->header('X-Device-Id');
        $requestId = $request->header('X-Request-Id') ?: Str::upper(Str::random(6));

        if ($user) {
            BookReadingProgress::updateOrCreate(
                ['user_id' => $user->id, 'book_id' => $book->id],
                ['last_page' => $page]
            );

            // Register (or refresh) this reader's device and keep the soft limit tidy.
            $session = $sessions->register($user, $deviceId ?: 'unknown-device');
            $activeDevices = $sessions->forUser($user)->where('revoked_at', null)->count();
        } else {
            $activeDevices = null;
        }

        // Audit trail: every page served is attributable to a reader + device.
        BookPageView::create([
            'user_id' => $user?->id,
            'book_id' => $book->id,
            'page' => $page,
            'device_id' => $deviceId,
            'ip' => $request->ip(),
            'viewed_at' => now(),
        ]);

        // The client draws this faintly over every page to discourage sharing
        // screenshots. It is unique per reader, device and request.
        $identity = $user
            ? Str::mask((string) ($user->phone ?? $user->email), '•', 3, -2)
            : 'Preview';
        $watermark = $identity . ' · ' . $requestId . ' · ' . now()->format('Y-m-d');

        $response = response()
            ->json([
                'data' => [
                    'page' => $page,
                    'total_pages' => $book->total_pages,
                    'allowed_pages' => $allowed,
                    'content' => $content,
                    'watermark' => $watermark,
                ],
            ])
            ->header('Cache-Control', 'private, no-store')
            ->header('X-Content-Type-Options', 'nosniff')
            ->header('Referrer-Policy', 'no-referrer');

        if ($activeDevices !== null) {
            $response->header('X-Reading-Devices', (string) $activeDevices);
        }

        return $response;
    }
}
