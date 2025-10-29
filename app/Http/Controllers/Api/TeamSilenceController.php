<?php

namespace App\Http\Controllers\Api;

use App\Events\SomethingNoteworthyHappened;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SilenceTeamRequest;
use App\Http\Resources\Api\TeamResource;
use App\Models\Team;
use Illuminate\Http\JsonResponse;

class TeamSilenceController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(SilenceTeamRequest $request, Team $team): JsonResponse
    {
        $this->authorize('update', $team);

        $team->alerts_silenced_until = $request->silencedUntil();
        $team->save();

        $actingUser = $request->user();
        $silencedUntil = $team->alerts_silenced_until;

        $message = $silencedUntil === null
            ? "{$actingUser->full_name} cleared alert silence for team {$team->name}"
            : "{$actingUser->full_name} silenced alerts for team {$team->name} until {$silencedUntil->toIso8601String()}";

        SomethingNoteworthyHappened::dispatch($message);

        return TeamResource::make($team->fresh())
            ->response();
    }
}
