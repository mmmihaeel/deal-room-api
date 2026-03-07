<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Folder\IndexFolderRequest;
use App\Http\Requests\Folder\StoreFolderRequest;
use App\Http\Requests\Folder\UpdateFolderRequest;
use App\Http\Resources\FolderResource;
use App\Models\DealSpace;
use App\Models\Folder;
use App\Services\AuditLogService;
use App\Services\CacheVersionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class FolderController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly CacheVersionService $cacheVersionService,
    ) {}

    public function index(IndexFolderRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();

        $dealSpace = DealSpace::query()->findOrFail((int) $validated['deal_space_id']);
        $this->authorize('view', $dealSpace);

        if (! empty($validated['organization_id']) && (int) $validated['organization_id'] !== $dealSpace->organization_id) {
            abort(422, 'The selected organization does not own the given deal space.');
        }

        $query = Folder::query()
            ->where('deal_space_id', $dealSpace->id)
            ->withCount('documents');

        if (array_key_exists('parent_id', $validated)) {
            if ($validated['parent_id'] === null) {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', (int) $validated['parent_id']);
            }
        }

        if (! empty($validated['search'])) {
            $query->where('name', 'like', '%'.$validated['search'].'%');
        }

        $sort = $validated['sort'] ?? 'created_at';
        $direction = $validated['direction'] ?? 'desc';
        $perPage = (int) ($validated['per_page'] ?? 15);

        $folders = $query
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        return FolderResource::collection($folders);
    }

    public function store(StoreFolderRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $dealSpace = DealSpace::query()->findOrFail((int) $validated['deal_space_id']);

        if ($dealSpace->organization_id !== (int) $validated['organization_id']) {
            abort(422, 'The selected organization does not own the given deal space.');
        }

        $this->authorize('create', [Folder::class, $dealSpace]);

        if (! empty($validated['parent_id'])) {
            $parent = Folder::query()->findOrFail((int) $validated['parent_id']);
            if ($parent->deal_space_id !== $dealSpace->id) {
                abort(422, 'Parent folder must belong to the same deal space.');
            }
        }

        $folder = Folder::query()->create([
            'organization_id' => $dealSpace->organization_id,
            'deal_space_id' => $dealSpace->id,
            'parent_id' => $validated['parent_id'] ?? null,
            'created_by_user_id' => $request->user()->id,
            'name' => $validated['name'],
        ]);

        $this->auditLogService->record(
            event: 'folder.created',
            actor: $request->user(),
            organization: $dealSpace->organization,
            auditable: $folder,
            context: $validated,
            request: $request,
        );

        $this->cacheVersionService->bump('folder-list', $request->user()->id);

        return (new FolderResource($folder->loadCount('documents')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Folder $folder): FolderResource
    {
        $this->authorize('view', $folder);

        return new FolderResource($folder->loadCount('documents'));
    }

    public function update(UpdateFolderRequest $request, Folder $folder): FolderResource
    {
        $this->authorize('update', $folder);

        $validated = $request->validated();

        if (array_key_exists('parent_id', $validated) && $validated['parent_id'] !== null) {
            $parent = Folder::query()->findOrFail((int) $validated['parent_id']);

            if ($parent->deal_space_id !== $folder->deal_space_id) {
                abort(422, 'Parent folder must belong to the same deal space.');
            }

            if ($parent->id === $folder->id) {
                abort(422, 'Folder cannot be its own parent.');
            }
        }

        $folder->fill($validated)->save();

        $this->auditLogService->record(
            event: 'folder.updated',
            actor: $request->user(),
            organization: $folder->organization,
            auditable: $folder,
            context: $validated,
            request: $request,
        );

        $this->cacheVersionService->bump('folder-list', $request->user()->id);

        return new FolderResource($folder->refresh()->loadCount('documents'));
    }

    public function destroy(Folder $folder, Request $request): Response
    {
        $this->authorize('delete', $folder);

        $this->auditLogService->record(
            event: 'folder.deleted',
            actor: $request->user(),
            organization: $folder->organization,
            auditable: $folder,
            context: ['folder_id' => $folder->id],
            request: $request,
        );

        $folder->delete();

        $this->cacheVersionService->bump('folder-list', $request->user()->id);

        return response()->noContent();
    }
}
