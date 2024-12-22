<?php

namespace Graywings\Instantiate\Tests\Sample\User;

readonly class Email
{
    public function __construct(private(set) string $value) {}
}
