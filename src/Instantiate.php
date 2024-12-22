<?php

declare(strict_types=1);

namespace Graywings\Instantiate;

use Closure;
use Graywings\Instantiate\PropertyNode\PropertyNode;
use JsonException;
use stdClass;

class Instantiate
{
    /**
     * Dynamically instantiates an object of the specified class name.
     *
     * This function generates an instance of the specified class based on the given array or stdClass object.
     *
     * @param array $value     An array or stdClass object containing the values. For the class properties.
     * @param  string       $className The name of the class to instantiate. Must be a class-string type.
     * @return mixed Returns an instance of the specified class on success, or null on failure.
     *
     * @throws InstantiateException Thrown if the specified class does not exist.
     *
     *  Example usage of this function:
     *  ```
     *  $data = ['prop1' => 'value1', 'prop2' => 'value2'];
     *  $object = instantiate($data, MyClass::class);
     *  ```
     */
    public static function array(
        array  $value,
        string $className
    ): mixed {
        return self::instantiateMain(
            $value,
            $className,
            fn(string $key, array $value) => $value[$key]
        );
    }

    public static function stdClass(
        stdClass $object,
        string $className
    ): mixed {
        return self::instantiateMain(
            $object,
            $className,
            fn(string $key, stdClass $object) => $object->$key
        );
    }

    /**
     * @param  string             $json
     * @param  class-string       $className
     * @param  int<0, 2147483647> $depth
     * @return mixed
     * @throws InstantiateException
     */
    public static function json(
        string $json,
        string $className,
        int $depth = 512
    ): mixed {
        try {
            if ($depth < 1) {
                throw new JsonException("\$depth: $depth is not natural numbers");
            }
            $decoded = json_decode($json, false, $depth, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
        } catch (JsonException $e) {
            throw new InstantiateException(
                "JSON decode error: " . $e->getMessage(),
                -1,
                $e
            );
        }
        return self::stdClass($decoded, $className);
    }

    /**
     * Creates an instance of the specified class by processing the given value.
     *
     * @param array|stdClass $value The input data used to create the object instance.
     * @param string $className The fully qualified class name to instantiate.
     * @param Closure $extract A closure function to extract the necessary data from the input value.
     *
     * @return mixed Returns an instance of the specified class, optionally modified by the callback.
     */
    private static function instantiateMain(
        array|stdClass $value,
        string $className,
        Closure $extract
    ): mixed {
        $propertyTree = PropertyNode::buildFromClassString($className);
        return $propertyTree->instantiate($value, $extract);
    }
}
