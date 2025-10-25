<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\RecordTaskCheckIn;
use App\Models\ScheduledTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PingController extends Controller
{
    public function __invoke(Request $request, string $token): JsonResponse
    {
        $task = ScheduledTask::where('unique_check_in_token', $token)->first();

        if (! $task) {
            return response()->json([
                'message' => 'Task not found',
            ], 404);
        }

        // Dispatch job to record check-in
        RecordTaskCheckIn::dispatch($task, $request->input('data'));

        return response()->json([
            'message' => 'Check-in recorded',
        ]);
    }
}
