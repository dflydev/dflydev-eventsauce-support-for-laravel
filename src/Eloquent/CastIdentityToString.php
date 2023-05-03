<?php

declare(strict_types=1);

/**
 * Copyright (c) 2023 Dragonfly Development Inc
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/dflydev/dflydev-eventsauce-support-for-laravel
 */

namespace Dflydev\EventSauce\SupportForLaravel\Eloquent;

use EventSauce\EventSourcing\AggregateRootId;
use Illuminate\Database\Eloquent\Model;

trait CastIdentityToString
{
    /**
     * @param string|null $value
     */
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
