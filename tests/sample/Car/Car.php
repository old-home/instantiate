<?php

namespace Graywings\Instantiate\Tests\Sample\Car;

readonly class Car
{
    public function __construct(
        private(set) string $name,
        private(set) int|CarSpeed $speed
    ) {}
}
