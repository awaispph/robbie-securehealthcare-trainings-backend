<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class MailConfigServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Mail configuration is handled via .env file
    }

    public function boot()
    {
        // Mail settings are loaded from config/mail.php which reads from .env
    }
}
