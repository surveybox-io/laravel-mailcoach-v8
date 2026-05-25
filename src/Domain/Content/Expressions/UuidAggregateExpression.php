<?php

namespace Spatie\Mailcoach\Domain\Content\Expressions;

use Exception;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Grammar;
use Tpetry\QueryExpressions\Concerns\IdentifiesDriver;
use Tpetry\QueryExpressions\Concerns\StringizeExpression;

class UuidAggregateExpression implements Expression
{
    use IdentifiesDriver;
    use StringizeExpression;

    public function __construct(
        private readonly string $column,
    ) {}

    public function getValue(Grammar $grammar): string
    {
        return match ($this->identify($grammar)) {
            'mariadb', 'mysql', 'sqlite' => "group_concat({$grammar->wrap($this->column)})",
            'pgsql' => "string_agg({$grammar->wrap($this->column)}::text, ',')",
            default => throw new Exception('Unsupported database driver'),
        };
    }
}
