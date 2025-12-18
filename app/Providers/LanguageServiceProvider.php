<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Services\LanguageService;

class LanguageServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(LanguageService::class, function ($app) {
            return new LanguageService();
        });
    }

    public function boot()
    {
        // Share available languages with all views
        View::composer('*', function ($view) {
            $view->with('available_languages', config('languages.available'));
        });
    }
}
