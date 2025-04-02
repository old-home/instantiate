<?php

declare(strict_types=1);

namespace Graywings\Instantiate\Type;

class UserDefinedType implements Type
{
    /**
     * @var class-string
     */
    private string $className;

    /**
     * @param class-string $className
     */
    public function __construct(string $className)
    {
        if (!class_exists($className) && !interface_exists($className)) {
            throw new \InvalidArgumentException("Class or interface '{$className}' does not exist");
        }

        $this->className = $className;
    }

    /**
     * @return class-string
     */
    public function getName(): string
    {
        return $this->className;
    }

    public function isValidValue(mixed $value): bool
    {
        return $value instanceof $this->className;
    }

    public function canCast(mixed $value): bool
    {
        // Casting to a user-defined type is only possible if the value is already of that type,
        // or if there are special casting rules
        return $this->isValidValue($value);
    }

    public function cast(mixed $value): object
    {
        if ($this->isValidValue($value)) {
            return $value;
        }

        throw new \InvalidArgumentException(
            "Cannot cast value of type " . (is_object($value) ? get_class($value) : gettype($value)) .
            " to {$this->className}"
        );
    }
}
