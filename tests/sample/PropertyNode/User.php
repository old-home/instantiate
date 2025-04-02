<?php

declare(strict_types=1);

namespace Graywings\Instantiate\Tests\Sample\PropertyNode;

use Graywings\Instantiate\PropertyNode\ArrayOf;

class User
{
    public int $id;
    public string $name;
    public ?string $email = null;
    public int $age;
    public bool $active = true;
    public Address $address;
    #[ArrayOf('string')]
    public array $roles = [];
    
    #[ArrayOf(Address::class)]
    public array $additionalAddresses = [];
    
    /**
     * Constructor with parameters for testing constructor-based property nodes
     * Note that the parameter order and types are different from the properties
     * to test that the property nodes are created correctly from parameters
     */
    public function __construct(
        string $name,
        int $id, 
        Address $address,
        int $age = 30,
        ?string $email = null,
        bool $active = true,
        #[ArrayOf('string')]
        array $roles = [],
        #[ArrayOf(Address::class)]
        array $additionalAddresses = []
    ) {
        $this->name = $name;
        $this->id = $id;
        $this->address = $address;
        $this->age = $age;
        $this->email = $email;
        $this->active = $active;
        $this->roles = $roles;
        $this->additionalAddresses = $additionalAddresses;
    }
}