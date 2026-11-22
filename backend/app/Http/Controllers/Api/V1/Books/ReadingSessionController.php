<?php

namespace App\Http\Controllers\Api\V1\Books;

use App\Http\Controllers\Controller;
use App\Models\ReadingSession;
use App\Services\Books\ReadingSessionService;
use Illuminate\Http\Request;

class ReadingSessionController extends Controller
{
    public function index(Request $request, ReadingSessionService $service)
    {
        $devices = $service->forUser($request->user())
            ->map(fn (ReadingSession $session) => [
                'id' => $session->id,
                'device_id' => $session->device_id,
                'name' => $session->name,
                'last_seen' => $session->last_seen?->toISOString(),
                'active' => $session->isActive(),
            ])
            ->values();

        return response()->json(['data' => [
            'max_devices' => (int) config('kpbook.max_reading_devices', 3),
            'devices' => $devices,
        ]]);
    }

    public function destroy(Request $request, ReadingSession $session, ReadingSessionService $service)
    {
        $service->revoke($request->user(), $session);

        return response()->json(['data' => ['ok' => true]]);
    }
}
