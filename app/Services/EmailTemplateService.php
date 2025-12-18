<?php

namespace App\Services;

use App\Http\Resources\EmailTemplateDTR;
use App\Models\EmailTemplate;

class EmailTemplateService extends BaseService
{
    public function __construct(EmailTemplate $model)
    {
        parent::__construct($model, EmailTemplateDTR::class);
    }

    // public function getSingle($id)
    // {
    //     $emailTemplates = EmailTemplate::orderBy('sort_order', 'ASC')->get();
    //     $emailTemplate = collect($emailTemplates)->where('id', $id)->first();
    //     return ['data' => $emailTemplate, 'AllEmailTemplates' => $emailTemplates];
    // }

    // public function getParents(){

    //     return EmailTemplate::select('id','name', 'subject')->orderBy('sort_order', 'ASC')->get();
    // }

    public function getDefaultSearchColumns()
    {
        return [
            'email_template_subject',
            'email_template_event',
            'created_at'
        ];
    }
}
