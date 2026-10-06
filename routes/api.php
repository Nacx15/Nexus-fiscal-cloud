<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Tenancy\CompanyContext;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/v1/health', function () {
    return response()->json([
        'service' => 'nexus-fiscal-cloud',
        'status' => 'ok',
    ]);
});

Route::middleware([
    'auth:sanctum',
    'company',
])
    ->prefix('v1')
    ->group(function () {

        Route::get(
            '/context',
            function (CompanyContext $context) {
                return response()->json([
                    'company' => [
                        'id' => $context->id(),
                        'name' => $context->company()->name,
                        'slug' => $context->company()->slug,
                    ],
                ]);
            }
        );
    });
