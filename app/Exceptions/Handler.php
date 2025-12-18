<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    protected function convertValidationExceptionToResponse(\Illuminate\Validation\ValidationException $e, $request)
    {
        $errors = collect($e->errors())
            ->map(fn($messages) => $messages[0]) // Take only the first message for each field
            ->toArray();

        return $request->expectsJson()
            ? response()->json([
                'message' => 'Please fill out the mandatory field(s)',
                'errors' => $errors,
            ], 422)
            : $this->invalid($request, $e);
    }

}
