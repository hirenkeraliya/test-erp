<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\RedirectResponse;

class RedirectWithErrorException extends Exception
{
    public function __construct(
        protected string $routeName,
        protected string $errorMessage
    ) {
    }

    public function render(): RedirectResponse
    {
        return to_route($this->routeName)
            ->with('error', $this->errorMessage);
    }

    public function report(): void
    {
        // We do not wish to report this exception.
    }
}
