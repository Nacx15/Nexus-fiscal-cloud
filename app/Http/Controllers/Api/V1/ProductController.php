<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreProductRequest;
use App\Http\Requests\Api\V1\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Tenancy\CompanyContext;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    public function __construct(
        private readonly CompanyContext $companyContext
    ) {
    }

    public function index(): AnonymousResourceCollection
    {
        $products = Product::query()
            ->forCurrentCompany()
            ->orderBy('description')
            ->paginate(20);

        return ProductResource::collection(
            $products
        );
    }

    public function store(
        StoreProductRequest $request
    ) {
        $product = new Product();

        $product->company_id =
            $this->companyContext->id();

        $product->fill(
            $request->validated()
        );

        $product->save();

        return (new ProductResource($product))
            ->response()
            ->setStatusCode(
                Response::HTTP_CREATED
            );
    }

    public function show(
        int $product
    ): ProductResource {
        return new ProductResource(
            $this->findProductOrFail(
                $product
            )
        );
    }

    public function update(
        UpdateProductRequest $request,
        int $product
    ): ProductResource {
        $productModel =
            $this->findProductOrFail(
                $product
            );

        $productModel->fill(
            $request->validated()
        );

        $productModel->save();

        return new ProductResource(
            $productModel
        );
    }

    public function destroy(
        int $product
    ): Response {
        $productModel =
            $this->findProductOrFail(
                $product
            );

        $productModel->delete();

        return response()->noContent();
    }

    private function findProductOrFail(
        int $productId
    ): Product {
        return Product::query()
            ->forCurrentCompany()
            ->findOrFail($productId);
    }
}
