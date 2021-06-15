<?php

namespace App\Http\Controllers;

use App\Filters\DocumentFilters;
use App\Http\Requests\Document\DestroyDocumentRequest;
use App\Http\Requests\Document\ShowDocumentRequest;
use App\Http\Requests\Document\StoreDocumentRequest;
use App\Http\Requests\Document\UpdateDocumentRequest;
use App\Models\Document;
use App\Repositories\DocumentRepository;
use App\Transformers\DocumentTransformer;
use App\Utils\Traits\MakesHash;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DocumentController extends BaseController
{
    use MakesHash;

    protected $entity_type = Document::class;

    protected $entity_transformer = DocumentTransformer::class;

    /**
     * @var DocumentRepository
     */
    protected $document_repo;

    public function __construct(DocumentRepository $document_repo)
    {
        parent::__construct();

        $this->middleware('password_protected', ['only' => ['destroy']]);

        $this->document_repo = $document_repo;
    }

    /**
     * @OA\Get(
     *      path="/api/v1/documents",
     *      operationId="getDocuments",
     *      tags={"documents"},
     *      summary="Gets a list of documents",
     *      description="Lists documents, search and filters allow fine grained lists to be generated.

    Query parameters can be added to performed more fine grained filtering of the documents, these are handled by the DocumentsFilters class which defines the methods available",
     *      @OA\Parameter(ref="#/components/parameters/X-Api-Secret"),
     *      @OA\Parameter(ref="#/components/parameters/X-Api-Token"),
     *      @OA\Parameter(ref="#/components/parameters/X-Requested-With"),
     *      @OA\Parameter(ref="#/components/parameters/include"),
     *      @OA\Parameter(ref="#/components/parameters/index"),
     *      @OA\Response(
     *          response=200,
     *          description="A list of documents",
     *          @OA\Header(header="X-MINIMUM-CLIENT-VERSION", ref="#/components/headers/X-MINIMUM-CLIENT-VERSION"),
     *          @OA\Header(header="X-RateLimit-Remaining", ref="#/components/headers/X-RateLimit-Remaining"),
     *          @OA\Header(header="X-RateLimit-Limit", ref="#/components/headers/X-RateLimit-Limit"),
     *          @OA\JsonContent(ref="#/components/schemas/Document"),
     *       ),
     *       @OA\Response(
     *          response=422,
     *          description="Validation error",
     *          @OA\JsonContent(ref="#/components/schemas/ValidationError"),
     *       ),
     *       @OA\Response(
     *           response="default",
     *           description="Unexpected Error",
     *           @OA\JsonContent(ref="#/components/schemas/Error"),
     *       ),
     *     )
     * @param DocumentsFilters $filters
     * @return Response|mixed
     */
    public function index(DocumentFilters $filters)
    {
        $documents = Document::filter($filters);

        return $this->listResponse($documents);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return void
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreDocumentRequest $request
     * @return void
     */
    public function store(StoreDocumentRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param ShowDocumentRequest $request
     * @param Document $document
     * @return Response
     */
    public function show(ShowDocumentRequest $request, Document $document)
    {
        return $this->itemResponse($document);
    }

    public function download(ShowDocumentRequest $request, Document $document)
    {
        return response()->streamDownload(function () use ($document) {
            echo file_get_contents($document->generateUrl());
        }, basename($document->generateUrl()));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param EditDocumentRegquest $request
     * @param Document $document
     * @return Response
     */
    public function edit(EditDocumentRegquest $request, Document $document)
    {
        return $this->itemResponse($document);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateDocumentRequest $request
     * @param Document $document
     * @return Response
     */
    public function update(UpdateDocumentRequest $request, Document $document)
    {
        return $this->itemResponse($document);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param DestroyDocumentRequest $request
     * @param Document $document
     * @return Response
     */
    public function destroy(DestroyDocumentRequest $request, Document $document)
    {
        $this->document_repo->delete($document);

        return response()->json(['message'=> ctrans('texts.success')]);
    }

    public function bulk()
    {
        $action = request()->input('action');

        $ids = request()->input('ids');

        $documents = Document::withTrashed()->whereIn('id', $this->transformKeys($ids))->company()->get();

        if (! $invoices) {
            return response()->json(['message' => ctrans('texts.no_documents_found')]);
        }

        /*
         * Send the other actions to the switch
         */
        $documents->each(function ($document, $key) use ($action) {
            if (auth()->user()->can('edit', $document)) {
                $this->{$action}($document);
            }
        });

        /* Need to understand which permission are required for the given bulk action ie. view / edit */

        return $this->listResponse(Document::withTrashed()->whereIn('id', $this->transformKeys($ids))->company());
    }
}
