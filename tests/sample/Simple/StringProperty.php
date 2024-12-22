<?php

namespace Graywings\Instantiate\Tests\Sample\Simple;

readonly class StringProperty
{
    public function __construct(private(set) string $value) {}
}
