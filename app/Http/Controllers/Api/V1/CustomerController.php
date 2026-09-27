<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\App\StoreCustomerRequest;
use App\Http\Requests\App\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;

class CustomerController extends ApiController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Customer::class);

        return CustomerResource::collection($this->indexQuery(
            Customer::class,
            $request,
            filters: [
                AllowedFilter::exact('is_active'),
                $this->searchFilter(['name', 'doc_number', 'email']),
            ],
            sorts: ['name', 'created_at'],
            defaultSort: 'name',
        ));
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $customer = Customer::create($request->validated());
        $customer->refresh();

        return (new CustomerResource($customer))
            ->response()
            ->setStatusCode(201);
    }

    public function show(string $customer): CustomerResource
    {
        $model = $this->findByUuid(Customer::class, $customer);
        $this->authorize('view', $model);

        return new CustomerResource($model);
    }

    public function update(UpdateCustomerRequest $request, string $customer): CustomerResource
    {
        $model = $this->findByUuid(Customer::class, $customer);
        $model->update($request->validated());

        return new CustomerResource($model);
    }

    public function destroy(string $customer): JsonResponse
    {
        $model = $this->findByUuid(Customer::class, $customer);
        $this->authorize('delete', $model);

        $model->delete();

        return response()->json(null, 204);
    }
}
