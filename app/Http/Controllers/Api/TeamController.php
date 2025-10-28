<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\TeamResource;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class TeamController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Team::class);

        $perPage = (int) $request->integer('per_page', 25);
        $perPage = min(max($perPage, 1), 100);

        $teams = QueryBuilder::for(
            Team::query()->forUser($request->user())
        )
            ->allowedFilters([
                AllowedFilter::partial('name'),
                AllowedFilter::exact('id'),
            ])
            ->allowedIncludes([
                'scheduledTasks',
            ])
            ->defaultSort('name')
            ->paginate($perPage)
            ->withQueryString();

        return TeamResource::collection($teams);
    }
}
