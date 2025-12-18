<?php

use Carbon\Carbon;
use App\Models\GeneralSetting;
use Illuminate\Support\Facades\Cache;
use App\Helpers\ResourceActionButtonsHelper;

if (!function_exists('str_slug')) {
    function str_slug($string, $model = null)
    {
        // Convert string to lowercase and replace spaces with hyphens
        $slug = strtolower(str_replace(' ', '-', $string));

        // Remove special characters except underscore
        $slug = preg_replace('/[^A-Za-z0-9\-_]/', '', $slug);

        // Remove multiple hyphens
        $slug = preg_replace('/-+/', '-', $slug);

        // Trim hyphens from beginning and end
        $slug = trim($slug, '-');

        // If model is provided, ensure unique slug
        if ($model) {
            $originalSlug = $slug;
            $count = 1;

            // Keep checking until we find a unique slug
            while ($model::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $count++;
            }
        }

        return $slug;
    }
}

if (!function_exists('convertToLocalTime')) {
    function convertToLocalTime($dateTime)
    {
        if (empty($dateTime)) {
            return null;
        }

        try {
            $timezone = getGeneralSettings('timezone') ?? 'UTC';
            return Carbon::parse($dateTime)->timezone($timezone);
        } catch (\Exception $e) {
            return Carbon::parse($dateTime);
        }
    }
}

if (!function_exists('getCurrentDateTime')) {
    function getCurrentDateTime()
    {
        try {
            $timezone = getGeneralSettings('timezone') ?? 'UTC';
            return Carbon::now()->timezone($timezone);
        } catch (\Exception $e) {
            return Carbon::now();
        }
    }
}

if (!function_exists('getCurrentTime')) {
    function getCurrentTime()
    {
        return getCurrentDateTime();
    }
}

if (!function_exists('dateFormat')) {
    function dateFormat($date, $format = null)
    {
        if (empty($date)) {
            return null;
        }

        $localDateTime = convertToLocalTime($date);

        if ($format == null) {
            $dateFormat = getGeneralSettings('date_format');
            return $localDateTime->format($dateFormat);
        }
        return $localDateTime->format($format);
    }
}

if (!function_exists('timeFormat')) {
    function timeFormat($time, $format = null)
    {
        if (empty($time)) {
            return null;
        }

        $localDateTime = convertToLocalTime($time);

        if ($format == null) {
            $timeFormat = getGeneralSettings('time_format');
            $timeFormat = $timeFormat == 12 ? 'h:i A' : 'H:i';
            return $localDateTime->format($timeFormat);
        }
        return $localDateTime->format($format);
    }
}

if (!function_exists('dateTimeFormat')) {
    function dateTimeFormat($dateTime, $format = null)
    {
        if (empty($dateTime)) {
            return null;
        }

        $localDateTime = convertToLocalTime($dateTime);

        if ($format == null) {
            $dateFormat = getGeneralSettings('date_format');
            $timeFormat = getGeneralSettings('time_format');
            $timeFormat = $timeFormat == 12 ? 'h:i A' : 'H:i';
            return $localDateTime->format($dateFormat . ' ' . $timeFormat);
        }
        return $localDateTime->format($format);
    }
}

if (!function_exists('getGeneralSettings')) {
    function getGeneralSettings($key = null)
    {
        try {
            $settings = Cache::remember('general_settings', 60 * 60 * 12, function () {
                // Check if the table exists first
                if (!\Schema::hasTable('general_settings')) {
                    return [];
                }
                return GeneralSetting::pluck('value', 'key')->toArray();
            });

            if ($key) {
                return $settings[$key] ?? null;
            }

            $generalSetting = new GeneralSetting();
            $logoSettings = [
                'logo_dark_lg_path' => $generalSetting->logo_dark_lg_path,
                'logo_light_lg_path' => $generalSetting->logo_light_lg_path,
                'favicon_path' => $generalSetting->favicon_path,
            ];


            $documentSettings = [
                'user_document_max_size' => $generalSetting->getDocumentMaxSize(),
                'user_document_allowed_extensions' => $generalSetting->getDocumentAllowedExtensions(),
            ];

            return (object) array_merge((array) $settings, $logoSettings);
        } catch (\Exception $e) {
            // Return default values if there's any error
            if ($key) {
                return null;
            }
            return (object) [
                'logo_dark_lg_path' => null,
                'logo_light_lg_path' => null,
                'favicon_path' => null,
            ];
        }
    }
}

if (!function_exists('getCurrencySymbol')) {
    function getCurrencySymbol()
    {
        $currency = getGeneralSettings('currency');
        // remove the text before /
        $currency = explode('/', $currency)[1];
        return $currency;
    }
}

if (!function_exists('checkSelected')) {
    function checkSelected($value, $optionValue)
    {
        return $value == $optionValue ? 'selected' : '';
    }
}

if (!function_exists('getResourceActionButtonsHelper')) {
    function getResourceActionButtons($moduleId, $model, $hasViewButton = false, $viewButtonLabel = 'View', $viewButtonType = 'view', $viewButtonIcon = 'mdi-eye', $isModalButtons = false)
    {
        return (new ResourceActionButtonsHelper($moduleId, $model, $hasViewButton, $viewButtonLabel, $viewButtonType, $viewButtonIcon, $isModalButtons))->getActionsHtml();
    }
}

if (!function_exists('isMenuActive')) {
    function isMenuActive($item) {
        if (isset($item['url']) && request()->is($item['url'])) {
            return true;
        }

        if (isset($item['submenu'])) {
            foreach ($item['submenu'] as $subitem) {
                if (isMenuActive($subitem)) {
                    return true;
                }
            }
        }

        return false;
    }
}

if (!function_exists('formatFileSize')) {
    function formatFileSize($bytes)
    {
        if ($bytes === 0) {
            return "0 Bytes";
        }

        $units = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        return round($bytes / (1024 ** $pow), 2) . ' ' . $units[$pow];
    }
}
