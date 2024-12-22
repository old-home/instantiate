<?php

namespace Graywings\Instantiate\Tests\Sample\Simple;

readonly class BoolProperty
{
    public function __construct(private(set) bool $value) {}
}
