<?php

if (!function_exists('jsonResponse')) {
    /**
     * Standard response JSON untuk API
     *
     * @param bool $success
     * @param string|null $message
     * @param mixed|null $data
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    function jsonResponse(bool $success, ?string $message = null, mixed $data = null, int $code = 200)
    {
        $response = [
            'success' => $success,
            'message' => $message,
        ];

        // Jika data dikirim (bahkan jika null khusus profile), masukkan ke response
        if (func_num_args() >= 3) {
            $response['data'] = $data;
        }

        return response()->json($response, $code);
    }
}