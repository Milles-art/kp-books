<?php

namespace App\Http\Controllers\Api\V1\Books;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Services\Books\BookAccessService;
use App\Services\Books\EntitlementService;
use Illuminate\Http\Request;

class EntitlementController extends Controller
{
    public function show(Request $request, Book $book, EntitlementService $entitlements, BookAccessService $access)
    {
        $user = $request->user();
        $record = $access->accessFor($user, $book);
        $device = $request->header('X-Device-Id');

        return response()->json(['data' =>
            $entitlements->issue($user, $book, $record, $device),
        ]);
    }
}
