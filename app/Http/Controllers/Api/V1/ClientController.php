<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreClientRequest;
use App\Http\Requests\Api\V1\UpdateClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\Tenancy\CompanyContext;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ClientController extends Controller
{
    public function __construct(
        private readonly CompanyContext $companyContext
    ) {
    }

    public function index(): AnonymousResourceCollection
    {
        $clients = Client::query()
            ->where(
                'company_id',
                $this->companyContext->id()
            )
            ->orderBy('tax_name')
            ->paginate(20);

        return ClientResource::collection($clients);
    }

    public function store(
        StoreClientRequest $request
    ): ClientResource {
        $client = new Client();

        $client->company_id =
            $this->companyContext->id();

        $client->fill(
            $request->validated()
        );

        $client->save();

        return (new ClientResource($client))
	    ->response()
	    ->setStatusCode(
	        Response::HTTP_CREATED
	    );
    }

    public function show(
        int $client
    ): ClientResource {
        return new ClientResource(
            $this->findClientOrFail($client)
        );
    }

    public function update(
        UpdateClientRequest $request,
        int $client
    ): ClientResource {
        $clientModel =
            $this->findClientOrFail($client);

        $clientModel->fill(
            $request->validated()
        );

        $clientModel->save();

        return new ClientResource(
            $clientModel
        );
    }

    public function destroy(
        int $client
    ): Response {
        $clientModel =
            $this->findClientOrFail($client);

        $clientModel->delete();

        return response()->noContent();
    }

    private function findClientOrFail(
        int $clientId
    ): Client {
        return Client::query()
            ->where(
                'company_id',
                $this->companyContext->id()
            )
            ->findOrFail($clientId);
    }
}
