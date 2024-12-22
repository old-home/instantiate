<?php

namespace Graywings\Instantiate\Tests\Sample\Simple;

readonly class BuiltinUnionProperty
{
    public function __construct(private(set) string|int|float|null|bool|array $value) {}
}
