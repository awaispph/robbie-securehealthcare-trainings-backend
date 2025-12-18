<?php

namespace App\Traits;

use App\Services\LanguageService;

trait HasTranslations
{
    protected function getModuleTranslations($module, $type = 'messages')
    {
        return app(LanguageService::class)->getModuleTranslations($module, $type);
    }

    protected function getAllModuleTranslations($module)
    {
        return app(LanguageService::class)->getAllModuleTranslations($module);
    }
}
