<?php

declare(strict_types=1);

namespace Graywings\Instantiate\Tests\Unit\Type;

use Graywings\Instantiate\Type\UserDefinedType;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UserDefinedType::class)]
class UserDefinedTypeTest extends TestCase
{
    public function testConstructorThrowsExceptionForNonExistentClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Class or interface 'NonExistentClass' does not exist");
        
        new UserDefinedType('NonExistentClass');
    }
    
    public function testGetName(): void
    {
        $type = new UserDefinedType(\stdClass::class);
        $this->assertSame(\stdClass::class, $type->getName());
    }
    
    public function testIsValidValue(): void
    {
        $type = new UserDefinedType(\stdClass::class);
        
        $this->assertTrue($type->isValidValue(new \stdClass()));
        $this->assertFalse($type->isValidValue('not an object'));
        $this->assertFalse($type->isValidValue([]));
        $this->assertFalse($type->isValidValue(new \Exception())); // Different class
        
        // Interface test
        $interfaceType = new UserDefinedType(\Traversable::class);
        $this->assertTrue($interfaceType->isValidValue(new \ArrayIterator()));
        $this->assertFalse($interfaceType->isValidValue(new \stdClass()));
    }
    
    public function testCastReturnsValueIfAlreadyCorrectType(): void
    {
        $obj = new \stdClass();
        $type = new UserDefinedType(\stdClass::class);
        
        $result = $type->cast($obj);
        $this->assertSame($obj, $result);
    }
    
    public function testCastThrowsExceptionForInvalidValue(): void
    {
        $type = new UserDefinedType(\stdClass::class);
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot cast value of type string to stdClass');
        
        $type->cast('not an object');
    }
}