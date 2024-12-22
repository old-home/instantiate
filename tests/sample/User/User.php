<?php

declare(strict_types=1);

namespace Graywings\Instantiate\Tests\Sample\User;

readonly class User
{
    public function __construct(
        private(set) UserId $id,
        private(set) Email $email,
    ) {}
}
