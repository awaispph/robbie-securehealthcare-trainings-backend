<?php

namespace App\Services;

use App\Models\Module;
use App\Models\Country;
use App\Models\GeneralSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class GeneralSettingService
{
    public function getPageData($url)
    {
        $module = Module::with(['translations' => function($query) {
            $query->where('locale', app()->getLocale());
        }])->where('url', $url)->first();

        $translation = $module->translations->first();

        $data = (object) [
            'singular_name' => $translation ? $translation->singular_name : $module->singular_name,
            'plural_name' => $translation ? $translation->plural_name : $module->plural_name,
            'module_id' => $module->id
        ];

        return $data;
    }

    public function getAllSettings()
    {
        $generalSettings = GeneralSetting::all();
        $keyedSettings = $generalSettings->keyBy('key');
        $mappedSettings = $keyedSettings->map(function ($item) {
            return $item['value'];
        });

        return (object) $mappedSettings->toArray();
    }

    public function getCountryDetails($countryId)
    {
        $country = Country::find($countryId);

        $currencies = [[
            'text' => $country->currency . ' (' . $country->currency_symbol . ')',
            'value' => $country->currency . '/' . $country->currency_symbol
        ]];

        $timezones = json_decode($country->timezones, true);
        $formattedTimezones = array_map(function ($timezone) {
            return [
                'text' => $timezone['zoneName'] . ' (' . $timezone['gmtOffsetName'] . ')',
                'value' => $timezone['zoneName']
            ];
        }, $timezones);

        return [
            'currencies' => $currencies,
            'timezones' => $formattedTimezones
        ];
    }

    public function updateSettings($data, $files = [])
    {
        // Handle document extensions array
        if (isset($data['user_document_allowed_extensions']) && is_array($data['user_document_allowed_extensions'])) {
            $data['user_document_allowed_extensions'] = implode(',', $data['user_document_allowed_extensions']);
        }

        foreach ($data as $key => $value) {
            if (in_array($key, $files) && request()->hasFile($key)) {
                $value = $this->storeImage($key);
            }

            GeneralSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        Cache::forget('general_settings');
        return getGeneralSettings();
    }

    private function storeImage($key)
    {
        if (request()->hasFile($key)) {
            $file = request()->file($key);

            // Delete old image if exists
            $oldValue = GeneralSetting::getValue($key);
            if ($oldValue && Storage::exists($oldValue)) {
                Storage::delete($oldValue);
            }

            // Define storage path and store file
            $path = 'general-settings/logo';
            $filename = $file->hashName();
            return Storage::disk('public')->putFileAs($path, $file, $filename);
        }

        return null;
    }

    public function updateMenuItems($menuItems)
    {
        try {
            DB::beginTransaction();

            foreach ($menuItems as $item) {
                // Validate translations
                if (empty($item['translations'])) {
                    throw new \Exception('Translations are required for all menu items');
                }

                // Check if all required languages have translations
                $requiredLocales = config('locales.available');
                foreach ($requiredLocales as $locale) {
                    if (!isset($item['translations'][$locale])) {
                        throw new \Exception("Missing translations for language: {$locale}");
                    }

                    $translation = $item['translations'][$locale];
                    if (empty($translation['singular_name']) || empty($translation['plural_name'])) {
                        throw new \Exception("Both singular and plural names are required for {$locale} language");
                    }
                }

                // Update the module
                $module = Module::findOrFail($item['id']);
                $module->parent_id = $item['parent_id'];
                $module->sort_order = $item['sort_order'];
                $module->save();

                // Update or create translations for all languages
                foreach ($item['translations'] as $locale => $translation) {
                    $module->translations()->updateOrCreate(
                        [
                            'locale' => $locale
                        ],
                        [
                            'singular_name' => trim($translation['singular_name']),
                            'plural_name' => trim($translation['plural_name'])
                        ]
                    );
                }
            }

            DB::commit();
            return [
                'success' => true,
                'message' => 'Menu settings updated successfully'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating menu items: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
