<?php

declare(strict_types=1);

namespace Graywings\Instantiate\PropertyNode;

use Graywings\Instantiate\Type\Type;
use Graywings\Instantiate\Type\BuiltinType;
use Graywings\Instantiate\Type\BuiltinTypeEnum;
use Graywings\Instantiate\Type\UserDefinedType;
use Graywings\Instantiate\Type\ArrayType;
use Graywings\Instantiate\Type\AlgebraicDataType;
use Graywings\Instantiate\Type\AlgebraicOperator;
use Graywings\Instantiate\PropertyNode\ArrayOf;
use ReflectionClass;
use ReflectionProperty;
use ReflectionNamedType;
use ReflectionUnionType;
use ReflectionIntersectionType;
use ReflectionAttribute;

/**
 * A class that builds a property tree structure from a class name
 */
class PropertyNode
{
    private string $name;
    private Type $type;
    private bool $nullable;

    /**
     * @var PropertyNode[] 
     */
    private array $children = [];

    /**
     * Create a new PropertyNode
     *
     * @param string $name     The name of the property
     * @param Type   $type     The type of the property
     * @param bool   $nullable Whether the property is nullable
     */
    public function __construct(string $name, Type $type, bool $nullable = false)
    {
        $this->name = $name;
        $this->type = $type;
        $this->nullable = $nullable;
    }

    /**
     * Build a property tree from a class name
     *
     * @param  string $className         The name of the class to build a property tree from
     * @param  bool   $usePublicProperty If true, use public properties; if false (default), use constructor parameters
     * @return PropertyNode[] Array of property nodes representing the class properties
     */
    public static function buildFromClass(string $className, bool $usePublicProperty = false): array
    {
        if (!class_exists($className)) {
            throw new \InvalidArgumentException("Class '{$className}' does not exist");
        }

        $reflection = new ReflectionClass($className);
        $propertyNodes = [];

        if ($usePublicProperty) {
            // Use public properties
            $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

            foreach ($properties as $property) {
                $propertyNodes[] = self::processProperty($property);
            }
        } else {
            // Use constructor parameters
            $constructor = $reflection->getConstructor();

            if ($constructor === null) {
                // If no constructor, return empty array
                return [];
            }

            $parameters = $constructor->getParameters();

            foreach ($parameters as $parameter) {
                $propertyNodes[] = self::processParameter($parameter);
            }
        }

        return $propertyNodes;
    }

    /**
     * Process a reflection parameter (from constructor) and create a PropertyNode
     *
     * @param  \ReflectionParameter $parameter The parameter to process
     * @return PropertyNode The created property node
     */
    private static function processParameter(\ReflectionParameter $parameter): PropertyNode
    {
        $name = $parameter->getName();
        $reflectionType = $parameter->getType();
        $nullable = $reflectionType?->allowsNull() ?? true;

        $type = self::processType($reflectionType);

        // Check for ArrayOf attribute on parameter if the type is array
        if ($type instanceof BuiltinType && $type->getName() === 'array') {
            $arrayOfAttributes = $parameter->getAttributes(ArrayOf::class);
            if (count($arrayOfAttributes) > 0) {
                /**
 * @var ArrayOf $arrayOfAttribute 
*/
                $arrayOfAttribute = $arrayOfAttributes[0]->newInstance();

                // Handle different types of element types
                if ($arrayOfAttribute->isUnionType()) {
                    // Handle union types (e.g., 'string|int')
                    $componentTypeNames = $arrayOfAttribute->getComponentTypes();
                    $componentTypes = array_map(
                        function ($typeName) {
                            return self::createTypeFromName($typeName);
                        }, $componentTypeNames
                    );

                    $elementType = new AlgebraicDataType($componentTypes, AlgebraicOperator::UNION);
                } elseif ($arrayOfAttribute->isIntersectionType()) {
                    // Handle intersection types (e.g., 'Countable&Iterator')
                    $componentTypeNames = $arrayOfAttribute->getComponentTypes();
                    $componentTypes = array_map(
                        function ($typeName) {
                            return self::createTypeFromName($typeName);
                        }, $componentTypeNames
                    );

                    $elementType = new AlgebraicDataType($componentTypes, AlgebraicOperator::INTERSECTION);
                } else {
                    // Handle simple types
                    $elementTypeName = $arrayOfAttribute->getType();
                    $elementType = self::createTypeFromName($elementTypeName);
                }

                // Create an ArrayType with the specified element type
                $type = new ArrayType($elementType);
            }
        }

        $node = new PropertyNode($name, $type, $nullable);

        // Process child nodes for complex types
        self::processChildNodes($node, $type);

        return $node;
    }

    /**
     * Process a reflection property and create a PropertyNode
     *
     * @param  ReflectionProperty $property The property to process
     * @return PropertyNode The created property node
     */
    private static function processProperty(ReflectionProperty $property): PropertyNode
    {
        $name = $property->getName();
        $reflectionType = $property->getType();
        $nullable = $reflectionType?->allowsNull() ?? true;

        $type = self::processType($reflectionType);

        // Check for ArrayOf attribute if property is an array
        if ($type instanceof BuiltinType && $type->getName() === 'array') {
            $arrayOfAttributes = $property->getAttributes(ArrayOf::class);
            if (count($arrayOfAttributes) > 0) {
                /**
 * @var ArrayOf $arrayOfAttribute 
*/
                $arrayOfAttribute = $arrayOfAttributes[0]->newInstance();

                // Handle different types of element types
                if ($arrayOfAttribute->isUnionType()) {
                    // Handle union types (e.g., 'string|int')
                    $componentTypeNames = $arrayOfAttribute->getComponentTypes();
                    $componentTypes = array_map(
                        function ($typeName) {
                            return self::createTypeFromName($typeName);
                        }, $componentTypeNames
                    );

                    $elementType = new AlgebraicDataType($componentTypes, AlgebraicOperator::UNION);
                } elseif ($arrayOfAttribute->isIntersectionType()) {
                    // Handle intersection types (e.g., 'Countable&Iterator')
                    $componentTypeNames = $arrayOfAttribute->getComponentTypes();
                    $componentTypes = array_map(
                        function ($typeName) {
                            return self::createTypeFromName($typeName);
                        }, $componentTypeNames
                    );

                    $elementType = new AlgebraicDataType($componentTypes, AlgebraicOperator::INTERSECTION);
                } else {
                    // Handle simple types
                    $elementTypeName = $arrayOfAttribute->getType();
                    $elementType = self::createTypeFromName($elementTypeName);
                }

                // Create an ArrayType with the specified element type
                $type = new ArrayType($elementType);
            }
        }

        $node = new PropertyNode($name, $type, $nullable);

        // Process child nodes
        self::processChildNodes($node, $type);

        return $node;
    }

    /**
     * Process child nodes for complex types
     *
     * @param PropertyNode $node The parent node
     * @param Type         $type The type to process
     */
    private static function processChildNodes(PropertyNode $node, Type $type): void
    {
        // If the type is a user-defined type, process its properties recursively
        if ($type instanceof UserDefinedType) {
            $className = $type->getName();
            if (class_exists($className)) {
                try {
                    $childNodes = self::buildFromClass($className);
                    foreach ($childNodes as $childNode) {
                        $node->addChild($childNode);
                    }
                } catch (\InvalidArgumentException $e) {
                    // Skip if there's an issue processing the child class
                }
            }
        } elseif ($type instanceof ArrayType) {
            // If it's an array type, check if the element type is a user-defined type
            $elementType = $type->getElementType();
            if ($elementType instanceof UserDefinedType) {
                $className = $elementType->getName();
                if (class_exists($className)) {
                    try {
                        $childNodes = self::buildFromClass($className);
                        foreach ($childNodes as $childNode) {
                            $node->addChild($childNode);
                        }
                    } catch (\InvalidArgumentException $e) {
                        // Skip if there's an issue processing the child class
                    }
                }
            }
        }
    }

    /**
     * Create a Type from a type name
     *
     * @param  string $typeName The name of the type
     * @return Type The created type
     */
    private static function createTypeFromName(string $typeName): Type
    {
        // Built-in PHP types
        $builtinTypes = [
            'int', 'float', 'string', 'bool', 'array', 'object', 'null', 'mixed'
        ];

        if (in_array($typeName, $builtinTypes)) {
            return new BuiltinType($typeName);
        }

        // Check if it's a class or interface
        if (class_exists($typeName) || interface_exists($typeName)) {
            return new UserDefinedType($typeName);
        }

        // Default to mixed if type cannot be determined
        return new BuiltinType(BuiltinTypeEnum::MIXED);
    }

    /**
     * Process a reflection type and convert it to our Type system
     *
     * @param  \ReflectionType|null $reflectionType The reflection type to process
     * @return Type The converted type
     */
    private static function processType(?\ReflectionType $reflectionType): Type
    {
        if ($reflectionType === null) {
            // If no type is specified, default to mixed
            return new BuiltinType(BuiltinTypeEnum::MIXED);
        }

        if ($reflectionType instanceof ReflectionNamedType) {
            $typeName = $reflectionType->getName();

            // Handle built-in types
            if ($reflectionType->isBuiltin()) {
                return new BuiltinType($typeName);
            } else {
                /**
 * @var class-string $typeName 
*/
                // Handle user-defined types
                return new UserDefinedType($typeName);
            }
        }

        if ($reflectionType instanceof ReflectionUnionType) {
            $types = [];
            foreach ($reflectionType->getTypes() as $type) {
                $types[] = self::processType($type);
            }

            return new AlgebraicDataType($types, AlgebraicOperator::UNION);
        }

        if ($reflectionType instanceof ReflectionIntersectionType) {
            $types = [];
            foreach ($reflectionType->getTypes() as $type) {
                $types[] = self::processType($type);
            }

            return new AlgebraicDataType($types, AlgebraicOperator::INTERSECTION);
        }

        // Default fallback
        return new BuiltinType(BuiltinTypeEnum::MIXED);
    }

    /**
     * Add a child node to this node
     *
     * @param PropertyNode $child The child node to add
     */
    public function addChild(PropertyNode $child): void
    {
        $this->children[] = $child;
    }

    /**
     * Get the name of the property
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the type of the property
     */
    public function getType(): Type
    {
        return $this->type;
    }

    /**
     * Check if the property is nullable
     */
    public function isNullable(): bool
    {
        return $this->nullable;
    }

    /**
     * Get the children of this node
     *
     * @return PropertyNode[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * Convert the property node to an array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            'name' => $this->name,
            'type' => $this->type->getName(),
            'nullable' => $this->nullable,
        ];

        if (!empty($this->children)) {
            $result['children'] = array_map(
                fn (PropertyNode $child) => $child->toArray(),
                $this->children
            );
        }

        return $result;
    }
}
