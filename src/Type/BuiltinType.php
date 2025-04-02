<?php

declare(strict_types=1);

namespace Graywings\Instantiate\Type;

/**
 * Class representing PHP's built-in types
 */
class BuiltinType implements Type
{
    private BuiltinTypeEnum $type;

    /**
     * Constructor that accepts either a BuiltinTypeEnum or a string name
     * 
     * @param BuiltinTypeEnum|string $type Type enum or type name ('int'|'float'|'bool'|'string'|'array'|'object'|'null'|'mixed')
     */
    public function __construct(BuiltinTypeEnum|string $type)
    {
        $this->type = $type instanceof BuiltinTypeEnum 
            ? $type 
            : BuiltinTypeEnum::fromName($type);
    }

    public function getName(): string
    {
        return $this->type->value;
    }

    public function isValidValue(mixed $value): bool
    {
        return $this->type->isValid($value);
    }
    
    public function canCast(mixed $value): bool
    {
        return $this->type->canCast($value);
    }

    public function cast(mixed $value): mixed
    {
        return $this->type->cast($value);
    }

    /**
     * Get the type enum
     */
    public function getType(): BuiltinTypeEnum
    {
        return $this->type;
    }
}