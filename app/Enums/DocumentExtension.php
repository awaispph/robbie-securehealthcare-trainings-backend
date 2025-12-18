<?php

namespace App\Enums;

enum DocumentExtension: string
{
    // Documents
    case PDF = 'pdf';
    case DOC = 'doc';
    case DOCX = 'docx';
    case XLS = 'xls';
    case XLSX = 'xlsx';
    case PPT = 'ppt';
    case PPTX = 'pptx';
    case TXT = 'txt';
    case RTF = 'rtf';
    case CSV = 'csv';

    // Images
    case JPG = 'jpg';
    case JPEG = 'jpeg';
    case PNG = 'png';
    case GIF = 'gif';
    case SVG = 'svg';
    case WEBP = 'webp';

    // Open Document Formats
    case ODT = 'odt';  // OpenDocument Text
    case ODS = 'ods';  // OpenDocument Spreadsheet
    case ODP = 'odp';  // OpenDocument Presentation

    // Archives
    case ZIP = 'zip';
    case RAR = 'rar';
    case _7Z = '7z';

    public static function getDefaultExtensions(): array
    {
        return [
            self::PDF->value,
            self::DOC->value,
            self::DOCX->value,
            self::JPG->value,
            self::JPEG->value,
            self::PNG->value,
        ];
    }

    public static function getGroupedExtensions(): array
    {
        return [
            'Documents' => [
                self::PDF->value => 'PDF Document',
                self::DOC->value => 'Word Document (DOC)',
                self::DOCX->value => 'Word Document (DOCX)',
                self::XLS->value => 'Excel Spreadsheet (XLS)',
                self::XLSX->value => 'Excel Spreadsheet (XLSX)',
                self::PPT->value => 'PowerPoint (PPT)',
                self::PPTX->value => 'PowerPoint (PPTX)',
                self::TXT->value => 'Text File',
                self::RTF->value => 'Rich Text Format',
                self::CSV->value => 'CSV File',
            ],
            'Images' => [
                self::JPG->value => 'JPG Image',
                self::JPEG->value => 'JPEG Image',
                self::PNG->value => 'PNG Image',
                self::GIF->value => 'GIF Image',
                self::SVG->value => 'SVG Vector Image',
                self::WEBP->value => 'WebP Image',
            ],
            'Open Document' => [
                self::ODT->value => 'OpenDocument Text',
                self::ODS->value => 'OpenDocument Spreadsheet',
                self::ODP->value => 'OpenDocument Presentation',
            ],
            'Archives' => [
                self::ZIP->value => 'ZIP Archive',
                self::RAR->value => 'RAR Archive',
                self::_7Z->value => '7Z Archive',
            ],
        ];
    }

    public static function getMimeType(string $extension): string
    {
        return match ($extension) {
            self::PDF->value => 'application/pdf',
            self::DOC->value => 'application/msword',
            self::DOCX->value => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            self::XLS->value => 'application/vnd.ms-excel',
            self::XLSX->value => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            self::PPT->value => 'application/vnd.ms-powerpoint',
            self::PPTX->value => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            self::JPG->value, self::JPEG->value => 'image/jpeg',
            self::PNG->value => 'image/png',
            self::GIF->value => 'image/gif',
            self::SVG->value => 'image/svg+xml',
            self::WEBP->value => 'image/webp',
            default => 'application/octet-stream',
        };
    }
}
