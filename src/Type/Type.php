<?php

declare(strict_types=1);

namespace Graywings\Instantiate\Type;

interface Type
{
    /**
     * Get the name of this type
     */
    public function getName(): string;
    
    /**
     * Determine if a value is valid for this type
     */
    public function isValidValue(mixed $value): bool;
    
    /**
     * Determine if a value can be cast to this type
     */
    public function canCast(mixed $value): bool;
    
    /**
     * Cast a value to this type
     * Throws an exception if the value cannot be cast
     */
    public function cast(mixed $value): mixed;
}