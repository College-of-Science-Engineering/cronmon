<?php

namespace App\Http\Controllers\Api;

use App\Events\SomethingNoteworthyHappened;
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

        $actingUser = $request->user();
        $silencedUntil = $scheduledTask->alerts_silenced_until;
        $teamName = $scheduledTask->team()->value('name');

        $message = $silencedUntil === null
            ? "{$actingUser->full_name} cleared alert silence for scheduled task {$scheduledTask->name} on team {$teamName}"
            : "{$actingUser->full_name} silenced alerts for scheduled task {$scheduledTask->name} on team {$teamName} until {$silencedUntil->toIso8601String()}";

        SomethingNoteworthyHappened::dispatch($message);

        return ScheduledTaskResource::make($scheduledTask->fresh('team'))
            ->response();
    }
}
