<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\FollowUp\StoreFollowUpRequest;
use App\Http\Requests\FollowUp\UpdateFollowUpRequest;
use App\Http\Resources\FollowUpResource;
use App\Models\FollowUp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FollowUpController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $followUps = FollowUp::query()
            ->with(['customer', 'lead', 'assignee'])
            ->when($request->query('assigned_to'), fn ($query, $userId) => $query->where('assigned_to', $userId))
            ->when($request->query('bucket'), fn ($query, $bucket) => match ($bucket) {
                'overdue' => $query->overdue(),
                'today' => $query->open()->whereBetween('due_at', [now()->startOfDay(), now()->endOfDay()]),
                'upcoming' => $query->open()->where('due_at', '>', now()->endOfDay()),
                'done' => $query->whereNotNull('done_at'),
                default => $query,
            })
            ->orderBy('due_at')
            ->paginate(min((int) $request->query('per_page', 15), 200));

        return FollowUpResource::collection($followUps);
    }

    public function store(StoreFollowUpRequest $request): JsonResponse
    {
        $followUp = FollowUp::create([
            ...$request->validated(),
            'assigned_to' => $request->validated('assigned_to', $request->user()->id),
        ]);

        return FollowUpResource::make($followUp->load(['customer', 'lead', 'assignee']))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateFollowUpRequest $request, FollowUp $followUp): FollowUpResource
    {
        $followUp->update($request->validated());

        return FollowUpResource::make($followUp->load(['customer', 'lead', 'assignee']));
    }

    public function complete(FollowUp $followUp): FollowUpResource
    {
        $followUp->update(['done_at' => now()]);

        return FollowUpResource::make($followUp->load(['customer', 'lead', 'assignee']));
    }

    public function destroy(FollowUp $followUp): JsonResponse
    {
        $followUp->delete();

        return response()->json(null, 204);
    }
}
