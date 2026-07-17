<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CustomerController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $customers = Customer::query()
            ->with('owner')
            ->withCount(['followUps as open_follow_ups_count' => fn ($q) => $q->whereNull('done_at')])
            ->when($request->query('q'), fn ($query, $q) => $query->where(
                fn ($w) => $w->whereLike('name', "%{$q}%")->orWhereLike('company', "%{$q}%")
            ))
            ->when($request->query('owner_id'), fn ($query, $owner) => $query->where('owner_id', $owner))
            ->latest()
            ->paginate(min((int) $request->query('per_page', 15), 200));

        return CustomerResource::collection($customers);
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $customer = Customer::create([
            ...$request->validated(),
            'owner_id' => $request->validated('owner_id', $request->user()->id),
        ]);

        return CustomerResource::make($customer->load('owner'))->response()->setStatusCode(201);
    }

    public function show(Customer $customer): CustomerResource
    {
        return CustomerResource::make($customer->load(['owner', 'lead', 'followUps.assignee']));
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): CustomerResource
    {
        $customer->update($request->validated());

        return CustomerResource::make($customer->load('owner'));
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $customer->delete();

        return response()->json(null, 204);
    }
}
