<?php

declare(strict_types=1);

namespace Graywings\Instantiate\Type;

use Graywings\Instantiate\InstantiateException;
use ReflectionAttribute;
use ReflectionParameter;
use ReflectionType;
use ReflectionNamedType;
use ReflectionUnionType;
use ReflectionIntersectionType;

readonly class AlgebraicDataType
{
    /**
     * @param array<Type> $types
     * @param AlgebraicOperator $algebraicOperator
     */
    public function __construct(
        private(set) array $types,
        private(set) AlgebraicOperator $algebraicOperator
    )
    {
    }

    /**
     * @param ReflectionType $reflectionType
     * @param array<ReflectionAttribute> $reflectionAttributes
     * @return self
     */
    public static function buildFromReflectionType(ReflectionType $reflectionType, array $reflectionAttributes): self
    {
        if ($reflectionType instanceof ReflectionNamedType) {
            $type = self::buildFromReflectionNamedType($reflectionType);
            if ($type === BuiltinType::ARRAY) {
                return new self(
                    [$type],
                    AlgebraicOperator::ARRAY
                );
            }
            if ($reflectionType->allowsNull() && $type !== BuiltinType::NULL) {
                return new self(
                    [BuiltinType::NULL, $type],
                    $type instanceof BuiltinType ? AlgebraicOperator::NONE : AlgebraicOperator::PRODUCT
                );
            }
            return new self(
                [$type],
                $type instanceof BuiltinType ? AlgebraicOperator::NONE : AlgebraicOperator::PRODUCT
            );
        } else {
            $types = [];
            /** @var ReflectionUnionType|ReflectionIntersectionType $reflectionType */
            foreach ($reflectionType->getTypes() as $reflectionNamedType) {
                $types[] = self::buildFromReflectionNamedType($reflectionNamedType);
            }
            if ($reflectionType instanceof ReflectionUnionType) {
                return new self(
                    $types,
                    AlgebraicOperator::UNION
                );
            } else if ($reflectionType instanceof ReflectionIntersectionType) {
                return new self(
                    $types,
                    AlgebraicOperator::INTERSECTION
                );
            }
        }
        throw new InstantiateException('Here must not be reached.');
    }

    private static function buildFromReflectionNamedType(ReflectionNamedType $reflectionNamedType): Type
    {
        return match ($reflectionNamedType->getName()) {
            BuiltinType::NULL->value => BuiltinType::NULL,
            BuiltinType::BOOL->value => BuiltinType::BOOL,
            BuiltinType::STRING->value => BuiltinType::STRING,
            BuiltinType::INT->value => BuiltinType::INT,
            BuiltinType::FLOAT->value => BuiltinType::FLOAT,
            BuiltinType::ARRAY->value => BuiltinType::ARRAY,
            default => new UserDefinedType($reflectionNamedType->getName())
        };
    }
}
