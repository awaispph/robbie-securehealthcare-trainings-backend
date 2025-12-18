<?php

namespace App\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\File;

class LanguageService
{
    protected $availableLanguages;

    public function __construct()
    {
        $this->availableLanguages = config('languages.available');
    }

    public function setLocale($locale)
    {
        if (!array_key_exists($locale, $this->availableLanguages)) {
            $locale = config('app.fallback_locale');
        }

        App::setLocale($locale);
        Session::put('locale', $locale);

        // Set the direction (RTL/LTR)
        $direction = in_array($locale, config('languages.rtl')) ? 'rtl' : 'ltr';
        Session::put('direction', $direction);

        return $locale;
    }

    public function getCurrentLocale()
    {
        return App::getLocale();
    }

    public function getDirection()
    {
        return Session::get('direction', 'ltr');
    }

    public function getModuleTranslations($module, $type = 'messages')
    {
        $locale = $this->getCurrentLocale();
        $cacheKey = "translations.{$locale}.{$module}.{$type}";

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($locale, $module, $type) {
            // Load common translations
            $common = trans('common', [], $locale);

            // Load module-specific translations
            $modulePath = resource_path("lang/{$locale}/{$module}/{$type}.php");
            $moduleTranslations = File::exists($modulePath) ? require $modulePath : [];

            return array_merge($common, $moduleTranslations);
        });
    }

    public function getAllModuleTranslations($module)
    {
        $locale = $this->getCurrentLocale();
        $cacheKey = "translations.{$locale}.{$module}.all";

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($locale, $module) {
            $translations = [];
            $moduleDir = resource_path("lang/{$locale}/{$module}");

            if (File::isDirectory($moduleDir)) {
                foreach (File::files($moduleDir) as $file) {
                    $type = pathinfo($file, PATHINFO_FILENAME);
                    $translations[$type] = require $file;
                }
            }

            return $translations;
        });
    }

    public function switchLanguage($locale)
    {
        $previousLocale = app()->getLocale();
        $newLocale = $this->setLocale($locale);

        return [
            'success' => true,
            'locale' => $newLocale,
            'direction' => $this->getDirection(),
            'previous' => $previousLocale
        ];
    }

    public function getLanguageData()
    {
        return [
            'available' => $this->availableLanguages,
            'current' => $this->getCurrentLocale(),
            'direction' => $this->getDirection()
        ];
    }
}
