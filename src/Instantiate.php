<?php

declare(strict_types=1);

namespace Graywings\Instantiate;

use Enum;
use Graywings\Instantiate\Exception\InstantiateException;
use Graywings\Instantiate\Exception\InstantiateArgumentsException;
use Graywings\Instantiate\PropertyNode\PropertyNode;
use Graywings\Instantiate\Type\Type;
use Graywings\Instantiate\Type\BuiltinType;
use Graywings\Instantiate\Type\ArrayType;
use Graywings\Instantiate\Type\UserDefinedType;
use Graywings\Instantiate\Type\AlgebraicDataType;
use Graywings\Instantiate\Type\AlgebraicOperator;
use ReflectionClass;
use ReflectionParameter;
use ReflectionNamedType;
use ReflectionEnum;
use ReflectionException;
use stdClass;
use UnitEnum;
use BackedEnum;

/**
 * Class for instantiating objects from various data sources
 */
class Instantiate
{
    /**
     * Create an instance from a JSON string
     *
     * @param  string $className The name of the class to instantiate
     * @param  string $json      The JSON string containing the data
     * @return object An instance of the specified class
     * @throws InstantiateException If the JSON is invalid or instantiation fails
     */
    public static function json(string $className, string $json): object
    {
        try {
            $data = json_decode($json, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new InstantiateArgumentsException('Invalid JSON: ' . json_last_error_msg());
            }

            return self::array($className, $data);
        } catch (InstantiateException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new InstantiateException(
                "Failed to instantiate {$className} from JSON: " . $e->getMessage(),
                $e
            );
        }
    }

    /**
     * Create an instance from a stdClass object
     *
     * @param  string   $className The name of the class to instantiate
     * @param  stdClass $data      The stdClass object containing the data
     * @return object An instance of the specified class
     * @throws InstantiateException If instantiation fails
     */
    public static function stdClass(string $className, stdClass $data): object
    {
        try {
            $encoded = json_encode($data);
            if ($encoded === false) {
                throw new InstantiateArgumentsException('It can\'t convert JSON string: ' . json_last_error_msg());
            }
            $array = json_decode($encoded, true);
            return self::array($className, $array);
        } catch (InstantiateException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new InstantiateException(
                "Failed to instantiate {$className} from stdClass: " . $e->getMessage(),
                $e
            );
        }
    }

    /**
     * Create an instance from an associative array
     *
     * @param  string               $className The name of the class to instantiate
     * @param  array<string, mixed> $data      The associative array containing the data
     * @return object An instance of the specified class
     * @throws InstantiateException If instantiation fails
     */
    public static function array(string $className, array $data): object
    {
        try {
            if (!class_exists($className)) {
                throw new InstantiateArgumentsException("Class {$className} does not exist");
            }

            // Get property nodes using constructor parameters
            $propertyNodes = PropertyNode::buildFromClass($className);

            // Prepare constructor arguments based on property nodes
            $constructorArgs = self::prepareConstructorArguments($propertyNodes, $data);

            // Create an instance of the class
            return new $className(...$constructorArgs);
        } catch (InstantiateException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new InstantiateException(
                "Failed to instantiate {$className} from array: " . $e->getMessage(),
                $e
            );
        }
    }

    /**
     * Prepare constructor arguments based on property nodes and input data
     *
     * @param  PropertyNode[]       $propertyNodes The property nodes from the class
     * @param  array<string, mixed> $data          The input data
     * @return mixed[] The prepared constructor arguments
     * @throws InstantiateArgumentsException If required arguments are missing or invalid
     */
    private static function prepareConstructorArguments(array $propertyNodes, array $data): array
    {
        $args = [];

        // Get reflection information about the class and its constructor
        $className = null;
        $reflection = null;
        $constructor = null;
        $parameters = [];

        foreach ($propertyNodes as $node) {
            $name = $node->getName();
            $type = $node->getType();
            $nullable = $node->isNullable();

            // Check if the parameter exists in the data
            if (!array_key_exists($name, $data)) {
                // If parameter is not in the data, we need to check if it has a default value
                // Initialize reflection objects if needed
                if ($reflection === null) {
                    // Find the class name from one of the property nodes with a UserDefinedType
                    foreach ($propertyNodes as $checkNode) {
                        if ($checkNode->getType() instanceof UserDefinedType) {
                            $className = $checkNode->getType()->getName();
                            break;
                        }
                    }

                    if ($className !== null) {
                        try {
                            $reflection = new ReflectionClass($className);
                            $constructor = $reflection->getConstructor();
                            if ($constructor !== null) {
                                $parameters = $constructor->getParameters();
                            }
                        } catch (ReflectionException $e) {
                            // Ignore reflection errors
                        }
                    }
                }

                // Check if the parameter has a default value
                $hasDefaultValue = false;
                $defaultValue = null;

                if (!empty($parameters)) {
                    foreach ($parameters as $param) {
                        if ($param->getName() === $name && $param->isOptional()) {
                            $hasDefaultValue = true;
                            $defaultValue = $param->getDefaultValue();
                            break;
                        }
                    }
                }

                if ($hasDefaultValue) {
                    // Use the default value from the constructor
                    $args[] = $defaultValue;
                    continue;
                } elseif ($nullable) {
                    // Parameter is nullable, so use null
                    $args[] = null;
                    continue;
                } else {
                    // Parameter is required but not provided
                    throw new InstantiateArgumentsException("Required parameter '{$name}' is missing");
                }
            }

            $value = $data[$name];

            // Handle null values
            if ($value === null) {
                if (!$nullable) {
                    throw new InstantiateArgumentsException("Parameter '{$name}' cannot be null");
                }

                $args[] = null;
                continue;
            }

            // Convert value based on type
            $args[] = self::convertValue($value, $type, $name);
        }

        return $args;
    }

    /**
     * Convert a value based on its type
     *
     * @param  mixed  $value The value to convert
     * @param  Type   $type  The type to convert to
     * @param  string $name  The name of the parameter (for error messages)
     * @return mixed The converted value
     * @throws InstantiateArgumentsException If the value cannot be converted
     */
    private static function convertValue(mixed $value, Type $type, string $name): mixed
    {
        // If the value is already valid, return it as is
        if ($type->isValidValue($value)) {
            return $value;
        }

        // If the value can be cast to the type, cast it
        if ($type->canCast($value)) {
            return $type->cast($value);
        }

        // Handle special cases based on the type
        if ($type instanceof BuiltinType) {
            return self::convertToBuiltinType($value, $type, $name);
        } elseif ($type instanceof UserDefinedType) {
            return self::convertToUserDefinedType($value, $type, $name);
        } elseif ($type instanceof ArrayType) {
            return self::convertToArrayType($value, $type, $name);
        } elseif ($type instanceof AlgebraicDataType) {
            return self::convertToAlgebraicDataType($value, $type, $name);
        }

        throw new InstantiateArgumentsException(
            "Cannot convert value for parameter '{$name}' to type " . $type->getName()
        );
    }

    /**
     * Convert a value to a built-in type
     *
     * @param  mixed       $value The value to convert
     * @param  BuiltinType $type  The built-in type
     * @param  string      $name  The name of the parameter
     * @return mixed The converted value
     * @throws InstantiateArgumentsException If the value cannot be converted
     */
    private static function convertToBuiltinType(mixed $value, BuiltinType $type, string $name): mixed
    {
        $typeName = $type->getName();

        // Handle array type specially
        if ($typeName === 'array' && is_object($value)) {
            return (array)$value;
        }

        throw new InstantiateArgumentsException(
            "Cannot convert value of type " . gettype($value) . " to {$typeName} for parameter '{$name}'"
        );
    }

    /**
     * Convert a value to a user-defined type
     *
     * @param  mixed           $value The value to convert
     * @param  UserDefinedType $type  The user-defined type
     * @param  string          $name  The name of the parameter
     * @return object The converted value
     * @throws InstantiateArgumentsException If the value cannot be converted
     */
    private static function convertToUserDefinedType(mixed $value, UserDefinedType $type, string $name): object
    {
        $className = $type->getName();

        // If the value is already an object of the required type, return it
        if (is_object($value) && $value instanceof $className) {
            return $value;
        }

        // Check if the class is an enum
        try {
            $reflection = new ReflectionClass($className);
            if ($reflection->isEnum()) {
                /**
 * @var class-string<UnitEnum> $className 
*/
                return self::convertToEnum($value, $className, $name);
            }
        } catch (ReflectionException $e) {
            // Ignore reflection errors and proceed with normal conversion
        }

        // If the value is an array or stdClass, try to instantiate the class from it
        if (is_array($value)) {
            return self::array($className, $value);
        } elseif (is_object($value) && $value instanceof stdClass) {
            return self::stdClass($className, $value);
        }

        throw new InstantiateArgumentsException(
            "Cannot convert value of type " . gettype($value) . " to {$className} for parameter '{$name}'"
        );
    }

    /**
     * Convert a value to an enum
     *
     * @template-covariant T of UnitEnum
     * @param              mixed           $value     The value to convert
     * @param              class-string<T> $enumClass The enum class name
     * @param              string          $name      The name of the parameter
     * @return             T::* The enum value
     * @throws             InstantiateArgumentsException If the value cannot be converted to the enum
     */
    private static function convertToEnum(mixed $value, string $enumClass, string $name): object // @phpstan-ignore return.unresolvableType, method.variance
    {
        // If the value is already an enum of the required type, return it
        if ($value instanceof $enumClass) {
            return $value;
        }

        try {
            $reflection = new ReflectionEnum($enumClass);
            $isBackedEnum = $reflection->isBacked();

            // Handle backed enums (string or int)
            if ($isBackedEnum) {
                // Try to get the case from the value
                if (is_string($value) || is_int($value)) {
                    // Use the from method if it exists
                    if (method_exists($enumClass, 'from')) {
                        try {
                            return $enumClass::from($value);
                        } catch (\ValueError $e) {
                            throw new InstantiateArgumentsException(
                                "Invalid enum value '{$value}' for parameter '{$name}': " . $e->getMessage()
                            );
                        }
                    }

                    // Try to use a custom fromString/fromInt method if it exists
                    $methodName = is_string($value) ? 'fromString' : 'fromInt';
                    if (method_exists($enumClass, $methodName)) {
                        $result = $enumClass::$methodName($value);
                        if ($result !== null) {
                            return $result;
                        }
                    }

                    // Try to find the case by iterating through all cases
                    foreach ($enumClass::cases() as $case) {
                        if ($case->value === $value) { // @phpstan-ignore property.notFound
                            return $case;
                        }
                    }
                }
            } else {
                // Handle pure (not backed) enums
                // Try to find the case by name if the value is a string
                if (is_string($value)) {
                    // Try to get the case by name using enum::NAME syntax
                    if (defined("$enumClass::$value")) {
                        return constant("$enumClass::$value");
                    }

                    // Try to find case with the same name
                    foreach ($enumClass::cases() as $case) {
                        if ($case->name === $value) {
                            return $case;
                        }
                    }
                }
            }

            throw new InstantiateArgumentsException(
                "Cannot convert value '{$value}' to enum {$enumClass} for parameter '{$name}'"
            );
        } catch (ReflectionException $e) {
            throw new InstantiateArgumentsException(
                "Error converting to enum {$enumClass} for parameter '{$name}': " . $e->getMessage()
            );
        }
    }

    /**
     * Convert a value to an array type
     *
     * @param  mixed     $value The value to convert
     * @param  ArrayType $type  The array type
     * @param  string    $name  The name of the parameter
     * @return mixed[] The converted value
     * @throws InstantiateArgumentsException If the value cannot be converted
     */
    private static function convertToArrayType(mixed $value, ArrayType $type, string $name): array
    {
        // If the value is not an array, try to convert it
        if (!is_array($value)) {
            if (is_object($value) && method_exists($value, 'toArray')) {
                $value = $value->toArray();
            } elseif ($value instanceof \Traversable) {
                $value = iterator_to_array($value);
            } elseif (is_object($value)) {
                $value = (array)$value;
            } else {
                throw new InstantiateArgumentsException(
                    "Cannot convert value of type " . gettype($value) . " to array for parameter '{$name}'"
                );
            }
        }

        // Convert each element of the array
        $elementType = $type->getElementType();
        $result = [];

        foreach ($value as $key => $element) {
            try {
                $result[$key] = self::convertValue($element, $elementType, "{$name}[{$key}]");
            } catch (InstantiateArgumentsException $e) {
                throw new InstantiateArgumentsException(
                    "Failed to convert element at key '{$key}' for parameter '{$name}': " . $e->getMessage(),
                    $e
                );
            }
        }

        return $result;
    }

    /**
     * Convert a value to an algebraic data type (union or intersection)
     *
     * @param  mixed             $value The value to convert
     * @param  AlgebraicDataType $type  The algebraic data type
     * @param  string            $name  The name of the parameter
     * @return mixed The converted value
     * @throws InstantiateArgumentsException If the value cannot be converted
     */
    private static function convertToAlgebraicDataType(mixed $value, AlgebraicDataType $type, string $name): mixed
    {
        $operator = $type->getOperator();
        $types = $type->getTypes();

        return match($operator) {
            AlgebraicOperator::UNION => function () use ($value, $types, $name) {
                // For union types, try each type until one works
                $errors = [];

                foreach ($types as $subType) {
                    try {
                        return self::convertValue($value, $subType, $name);
                    } catch (InstantiateArgumentsException $e) {
                        $errors[] = $e->getMessage();
                    }
                }

                throw new InstantiateArgumentsException(
                    "Cannot convert value for parameter '{$name}' to any of the union types: " . implode(', ', $errors)
                );
            },
            AlgebraicOperator::INTERSECTION => function () use ($value, $types, $name) {
                // For intersection types, the value must conform to all types
                // This is generally only applicable for interfaces and abstract classes
                // For simplicity, we'll just check if the value is an object that implements all required interfaces

                if (!is_object($value)) {
                    throw new InstantiateArgumentsException(
                        "Cannot convert non-object value to intersection type for parameter '{$name}'"
                    );
                }

                foreach ($types as $subType) {
                    if (!($subType instanceof UserDefinedType)) {
                        throw new InstantiateArgumentsException(
                            "Intersection type for parameter '{$name}' contains non-user-defined type"
                        );
                    }

                    $interfaceName = $subType->getName();

                    if (!($value instanceof $interfaceName)) {
                        throw new InstantiateArgumentsException(
                            "Object does not implement required interface '{$interfaceName}' for parameter '{$name}'"
                        );
                    }
                }

                return $value;

            }
        };
    }
}
