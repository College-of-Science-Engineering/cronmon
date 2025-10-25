<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RecordPingRequest;
use App\Jobs\RecordTaskCheckIn;
use App\Models\ScheduledTask;
use Illuminate\Http\JsonResponse;

class PingController extends Controller
{
    public function __invoke(RecordPingRequest $request, string $token): JsonResponse
    {
        $task = ScheduledTask::where('unique_check_in_token', $token)->first();

        if (! $task) {
            return response()->json([
                'message' => 'Task not found',
            ], 404);
        }

        // Dispatch job to record check-in
        $validated = $request->validated();

        RecordTaskCheckIn::dispatch($task, $validated['data'] ?? null);

        return response()->json([
            'message' => 'Check-in recorded',
        ]);
    }
}
