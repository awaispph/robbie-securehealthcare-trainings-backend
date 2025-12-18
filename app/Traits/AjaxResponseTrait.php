<?php

namespace App\Traits;

trait AjaxResponseTrait
{
    protected function sendSuccessResponse($message, $data = null, $statusCode = 200)
    {
        return response()->json([
            'error' => false,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    protected function sendErrorResponse($message, $statusCode = 500, $debug = null)
    {
        $response = [
            'error' => true,
            'message' => $message
        ];

        if (config('app.debug') && $debug) {
            $response['debug'] = $debug;
        }

        return response()->json($response, $statusCode);
    }
}
