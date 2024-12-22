<?php

namespace Graywings\Instantiate\Tests\Sample\Simple;

readonly class FloatProperty
{
    public function __construct(private(set) float $value) {}
}
