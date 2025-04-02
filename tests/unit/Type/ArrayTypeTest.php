<?php

declare(strict_types=1);

namespace Graywings\Instantiate\Tests\Unit\Type;

use Graywings\Instantiate\Type\ArrayType;
use Graywings\Instantiate\Type\BuiltinType;
use Graywings\Instantiate\Type\BuiltinTypeEnum;
use Graywings\Instantiate\Type\UserDefinedType;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ArrayType::class)]
#[CoversClass(BuiltinType::class)]
#[CoversClass(BuiltinTypeEnum::class)]
#[CoversClass(UserDefinedType::class)]
class ArrayTypeTest extends TestCase
{
    public function testGetName(): void
    {
        $arrayType = new ArrayType(new BuiltinType('string'));
        $this->assertSame('array<string>', $arrayType->getName());

        $nestedArray = new ArrayType(new ArrayType(new BuiltinType('int')));
        $this->assertSame('array<array<int>>', $nestedArray->getName());
    }

    public function testIsValidValue(): void
    {
        $arrayType = new ArrayType(new BuiltinType('string'));

        // Empty arrays are always valid
        $this->assertTrue($arrayType->isValidValue([]));

        // All elements are strings
        $this->assertTrue($arrayType->isValidValue(['a', 'b', 'c']));

        // Integer keys or string keys are both OK (ArrayType only checks element types)
        $this->assertTrue($arrayType->isValidValue(['key' => 'value']));

        // Contains non-string elements
        $this->assertFalse($arrayType->isValidValue(['a', 1, 'c']));

        // Non-array types
        $this->assertFalse($arrayType->isValidValue('not an array'));
        $this->assertFalse($arrayType->isValidValue(42));
        $this->assertFalse($arrayType->isValidValue(new \stdClass()));
    }

    public function testCast(): void
    {
        $arrayType = new ArrayType(new BuiltinType('int'));

        // Cast an array of strings to an array of integers
        $result = $arrayType->cast(['1', '2', '3']);
        $this->assertSame([1, 2, 3], $result);

        // Keys are preserved
        $result = $arrayType->cast(['a' => '10', 'b' => '20']);
        $this->assertSame(['a' => 10, 'b' => 20], $result);

        // Conversion from Traversable
        $arrayObject = new \ArrayObject(['1', '2', '3']);
        $result = $arrayType->cast($arrayObject);
        $this->assertSame([1, 2, 3], $result);
    }

    public function testCastThrowsExceptionForNonIterableValue(): void
    {
        $arrayType = new ArrayType(new BuiltinType('string'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot cast non-iterable value to array');

        $arrayType->cast('not an array');
    }

    public function testNestedArrayTypeCast(): void
    {
        $nestedArrayType = new ArrayType(new ArrayType(new BuiltinType('int')));

        $result = $nestedArrayType->cast(
            [
            ['1', '2'],
            ['3', '4']
            ]
        );

        $this->assertSame(
            [
            [1, 2],
            [3, 4]
            ], $result
        );
    }

    public function testUserDefinedTypeArray(): void
    {
        // Array of \ArrayObject type
        $arrayObjectType = new ArrayType(new UserDefinedType(\ArrayObject::class));

        $this->assertTrue(
            $arrayObjectType->isValidValue(
                [
                new \ArrayObject(),
                new \ArrayObject()
                ]
            )
        );

        $this->assertFalse(
            $arrayObjectType->isValidValue(
                [
                new \ArrayObject(),
                new \stdClass() // Not an ArrayObject
                ]
            )
        );
    }

    public function testGetElementType(): void
    {
        $elementType = new BuiltinType('string');
        $arrayType = new ArrayType($elementType);

        $this->assertSame($elementType, $arrayType->getElementType());
    }
}
