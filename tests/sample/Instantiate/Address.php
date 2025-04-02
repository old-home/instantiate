<?php

declare(strict_types=1);

namespace Graywings\Instantiate\Tests\Sample\Instantiate;

readonly class Address
{
    public function __construct(
        private string $street,
        private string $city,
        private string $zipCode,
        private ?string $state = null,
        private string $country = 'Japan'
    ) {
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getZipCode(): string
    {
        return $this->zipCode;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function getCountry(): string
    {
        return $this->country;
    }
}
