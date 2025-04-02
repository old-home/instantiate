<?php

declare(strict_types=1);

namespace Graywings\Instantiate\Type;

class AlgebraicDataType implements Type
{
    /**
     * @var Type[] 
     */
    private array $types;
    private AlgebraicOperator $operator;
    
    /**
     * @param Type[] $types
     */
    public function __construct(array $types, AlgebraicOperator $operator)
    {
        if (count($types) < 2) {
            throw new \InvalidArgumentException("AlgebraicDataType requires at least 2 types");
        }
        
        $this->types = $types;
        $this->operator = $operator;
    }
    
    public function getName(): string
    {
        $typeNames = array_map(fn (Type $type) => $type->getName(), $this->types);
        $separator = match ($this->operator) {
            AlgebraicOperator::UNION => '|',
            AlgebraicOperator::INTERSECTION => '&',
        };
        
        return implode($separator, $typeNames);
    }
    
    public function isValidValue(mixed $value): bool
    {
        return match ($this->operator) {
            AlgebraicOperator::UNION => $this->isValidForUnion($value),
            AlgebraicOperator::INTERSECTION => $this->isValidForIntersection($value),
        };
    }
    
    private function isValidForUnion(mixed $value): bool
    {
        // For Union types, the value must be valid for at least one type
        foreach ($this->types as $type) {
            if ($type->isValidValue($value)) {
                return true;
            }
        }
        
        return false;
    }
    
    private function isValidForIntersection(mixed $value): bool
    {
        // For Intersection types, the value must be valid for all types
        foreach ($this->types as $type) {
            if (!$type->isValidValue($value)) {
                return false;
            }
        }
        
        return true;
    }
    
    public function canCast(mixed $value): bool
    {
        return match ($this->operator) {
            AlgebraicOperator::UNION => $this->canCastForUnion($value),
            AlgebraicOperator::INTERSECTION => $this->canCastForIntersection($value),
        };
    }
    
    private function canCastForUnion(mixed $value): bool
    {
        // For Union types, the value must be castable to at least one type
        foreach ($this->types as $type) {
            if ($type->canCast($value)) {
                return true;
            }
        }
        
        return false;
    }
    
    private function canCastForIntersection(mixed $value): bool
    {
        // For Intersection types, the value must be castable to all types
        foreach ($this->types as $type) {
            if (!$type->canCast($value)) {
                return false;
            }
        }
        
        return true;
    }
    
    public function cast(mixed $value): mixed
    {
        throw new \RuntimeException(
            "Direct casting of algebraic data types is not supported. " .
            "Please use canCast() to check if casting is possible, " .
            "and then cast to a specific type using one of the component types."
        );
    }
    
    /**
     * @return Type[]
     */
    public function getTypes(): array
    {
        return $this->types;
    }
    
    public function getOperator(): AlgebraicOperator
    {
        return $this->operator;
    }
}