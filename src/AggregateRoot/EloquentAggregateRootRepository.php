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

use Dflydev\EventSauce\Support\MessagePreparation\DefaultMessagePreparation;
use Dflydev\EventSauce\Support\MessagePreparation\MessagePreparation;
use Dflydev\EventSauce\Support\Transaction\Transaction;
use EventSauce\EventSourcing\AggregateRoot;
use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\AggregateRootRepository;
use EventSauce\EventSourcing\MessageDispatcher;
use EventSauce\EventSourcing\MessageRepository;
use EventSauce\EventSourcing\UnableToPersistMessages;
use EventSauce\MessageOutbox\OutboxRepository;
use Throwable;

use function assert;
use function count;

/**
 * @template T1 of AggregateRoot
 * @template T2 of AggregateRootId
 *
 * @implements AggregateRootRepository<T1>
 */
final readonly class EloquentAggregateRootRepository implements AggregateRootRepository
{
    private MessageRepository|null $messageRepository;
    private MessagePreparation $messagePreparation;

    /**
     * @param class-string<EloquentAggregateRoot<T1, T2>> $aggregateRootClassName
     */
    public function __construct(
        private string $aggregateRootClassName,
        private Transaction $transaction,
        ?MessageRepository $messageRepository = null,
        ?MessagePreparation $messagePreparation = null,
        private ?MessageDispatcher $transactionalMessageDispatcher = null,
        private ?MessageDispatcher $synchronousMessageDispatcher = null,
        private ?OutboxRepository $outboxRepository = null,
    ) {
        $this->messageRepository = $messageRepository;
        $this->messagePreparation = $messagePreparation ?? new DefaultMessagePreparation();
    }

    /**
     * @phpstan-param T2 $aggregateRootId
     *
     * @return EloquentAggregateRoot<T1,T2>
     */
    public function retrieve(AggregateRootId $aggregateRootId): EloquentAggregateRoot
    {
        $aggregateRootClassName = $this->aggregateRootClassName;

        return $aggregateRootClassName::findByAggregateRootId($aggregateRootId);
    }

    public function persist(object $aggregateRoot): void
    {
        assert($aggregateRoot instanceof EloquentAggregateRoot, 'Expected $aggregateRoot to be an instance of '.AggregateRoot::class);

        $messages = $this->messagePreparation->prepareMessages(
            $this->aggregateRootClassName,
            $aggregateRoot->aggregateRootId(),
            $aggregateRoot->aggregateRootVersion(),
            ...$aggregateRoot->releaseEvents()
        );

        if (count($messages) === 0) {
            return;
        }

        try {
            $this->transaction->begin();

            try {
                $aggregateRoot->persistAggregateRoot();

                $this->messageRepository?->persist(...$messages);
                $this->outboxRepository?->persist(...$messages);
                $this->transactionalMessageDispatcher?->dispatch(...$messages);

                $this->transaction->commit();
            } catch (Throwable $exception) {
                $this->transaction->rollBack();
                throw $exception;
            }
        } catch (Throwable $exception) {
            throw UnableToPersistMessages::dueTo('', $exception);
        }

        $this->synchronousMessageDispatcher?->dispatch(...$messages);
    }

    public function persistEvents(AggregateRootId $aggregateRootId, int $aggregateRootVersion, object ...$events): void
    {
        // TODO: Implement persistEvents() method.
    }
}
