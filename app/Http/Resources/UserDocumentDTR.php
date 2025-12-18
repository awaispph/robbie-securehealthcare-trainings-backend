<?php

namespace App\Http\Resources;

use App\Services\UserDocumentService;
use Illuminate\Http\Resources\Json\JsonResource;

class UserDocumentDTR extends JsonResource
{
    public static $moduleId;

    public static function collection($resource)
    {
        $service = app(UserDocumentService::class);
        self::$moduleId = $service->getModuleFromUrl()->id;
        return parent::collection($resource);
    }

    public function toArray($request)
    {
        $filePath = storage_path('app/public/' . $this->file_path);
        $fileSize = file_exists($filePath) ? formatFileSize(filesize($filePath)) : 'N/A';

        return [
            'id' => $this->id,
            'title' => $this->title,
            'file_name' => $this->file_name,
            'file_type' => pathinfo($this->file_name, PATHINFO_EXTENSION),
            'file_size' => $fileSize,
            'document_type' => $this->document_type,
            'description' => $this->description,
            'expiry_date' => $this->expiry_date ? dateFormat($this->expiry_date) : null,
            'created_by' => $this->creator ? $this->creator->name : null,
            'created_at' => dateTimeFormat($this->created_at),
            'action' => getResourceActionButtons(self::$moduleId, $this, true, 'Download', 'download', 'mdi-download', true),
            'download_url' => route('download-user-document', $this->id),
        ];
    }
}
