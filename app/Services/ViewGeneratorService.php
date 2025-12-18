<?php

namespace App\Services;

use Illuminate\Support\Str;

class ViewGeneratorService
{
    public function generateFormFields(array $fields, string $moduleName)
    {
        $formFields = [];
        foreach ($fields as $field) {
            $formFields[] = $this->generateFormField($field, $moduleName);
        }
        return implode("\n", $formFields);
    }

    protected function generateFormField($field, string $moduleName)
    {
        $html = '<div class="col-lg-6 col-sm-12 mb-3">' . PHP_EOL;

        // Generate label
        $html .= $this->generateLabel($field, $moduleName);

        // Generate field based on type
        switch ($field['type']) {
            case 'select':
            case 'multiselect':
                $html .= $this->generateSelectInput($field, $moduleName);
                break;

            case 'radio':
                $html .= $this->generateRadioInput($field, $moduleName);
                break;

            case 'checkbox':
                $html .= $this->generateCheckboxInput($field, $moduleName);
                break;

            case 'textarea':
                $html .= $this->generateTextareaInput($field, $moduleName);
                break;

            case 'date':
                $html .= $this->generateDateInput($field, $moduleName);
                break;

            case 'time':
                $html .= $this->generateTimeInput($field, $moduleName);
                break;

            case 'datetime':
            case 'timestamp':
                $html .= $this->generateDateTimeInput($field, $moduleName);
                break;

            default:
                $html .= $this->generateDefaultInput($field, $moduleName);
        }

        $html .= '</div>' . PHP_EOL;
        return $html;
    }

    protected function generateLabel($field, $moduleName)
    {
        $labelPath = $moduleName . '/' . $moduleName . '.fields.' . $field['name'] . '.label';
        $required = $this->isFieldRequired($field) ? ' <span class="text-danger">*</span>' : '';

        return sprintf(
            '    <label for="{{ $action }}_%s" class="form-label">{{ __("%s") }}%s</label>%s',
            $field['name'],
            $labelPath,
            $required,
            PHP_EOL
        );
    }

    protected function generateRadioInput($field, $moduleName)
    {
        $placeholderPath = $moduleName . '/' . $moduleName . '.fields.' . $field['name'] . '.placeholder';
        $html = '    <div class="d-flex flex-wrap gap-3">' . PHP_EOL;

        if (isset($field['options'])) {
            foreach ($field['options'] as $option) {
                $id = sprintf('{{ $action }}_%s_%s', $field['name'], Str::slug($option['value']));
                $html .= '        <div class="form-check form-check-inline">' . PHP_EOL;
                $html .= sprintf(
                    '            <input class="form-check-input" type="radio" name="%s" id="%s" value="%s"%s>%s',
                    $field['name'],
                    $id,
                    $option['value'],
                    $this->getInputAttributes($field),
                    PHP_EOL
                );
                $html .= sprintf(
                    '            <label class="form-check-label" for="%s">%s</label>%s',
                    $id,
                    $option['label'],
                    PHP_EOL
                );
                $html .= '        </div>' . PHP_EOL;
            }
        }

        $html .= '    </div>' . PHP_EOL;
        return $html;
    }

    protected function generateSelectInput($field, $moduleName)
    {
        $placeholderPath = $moduleName . '/' . $moduleName . '.fields.' . $field['name'] . '.placeholder';
        $isMultiple = $field['type'] === 'multiselect';

        $html = sprintf(
            '    <select class="form-control select2-field" %s id="{{ $action }}_%s" name="%s%s" data-placeholder="{{ __("%s") }}"%s>%s',
            $isMultiple ? 'multiple' : '',
            $field['name'],
            $field['name'],
            $isMultiple ? '[]' : '',
            $placeholderPath,
            $this->getInputAttributes($field),
            PHP_EOL
        );

        if (isset($field['options'])) {
            foreach ($field['options'] as $option) {
                $html .= sprintf(
                    '        <option value="%s">%s</option>%s',
                    $option['value'],
                    $option['label'],
                    PHP_EOL
                );
            }
        }

        $html .= '    </select>' . PHP_EOL;
        return $html;
    }

    protected function generateCheckboxInput($field, $moduleName)
    {
        $html = '    <div class="form-check">' . PHP_EOL;
        $html .= sprintf(
            '        <input type="checkbox" class="form-check-input" id="{{ $action }}_%s" name="%s" value="1"%s>%s',
            $field['name'],
            $field['name'],
            $this->getInputAttributes($field),
            PHP_EOL
        );
        $html .= '    </div>' . PHP_EOL;
        return $html;
    }

    protected function generateDateInput($field, $moduleName)
    {
        $html = sprintf(
            '    <input type="date" class="form-control flatpickr-date-input" id="{{ $action }}_%s" name="%s"%s>%s',
            $field['name'],
            $field['name'],
            $this->getInputAttributes($field),
            PHP_EOL
        );
        return $html;
    }

    protected function generateTimeInput($field, $moduleName)
    {
        $html = sprintf(
            '    <input type="time" class="form-control flatpickr-time-input" id="{{ $action }}_%s" name="%s"%s>%s',
            $field['name'],
            $field['name'],
            $this->getInputAttributes($field),
            PHP_EOL
        );
        return $html;
    }

    protected function generateDateTimeInput($field, $moduleName)
    {
        $html = sprintf(
            '    <input type="datetime" class="form-control flatpickr-datetime-input" id="{{ $action }}_%s" name="%s"%s>%s',
            $field['name'],
            $field['name'],
            $this->getInputAttributes($field),
            PHP_EOL
        );
        return $html;
    }


    protected function generateTextareaInput($field, $moduleName)
    {
        $placeholderPath = $moduleName . '/' . $moduleName . '.fields.' . $field['name'] . '.placeholder';
        return sprintf(
            '    <textarea class="form-control" id="{{ $action }}_%s" name="%s" placeholder="{{ __("%s") }}"%s></textarea>%s',
            $field['name'],
            $field['name'],
            $placeholderPath,
            $this->getInputAttributes($field),
            PHP_EOL
        );
    }

    protected function generateDefaultInput($field, $moduleName)
    {
        $placeholderPath = $moduleName . '/' . $moduleName . '.fields.' . $field['name'] . '.placeholder';
        return sprintf(
            '    <input type="%s" class="form-control" id="{{ $action }}_%s" name="%s" placeholder="{{ __("%s") }}"%s>%s',
            $field['type'],
            $field['name'],
            $field['name'],
            $placeholderPath,
            $this->getInputAttributes($field),
            PHP_EOL
        );
    }

    protected function getInputAttributes($field)
    {
        $attributes = [];

        if ($this->isFieldRequired($field)) {
            $attributes[] = 'required';
        }

        if (!empty($field['frontend_validation'])) {
            foreach ($field['frontend_validation'] as $validation) {
                switch ($validation) {
                    case 'maxLength':
                        $maxLength = $field['frontend_params']['maxLength'] ?? '';
                        $attributes[] = 'maxlength="' . $maxLength . '"';
                        break;
                    case 'pattern':
                        $pattern = $field['frontend_params']['pattern'] ?? '';
                        $attributes[] = 'pattern="' . $pattern . '"';
                        break;
                    // Add more validation types as needed
                }
            }
        }

        return !empty($attributes) ? ' ' . implode(' ', $attributes) : '';
    }

    protected function isFieldRequired($field)
    {
        return in_array('required', $field['frontend_validation'] ?? []);
    }

    protected function getRequiredStar(array $field)
    {
        return in_array('required', $field['validation'] ?? []) ? '<span class="text-danger">*</span>' : '';
    }

    protected function generateInputField(array $field)
    {
        $attributes = $this->getFieldAttributes($field);
        return <<<HTML
        <input type="{$field['type']}"
               class="form-control"
               id="{{ \$action }}_{$field['name']}"
               name="{$field['name']}"
               {$attributes}
               value="{{ old('{$field['name']}', \${$field['name']} ?? '') }}">
        HTML;
    }

    protected function generateTextareaField(array $field)
    {
        $attributes = $this->getFieldAttributes($field);
        return <<<HTML
        <textarea class="form-control"
                  id="{{ \$action }}_{$field['name']}"
                  name="{$field['name']}"
                  {$attributes}>{{ old('{$field['name']}', \${$field['name']} ?? '') }}</textarea>
        HTML;
    }

    protected function generateSelectField(array $field)
    {
        $attributes = $this->getFieldAttributes($field);
        $options = $this->generateSelectOptions($field['options'] ?? []);
        return <<<HTML
        <select class="form-select"
                id="{{ \$action }}_{$field['name']}"
                name="{$field['name']}"
                {$attributes}>
            <option value="">{{ __('Select {$field['label']}') }}</option>
            {$options}
        </select>
        HTML;
    }

    protected function generateSelectOptions(array $options)
    {
        $optionsHtml = [];
        foreach ($options as $value => $label) {
            $optionsHtml[] = <<<HTML
            <option value="{$value}">{{ __('$label') }}</option>
            HTML;
        }
        return implode("\n", $optionsHtml);
    }

    protected function generateRadioField(array $field)
    {
        $attributes = $this->getFieldAttributes($field);
        return <<<HTML
        <input type="radio"
               id="{{ \$action }}_{$field['name']}"
               name="{$field['name']}"
               {$attributes}>
        HTML;
    }

    protected function generateCheckboxField(array $field)
    {
        $attributes = $this->getFieldAttributes($field);
        return <<<HTML
        <input type="checkbox"
               id="{{ \$action }}_{$field['name']}"
               name="{$field['name']}"
               {$attributes}>
        HTML;
    }

    protected function generateFileField(array $field)
    {
        $attributes = $this->getFieldAttributes($field);
        return <<<HTML
        <input type="file"
               class="form-control"
               id="{{ \$action }}_{$field['name']}"
               name="{$field['name']}"
               {$attributes}>
        HTML;
    }

    protected function generateDateField(array $field)
    {
        $attributes = $this->getFieldAttributes($field);
        return <<<HTML
        <input type="date"
               class="form-control"
               id="{{ \$action }}_{$field['name']}"
               name="{$field['name']}"
               {$attributes}>
        HTML;
    }

    protected function generateTimeField(array $field)
    {
        $attributes = $this->getFieldAttributes($field);
        return <<<HTML
        <input type="time"
               class="form-control flatpickr-time-input"
               id="{{ \$action }}_{$field['name']}"
               name="{$field['name']}"
               {$attributes}>
        HTML;
    }

    protected function getFieldAttributes(array $field)
    {
        $attributes = [];

        if (!empty($field['placeholder'])) {
            $attributes[] = 'placeholder="' . e($field['placeholder']) . '"';
        }

        if (!empty($field['validation'])) {
            foreach ($field['validation'] as $rule) {
                switch ($rule) {
                    case 'required':
                        $attributes[] = 'required';
                        break;
                    case 'email':
                        $attributes[] = 'pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"';
                        break;
                    case 'min':
                        $attributes[] = 'minlength="' . $field['validation_params']['min'] . '"';
                        break;
                    case 'max':
                        $attributes[] = 'maxlength="' . $field['validation_params']['max'] . '"';
                        break;
                    case 'numeric':
                        $attributes[] = 'pattern="[0-9]+"';
                        break;
                }
            }
        }

        return implode(' ', $attributes);
    }

    public function generateTranslationFiles($data)
    {
        foreach ($data['translations'] as $locale => $translation) {
            $translations = [
                'page_card_title' => ':plural_name',
                'table_headers' => array_merge(
                    $this->generateTableHeaders($translation['fields']),
                    [
                        'created_at' => trans('common.attributes.created_at', [], $locale),
                        'actions' => trans('common.attributes.actions', [], $locale)
                    ]
                ),
                'modal_card_title' => [
                    'add' => trans('common.actions.add_new', [], $locale) . ' :singular_name',
                    'edit' => trans('common.actions.edit', [], $locale) . ' :singular_name'
                ],
                'fields' => $this->generateFieldTranslations($translation['fields']),
                'buttons' => [
                    'submit' => trans('common.actions.submit', [], $locale),
                    'update' => trans('common.actions.update', [], $locale),
                    'close' => trans('common.actions.close', [], $locale)
                ],
                'messages' => [
                    'success' => [
                        'created' => ':singular_name ' . trans('common.messages.created', [], $locale),
                        'updated' => ':singular_name ' . trans('common.messages.updated', [], $locale),
                        'archived' => ':singular_name ' . trans('common.messages.archived', [], $locale),
                        'deleted' => ':singular_name ' . trans('common.messages.deleted', [], $locale),
                        'restored' => ':singular_name ' . trans('common.messages.restored', [], $locale),
                    ],
                    'errors' => [
                        'create_failed' => trans('common.messages.create_failed', [], $locale) . ' :singular_name',
                        'update_failed' => trans('common.messages.update_failed', [], $locale) . ' :singular_name',
                        'delete_failed' => trans('common.messages.delete_failed', [], $locale) . ' :singular_name',
                        'restore_failed' => trans('common.messages.restore_failed', [], $locale) . ' :singular_name',
                    ],
                ],
                'validation' => $this->generateValidationMessages($translation['fields'])
            ];

            $modulePath = $fileName = Str::studly($data['name']);
            // $fileName = Str::lower($data['name']);

            $langPath = lang_path($locale . '/' . $modulePath);
            if (!file_exists($langPath)) {
                mkdir($langPath, 0755, true);
            }

            $langFile = $langPath . '/' . $fileName . '.php';
            $content = "<?php\n\nreturn " . var_export($translations, true) . ";\n";
            file_put_contents($langFile, $content);
        }

        return true;
    }

    private function generateTableHeaders($fields)
    {
        $headers = [];
        foreach ($fields as $fieldName => $fieldData) {
            $headers[$fieldName] = $fieldData['label'] ?? Str::title($fieldName);
        }
        return $headers;
    }

    private function generateFieldTranslations($fields)
    {
        $translations = [];
        foreach ($fields as $fieldName => $fieldData) {
            $translations[$fieldName] = [
                'label' => $fieldData['label'],
                'placeholder' => $fieldData['placeholder'] ?? 'Enter ' . Str::title($fieldData['label']),
            ];
        }
        return $translations;
    }

    private function generateValidationMessages($fields)
    {
        $messages = [];
        foreach ($fields as $fieldName => $fieldData) {
            if (!empty($fieldData['validation'])) {
                foreach ($fieldData['validation'] as $rule) {
                    $messages[$fieldName . '.' . $rule] = $this->getValidationMessage($fieldData['label'], $rule);
                }
            }
        }
        return $messages;
    }

    private function getValidationMessage($fieldName, $rule)
    {
        $fieldLabel = Str::title($fieldName);
        switch ($rule) {
            case 'required':
                return "The {$fieldLabel} field is required.";
            case 'max':
                return "The {$fieldLabel} field must not be greater than :max.";
            case 'string':
                return "The {$fieldLabel} field is invalid.";
            default:
                return "The {$fieldLabel} field is invalid.";
        }
    }
}
