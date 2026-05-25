<?php

namespace Spatie\Mailcoach\Domain\Campaign\Expressions;

use Exception;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Grammar;
use Tpetry\QueryExpressions\Concerns\IdentifiesDriver;
use Tpetry\QueryExpressions\Concerns\StringizeExpression;

class CampaignSortingDateExpression implements Expression
{
    use IdentifiesDriver;
    use StringizeExpression;

    public function __construct(
        private readonly string $table,
    ) {}

    public function getValue(Grammar $grammar): string
    {
        $draftTimestamp = 32472140400;

        $draftDate = match ($this->identify($grammar)) {
            'mariadb', 'mysql' => "CONCAT({$draftTimestamp},{$this->stringize($grammar, "{$this->table}.id")})",
            'pgsql' => "to_timestamp({$draftTimestamp} + {$this->stringize($grammar, "{$this->table}.id")})",
            'sqlite' => "datetime({$draftTimestamp} + {$this->stringize($grammar, "{$this->table}.id")}, 'unixepoch')",
            default => throw new Exception('Unsupported database driver'),
        };

        return "
            CASE
                WHEN {$grammar->wrap("{$this->table}.status")} = 'draft' AND {$grammar->wrap("{$this->table}.scheduled_at")} IS NULL THEN {$draftDate}
                WHEN {$grammar->wrap("{$this->table}.scheduled_at")} IS NOT NULL THEN {$grammar->wrap("{$this->table}.scheduled_at")}
                WHEN {$grammar->wrap("{$this->table}.sent_at")} IS NOT NULL THEN {$grammar->wrap("{$this->table}.sent_at")}
                ELSE {$grammar->wrap("{$this->table}.updated_at")}
            END
        ";
    }
}
