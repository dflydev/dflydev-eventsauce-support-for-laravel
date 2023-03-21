<?php

declare(strict_types=1);

namespace Dflydev\EventSauce\SupportForLaravel\Eloquent;

use EventSauce\EventSourcing\AggregateRootId;
use Illuminate\Database\Eloquent\Model;

trait CastIdentityToString
{
    public function set(Model $model, string $key, mixed $value, array $attributes)
    {
        if (is_null($value) || is_string($value)) {
            return $value;
        }

        assert(is_object($value), 'Expected $value to be an object if it is not a string or null.');

        assert($value instanceof AggregateRootId, 'Expected '.$value::class.' to be an instance of '.AggregateRootId::class);

        return $value->toString();
    }
}
