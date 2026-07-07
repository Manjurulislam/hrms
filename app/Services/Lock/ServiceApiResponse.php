<?php

namespace App\Services\Lock;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\HttpFoundation\Response;

class ServiceApiResponse
{
    // Error code constants
    protected const ERROR_FORBIDDEN    = 'GEN-FORBIDDEN';
    protected const ERROR_UNAUTHORIZED = 'GEN-UNAUTHORIZED';
    protected const ERROR_NOT_FOUND    = 'GEN-NOT-FOUND';
    protected const ERROR_VALIDATION   = 'GEN-VALIDATION';
    protected const ERROR_INTERNAL     = 'GEN-API-ERROR';
    protected const ERROR_BAD_REQUEST  = 'GEN-WRONG-ARGS';
    protected const ERROR_CONFLICT     = 'GEN-CONFLICT';

    /**
     * Generates a Response with a 403 HTTP header and a given message.
     *
     * @param string $message
     * @return JsonResponse
     */
    public function errorForbidden(string $message = 'Forbidden'): JsonResponse
    {
        return $this->respondWithError($message, Response::HTTP_FORBIDDEN, self::ERROR_FORBIDDEN);
    }

    /**
     * Generic application-level error response.
     * GEN prefix indicates these are application-wide generic errors,
     * not specific to business logic modules.
     *
     * @param string $message
     * @param int $status
     * @param string $code
     * @param mixed $details
     * @return JsonResponse
     */
    public function respondWithError(string $message, int $status, string $code, mixed $details = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error'   => [
                'code'    => $code,
                'details' => $details
            ]
        ], $status);
    }

    /**
     * Generates a Response with a 500 HTTP header and a given message.
     *
     * @param string $message
     * @return JsonResponse
     */
    public function errorInternalError(string $message = 'Internal server error'): JsonResponse
    {
        return $this->respondWithError($message, Response::HTTP_INTERNAL_SERVER_ERROR, self::ERROR_INTERNAL);
    }

    /**
     * Generates a Response with a 404 HTTP header and a given message.
     *
     * @param string $message
     * @return JsonResponse
     */
    public function errorNotFound(string $message = 'Resource Not Found'): JsonResponse
    {
        return $this->respondWithError($message, Response::HTTP_NOT_FOUND, self::ERROR_NOT_FOUND);
    }

    /**
     * Generates a Response with a 401 HTTP header and a given message.
     *
     * @param string $message
     * @return JsonResponse
     */
    public function errorUnauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->respondWithError($message, Response::HTTP_UNAUTHORIZED, self::ERROR_UNAUTHORIZED);
    }

    /**
     * Generates a Response with a 400 HTTP header and a given message.
     *
     * @param string $message
     * @param int $status
     * @return JsonResponse
     */
    public function errorWrongArgs(
        string $message = 'Wrong Arguments',
        int    $status = Response::HTTP_BAD_REQUEST
    ): JsonResponse
    {
        return $this->respondWithError($message, $status, self::ERROR_BAD_REQUEST);
    }

    /**
     * Generates a Response with a 409 HTTP header and a given message.
     *
     * @param string $message
     * @param mixed $details
     * @return JsonResponse
     */
    public function errorConflict(string $message = 'Resource Conflict', mixed $details = null): JsonResponse
    {
        return $this->respondWithError($message, Response::HTTP_CONFLICT, self::ERROR_CONFLICT, $details);
    }

    /**
     * Responds with validation errors in consistent format
     *
     * @param array $errors
     * @param string $message
     * @return JsonResponse
     */
    public function errorValidation(array $errors, string $message = 'The given data was invalid.'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error'   => [
                'code'    => self::ERROR_VALIDATION,
                'details' => $errors
            ]
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * Responds with an API Resource.
     *
     * @param JsonResource $resource
     * @param int $status
     * @param string $message
     * @return JsonResponse
     */
    public function respondWithResource(
        JsonResource $resource,
        int          $status = Response::HTTP_OK,
        string       $message = 'Success'
    ): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $resource
        ], $status);
    }

    /**
     * Responds with array data.
     *
     * @param array|null $data
     * @param int $status
     * @param string $message
     * @return JsonResponse
     */
    public function respondWithArray(
        ?array $data,
        int    $status = Response::HTTP_OK,
        string $message = 'Success'
    ): JsonResponse
    {
        return $this->success($data, $message, $status);
    }

    /**
     * Main success response method - consolidated from multiple methods.
     *
     * @param mixed $data
     * @param string|null $message
     * @param int $responseCode
     * @return JsonResponse
     */
    public function success(mixed $data = [], ?string $message = null, int $responseCode = Response::HTTP_OK): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message ?? 'Success',
        ];

        // Only include data key if data is provided
        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $responseCode);
    }

    /**
     * Responds with object data.
     *
     * @param mixed $data
     * @param int $status
     * @param string $message
     * @return JsonResponse
     */
    public function respondWithObject(
        mixed  $data,
        int    $status = Response::HTTP_OK,
        string $message = 'Success'
    ): JsonResponse
    {
        return $this->success($data, $message, $status);
    }

    /**
     * Responds with a paginated API Resource.
     *
     * @param JsonResource $resource
     * @param int $status
     * @param string $message
     * @return JsonResponse
     */
    public function respondWithPagination(
        JsonResource $resource,
        int          $status = Response::HTTP_OK,
        string       $message = 'Success'
    ): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            ...$resource->response()->getData(true)
        ], $status);
    }

    /**
     * Responds with a simple message.
     *
     * @param string $message
     * @param int $status
     * @return JsonResponse
     */
    public function respondWithMessage(string $message, int $status = Response::HTTP_OK): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message
        ], $status);
    }

    /**
     * Created response (201)
     *
     * @param mixed $data
     * @param string $message
     * @return JsonResponse
     */
    public function created(mixed $data = null, string $message = 'Resource created successfully'): JsonResponse
    {
        return $this->success($data, $message, Response::HTTP_CREATED);
    }

    /**
     * No content response (204)
     *
     * @return JsonResponse
     */
    public function noContent(): JsonResponse
    {
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Custom error with specific code and details
     *
     * @param string $message
     * @param string $errorCode
     * @param int $status
     * @param mixed $details
     * @return JsonResponse
     */
    public function customError(string $message, string $errorCode, int $status = Response::HTTP_BAD_REQUEST, mixed $details = null): JsonResponse
    {
        return $this->respondWithError($message, $status, $errorCode, $details);
    }
}
