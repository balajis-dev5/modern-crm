<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lead\ChangeLeadStageRequest;
use App\Http\Requests\Lead\StoreLeadRequest;
use App\Http\Requests\Lead\UpdateLeadRequest;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\LeadResource;
use App\Models\Lead;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LeadController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $sort = in_array($request->query('sort'), ['name', 'deal_value', 'created_at'], true)
            ? $request->query('sort')
            : 'created_at';

        $leads = Lead::query()
            ->with('owner')
            ->when($request->query('q'), fn ($query, $q) => $query->where(
                fn ($w) => $w->whereLike('name', "%{$q}%")->orWhereLike('company', "%{$q}%")
            ))
            ->when($request->query('stage'), fn ($query, $stage) => $query->where('stage', $stage))
            ->when($request->query('source'), fn ($query, $source) => $query->where('source', $source))
            ->when($request->query('owner_id'), fn ($query, $owner) => $query->where('owner_id', $owner))
            ->orderBy($sort, $request->query('dir') === 'asc' ? 'asc' : 'desc')
            ->paginate(min((int) $request->query('per_page', 15), 200));

        return LeadResource::collection($leads);
    }

    public function store(StoreLeadRequest $request): JsonResponse
    {
        $lead = Lead::create([
            ...$request->validated(),
            'stage' => $request->validated('stage', 'new'),
            'owner_id' => $request->validated('owner_id', $request->user()->id),
        ]);

        return LeadResource::make($lead->load('owner'))->response()->setStatusCode(201);
    }

    public function show(Lead $lead): LeadResource
    {
        return LeadResource::make($lead->load(['owner', 'stageHistories.changedBy', 'customer']));
    }

    public function update(UpdateLeadRequest $request, Lead $lead): LeadResource
    {
        $lead->update($request->validated());

        return LeadResource::make($lead->load('owner'));
    }

    public function destroy(Lead $lead): JsonResponse
    {
        $lead->delete();

        return response()->json(null, 204);
    }

    /**
     * Kanban stage move. Every transition is recorded in lead_stage_histories
     * so "how did this deal get here" is always answerable.
     */
    public function changeStage(ChangeLeadStageRequest $request, Lead $lead): LeadResource
    {
        $lead->changeStage($request->validated('stage'), $request->user());

        return LeadResource::make($lead->load(['owner', 'stageHistories.changedBy']));
    }

    /**
     * Winning a lead promotes it to a customer in one step.
     */
    public function convert(Request $request, Lead $lead): JsonResponse
    {
        if ($lead->customer()->exists()) {
            return ApiResponse::error('Lead is already converted.', 'LEAD_ALREADY_CONVERTED', 422);
        }

        $lead->changeStage('won', $request->user());

        $customer = $lead->customer()->create([
            'name' => $lead->name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'company' => $lead->company,
            'owner_id' => $lead->owner_id,
        ]);

        return CustomerResource::make($customer->load('owner'))->response()->setStatusCode(201);
    }
}
