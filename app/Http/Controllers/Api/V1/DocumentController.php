<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\App\StoreDocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Models\Document;
use App\Models\Order;
use App\Services\Billing\DocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Spatie\QueryBuilder\AllowedFilter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends ApiController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Document::class);

        return DocumentResource::collection($this->indexQuery(
            Document::class,
            $request,
            filters: [
                AllowedFilter::exact('type'),
                AllowedFilter::exact('status'),
                $this->searchFilter(['customer_name', 'customer_doc_number', 'series']),
            ],
            sorts: ['issue_date', 'number', 'total', 'created_at'],
            with: ['order', 'customer'],
        ));
    }

    public function show(string $document): DocumentResource
    {
        $model = $this->findByUuid(Document::class, $document);
        $this->authorize('view', $model);

        return new DocumentResource($model->load('order', 'customer'));
    }

    public function annul(Request $request, string $document): DocumentResource|JsonResponse
    {
        $model = $this->findByUuid(Document::class, $document);
        $this->authorize('annul', $model);

        $validated = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);

        try {
            $model = app(DocumentService::class)->annul($model, $validated['reason'] ?? null);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return new DocumentResource($model->load('order'));
    }

    public function pdf(string $document): StreamedResponse|JsonResponse
    {
        $model = $this->findByUuid(Document::class, $document);
        $this->authorize('view', $model);

        if ($model->pdf_path === null || ! Storage::disk('local')->exists($model->pdf_path)) {
            return response()->json(['message' => 'El comprobante no tiene PDF disponible.'], 404);
        }

        return Storage::disk('local')->download($model->pdf_path, $model->fullNumber().'.pdf');
    }

    public function indexForOrder(string $order): AnonymousResourceCollection
    {
        $model = $this->findByUuid(Order::class, $order);
        $this->authorize('viewAny', Document::class);

        return DocumentResource::collection(
            $model->documents()->with('order')->orderByDesc('created_at')->get(),
        );
    }

    public function storeForOrder(StoreDocumentRequest $request, string $order): DocumentResource|JsonResponse
    {
        $model = $this->findByUuid(Order::class, $order);
        $this->authorize('create', Document::class);

        try {
            $document = app(DocumentService::class)->emit($model, $request->validated());
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return (new DocumentResource($document->load('order', 'customer')))
            ->response()
            ->setStatusCode(201);
    }
}
