<?php

namespace App\Traits;

use App\Models\User;
use App\Mail\DynamicEmail;
use App\Models\EmailTemplate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

trait EmailTemplateTrait
{
    public function sendEmailByEvent(array $data): bool
    {
        // Get the template name from the event object
        $templateEventName = $data['event'];

        // Find template
        $template = EmailTemplate::where('email_template_event', $templateEventName)
            ->where('email_template_status', 1)
            ->first();

        if (!$template) {
            Log::warning("No active email template found for event Test of : {$templateEventName}");
            return false;
        }

        // Validate required data
        $requiredVariables = $this->extractVariables($template->email_template_variables);
        $missingVariables = $this->findMissingVariables($requiredVariables, $data);

        if (!empty($missingVariables)) {
            Log::error("Missing required variables for {$templateEventName}", [
                'missing' => $missingVariables
            ]);
            return false;
        }

        // Process email content
        $subject = $this->replaceVariables($template->email_template_subject, $data);
        $body = $this->replaceVariables($template->email_template_body, $data);

        // Determine recipients
        $to = $template->email_template_type == 2
            ? array_filter(array_map('trim', explode(',', $template->email_template_emails)))
            : $data['Email'];

        // Log::info('Email Template Body:', [
        //     'event' => $templateEventName,
        //     'body' => $body,
        //     'to' => $to
        // ]);

        // For type 2, send to multiple recipients
        if ($template->email_template_type == 2) {
            foreach ($to as $recipient) {
                Mail::to($recipient)->send(new DynamicEmail($subject, $body));
                // Log::info('Email sent to: ' . $recipient);
            }
            // Log::info('Email sent to multiple recipients');
        } else {
            Mail::to($to)->send(new DynamicEmail($subject, $body));
            // Log::info('Email sent to: ' . $to);
        }
        return true;
    }

    private function extractVariables(string $variablesString): array
    {
        preg_match_all('/{([^}]+)}/', $variablesString, $matches);
        return $matches[1] ?? [];
    }

    private function findMissingVariables(array $required, array $provided): array
    {
        return array_filter($required, function ($var) use ($provided) {
            return !isset($provided[$var]);
        });
    }

    private function replaceVariables(string $content, array $data): string
    {
        return preg_replace_callback('/{([^}]+)}/', function ($matches) use ($data) {
            $key = $matches[1];
            return $data[$key] ?? $matches[0];
        }, $content);
    }
}
