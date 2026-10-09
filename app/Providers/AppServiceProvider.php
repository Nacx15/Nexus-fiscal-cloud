<?php

namespace App\Providers;

use App\Application\Fiscal\Contracts\PacClient;
use App\Infrastructure\Pac\FakePacClient;
use App\Tenancy\CompanyContext;
use Illuminate\Support\ServiceProvider;
use LogicException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Company Context
        |--------------------------------------------------------------------------
        */

        $this->app->scoped(
            CompanyContext::class,
            fn () => new CompanyContext()
        );

        /*
        |--------------------------------------------------------------------------
        | PAC Client
        |--------------------------------------------------------------------------
        */

        $this->app->bind(
            PacClient::class,
            function ($app) {
                return match (
                    config('pac.driver')
                ) {
                    'fake' =>
                        $app->make(
                            FakePacClient::class
                        ),

                    default =>
                        throw new LogicException(
                            'Unsupported PAC driver: '
                            . config('pac.driver')
                        ),
                };
            }
        );
    }

    public function boot(): void
    {
        //
    }
}
