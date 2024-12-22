<?php

declare(strict_types=1);

namespace Graywings\Instantiate\PropertyNode;

use Graywings\Instantiate\ArrayType;
use Graywings\Instantiate\InstantiateException;
use Graywings\Instantiate\Type\AlgebraicDataType;
use Graywings\Instantiate\Type\AlgebraicOperator;
use Graywings\Instantiate\Type\BuiltinType;
use Graywings\Instantiate\Type\UserDefinedType;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use ReflectionUnionType;

readonly class PropertyNode {
    /**
     * @param string $name
     * @param AlgebraicDataType $algebraicDataType
     * @param array<self> $nodes
     */
    public function __construct(
        private(set) string $name,
        private(set) AlgebraicDataType $algebraicDataType,
        private(set) array $nodes
    ) {}

    /**
     * Constructs an instance of the class using the provided class name string.
     *
     * @param string $className The fully qualified name of the class to create the instance from.
     * @return self An instance of the class constructed from the given class name.
     */
    public static function buildFromClassString(string $className): self
    {
        $reflectionClass = new ReflectionClass($className);
        return new self(
            '',
            new AlgebraicDataType(
                [new UserDefinedType($className)],
                AlgebraicOperator::PRODUCT
            ),
            self::buildNodesFromParameters(
                $reflectionClass->getConstructor()->getParameters()
            )
        );
    }

    /**
     * Builds an array of PropertyNode objects from the provided reflection parameters.
     *
     * @param array<ReflectionParameter> $reflectionParameters The array of ReflectionParameter objects from which nodes will be built.
     * @return array<PropertyNode> An array of PropertyNode objects created based on the provided reflection parameters.
     */
    private static function buildNodesFromParameters(array $reflectionParameters): array
    {
        /** @var array<PropertyNode> $nodes */
        $nodes = [];
        foreach($reflectionParameters as $reflectionParameter) {
            $reflectionAttributes = $reflectionParameter->getAttributes();
            $nodes[] = self::buildNodeFromReflectionType($reflectionParameter->getType(), $reflectionAttributes, $reflectionParameter->getName());
        }
        return $nodes;
    }

    /**
     * @param ReflectionType $reflectionType The reflection type used to construct the node.
     * @param array $reflectionAttributes Attributes associated with the reflection type.
     * @param string $name The name of the property node to build.
     * @return self An instance of the PropertyNode constructed from the given reflection type.
     */
    private static function buildNodeFromReflectionType(ReflectionType $reflectionType, array $reflectionAttributes, string $name = ''): self
    {
        $type = AlgebraicDataType::buildFromReflectionType($reflectionType, $reflectionAttributes);
        return new PropertyNode(
            $name,
            $type,
            match($type->algebraicOperator) {
                AlgebraicOperator::NONE => [],
                AlgebraicOperator::PRODUCT => self::buildNodesFromUserDefinedType($reflectionType, $reflectionAttributes),
                AlgebraicOperator::UNION => self::buildNodesFromUnionType($reflectionType, $reflectionAttributes),
                AlgebraicOperator::INTERSECTION => self::buildNodesFromIntersectionType($reflectionType, $reflectionAttributes),
                AlgebraicOperator::ARRAY => self::buildNodesArray($reflectionType, $reflectionAttributes)
            }
        );
    }

    /**
     * Builds an array of nodes from a user-defined type by inspecting its constructor's parameters.
     *
     * @param ReflectionNamedType $namedType The type of the user-defined class to process.
     * @param array $reflectionAttributes An array of attributes related to the reflection process.
     * @return array An array of nodes derived from the parameters of the class constructor.
     */
    private static function buildNodesFromUserDefinedType(ReflectionNamedType $namedType, array $reflectionAttributes): array
    {
        $className = $namedType->getName();
        $reflectionClass = new ReflectionClass($className);
        return self::buildNodesFromParameters($reflectionClass->getConstructor()->getParameters());
    }

    /**
     * Builds an array of PropertyNode instances from a union type and its associated reflection attributes.
     *
     * @param ReflectionUnionType $unionType The union type from which nodes will be built.
     * @param array $reflectionAttributes An array of reflection attributes used during the node building process.
     * @return array<PropertyNode> An array of PropertyNode instances generated from the given union type and attributes.
     */
    private static function buildNodesFromUnionType(ReflectionUnionType $unionType, array $reflectionAttributes): array
    {
        /** @var array<PropertyNode> $nodes */
        $nodes = [];
        /** @var ReflectionNamedType $reflectionNamedType */
        foreach ($unionType->getTypes() as $reflectionNamedType) {
            $algebraicDataType = AlgebraicDataType::buildFromReflectionType($reflectionNamedType, $reflectionAttributes);
            $nodes[] = new PropertyNode(
                '',
                $algebraicDataType,
                match($algebraicDataType->algebraicOperator) {
                    AlgebraicOperator::NONE => [],
                    AlgebraicOperator::PRODUCT => self::buildNodesFromUserDefinedType($reflectionNamedType, $reflectionAttributes),
                    AlgebraicOperator::ARRAY => self::buildNodesArray($reflectionNamedType, $reflectionAttributes),
                    AlgebraicOperator::UNION, AlgebraicOperator::INTERSECTION => throw new InstantiateException('Not implemented.')
                }
            );
        }
        return $nodes;
    }

    /**
     * Builds an array of nodes from the provided intersection type and reflection attributes.
     *
     * @param ReflectionIntersectionType $intersectionType An intersection type to process.
     * @param array $reflectionAttributes Reflection attributes associated with the types.
     * @return array<PropertyNode> An array of constructed nodes.
     */
    private static function buildNodesFromIntersectionType(ReflectionIntersectionType $intersectionType, array $reflectionAttributes): array
    {
        /** @var array<PropertyNode> $nodes */
        $nodes = [];
        /** @var ReflectionNamedType $type */
        foreach ($intersectionType->getTypes() as $type) {
            $className = $type->getName();
            $reflectionClass = new ReflectionClass($className);
            $nodes[] = self::buildNodesFromParameters($reflectionClass->getConstructor()->getParameters());
        }
        return $nodes;
    }

    /**
     * @param ReflectionType $type
     * @param array $reflectionAttributes
     * @return array
     */
    private static function buildNodesArray(ReflectionType $type, array $reflectionAttributes): array
    {
        $arrayType = null;
        foreach ($reflectionAttributes as $reflectionAttribute) {
            if ($reflectionAttribute->getName() === ArrayType::class) {
                $arrayType = $reflectionAttribute->newInstance();
            }
        }

        if ($arrayType === null) {
            return [new PropertyNode(
                '',
                new AlgebraicDataType([BuiltinType::MIXED], AlgebraicOperator::PRODUCT),
                []
            )];
        }
        return [self::buildFromClassString($arrayType)];
    }

    public function instantiate(
        mixed $value,
        callable $extract
    ): mixed
    {
        return match($this->algebraicDataType->algebraicOperator) {
            AlgebraicOperator::NONE => $value,
            AlgebraicOperator::UNION => (function($value) use ($extract) {
                $arguments = [];
                foreach ($this->nodes as $node) {
                    $arguments[] = $node->instantiate(
                        $extract($value, $node->name),
                        $extract
                    );
                }
                return $arguments[0];
            })($value),
            AlgebraicOperator::INTERSECTION => (function($value) use ($extract) {
                $arguments = [];
                foreach ($this->nodes as $node) {
                    $arguments[] = $node->instantiate(
                        $extract($value, $node->name),
                        $extract
                    );
                }
                return $arguments[0];
            })($value),
            AlgebraicOperator::PRODUCT => (function ($value) use ($extract) {
                $arguments = [];
                foreach ($this->nodes as $node) {
                    $arguments[] = $node->instantiate(
                        $extract($value, $node->name),
                        $extract
                    );
                }
                return $arguments;
            })($value),
            AlgebraicOperator::ARRAY => throw new InstantiateException('Not implemented.')
        };
    }
}
