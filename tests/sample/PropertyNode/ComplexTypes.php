<?php

declare(strict_types=1);

namespace Graywings\Instantiate\Tests\Sample\PropertyNode;

use Countable;
use ArrayAccess;
use Graywings\Instantiate\PropertyNode\ArrayOf;
use Iterator;

/**
 * Class with complex type properties for testing union and intersection types
 */
class ComplexTypes
{
    // Union type properties
    public string|int $stringOrInt;
    public string|float|null $stringOrFloatOrNull;
    public Address|User $addressOrUser;

    // Intersection type properties (PHP 8.1+)
    public Countable&Iterator $countableAndIterator;
    public ArrayAccess&Countable $arrayAccessAndCountable;

    // Array with complex element types
    #[ArrayOf('string|int')]
    public array $mixedArray = [];

    // Nested union types in arrays
    #[ArrayOf(Address::class . '|' . User::class)]
    public array $complexObjects = [];
}
