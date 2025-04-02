<?php

declare(strict_types=1);

namespace Graywings\Instantiate\Tests\Unit\Instantiate;

use Graywings\Instantiate\Exception\InstantiateArgumentsException;
use Graywings\Instantiate\Exception\InstantiateException;
use Graywings\Instantiate\Instantiate;
use Graywings\Instantiate\PropertyNode\PropertyNode;
use Graywings\Instantiate\Tests\Sample\Instantiate\Address;
use Graywings\Instantiate\Tests\Sample\Instantiate\User;
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
class InstantiateTest extends TestCase
{
    public function testInstantiateFromArray(): void
    {
        $data = [
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 35,
            'active' => true,
            'roles' => ['user', 'admin']
        ];

        $user = Instantiate::array(User::class, $data);

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame(1, $user->getId());
        $this->assertSame('John Doe', $user->getName());
        $this->assertSame('john@example.com', $user->getEmail());
        $this->assertSame(35, $user->getAge());
        $this->assertTrue($user->isActive());
        $this->assertSame(['user', 'admin'], $user->getRoles());
        $this->assertNull($user->getAddress());
    }

    public function testInstantiateWithNestedObject(): void
    {
        $data = [
            'id' => 1,
            'name' => 'John Doe',
            'age' => 32,
            'active' => true,
            'roles' => ['user', 'admin'],
            'address' => [
                'street' => '123 Main St',
                'city' => 'New York',
                'zipCode' => '10001',
                'country' => 'Japan'
            ]
        ];

        $user = Instantiate::array(User::class, $data);

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame(1, $user->getId());
        $this->assertSame('John Doe', $user->getName());

        $address = $user->getAddress();
        $this->assertInstanceOf(Address::class, $address);
        $this->assertSame('123 Main St', $address->getStreet());
        $this->assertSame('New York', $address->getCity());
        $this->assertSame('10001', $address->getZipCode());
        $this->assertNull($address->getState());
        $this->assertSame('Japan', $address->getCountry());
    }

    public function testInstantiateFromStdClass(): void
    {
        $data = new \stdClass();
        $data->id = 1;
        $data->name = 'John Doe';
        $data->age = 35;
        $data->email = 'john@example.com';
        $data->active = true;
        $data->roles = ['user', 'admin'];

        $address = new \stdClass();
        $address->street = '123 Main St';
        $address->city = 'New York';
        $address->zipCode = '10001';
        $address->country = 'America';

        $data->address = $address;

        $user = Instantiate::stdClass(User::class, $data);

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame(1, $user->getId());
        $this->assertSame('John Doe', $user->getName());
        $this->assertSame('john@example.com', $user->getEmail());

        $userAddress = $user->getAddress();
        $this->assertInstanceOf(Address::class, $userAddress);
        $this->assertSame('123 Main St', $userAddress->getStreet());
    }

    public function testInstantiateFromJson(): void
    {
        $json = <<<JSON
        {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "age": 35,
            "active": true,
            "address": {
                "street": "123 Main St",
                "city": "New York",
                "zipCode": "10001",
                "state": "NY",
                "country": "America"
            },
            "roles": ["user", "admin"]
        }
        JSON;

        $user = Instantiate::json(User::class, $json);

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame(1, $user->getId());
        $this->assertSame('John Doe', $user->getName());
        $this->assertSame('john@example.com', $user->getEmail());

        $address = $user->getAddress();
        $this->assertInstanceOf(Address::class, $address);
        $this->assertSame('123 Main St', $address->getStreet());
        $this->assertSame('NY', $address->getState());

        $this->assertSame(['user', 'admin'], $user->getRoles());
    }

    public function testInstantiateThrowsExceptionForInvalidJson(): void
    {
        $invalidJson = '{invalid: json}';

        $this->expectException(InstantiateArgumentsException::class);
        $this->expectExceptionMessage('Invalid JSON:');

        Instantiate::json(User::class, $invalidJson);
    }

    public function testInstantiateThrowsExceptionForMissingRequiredParameter(): void
    {
        $data = [
            // Missing 'id' which is required
            'name' => 'John Doe'
        ];

        $this->expectException(InstantiateArgumentsException::class);
        $this->expectExceptionMessage("Required parameter 'id' is missing");

        Instantiate::array(User::class, $data);
    }

    public function testInstantiateThrowsExceptionForInvalidNullValue(): void
    {
        $data = [
            'id' => null, // Cannot be null
            'name' => 'John Doe'
        ];

        $this->expectException(InstantiateArgumentsException::class);
        $this->expectExceptionMessage("Parameter 'id' cannot be null");

        Instantiate::array(User::class, $data);
    }

    public function testInstantiateWithTypeConversion(): void
    {
        $data = [
            'id' => '42', // String instead of int
            'name' => 'John Doe',
            'age' => '35', // String instead of int
            'active' => 1, // Integer instead of boolean
            'roles' => ['user', 'admin']
        ];

        $user = Instantiate::array(User::class, $data);

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame(42, $user->getId()); // Converted to int
        $this->assertSame(35, $user->getAge()); // Converted to int
        $this->assertTrue($user->isActive()); // Converted to boolean
    }

    public function testInstantiateWithDefaultValues(): void
    {
        // Only provide minimal required parameters
        $data = [
            'id' => 1,
            'name' => 'John Doe',
            'age' => 30,
            'active' => true,
            'roles' => []
        ];

        $user = Instantiate::array(User::class, $data);

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame(1, $user->getId());
        $this->assertSame('John Doe', $user->getName());

        // These should use constructor default values
        $this->assertNull($user->getEmail()); // default is null
        $this->assertSame(30, $user->getAge()); // default is 30
        $this->assertTrue($user->isActive()); // default is true
        $this->assertNull($user->getAddress()); // default is null
        $this->assertSame([], $user->getRoles()); // default is []
    }

    public function testInstantiateRespectsDefaultValuesForNestedObjects(): void
    {
        $data = [
            'id' => 1,
            'name' => 'John Doe',
            'age' => 32,
            'active' => true,
            'roles' => ['user', 'admin'],
            'address' => [
                'street' => '123 Main St',
                'city' => 'New York',
                'zipCode' => '10001',
                'country' => 'Japan'
                // state and country are not provided, should use defaults
            ]
        ];

        $user = Instantiate::array(User::class, $data);

        $this->assertInstanceOf(User::class, $user);

        $address = $user->getAddress();
        $this->assertInstanceOf(Address::class, $address);
        $this->assertSame('123 Main St', $address->getStreet());
        $this->assertSame('New York', $address->getCity());
        $this->assertSame('10001', $address->getZipCode());
        $this->assertNull($address->getState()); // default is null
        $this->assertSame('Japan', $address->getCountry()); // default is 'Japan'
    }

    public function testInstantiateThrowsExceptionForNonExistentClass(): void
    {
        $this->expectException(InstantiateArgumentsException::class);
        $this->expectExceptionMessage("Class NonExistentClass does not exist");

        Instantiate::array('NonExistentClass', []);
    }

    public function testInstantiateNestedArrayOfObjects(): void
    {
        $data = [
            'id' => 1,
            'name' => 'John Doe',
            'addresses' => [
                [
                    'street' => '123 Main St',
                    'city' => 'New York',
                    'zipCode' => '10001',
                    'country' => 'Japan'
                ],
                [
                    'street' => '456 Elm St',
                    'city' => 'Boston',
                    'zipCode' => '02101',
                    'state' => 'MA',
                    'country' => 'England'
                ]
            ]
        ];

        // For this test, we need a different class with an array of Address objects
        $user = new class($data['id'], $data['name'], null, 30, true, null, [],
            array_map(fn($addrData) => Instantiate::array(Address::class, $addrData), $data['addresses'])
        ) {
            public function __construct(
                private int $id,
                private string $name,
                private ?string $email = null,
                private int $age = 30,
                private bool $active = true,
                private ?Address $address = null,
                private array $roles = [],
                private array $addresses = []
            ) {
            }

            public function getAddresses(): array
            {
                return $this->addresses;
            }
        };

        $addresses = $user->getAddresses();
        $this->assertCount(2, $addresses);
        $this->assertInstanceOf(Address::class, $addresses[0]);
        $this->assertInstanceOf(Address::class, $addresses[1]);
        $this->assertSame('123 Main St', $addresses[0]->getStreet());
        $this->assertSame('456 Elm St', $addresses[1]->getStreet());
        $this->assertSame('MA', $addresses[1]->getState());
    }
}
