<?php

declare(strict_types=1);

namespace Graywings\Instantiate;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
readonly class ArrayType
{
    public function __construct(private(set) string $type)
    {
    }
}
