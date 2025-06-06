<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = ['current_password', 'password', 'password_confirmation'];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e): void {
            if (app()->bound('sentry')) {
                App::get('sentry')->captureException($e);
            }
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param Request $request
     */
    public function render($request, Throwable $throwable): RedirectResponse|Response
    {
        /** @var \Illuminate\Http\Response $response */
        $response = parent::render($request, $throwable);

        if ($throwable instanceof UnauthorizedException) {
            abort(417, 'This account is not authorized to perform this action.');
        }

        if (419 === $response->status()) {
            throw new RedirectBackWithErrorException('The page has expired, please try again.');
        }

        if (417 === $response->status()) {
            throw new RedirectBackWithErrorException($throwable->getMessage());
        }

        if (! $request->headers->get('authorization') && ! $request->has('device_type')) {
            return $response;
        }

        if ($request->expectsJson() && 404 === $response->status()) {
            return response()->json([
                'message' => 'Oops! The record could not be found!',
            ], 404);
        }

        if (! $throwable instanceof ValidationException) {
            return $response;
        }

        abort(412, implode(', ', $throwable->validator->errors()->all()));
    }
}
