<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\EventCandidateCourse;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class CertificateController extends Controller
{
    public function index(Request $request)
    {
        $query = Certificate::with(['candidate', 'event', 'course']);

        if ($request->has('candidate_id')) {
            $query->where('candidate_id', $request->candidate_id);
        }

        if ($request->has('event_id')) {
            $query->where('event_id', $request->event_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('published')) {
            $query->where('published', $request->published === 'true');
        }

        $certificates = $query->orderBy('issued_date', 'desc')->paginate($request->per_page ?? 15);

        return response()->json([
            'data' => $certificates->items(),
            'meta' => [
                'current_page' => $certificates->currentPage(),
                'total' => $certificates->total(),
            ]
        ]);
    }

    public function generateCourse(Request $request)
    {
        $validated = $request->validate([
            'candidate_id' => 'required|exists:candidates,id',
            'event_id' => 'required|exists:events,id',
            'course_id' => 'required|exists:courses,id',
        ]);

        // Check if candidate passed the course
        $attendance = EventCandidateCourse::where('event_id', $validated['event_id'])
            ->where('candidate_id', $validated['candidate_id'])
            ->where('course_id', $validated['course_id'])
            ->first();

        if (!$attendance || $attendance->attended !== 'yes' || $attendance->result !== 'pass') {
            return response()->json([
                'message' => 'Candidate must attend and pass the course to receive a certificate'
            ], 422);
        }

        // Check if certificate already exists
        $existing = Certificate::where('candidate_id', $validated['candidate_id'])
            ->where('event_id', $validated['event_id'])
            ->where('course_id', $validated['course_id'])
            ->first();

        if ($existing) {
            return response()->json(['data' => $existing, 'message' => 'Certificate already exists']);
        }

        // Calculate expiry date based on course settings and event start date
        $event = \App\Models\Event::findOrFail($validated['event_id']);
        $course = \App\Models\Course::findOrFail($validated['course_id']);
        $expiryDate = $course->calculateExpiryDate($event->start_date);

        $certificate = Certificate::create([
            'candidate_id' => $validated['candidate_id'],
            'event_id' => $validated['event_id'],
            'course_id' => $validated['course_id'],
            'type' => 'course',
            'certificate_number' => Certificate::generateNumber(),
            'issued_date' => now()->toDateString(),
            'expiry_date' => $expiryDate?->toDateString(),
        ]);

        return response()->json(['data' => $certificate->load(['candidate', 'event.trainer', 'course']), 'message' => 'Certificate generated'], 201);
    }

    public function generateEvent(Request $request)
    {
        $validated = $request->validate([
            'candidate_id' => 'required|exists:candidates,id',
            'event_id' => 'required|exists:events,id',
        ]);

        // Get courses assigned to this candidate in this event that they passed
        $passedCourses = EventCandidateCourse::where('event_id', $validated['event_id'])
            ->where('candidate_id', $validated['candidate_id'])
            ->where('attended', 'yes')
            ->where('result', 'pass')
            ->get();

        if ($passedCourses->isEmpty()) {
            return response()->json([
                'message' => 'Candidate must attend and pass at least one course to receive an event certificate'
            ], 422);
        }

        // Check if certificate already exists
        $existing = Certificate::where('candidate_id', $validated['candidate_id'])
            ->where('event_id', $validated['event_id'])
            ->whereNull('course_id')
            ->where('type', 'event')
            ->first();

        if ($existing) {
            return response()->json(['data' => $existing, 'message' => 'Certificate already exists']);
        }

        // Calculate expiry date - use the longest expiry among passed courses
        $event = \App\Models\Event::findOrFail($validated['event_id']);
        $passedCourseIds = $passedCourses->pluck('course_id')->toArray();
        $courses = \App\Models\Course::whereIn('id', $passedCourseIds)->get();
        
        $expiryDate = null;
        foreach ($courses as $course) {
            $courseExpiry = $course->calculateExpiryDate($event->start_date);
            if ($courseExpiry && (!$expiryDate || $courseExpiry->gt($expiryDate))) {
                $expiryDate = $courseExpiry;
            }
        }

        // Store passed course IDs in certificate for reference
        $certificate = Certificate::create([
            'candidate_id' => $validated['candidate_id'],
            'event_id' => $validated['event_id'],
            'course_id' => null,
            'type' => 'event',
            'certificate_number' => Certificate::generateNumber(),
            'issued_date' => now()->toDateString(),
            'expiry_date' => $expiryDate?->toDateString(),
            'passed_course_ids' => $passedCourseIds,
        ]);

        return response()->json(['data' => $certificate->load(['candidate', 'event.trainer']), 'message' => 'Certificate generated'], 201);
    }

    public function show(Certificate $certificate)
    {
        return response()->json(['data' => $certificate->load(['candidate', 'event.trainer', 'course'])]);
    }

    public function publish(Certificate $certificate)
    {
        $certificate->update([
            'published' => true,
            'published_at' => now(),
        ]);

        return response()->json(['data' => $certificate, 'message' => 'Certificate published']);
    }

    public function download(Certificate $certificate)
    {
        $certificate->load(['candidate', 'event.trainer', 'course']);
        
        $html = $this->generateCertificateHtml($certificate);
        $filename = 'certificate_' . $certificate->certificate_number . '.pdf';
        
        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');
        
        return $pdf->download($filename);
    }

    public function bulkGenerate(Request $request)
    {
        $validated = $request->validate([
            'event_id' => 'required|exists:events,id',
            'type' => 'required|in:course,event',
            'course_id' => 'required_if:type,course|exists:courses,id',
        ]);

        $event = \App\Models\Event::with('courses', 'candidates')->findOrFail($validated['event_id']);
        $generated = [];
        $skipped = [];

        // Pre-load course for expiry calculation
        $course = isset($validated['course_id']) ? \App\Models\Course::find($validated['course_id']) : null;

        foreach ($event->candidates as $candidate) {
            if ($validated['type'] === 'course') {
                // Generate course certificate
                $attendance = EventCandidateCourse::where('event_id', $validated['event_id'])
                    ->where('candidate_id', $candidate->id)
                    ->where('course_id', $validated['course_id'])
                    ->first();

                if ($attendance && $attendance->attended === 'yes' && $attendance->result === 'pass') {
                    $existing = Certificate::where('candidate_id', $candidate->id)
                        ->where('event_id', $validated['event_id'])
                        ->where('course_id', $validated['course_id'])
                        ->first();

                    if (!$existing) {
                        $expiryDate = $course?->calculateExpiryDate($event->start_date);
                        $cert = Certificate::create([
                            'candidate_id' => $candidate->id,
                            'event_id' => $validated['event_id'],
                            'course_id' => $validated['course_id'],
                            'type' => 'course',
                            'certificate_number' => Certificate::generateNumber(),
                            'issued_date' => now()->toDateString(),
                            'expiry_date' => $expiryDate?->toDateString(),
                        ]);
                        $generated[] = $cert;
                    } else {
                        $skipped[] = ['candidate' => $candidate->first_name . ' ' . $candidate->last_name, 'reason' => 'Already exists'];
                    }
                } else {
                    $skipped[] = ['candidate' => $candidate->first_name . ' ' . $candidate->last_name, 'reason' => 'Not passed'];
                }
            } else {
                // Generate event certificate - based on passed courses assigned to candidate
                $passedCourses = EventCandidateCourse::where('event_id', $validated['event_id'])
                    ->where('candidate_id', $candidate->id)
                    ->where('attended', 'yes')
                    ->where('result', 'pass')
                    ->get();

                if ($passedCourses->isNotEmpty()) {
                    $existing = Certificate::where('candidate_id', $candidate->id)
                        ->where('event_id', $validated['event_id'])
                        ->whereNull('course_id')
                        ->where('type', 'event')
                        ->first();

                    if (!$existing) {
                        // Calculate expiry date - use the longest expiry among passed courses
                        $passedCourseIds = $passedCourses->pluck('course_id')->toArray();
                        $courses = \App\Models\Course::whereIn('id', $passedCourseIds)->get();
                        
                        $expiryDate = null;
                        foreach ($courses as $c) {
                            $courseExpiry = $c->calculateExpiryDate($event->start_date);
                            if ($courseExpiry && (!$expiryDate || $courseExpiry->gt($expiryDate))) {
                                $expiryDate = $courseExpiry;
                            }
                        }

                        $cert = Certificate::create([
                            'candidate_id' => $candidate->id,
                            'event_id' => $validated['event_id'],
                            'course_id' => null,
                            'type' => 'event',
                            'certificate_number' => Certificate::generateNumber(),
                            'issued_date' => now()->toDateString(),
                            'expiry_date' => $expiryDate?->toDateString(),
                            'passed_course_ids' => $passedCourseIds,
                        ]);
                        $generated[] = $cert;
                    } else {
                        $skipped[] = ['candidate' => $candidate->first_name . ' ' . $candidate->last_name, 'reason' => 'Already exists'];
                    }
                } else {
                    $skipped[] = ['candidate' => $candidate->first_name . ' ' . $candidate->last_name, 'reason' => 'No passed courses'];
                }
            }
        }

        return response()->json([
            'message' => count($generated) . ' certificates generated',
            'generated' => count($generated),
            'skipped' => count($skipped),
            'skipped_details' => $skipped,
        ]);
    }

    public function eventCertificates(Request $request, $eventId)
    {
        $certificates = Certificate::with(['candidate', 'course'])
            ->where('event_id', $eventId)
            ->orderBy('type')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $certificates]);
    }

    // Generate and download course certificate in one action
    public function generateAndDownloadCourse(Request $request)
    {
        $validated = $request->validate([
            'candidate_id' => 'required|exists:candidates,id',
            'event_id' => 'required|exists:events,id',
            'course_id' => 'required|exists:courses,id',
        ]);

        // Check if certificate already exists
        $certificate = Certificate::where('candidate_id', $validated['candidate_id'])
            ->where('event_id', $validated['event_id'])
            ->where('course_id', $validated['course_id'])
            ->first();

        if (!$certificate) {
            // Check if candidate passed the course
            $attendance = EventCandidateCourse::where('event_id', $validated['event_id'])
                ->where('candidate_id', $validated['candidate_id'])
                ->where('course_id', $validated['course_id'])
                ->first();

            if (!$attendance || $attendance->attended !== 'yes' || $attendance->result !== 'pass') {
                return response()->json([
                    'message' => 'Candidate must attend and pass the course to receive a certificate'
                ], 422);
            }

            // Calculate expiry date based on course settings and event start date
            $event = \App\Models\Event::findOrFail($validated['event_id']);
            $course = \App\Models\Course::findOrFail($validated['course_id']);
            $expiryDate = $course->calculateExpiryDate($event->start_date);

            $certificate = Certificate::create([
                'candidate_id' => $validated['candidate_id'],
                'event_id' => $validated['event_id'],
                'course_id' => $validated['course_id'],
                'type' => 'course',
                'certificate_number' => Certificate::generateNumber(),
                'issued_date' => now()->toDateString(),
                'expiry_date' => $expiryDate?->toDateString(),
            ]);
        }

        $certificate->load(['candidate', 'event.trainer', 'course']);
        $html = $this->generateCertificateHtml($certificate);
        $filename = 'certificate_' . $certificate->certificate_number . '.pdf';

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');
        
        return $pdf->download($filename);
    }

    // Generate and download event certificate in one action
    public function generateAndDownloadEvent(Request $request)
    {
        $validated = $request->validate([
            'candidate_id' => 'required|exists:candidates,id',
            'event_id' => 'required|exists:events,id',
        ]);

        // Check if certificate already exists
        $certificate = Certificate::where('candidate_id', $validated['candidate_id'])
            ->where('event_id', $validated['event_id'])
            ->whereNull('course_id')
            ->where('type', 'event')
            ->first();

        if (!$certificate) {
            // Get courses assigned to this candidate that they passed
            $passedCourses = EventCandidateCourse::where('event_id', $validated['event_id'])
                ->where('candidate_id', $validated['candidate_id'])
                ->where('attended', 'yes')
                ->where('result', 'pass')
                ->get();

            if ($passedCourses->isEmpty()) {
                return response()->json([
                    'message' => 'Candidate must attend and pass at least one course to receive an event certificate'
                ], 422);
            }

            // Calculate expiry date - use the longest expiry among passed courses
            $event = \App\Models\Event::findOrFail($validated['event_id']);
            $passedCourseIds = $passedCourses->pluck('course_id')->toArray();
            $courses = \App\Models\Course::whereIn('id', $passedCourseIds)->get();
            
            $expiryDate = null;
            foreach ($courses as $course) {
                $courseExpiry = $course->calculateExpiryDate($event->start_date);
                if ($courseExpiry && (!$expiryDate || $courseExpiry->gt($expiryDate))) {
                    $expiryDate = $courseExpiry;
                }
            }

            $certificate = Certificate::create([
                'candidate_id' => $validated['candidate_id'],
                'event_id' => $validated['event_id'],
                'course_id' => null,
                'type' => 'event',
                'certificate_number' => Certificate::generateNumber(),
                'issued_date' => now()->toDateString(),
                'expiry_date' => $expiryDate?->toDateString(),
                'passed_course_ids' => $passedCourseIds,
            ]);
        }

        $certificate->load(['candidate', 'event.trainer']);
        $html = $this->generateCertificateHtml($certificate);
        $filename = 'certificate_' . $certificate->certificate_number . '.pdf';

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');
        
        return $pdf->download($filename);
    }

    // Bulk generate and download as ZIP
    public function bulkDownload(Request $request)
    {
        $validated = $request->validate([
            'event_id' => 'required|exists:events,id',
            'type' => 'required|in:course,event,all',
            'course_id' => 'required_if:type,course|exists:courses,id',
        ]);

        $event = \App\Models\Event::with('courses', 'candidates')->findOrFail($validated['event_id']);
        $certificates = [];

        // Pre-load courses for expiry calculation
        $coursesById = $event->courses->keyBy('id');

        foreach ($event->candidates as $candidate) {
            if ($validated['type'] === 'course' || $validated['type'] === 'all') {
                $courseIds = $validated['type'] === 'course' ? [$validated['course_id']] : $event->courses->pluck('id')->toArray();
                
                foreach ($courseIds as $courseId) {
                    $attendance = EventCandidateCourse::where('event_id', $validated['event_id'])
                        ->where('candidate_id', $candidate->id)
                        ->where('course_id', $courseId)
                        ->first();

                    if ($attendance && $attendance->attended === 'yes' && $attendance->result === 'pass') {
                        $course = $coursesById[$courseId] ?? \App\Models\Course::find($courseId);
                        $expiryDate = $course?->calculateExpiryDate($event->start_date);
                        
                        $cert = Certificate::firstOrCreate(
                            [
                                'candidate_id' => $candidate->id,
                                'event_id' => $validated['event_id'],
                                'course_id' => $courseId,
                            ],
                            [
                                'type' => 'course',
                                'certificate_number' => Certificate::generateNumber(),
                                'issued_date' => now()->toDateString(),
                                'expiry_date' => $expiryDate?->toDateString(),
                            ]
                        );
                        $cert->load(['candidate', 'event.trainer', 'course']);
                        $certificates[] = $cert;
                    }
                }
            }

            if ($validated['type'] === 'event' || $validated['type'] === 'all') {
                $passedCourses = EventCandidateCourse::where('event_id', $validated['event_id'])
                    ->where('candidate_id', $candidate->id)
                    ->where('attended', 'yes')
                    ->where('result', 'pass')
                    ->get();

                if ($passedCourses->isNotEmpty()) {
                    // Calculate expiry date - use the longest expiry among passed courses
                    $passedCourseIds = $passedCourses->pluck('course_id')->toArray();
                    $expiryDate = null;
                    foreach ($passedCourseIds as $courseId) {
                        $course = $coursesById[$courseId] ?? \App\Models\Course::find($courseId);
                        $courseExpiry = $course?->calculateExpiryDate($event->start_date);
                        if ($courseExpiry && (!$expiryDate || $courseExpiry->gt($expiryDate))) {
                            $expiryDate = $courseExpiry;
                        }
                    }

                    $cert = Certificate::firstOrCreate(
                        [
                            'candidate_id' => $candidate->id,
                            'event_id' => $validated['event_id'],
                            'course_id' => null,
                            'type' => 'event',
                        ],
                        [
                            'certificate_number' => Certificate::generateNumber(),
                            'issued_date' => now()->toDateString(),
                            'expiry_date' => $expiryDate?->toDateString(),
                            'passed_course_ids' => $passedCourseIds,
                        ]
                    );
                    $cert->load(['candidate', 'event.trainer']);
                    $certificates[] = $cert;
                }
            }
        }

        if (empty($certificates)) {
            return response()->json(['message' => 'No eligible candidates found'], 404);
        }

        // Create ZIP file
        $zipFileName = 'certificates_' . $event->id . '_' . time() . '.zip';
        $zipPath = storage_path('app/temp/' . $zipFileName);
        
        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        foreach ($certificates as $cert) {
            $html = $this->generateCertificateHtml($cert);
            $candidateName = preg_replace('/[^a-zA-Z0-9]/', '_', $cert->candidate->first_name . '_' . $cert->candidate->last_name);
            $certType = $cert->type === 'event' ? 'Event' : ($cert->course->name ?? 'Course');
            $fileName = $candidateName . '_' . $certType . '_' . $cert->certificate_number . '.pdf';
            
            $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');
            $zip->addFromString($fileName, $pdf->output());
        }

        $zip->close();

        return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
    }

    private function generateCertificateHtml(Certificate $certificate)
    {
        $candidate = $certificate->candidate;
        $event = $certificate->event;
        $course = $certificate->course;
        $isEventCert = $certificate->type === 'event';

        // Ensure trainer is loaded
        if ($event && !$event->relationLoaded('trainer')) {
            $event->load('trainer');
        }

        // For event certificates, always get fresh list of passed courses from attendance records
        $coursesWithExpiry = [];
        if ($isEventCert) {
            $passedCourseIds = EventCandidateCourse::where('event_id', $certificate->event_id)
                ->where('candidate_id', $certificate->candidate_id)
                ->where('attended', 'yes')
                ->where('result', 'pass')
                ->pluck('course_id')
                ->toArray();
            $passedCourses = \App\Models\Course::whereIn('id', $passedCourseIds)->get();
            
            // Build course list with individual expiry dates
            foreach ($passedCourses as $c) {
                $courseExpiry = $c->calculateExpiryDate($event->start_date);
                $coursesWithExpiry[] = [
                    'name' => $c->name,
                    'expiry' => $courseExpiry ? $courseExpiry->format('d.m.Y') : null,
                ];
            }
            
            // Build course list HTML with expiry for each course (left-aligned in centered block)
            $courseLines = array_map(function($c) {
                if ($c['expiry']) {
                    return '<div class="course-list-item">' . htmlspecialchars($c['name']) . ' <span class="course-expiry">(Expires: ' . $c['expiry'] . ')</span></div>';
                }
                return '<div class="course-list-item">' . htmlspecialchars($c['name']) . '</div>';
            }, $coursesWithExpiry);
            $courseName = '<div class="course-list">' . implode('', $courseLines) . '</div>';
            $isEventCertHtml = true;
        } else {
            $courseName = $course->name ?? 'Training Course';
            $isEventCertHtml = false;
        }
        
        $awardedDate = $certificate->issued_date->format('d.m.Y');
        $expiryDate = $certificate->expiry_date ? $certificate->expiry_date->format('d.m.Y') : null;
        $trainer = $event->trainer?->name ?? 'Secure Training Services';

        // Get logo as base64 from backend storage
        $logoPath = storage_path('app/public/logo.png');
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }

        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Certificate - ' . $certificate->certificate_number . '</title>
    <style>
        @page { size: A4 portrait; margin: 0; }
        html, body { 
            margin: 0; 
            padding: 0;
            width: 100%;
            height: 100%;
        }
        body { 
            font-family: Arial, sans-serif; 
            background: white;
        }
        .certificate {
            width: 100%;
            height: 100%;
            max-height: 100%;
            background: white;
            position: relative;
            overflow: hidden;
            page-break-after: avoid;
            page-break-inside: avoid;
        }
        
        /* Top left diagonal stripes - parallelogram bars */
        .stripe-red {
            position: absolute;
            top: -110px;
            left: 0px;
            width: 130px;
            height: 400px;
            background-color: #cd1618;
            transform: skewX(-60deg);
        }
        .stripe-pink {
            position: absolute;
            top: -60px;
            left: 0px;
            width: 130px;
            height: 500px;
            background-color: #f39a8e;
            transform: skewX(-60deg);
        }
        
        /* Logo section */
        .logo-section {
            position: relative;
            text-align: center;
            padding-top: 25px;
            margin-bottom: 10px;
            z-index: 100;
        }
        .logo-img {
            width: 200px;
            height: auto;
            margin: -20 auto;
            display: block;
        }
        
        /* Certificate content */
        .content {
            padding: 30px 60px;
            text-align: center;
        }
        .certify-text {
            font-size: 26px;
            color: #333;
            font-weight: bold;
            border-bottom: 2px solid #333;
            display: inline-block;
            padding-bottom: 5px;
            margin-bottom: 20px;
        }
        .candidate-name {
            font-size: 30px;
            color: #333;
            font-weight: bold;
            margin: 25px 0;
        }
        .awarded-text {
            font-size: 20px;
            color: #666;
            margin: 30px 0 20px;
        }
        .course-name {
            font-size: 24px;
            color: #C41E3A;
            font-weight: bold;
            margin: 20px 0 50px;
        }
        .course-list {
            display: inline-block;
            text-align: left;
            margin: 20px 0 50px;
        }
        .course-list-item {
            font-size: 20px;
            color: #C41E3A;
            font-weight: bold;
            margin: 8px 0;
        }
        .course-expiry {
            font-size: 14px;
            color: #666;
            font-weight: normal;
        }
        
        /* Details section */
        .details {
            text-align: left;
            padding: 0 60px;
            font-size: 18px;
            color: #333;
            line-height: 2;
        }
        .details p {
            margin: 8px 0;
        }
        
        /* Bottom right heart decoration - large logo partially visible */
        .heart-decoration {
            position: absolute;
            bottom: -240px;
            right: -280px;
            width: 600px;
            height: 600px;
            overflow: visible;
        }
        .heart-decoration img {
            width: 100%;
            height: auto;
        }
        
        /* Footer */
        .footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: #C41E3A;
            color: white;
            padding: 15px 30px;
            font-size: 14px;
            font-weight: 500;
        }
        .footer table {
            width: 100%;
            border-collapse: collapse;
        }
        .footer td {
            padding: 0;
            color: white;
            font-weight: 500;
        }
        .footer .left { text-align: left; width: 33%; }
        .footer .center { text-align: center; width: 34%; }
        .footer .right { text-align: right; width: 33%; }
    </style>
</head>
<body>
    <div class="certificate">
        <!-- Top left diagonal stripes using CSS borders -->
        <div class="stripe-red"></div>
        <div class="stripe-pink"></div>
        
        <!-- Logo section -->
        <div class="logo-section">
            ' . ($logoBase64 ? '<img src="' . $logoBase64 . '" class="logo-img" alt="Secure Training Services">' : '<div class="company-name"><span class="secure">Secure</span><span class="training">Training</span><span class="services">Services</span></div>') . '
        </div>
        
        <!-- Certificate content -->
        <div class="content">
            <div class="certify-text">This is to Certify that:</div>
            <div class="candidate-name">' . htmlspecialchars($candidate->first_name . ' ' . $candidate->last_name) . '</div>
            <div class="awarded-text">Has been awarded the certificate in:</div>
            ' . ($isEventCertHtml ? $courseName : '<div class="course-name">' . htmlspecialchars($courseName) . '</div>') . '
        </div>
        
        <!-- Details section -->
        <div class="details">
            <p>Awarded on: ' . $awardedDate . '</p>
            <p>Certificate Number: ' . $certificate->certificate_number . '</p>
            ' . (!$isEventCert && $expiryDate ? '<p>Certificate Expiry: ' . $expiryDate . '</p>' : '') . '
            <p>Trainer: ' . htmlspecialchars($trainer) . '</p>
        </div>
        
        <!-- Bottom right heart decoration - logo partially visible -->
        <div class="heart-decoration">
            ' . ($logoBase64 ? '<img src="' . $logoBase64 . '" alt="">' : '') . '
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <table>
                <tr>
                    <td class="left">securetrainingservices.co.uk</td>
                    <td class="center">0121 794 4902</td>
                    <td class="right">info@securetrainingservices.co.uk</td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>';
    }
}
