<?php

namespace App\Http\Controllers\Api;

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

        return TeamResource::make($team->fresh())
            ->response();
    }
}
