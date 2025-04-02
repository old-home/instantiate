<?php

declare(strict_types=1);

namespace Graywings\Instantiate\Exception;

class InstantiateException extends \RuntimeException
{
    public function __construct(string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}