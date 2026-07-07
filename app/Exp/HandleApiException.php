<?php

namespace App\Exp;

use App\Facades\ResponseHandler;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class HandleApiException
{
    public function rendered(Throwable $e): JsonResponse
    {
        $this->logError($e);

        return match (true) {
            $e instanceof AuthenticationException =>
            ResponseHandler::errorUnauthorized(),

            $e instanceof ValidationException =>
            ResponseHandler::errorValidation($e->errors(), $e->getMessage()),

            $e instanceof AuthorizationException =>
            ResponseHandler::errorForbidden($e->getMessage() ?: 'Forbidden'),

            $e instanceof ModelNotFoundException,
            $e instanceof NotFoundHttpException =>
            ResponseHandler::errorNotFound(),

            $e instanceof MethodNotAllowedHttpException =>
            ResponseHandler::customError('Method not allowed', 'GEN-METHOD-NOT-ALLOWED', Response::HTTP_METHOD_NOT_ALLOWED),

            $e instanceof QueryException =>
            ResponseHandler::errorInternalError('Database error'),

            $e instanceof HttpException =>
            ResponseHandler::customError($e->getMessage() ?: 'Error', 'GEN-HTTP-ERROR', $e->getStatusCode()),

            default =>
            ResponseHandler::errorInternalError(),
        };
    }

    private function logError(Throwable $e): void
    {
        if ($e instanceof ValidationException || $e instanceof AuthenticationException) {
            return;
        }

        Log::error('API Exception: ' . $e->getMessage(), [
            'exception' => get_class($e),
            'file'      => $e->getFile(),
            'line'      => $e->getLine(),
        ]);
    }
}
