<?php

namespace Graywings\Instantiate\Tests\Sample\User;

readonly class UserId
{
    public function __construct(private(set) int $value) {}
}
