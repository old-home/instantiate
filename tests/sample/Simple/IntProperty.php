<?php

namespace Graywings\Instantiate\Tests\Sample\Simple;

readonly class IntProperty
{
    public function __construct(private(set) int $value) {}
}
