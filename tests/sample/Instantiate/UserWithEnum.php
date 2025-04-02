<?php

declare(strict_types=1);

namespace Graywings\Instantiate\Tests\Sample\Instantiate;

class UserWithEnum
{
    public function __construct(
        private int $id,
        private string $name,
        private UserStatus $status,
        private ?string $email = null,
        private ?Address $address = null
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
    
    public function getStatus(): UserStatus
    {
        return $this->status;
    }
    
    public function getEmail(): ?string
    {
        return $this->email;
    }
    
    public function getAddress(): ?Address
    {
        return $this->address;
    }
}