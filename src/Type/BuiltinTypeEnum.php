<?php

declare(strict_types=1);

namespace Graywings\Instantiate\Type;

/**
 * Enumeration representing PHP's built-in types
 */
enum BuiltinTypeEnum: string
{
    case INT = 'int';
    case FLOAT = 'float';
    case BOOL = 'bool';
    case STRING = 'string';
    case ARRAY = 'array';
    case OBJECT = 'object';
    case NULL = 'null';
    case MIXED = 'mixed';

    /**
     * Determine if a value is valid for this type
     */
    public function isValid(mixed $value): bool
    {
        return match($this) {
            self::INT => is_int($value),
            self::FLOAT => is_float($value),
            self::BOOL => is_bool($value),
            self::STRING => is_string($value),
            self::ARRAY => is_array($value),
            self::OBJECT => is_object($value),
            self::NULL => is_null($value),
            self::MIXED => true,
        };
    }

    /**
     * Determine if a value can be cast to this type
     */
    public function canCast(mixed $value): bool
    {
        // If already valid, can always be cast
        if ($this->isValid($value)) {
            return true;
        }

        return match($this) {
            self::INT => is_numeric($value) || is_bool($value),
            self::FLOAT => is_numeric($value) || is_bool($value),
            self::BOOL => true,  // All values can be cast to bool
            self::STRING => true, // All values can be cast to string
            self::ARRAY => is_string($value) || is_array($value), // Only strings and arrays can be cast to array
            self::OBJECT => true, // All values can be cast to object
            self::NULL => true, // All values can be cast to null
            self::MIXED => true, // All values can be cast to mixed
        };
    }

    /**
     * Cast a value to this type
     */
    public function cast(mixed $value): mixed
    {
        return match($this) {
            self::INT => (int)$value,
            self::FLOAT => (float)$value,
            self::BOOL => (bool)$value,
            self::STRING => (string)$value,
            self::ARRAY => $this->castToArray($value),
            self::OBJECT => is_object($value) ? $value : (object)$value,
            self::NULL => null,
            self::MIXED => $value,
        };
    }

    /**
     * Convert to array
     * Only strings can be converted, other types will throw an exception
     *
     * @param  mixed $value
     * @return mixed[]
     */
    private function castToArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            return str_split($value);
        }

        throw new \InvalidArgumentException(
            "Cannot cast value of type " . gettype($value) . " to array"
        );
    }

    /**
     * Get type from name
     *
     * @throws \InvalidArgumentException When type name is unknown
     */
    public static function fromName(string $name): self
    {
        foreach (self::cases() as $case) {
            if ($case->value === $name) {
                return $case;
            }
        }
        throw new \InvalidArgumentException("Unknown builtin type: {$name}");
    }
}
