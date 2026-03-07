<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Document\IndexDocumentRequest;
use App\Http\Requests\Document\StoreDocumentRequest;
use App\Http\Requests\Document\UpdateDocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Models\DealSpace;
use App\Models\Document;
use App\Models\Folder;
use App\Services\AuditLogService;
use App\Services\CacheVersionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class DocumentController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly CacheVersionService $cacheVersionService,
    ) {}

    public function index(IndexDocumentRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();

        $dealSpace = DealSpace::query()->findOrFail((int) $validated['deal_space_id']);
        $this->authorize('view', $dealSpace);

        if (! empty($validated['organization_id']) && (int) $validated['organization_id'] !== $dealSpace->organization_id) {
            abort(422, 'The selected organization does not own the given deal space.');
        }

        $query = Document::query()->where('deal_space_id', $dealSpace->id);

        if (array_key_exists('folder_id', $validated)) {
            if ($validated['folder_id'] === null) {
                $query->whereNull('folder_id');
            } else {
                $query->where('folder_id', (int) $validated['folder_id']);
            }
        }

        if (! empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($builder) use ($search) {
                $builder->where('title', 'like', "%{$search}%")
                    ->orWhere('filename', 'like', "%{$search}%");
            });
        }

        if (! empty($validated['mime_type'])) {
            $query->where('mime_type', $validated['mime_type']);
        }

        $sort = $validated['sort'] ?? 'uploaded_at';
        $direction = $validated['direction'] ?? 'desc';
        $perPage = (int) ($validated['per_page'] ?? 15);

        $documents = $query
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        return DocumentResource::collection($documents);
    }

    public function store(StoreDocumentRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $dealSpace = DealSpace::query()->findOrFail((int) $validated['deal_space_id']);

        if ($dealSpace->organization_id !== (int) $validated['organization_id']) {
            abort(422, 'The selected organization does not own the given deal space.');
        }

        $this->authorize('create', [Document::class, $dealSpace]);

        if (! empty($validated['folder_id'])) {
            $folder = Folder::query()->findOrFail((int) $validated['folder_id']);
            if ($folder->deal_space_id !== $dealSpace->id) {
                abort(422, 'Folder must belong to the same deal space.');
            }
        }

        $document = Document::query()->create([
            'organization_id' => $dealSpace->organization_id,
            'deal_space_id' => $dealSpace->id,
            'folder_id' => $validated['folder_id'] ?? null,
            'owner_user_id' => $request->user()->id,
            'title' => $validated['title'],
            'filename' => $validated['filename'],
            'mime_type' => $validated['mime_type'],
            'size_bytes' => $validated['size_bytes'],
            'version' => 1,
            'checksum' => $validated['checksum'] ?? null,
            'metadata' => $validated['metadata'] ?? null,
            'uploaded_at' => now(),
        ]);

        $this->auditLogService->record(
            event: 'document.created',
            actor: $request->user(),
            organization: $dealSpace->organization,
            auditable: $document,
            context: [
                'title' => $document->title,
                'size_bytes' => $document->size_bytes,
                'mime_type' => $document->mime_type,
            ],
            request: $request,
        );

        $this->cacheVersionService->bump('document-list', $request->user()->id);

        return (new DocumentResource($document))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Document $document): DocumentResource
    {
        $this->authorize('view', $document);

        $cachedDocument = $this->cacheVersionService->remember(
            domain: 'document-show',
            scope: $document->id,
            params: ['updated_at' => (string) $document->updated_at],
            seconds: 120,
            resolver: fn () => Document::query()->findOrFail($document->id),
        );

        return new DocumentResource($cachedDocument);
    }

    public function update(UpdateDocumentRequest $request, Document $document): DocumentResource
    {
        $this->authorize('update', $document);

        $validated = $request->validated();

        if (array_key_exists('folder_id', $validated) && $validated['folder_id'] !== null) {
            $folder = Folder::query()->findOrFail((int) $validated['folder_id']);
            if ($folder->deal_space_id !== $document->deal_space_id) {
                abort(422, 'Folder must belong to the same deal space.');
            }
        }

        if (($validated['increment_version'] ?? false) === true) {
            $validated['version'] = $document->version + 1;
        }

        unset($validated['increment_version']);

        $document->fill($validated)->save();

        $this->auditLogService->record(
            event: 'document.updated',
            actor: $request->user(),
            organization: $document->organization,
            auditable: $document,
            context: $validated,
            request: $request,
        );

        $this->cacheVersionService->bump('document-list', $request->user()->id);
        $this->cacheVersionService->bump('document-show', $document->id);

        return new DocumentResource($document->refresh());
    }

    public function destroy(Document $document, Request $request): Response
    {
        $this->authorize('delete', $document);

        $this->auditLogService->record(
            event: 'document.deleted',
            actor: $request->user(),
            organization: $document->organization,
            auditable: $document,
            context: ['document_id' => $document->id],
            request: $request,
        );

        $document->delete();

        $this->cacheVersionService->bump('document-list', $request->user()->id);
        $this->cacheVersionService->bump('document-show', $document->id);

        return response()->noContent();
    }
}
