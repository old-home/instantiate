<?php

declare(strict_types=1);

namespace Graywings\Instantiate\Tests\Unit\Type;

use Graywings\Instantiate\Type\BuiltinType;
use Graywings\Instantiate\Type\BuiltinTypeEnum;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(BuiltinType::class)]
#[CoversClass(BuiltinTypeEnum::class)]
class BuiltinTypeTest extends TestCase
{
    public function testConstructorThrowsExceptionForInvalidType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown builtin type: invalid_type');
        
        new BuiltinType('invalid_type');
    }
    
    #[DataProvider('validValuesProvider')]
    public function testIsValidValue(string $typeName, mixed $value, bool $expected): void
    {
        $type = new BuiltinType($typeName);
        $this->assertSame($expected, $type->isValidValue($value));
    }
    
    #[DataProvider('castValuesProvider')]
    public function testCast(string $typeName, mixed $value, mixed $expected): void
    {
        $type = new BuiltinType($typeName);
        $this->assertSame($expected, $type->cast($value));
    }
    
    public function testGetName(): void
    {
        $type = new BuiltinType('string');
        $this->assertSame('string', $type->getName());
    }
    
    public function testGetType(): void
    {
        $type = new BuiltinType('string');
        $this->assertSame(BuiltinTypeEnum::STRING, $type->getType());
    }
    
    public function testConstructWithBuiltinTypeEnum(): void
    {
        // Test with direct enum value
        $typeWithEnum = new BuiltinType(BuiltinTypeEnum::INT);
        $this->assertSame('int', $typeWithEnum->getName());
        $this->assertSame(BuiltinTypeEnum::INT, $typeWithEnum->getType());
        
        // Ensure it works the same as when using a string
        $typeWithString = new BuiltinType('int');
        $this->assertSame($typeWithEnum->getName(), $typeWithString->getName());
        $this->assertSame($typeWithEnum->getType(), $typeWithString->getType());
        
        // Verify behavior is consistent
        $this->assertTrue($typeWithEnum->isValidValue(42));
        $this->assertFalse($typeWithEnum->isValidValue('42'));
        $this->assertSame(42, $typeWithEnum->cast('42'));
    }
    
    public function testArrayCastThrowsExceptionForNonStringValues(): void
    {
        $type = new BuiltinType('array');
        
        // Strings and arrays can be cast
        $this->assertSame(['h', 'e', 'l', 'l', 'o'], $type->cast('hello'));
        $this->assertSame([1, 2, 3], $type->cast([1, 2, 3]));
        
        // Other types cannot be cast
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot cast value of type object to array');
        $type->cast(new \stdClass());
    }
    
    public static function validValuesProvider(): array
    {
        return [
            // typeName, value, expected
            ['int', 42, true],
            ['int', '42', false],
            ['int', 42.0, false],
            
            ['float', 42.5, true],
            ['float', 42, false],
            ['float', '42.5', false],
            
            ['bool', true, true],
            ['bool', false, true],
            ['bool', 1, false],
            ['bool', 0, false],
            ['bool', 'true', false],
            
            ['string', 'hello', true],
            ['string', '', true],
            ['string', 42, false],
            ['string', true, false],
            
            ['array', [], true],
            ['array', [1, 2, 3], true],
            ['array', ['a' => 'b'], true],
            ['array', new \stdClass(), false],
            ['array', 'not an array', false],
            
            ['object', new \stdClass(), true],
            ['object', (object)['a' => 'b'], true],
            ['object', [], false],
            ['object', 'not an object', false],
            
            ['null', null, true],
            ['null', false, false],
            ['null', '', false],
            ['null', 0, false],
            
            ['mixed', null, true],
            ['mixed', 42, true],
            ['mixed', 'hello', true],
            ['mixed', [], true],
            ['mixed', new \stdClass(), true],
        ];
    }
    
    public static function castValuesProvider(): array
    {
        return [
            // typeName, value, expected
            ['int', '42', 42],
            ['int', 42.5, 42],
            ['int', true, 1],
            ['int', false, 0],
            
            ['float', '42.5', 42.5],
            ['float', 42, 42.0],
            ['float', true, 1.0],
            ['float', false, 0.0],
            
            ['bool', 1, true],
            ['bool', 0, false],
            ['bool', '1', true],
            ['bool', '0', false],
            ['bool', '', false],
            
            ['string', 42, '42'],
            ['string', 42.5, '42.5'],
            ['string', true, '1'],
            ['string', false, ''],
            
            ['array', 'hello', ['h', 'e', 'l', 'l', 'o']],
            // Test case for casting object to array is removed because it throws an exception
            // ['array', new \stdClass(), []],
            
            ['null', 'anything', null],
            ['null', 42, null],
            ['null', true, null],
            
            ['mixed', 'hello', 'hello'],
            ['mixed', 42, 42],
            ['mixed', true, true],
        ];
    }
}