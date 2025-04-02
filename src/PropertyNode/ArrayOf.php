<?php

declare(strict_types=1);

namespace Graywings\Instantiate\PropertyNode;

use Attribute;

/**
 * Attribute to specify the type of elements in an array property
 *
 * Examples:
 * - #[ArrayOf('string')] - Array of strings
 * - #[ArrayOf(User::class)] - Array of User objects
 * - #[ArrayOf('string|int')] - Array of strings or integers (union type)
 * - #[ArrayOf('Countable&Iterator')] - Array of objects implementing both Countable and Iterator (intersection type)
 */
#[Attribute(Attribute::TARGET_PROPERTY|Attribute::TARGET_PARAMETER)]
class ArrayOf
{
    /**
     * @param string $type The class name or type of the array elements
     */
    public function __construct(
        private string $type
    ) {
    }

    /**
     * Get the type of array elements
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Check if the type is a union type (contains |)
     */
    public function isUnionType(): bool
    {
        return str_contains($this->type, '|');
    }

    /**
     * Check if the type is an intersection type (contains &)
     */
    public function isIntersectionType(): bool
    {
        return str_contains($this->type, '&');
    }

    /**
     * Get the component types of a union or intersection type
     *
     * @return string[] Array of component type names
     */
    public function getComponentTypes(): array
    {
        if ($this->isUnionType()) {
            return array_map('trim', explode('|', $this->type));
        }

        if ($this->isIntersectionType()) {
            return array_map('trim', explode('&', $this->type));
        }

        return [$this->type];
    }
}
