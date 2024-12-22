<?php

declare(strict_types=1);

namespace Graywings\Instantiate\Type;

enum AlgebraicOperator {
    case NONE;
    case PRODUCT;
    case UNION;
    case INTERSECTION;
    case ARRAY;
}
