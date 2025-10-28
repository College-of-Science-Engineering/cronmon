<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreScheduledTaskRequest;
use App\Http\Requests\Api\UpdateScheduledTaskRequest;
use App\Http\Resources\Api\ScheduledTaskResource;
use App\Models\ScheduledTask;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ScheduledTaskController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ScheduledTask::class);

        $perPage = (int) $request->integer('per_page', 25);
        $perPage = min(max($perPage, 1), 100);

        $tasks = QueryBuilder::for(
            ScheduledTask::query()->forUser($request->user())
        )
            ->with('team')
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('team_id'),
                AllowedFilter::scope('checked_between'),
                AllowedFilter::callback('silenced', function ($query, $value) {
                    $shouldBeSilenced = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

                    if ($shouldBeSilenced === null) {
                        return;
                    }

                    $query->silenced($shouldBeSilenced);
                }),
            ])
            ->allowedIncludes([
                'team',
                'taskRuns',
            ])
            ->defaultSort('-updated_at')
            ->paginate($perPage)
            ->withQueryString();

        return ScheduledTaskResource::collection($tasks);
    }

    public function store(StoreScheduledTaskRequest $request): JsonResponse
    {
        $this->authorize('create', ScheduledTask::class);

        $validated = $request->validated();

        /** @var \App\Models\User $user */
        $user = $request->user();

        $team = $this->resolveTeam($user, $validated['team_id']);

        $this->authorize('update', $team);

        $scheduledTask = ScheduledTask::create([
            'team_id' => $team->getKey(),
            'created_by' => $user->getKey(),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'schedule_type' => $validated['schedule_type'],
            'schedule_value' => $validated['schedule_value'],
            'timezone' => $validated['timezone'],
            'grace_period_minutes' => $validated['grace_period_minutes'],
            'unique_check_in_token' => Str::uuid()->toString(),
            'status' => 'pending',
        ])->fresh(['team']);

        return ScheduledTaskResource::make($scheduledTask)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(UpdateScheduledTaskRequest $request, ScheduledTask $scheduledTask): JsonResponse
    {
        $this->authorize('update', $scheduledTask);

        $validated = $request->validated();

        /** @var \App\Models\User $user */
        $user = $request->user();

        $team = $this->resolveTeam($user, $validated['team_id']);

        $this->authorize('update', $team);

        $scheduledTask->update([
            'team_id' => $team->getKey(),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'schedule_type' => $validated['schedule_type'],
            'schedule_value' => $validated['schedule_value'],
            'timezone' => $validated['timezone'],
            'grace_period_minutes' => $validated['grace_period_minutes'],
        ]);

        return ScheduledTaskResource::make($scheduledTask->fresh('team'))
            ->response();
    }

    public function destroy(ScheduledTask $scheduledTask): Response
    {
        $this->authorize('delete', $scheduledTask);

        $scheduledTask->delete();

        return response()->noContent();
    }

    protected function resolveTeam(User $user, int $teamId): Team
    {
        return Team::query()
            ->forUser($user)
            ->findOrFail($teamId);
    }
}
