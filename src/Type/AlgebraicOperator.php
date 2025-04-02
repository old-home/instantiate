<?php

declare(strict_types=1);

namespace Graywings\Instantiate\Type;

enum AlgebraicOperator: string
{
    case UNION = 'union';        // |
    case INTERSECTION = 'intersection';  // &
}