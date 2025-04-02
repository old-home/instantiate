<?php

declare(strict_types=1);

namespace Graywings\Instantiate\Tests\Sample\Instantiate;

readonly class User
{
    public function __construct(
        private int $id,
        private string $name,
        private ?string $email = null,
        private int $age = 30,
        private bool $active = true,
        private ?Address $address = null,
        private array $roles = []
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getAge(): int
    {
        return $this->age;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getAddress(): ?Address
    {
        return $this->address;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }
}
