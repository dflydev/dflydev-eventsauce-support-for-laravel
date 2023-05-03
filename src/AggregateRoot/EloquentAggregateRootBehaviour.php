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

use Dflydev\EventSauce\Support\AggregateRoot\EventedAggregateRootBehaviour;
use EventSauce\EventSourcing\AggregateRoot;
use EventSauce\EventSourcing\AggregateRootId;
use Illuminate\Database\Eloquent\Model;

/**
 * @template T1 of AggregateRoot
 * @template T2 of AggregateRootId
 *
 * @property int $aggregate_root_version
 *
 * @method fill(array $attributes)
 * @method static \Illuminate\Database\Eloquent\Builder query()
 * @method static string aggregateRootIdColumnName()
 */
trait EloquentAggregateRootBehaviour
{
    /**
     * @use EventedAggregateRootBehaviour<T2>
     */
    use EventedAggregateRootBehaviour;

    public static function bootEloquentAggregateRootBehaviour(): void
    {
        static::creating(self::syncAggregateRootVersion(...));
        static::updating(self::syncAggregateRootVersion(...));
    }

    public static function findByAggregateRootId(AggregateRootId $aggregateRootId): EloquentAggregateRoot
    {
        /** @var EloquentAggregateRoot<T1,T2>|null $aggregateRoot */
        $aggregateRoot = static::query()
            ->where(self::aggregateRootIdColumnName(), '=', $aggregateRootId->toString())
            ->first();

        assert(!is_null($aggregateRoot), 'Aggregate Root not found');

        return $aggregateRoot;
    }

    /**
     * @phpstan-return T2
     */
    public function aggregateRootId(): AggregateRootId
    {
        return $this->{static::aggregateRootIdColumnName()};
    }

    public function persistAggregateRoot(): void
    {
        $this->save();
    }

    /**
     * @param EloquentAggregateRoot&Model $model
     */
    public static function syncAggregateRootVersion(EloquentAggregateRoot $model): void
    {
        $model->setAttribute('aggregate_root_version', $model->aggregateRootVersion());
    }

    public static function reconstituteAndPersistFromEvents(AggregateRootId $aggregateRootId, \Generator $events): static
    {
        $aggregateRoot = new self();

        $aggregateRoot->fill([
            static::aggregateRootIdColumnName() => $aggregateRootId,
        ]);

        /** @var object $event */
        foreach ($events as $event) {
            $aggregateRoot->apply($event);
        }

        $aggregateRootVersion = $events->getReturn();

        $aggregateRoot->aggregateRootVersion = (is_int($aggregateRootVersion) && $aggregateRootVersion >= 0)
            ? $aggregateRootVersion
            : 0;

        $aggregateRoot->persistAggregateRoot();

        return $aggregateRoot;
    }
}
