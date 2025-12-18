<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\CandidateController;
use App\Http\Controllers\Api\CertificateController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\TrainerController;
use App\Http\Controllers\Api\DocumentController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
        Route::put('profile', [AuthController::class, 'updateProfile']);
        Route::put('password', [AuthController::class, 'changePassword']);
    });

    // Protected routes
    Route::middleware('auth:api')->group(function () {
        // Dashboard
        Route::get('dashboard/stats', [DashboardController::class, 'stats']);
        Route::get('dashboard/upcoming-events', [DashboardController::class, 'upcomingEvents']);

        // Courses
        Route::apiResource('courses', CourseController::class);

        // Locations
        Route::apiResource('locations', LocationController::class);

        // Events
        Route::get('events/upcoming', [EventController::class, 'upcoming']);
        Route::apiResource('events', EventController::class);

        // Event Candidates
        Route::get('events/{event}/candidates', [EventController::class, 'candidates']);
        Route::post('events/{event}/candidates', [EventController::class, 'addCandidates']);
        Route::delete('events/{event}/candidates/{candidate}', [EventController::class, 'removeCandidate']);

        // Event Attendance
        Route::get('events/{event}/attendance', [EventController::class, 'attendance']);
        Route::put('events/{event}/candidates/{candidate}/courses/{course}', [EventController::class, 'updateAttendance']);

        // Candidates
        Route::apiResource('candidates', CandidateController::class);

        // Certificates
        Route::get('certificates', [CertificateController::class, 'index']);
        Route::post('certificates/course', [CertificateController::class, 'generateCourse']);
        Route::post('certificates/event', [CertificateController::class, 'generateEvent']);
        Route::post('certificates/bulk', [CertificateController::class, 'bulkGenerate']);
        Route::post('certificates/download-course', [CertificateController::class, 'generateAndDownloadCourse']);
        Route::post('certificates/download-event', [CertificateController::class, 'generateAndDownloadEvent']);
        Route::post('certificates/bulk-download', [CertificateController::class, 'bulkDownload']);
        Route::get('certificates/event/{event}', [CertificateController::class, 'eventCertificates']);
        Route::get('certificates/{certificate}', [CertificateController::class, 'show']);
        Route::put('certificates/{certificate}/publish', [CertificateController::class, 'publish']);
        Route::get('certificates/{certificate}/download', [CertificateController::class, 'download']);

        // Users Management
        Route::apiResource('users', UserController::class);

        // Trainers Management
        Route::apiResource('trainers', TrainerController::class);

        // Documents
        Route::get('documents', [DocumentController::class, 'index']);
        Route::post('documents', [DocumentController::class, 'upload']);
        Route::delete('documents/{document}', [DocumentController::class, 'destroy']);
        Route::get('documents/{document}/download', [DocumentController::class, 'download']);
        Route::get('documents/counts', [DocumentController::class, 'counts']);
    });
});
