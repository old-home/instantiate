<?php

declare(strict_types=1);

namespace Graywings\Instantiate\Type;

interface Type {
    public function name(): string;
    public function match(mixed $value): bool;
}
