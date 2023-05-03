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

namespace Dflydev\EventSauce\SupportForLaravel\AggregateRoot;

use Dflydev\EventSauce\Support\AggregateRoot\EventedAggregateRoot;
use EventSauce\EventSourcing\AggregateRoot;
use EventSauce\EventSourcing\AggregateRootId;

/**
 * @template T1 of AggregateRoot
 * @template T2 of AggregateRootId
 *
 * @extends EventedAggregateRoot<T1,T2>
 */
interface EloquentAggregateRoot extends EventedAggregateRoot
{
    public static function aggregateRootIdColumnName(): string;

    /**
     * @phpstan-param T2 $aggregateRootId
     *
     * @return EloquentAggregateRoot<T1, T2>
     */
    public static function findByAggregateRootId(AggregateRootId $aggregateRootId): EloquentAggregateRoot;

    public function persistAggregateRoot(): void;
}
