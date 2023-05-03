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

namespace Dflydev\EventSauce\SupportForLaravel\Testing;

use Dflydev\EventSauce\Support\Testing\AggregateRootTestingBehavior;
use Dflydev\EventSauce\Support\Testing\GivenWhenThen\Scenario;
use Dflydev\EventSauce\SupportForLaravel\AggregateRoot\EloquentAggregateRoot;
use Dflydev\EventSauce\SupportForLaravel\AggregateRoot\EloquentAggregateRootRepository;
use EventSauce\EventSourcing\AggregateRoot;
use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\AggregateRootRepository;

/**
 * @template T1 of AggregateRoot
 * @template T2 of AggregateRootId
 */
trait EloquentAggregateRootTestingBehavior
{
    /**
     * @use AggregateRootTestingBehavior<T1,T2>
     */
    use AggregateRootTestingBehavior;

    /**
     * @return AggregateRootRepository<T1>
     */
    protected function eloquentAggregateRootRepository(): AggregateRootRepository
    {
        return new EloquentAggregateRootRepository(
            $this->aggregateRootType(),
            $this->transaction(),
            $this->messageRepository(),
            $this->messagePreparation()
        );
    }

    public function configureForEloquentAggregateRootType(string $aggregateRootType): void
    {
        self::setAggregateRootType($aggregateRootType);

        $this->scenarioConfiguration = $this->scenarioConfigurationForEloquent(...);
    }

    private function scenarioConfigurationForEloquent(Scenario $scenario): Scenario
    {
        /** @var EloquentAggregateRoot $aggregateRootType */
        $aggregateRootType = self::aggregateRootType();

        assert(in_array(EloquentAggregateRoot::class, class_implements($aggregateRootType)));

        $generator = function (array $events) {
            yield from $events;

            return count($events);
        };

        return $scenario->visitEvents(function (...$events) use ($aggregateRootType, $generator) {
            if (count($events) === 0) {
                return;
            }

            // We do not use the aggregate root repository here, we just need to persist
            // it based on the events that were recorded.
            $aggregateRootType::reconstituteAndPersistFromEvents($this->aggregateRootId(), $generator($events));
        });
    }
}
