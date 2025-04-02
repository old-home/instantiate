<?php

declare(strict_types=1);

namespace Graywings\Instantiate\Exception;

class InstantiateArgumentsException extends InstantiateException
{
    public function __construct(string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, $previous);
    }
}