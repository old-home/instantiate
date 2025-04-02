<?php

declare(strict_types=1);

namespace Graywings\Instantiate\Tests\Sample\PropertyNode;

class Address
{
    public string $street;
    public string $city;
    public string $zipCode;
    public ?string $state = null;
    public string $country;

    /**
     * Constructor with parameters for testing constructor-based property nodes
     */
    public function __construct(
        string $street,
        string $city,
        string $zipCode,
        ?string $state = null,
        string $country = 'Japan'
    ) {
        $this->street = $street;
        $this->city = $city;
        $this->zipCode = $zipCode;
        $this->state = $state;
        $this->country = $country;
    }
}
