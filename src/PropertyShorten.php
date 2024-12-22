<?php

declare(strict_types=1);

namespace Graywings\Instantiate;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
readonly class PropertyShorten
{
    public function __construct(private(set) string $propertyName)
    {
    }
}
