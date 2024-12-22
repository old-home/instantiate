<?php

namespace Graywings\Instantiate\Tests\Sample\Car;

readonly class CarSpeed
{
    public function __construct(private(set) int $value) {}
}
