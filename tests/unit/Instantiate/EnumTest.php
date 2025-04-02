<?php

declare(strict_types=1);

namespace Graywings\Instantiate\Tests\Unit\Instantiate;

use Graywings\Instantiate\Exception\InstantiateArgumentsException;
use Graywings\Instantiate\Exception\InstantiateException;
use Graywings\Instantiate\Instantiate;
use Graywings\Instantiate\PropertyNode\PropertyNode;
use Graywings\Instantiate\Tests\Sample\Instantiate\UserWithEnum;
use Graywings\Instantiate\Tests\Sample\Instantiate\UserStatus;
use Graywings\Instantiate\Type\BuiltinType;
use Graywings\Instantiate\Type\BuiltinTypeEnum;
use Graywings\Instantiate\Type\UserDefinedType;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Instantiate::class)]
#[CoversClass(InstantiateException::class)]
#[CoversClass(InstantiateArgumentsException::class)]
#[CoversClass(PropertyNode::class)]
#[CoversClass(BuiltinType::class)]
#[CoversClass(BuiltinTypeEnum::class)]
#[CoversClass(UserDefinedType::class)]
class EnumTest extends TestCase
{
    public function testInstantiateWithEnumFromString(): void
    {
        $data = [
            'id' => 1,
            'name' => 'John Doe',
            'status' => 'active' // String value for enum
        ];

        $user = Instantiate::array(UserWithEnum::class, $data);

        $this->assertInstanceOf(UserWithEnum::class, $user);
        $this->assertSame(1, $user->getId());
        $this->assertSame('John Doe', $user->getName());

        // Verify the enum was correctly converted
        $this->assertInstanceOf(UserStatus::class, $user->getStatus());
        $this->assertSame(UserStatus::ACTIVE, $user->getStatus());
        $this->assertSame('active', $user->getStatus()->value);
    }

    public function testInstantiateWithEnumDirectValue(): void
    {
        // Test with actual enum value
        $data = [
            'id' => 2,
            'name' => 'Jane Smith',
            'status' => UserStatus::INACTIVE // Direct enum instance
        ];

        $user = Instantiate::array(UserWithEnum::class, $data);

        $this->assertInstanceOf(UserWithEnum::class, $user);
        $this->assertInstanceOf(UserStatus::class, $user->getStatus());
        $this->assertSame(UserStatus::INACTIVE, $user->getStatus());
    }

    public function testInstantiateEnumFromJson(): void
    {
        $json = <<<JSON
        {
            "id": 1,
            "name": "John Doe",
            "status": "pending",
            "address": {
                "street": "123 Main St",
                "city": "New York",
                "zipCode": "10001",
                "country": "America"
            }
        }
        JSON;

        $user = Instantiate::json(UserWithEnum::class, $json);

        $this->assertInstanceOf(UserWithEnum::class, $user);
        $this->assertSame(1, $user->getId());
        $this->assertSame('John Doe', $user->getName());

        // Verify the enum was correctly converted from JSON
        $this->assertInstanceOf(UserStatus::class, $user->getStatus());
        $this->assertSame(UserStatus::PENDING, $user->getStatus());
        $this->assertSame('pending', $user->getStatus()->value);
    }

    public function testInstantiateThrowsExceptionForInvalidEnumValue(): void
    {
        $data = [
            'id' => 1,
            'name' => 'John Doe',
            'status' => 'unknown_status' // Invalid enum value
        ];

        $this->expectException(InstantiateArgumentsException::class);
        $this->expectExceptionMessage("Invalid enum value 'unknown_status' for parameter 'status': \"unknown_status\" is not a valid backing value for enum Graywings\Instantiate\Tests\Sample\Instantiate\UserStatus");

        Instantiate::array(UserWithEnum::class, $data);
    }
}
