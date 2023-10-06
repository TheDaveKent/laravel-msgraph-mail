<?php

namespace InnoGE\LaravelMsGraphMail;

use Illuminate\Support\Facades\Mail;
use InnoGE\LaravelMsGraphMail\Exceptions\ConfigurationMissing;
use InnoGE\LaravelMsGraphMail\Services\MicrosoftGraphApiService;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelMsGraphMailServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-msgraph-mail')
            ->hasConfigFile();
    }

    public function boot(): void
    {
        $this->app->bind(MicrosoftGraphApiService::class, function () {
            //throw exceptions when config is missing
            throw_unless(filled(config('mail.mailers.microsoft-graph.client_id')), ConfigurationMissing::clientId());
            throw_unless(filled(config('mail.mailers.microsoft-graph.client_secret')), ConfigurationMissing::clientSecret());

            return new MicrosoftGraphApiService(
                clientId: config('mail.mailers.microsoft-graph.client_id', ''),
                clientSecret: config('mail.mailers.microsoft-graph.client_secret', ''),
                accessTokenTtl: config('mail.mailers.microsoft-graph.access_token_ttl', 3000),
            );
        });

        Mail::extend('microsoft-graph', function (array $config) {
            return new MicrosoftGraphTransport(
                app()->make(MicrosoftGraphApiService::class));
        });

        // allows for override at runtime
        Mail::macro('setTenantId', function($tenantId){
            $this->getSymfonyTransport()->setTenantId($tenantId);
            return $this;
        });

    }
}
