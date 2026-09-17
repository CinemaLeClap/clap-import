<?php

namespace App\Services\FileMaker\Exceptions;

use Exception;
use Throwable;

class FileMakerRequestException extends Exception
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        string $message,
        private readonly array $context = [],
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Données jointes au rapport d'exception par le gestionnaire Laravel.
     *
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return $this->context;
    }
}
