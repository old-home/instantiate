<?php

declare(strict_types=1);

namespace Graywings\Instantiate\Tests\Sample\Simple;

readonly class NullProperty
{
    public function __construct(private(set) null $nullProperty)
    {
    }
}
