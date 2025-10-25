<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ScheduledTask;
use App\Models\TaskRun;
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

        // Create TaskRun record
        TaskRun::create([
            'scheduled_task_id' => $task->id,
            'checked_in_at' => now(),
            'expected_at' => null, // Will be calculated later by background job
            'was_late' => false, // Will be determined later by background job
            'lateness_minutes' => null,
            'data' => $request->input('data'),
        ]);

        // Update task's last check-in time
        $task->update([
            'last_checked_in_at' => now(),
            'status' => 'ok',
        ]);

        return response()->json([
            'message' => 'Check-in recorded',
        ]);
    }
}
