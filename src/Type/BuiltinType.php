<?php

declare(strict_types=1);

namespace Graywings\Instantiate\Type;

enum BuiltinType: string implements Type {
    case NULL = 'null';
    case STRING = 'string';
    case INT = 'int';
    case FLOAT = 'float';
    case BOOL = 'bool';
    case ARRAY = 'array';
    case MIXED = 'mixed';

    public function match(mixed $value): bool
    {
        return gettype($value) === $this->value;
    }

    public function name(): string
    {
        return $this->value;
    }
}
