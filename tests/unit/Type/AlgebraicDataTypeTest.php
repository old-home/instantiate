<?php

declare(strict_types=1);

namespace Graywings\Instantiate\Tests\Unit\Type;

use Graywings\Instantiate\Type\AlgebraicDataType;
use Graywings\Instantiate\Type\AlgebraicOperator;
use Graywings\Instantiate\Type\BuiltinType;
use Graywings\Instantiate\Type\BuiltinTypeEnum;
use Graywings\Instantiate\Type\UserDefinedType;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AlgebraicDataType::class)]
#[CoversClass(AlgebraicOperator::class)]
#[CoversClass(BuiltinType::class)]
#[CoversClass(BuiltinTypeEnum::class)]
#[CoversClass(UserDefinedType::class)]
class AlgebraicDataTypeTest extends TestCase
{
    public function testConstructorThrowsExceptionForLessThanTwoTypes(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('AlgebraicDataType requires at least 2 types');

        new AlgebraicDataType(
            [new BuiltinType('string')],
            AlgebraicOperator::UNION
        );
    }

    public function testGetName(): void
    {
        $unionType = new AlgebraicDataType(
            [new BuiltinType('string'), new BuiltinType('int')],
            AlgebraicOperator::UNION
        );

        $this->assertSame('string|int', $unionType->getName());

        $intersectionType = new AlgebraicDataType(
            [new BuiltinType('array'), new UserDefinedType(\Traversable::class)],
            AlgebraicOperator::INTERSECTION
        );

        $this->assertSame('array&Traversable', $intersectionType->getName());
    }

    public function testIsValidValueForUnion(): void
    {
        $unionType = new AlgebraicDataType(
            [new BuiltinType('string'), new BuiltinType('int')],
            AlgebraicOperator::UNION
        );

        // Valid if it conforms to either type
        $this->assertTrue($unionType->isValidValue('hello'));
        $this->assertTrue($unionType->isValidValue(42));

        // Does not conform to either type
        $this->assertFalse($unionType->isValidValue(true));
        $this->assertFalse($unionType->isValidValue([]));
    }

    public function testIsValidValueForIntersection(): void
    {
        // \ArrayObject implements \Countable
        $intersectionType = new AlgebraicDataType(
            [new UserDefinedType(\ArrayObject::class), new UserDefinedType(\Countable::class)],
            AlgebraicOperator::INTERSECTION
        );

        // Valid because it implements both interfaces
        $this->assertTrue($intersectionType->isValidValue(new \ArrayObject()));

        // Invalid because \Exception does not implement \Countable
        $this->assertFalse($intersectionType->isValidValue(new \Exception()));
    }

    public function testCanCastForUnion(): void
    {
        $unionType = new AlgebraicDataType(
            [new BuiltinType('string'), new BuiltinType('int')],
            AlgebraicOperator::UNION
        );

        // Valid if it can be cast to any of the types
        $this->assertTrue($unionType->canCast('42'));
        $this->assertTrue($unionType->canCast(42));
        $this->assertTrue($unionType->canCast(true)); // bool can be cast to both string and int

        // Invalid if it cannot be cast to any type
        $unionType = new AlgebraicDataType(
            [new UserDefinedType(\ArrayObject::class), new UserDefinedType(\Countable::class)],
            AlgebraicOperator::UNION
        );
        $this->assertFalse($unionType->canCast('not castable'));
    }

    public function testCanCastForIntersection(): void
    {
        // Test combination of BuiltinTypes
        $intersectionType = new AlgebraicDataType(
            [new BuiltinType('string'), new BuiltinType('int')],
            AlgebraicOperator::INTERSECTION
        );

        // Numbers can be cast to both string and int
        $this->assertTrue($intersectionType->canCast(42));

        // Numeric strings can be cast to int
        $this->assertTrue($intersectionType->canCast('42'));

        // Non-numeric strings cannot be cast to int
        $this->assertFalse($intersectionType->canCast('hello'));
    }

    public function testCastThrowsException(): void
    {
        $unionType = new AlgebraicDataType(
            [new BuiltinType('string'), new BuiltinType('int')],
            AlgebraicOperator::UNION
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Direct casting of algebraic data types is not supported');

        $unionType->cast('42');
    }

    public function testGetTypes(): void
    {
        $types = [new BuiltinType('string'), new BuiltinType('int')];

        $algebraicType = new AlgebraicDataType(
            $types,
            AlgebraicOperator::UNION
        );

        $this->assertSame($types, $algebraicType->getTypes());
    }

    public function testGetOperator(): void
    {
        $operator = AlgebraicOperator::INTERSECTION;

        $algebraicType = new AlgebraicDataType(
            [new BuiltinType('string'), new BuiltinType('int')],
            $operator
        );

        $this->assertSame($operator, $algebraicType->getOperator());
    }
}
