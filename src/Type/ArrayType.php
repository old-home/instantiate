<?php

declare(strict_types=1);

namespace Graywings\Instantiate\Type;

/**
 * Class representing array type (collection type)
 * Represents types like Array<T>
 */
class ArrayType implements Type
{
    private Type $elementType;

    /**
     * @param Type $elementType Type of array elements
     */
    public function __construct(Type $elementType)
    {
        $this->elementType = $elementType;
    }

    public function getName(): string
    {
        return "array<{$this->elementType->getName()}>";
    }

    public function isValidValue(mixed $value): bool
    {
        if (!is_array($value)) {
            return false;
        }

        // Empty arrays are always valid
        if (empty($value)) {
            return true;
        }

        // Check if all elements conform to the specified type
        foreach ($value as $element) {
            if (!$this->elementType->isValidValue($element)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  mixed $value
     * @return bool
     */
    public function canCast(mixed $value): bool
    {
        // Only strings, arrays, or Traversable can be cast
        if (!is_array($value) && !($value instanceof \Traversable) && !is_string($value)) {
            return false;
        }

        return true;
    }

    /**
     * @param  mixed $value
     * @return mixed[]
     */
    public function cast(mixed $value): array
    {
        if (!is_array($value) && !($value instanceof \Traversable)) {
            throw new \InvalidArgumentException("Cannot cast non-iterable value to array");
        }

        $result = [];

        foreach ($value as $key => $element) {
            $result[$key] = $this->elementType->cast($element);
        }

        return $result;
    }

    /**
     * Get the element type
     */
    public function getElementType(): Type
    {
        return $this->elementType;
    }
}
