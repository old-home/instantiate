<?php

declare(strict_types=1);

namespace Graywings\Instantiate\Type;

use Graywings\Instantiate\InstantiateException;

readonly class UserDefinedType implements Type {
    public function __construct(private(set) string $fqcn) {
        if (!class_exists($fqcn)) {
            throw new InstantiateException('Class ' . $fqcn . " doesn't exist.");
        }
    }

    public function match(mixed $value): bool
    {
        return $value instanceof $this->fqcn;
    }

    public function name(): string
    {
        return $this->fqcn;
    }
}
