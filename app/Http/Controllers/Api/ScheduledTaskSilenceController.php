<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SilenceScheduledTaskRequest;
use App\Http\Resources\Api\ScheduledTaskResource;
use App\Models\ScheduledTask;
use Illuminate\Http\JsonResponse;

class ScheduledTaskSilenceController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(SilenceScheduledTaskRequest $request, ScheduledTask $scheduledTask): JsonResponse
    {
        $this->authorize('update', $scheduledTask);

        $scheduledTask->alerts_silenced_until = $request->silencedUntil();
        $scheduledTask->save();

        return ScheduledTaskResource::make($scheduledTask->fresh('team'))
            ->response();
    }
}
