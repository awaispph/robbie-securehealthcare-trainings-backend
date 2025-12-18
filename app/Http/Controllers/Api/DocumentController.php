<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CandidateCourseDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'event_id' => 'required|exists:events,id',
            'candidate_id' => 'required|exists:candidates,id',
            'course_id' => 'required|exists:courses,id',
        ]);

        $documents = CandidateCourseDocument::where('event_id', $validated['event_id'])
            ->where('candidate_id', $validated['candidate_id'])
            ->where('course_id', $validated['course_id'])
            ->orderBy('uploaded_at', 'desc')
            ->get();

        return response()->json(['data' => $documents]);
    }

    public function upload(Request $request)
    {
        $validated = $request->validate([
            'event_id' => 'required|exists:events,id',
            'candidate_id' => 'required|exists:candidates,id',
            'course_id' => 'required|exists:courses,id',
            'file' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx',
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $mimeType = $file->getMimeType();
        $fileSize = $file->getSize();

        // Generate unique file path
        $fileName = Str::uuid() . '.' . $extension;
        $path = "documents/event_{$validated['event_id']}/candidate_{$validated['candidate_id']}/course_{$validated['course_id']}/{$fileName}";

        // Store file - use S3 if configured, otherwise local public disk
        $disk = config('filesystems.default') === 's3' ? 's3' : 'public';
        Storage::disk($disk)->put($path, file_get_contents($file));

        $document = CandidateCourseDocument::create([
            'event_id' => $validated['event_id'],
            'candidate_id' => $validated['candidate_id'],
            'course_id' => $validated['course_id'],
            'file_name' => $originalName,
            'file_path' => $path,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
            'uploaded_at' => now(),
        ]);

        return response()->json([
            'data' => $document,
            'message' => 'Document uploaded successfully'
        ], 201);
    }

    public function destroy(CandidateCourseDocument $document)
    {
        // Delete file from storage
        $disk = config('filesystems.default') === 's3' ? 's3' : 'public';
        if (Storage::disk($disk)->exists($document->file_path)) {
            Storage::disk($disk)->delete($document->file_path);
        }

        $document->delete();

        return response()->json(['message' => 'Document deleted successfully']);
    }

    public function download(CandidateCourseDocument $document)
    {
        $disk = config('filesystems.default') === 's3' ? 's3' : 'public';
        
        if (!Storage::disk($disk)->exists($document->file_path)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        return Storage::disk($disk)->download($document->file_path, $document->file_name);
    }

    // Get document counts for attendance grid
    public function counts(Request $request)
    {
        $validated = $request->validate([
            'event_id' => 'required|exists:events,id',
        ]);

        $counts = CandidateCourseDocument::where('event_id', $validated['event_id'])
            ->selectRaw('candidate_id, course_id, COUNT(*) as count')
            ->groupBy('candidate_id', 'course_id')
            ->get()
            ->groupBy('candidate_id')
            ->map(function ($items) {
                return $items->pluck('count', 'course_id');
            });

        return response()->json(['data' => $counts]);
    }
}
