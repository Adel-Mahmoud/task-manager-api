<?php

namespace App\Traits;

trait ApiResponse
{
    protected function successResponse($data = null, $message = 'Success', $code = 200)
    {
        return response()->json([
            'ok' => true,
            'data' => $data,
            'message' => $message,
        ], $code);
    }

    protected function errorResponse($message = 'Something went wrong', $code = 500, $errors = null)
    {
        return response()->json([
            'ok' => false,
            'data' => null,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }
}
