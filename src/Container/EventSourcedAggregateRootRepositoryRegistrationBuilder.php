<?php

declare(strict_types=1);

namespace Dflydev\EventSauce\SupportForLaravel\Container;

use Dflydev\EventSauce\Support\AggregateRoot\EventSourcedAggregateRoot;
use Dflydev\EventSauce\Support\AggregateRoot\EventSourcedAggregateRootRepository;
use Dflydev\EventSauce\Support\Transaction\Transaction;
use Dflydev\EventSauce\SupportForLaravel\EventSauceConfiguration;
use EventSauce\EventSourcing\AggregateRootRepository;
use EventSauce\EventSourcing\MessageDispatcher;
use EventSauce\EventSourcing\MessageRepository;
use EventSauce\EventSourcing\SynchronousMessageDispatcher;
use EventSauce\MessageOutbox\OutboxRepository;
use Illuminate\Contracts\Foundation\Application;

final class EventSourcedAggregateRootRepositoryRegistrationBuilder
{
    /**
     * @var MessageRepository|class-string<MessageRepository>|null
     */
    private MessageRepository|string|null $messageRepository;

    /**
     * @var OutboxRepository|class-string<OutboxRepository>|null
     */
    private OutboxRepository|string|null $outboxRepository;

    /**
     * @var MessageDispatcher|class-string<MessageDispatcher>|null
     */
    private MessageDispatcher|string|null $transactionalMessageDispatcher;

    /**
     * @var MessageDispatcher|class-string<MessageDispatcher>|null
     */
    private MessageDispatcher|string|null $synchronousMessageDispatcher = null;
    private bool $withoutOutboxRepository = false;
    private bool $withoutTransactionalMessageDispatcher = false;
    private bool $withoutSynchronousMessageDispatcher = false;

    private function __construct()
    {
    }

    public static function new(): self
    {
        return new self();
    }

    /**
     * @param class-string<MessageRepository>|MessageRepository $messageRepository
     */
    public function withMessageRepository(string|MessageRepository $messageRepository): self
    {
        $instance = clone $this;
        $instance->messageRepository = $messageRepository;

        return $instance;
    }

    /**
     * @param class-string<OutboxRepository>|OutboxRepository|null $outboxRepository
     */
    public function withOutboxRepository(null|string|OutboxRepository $outboxRepository = null): self
    {
        $instance = clone $this;
        $instance->outboxRepository = $outboxRepository ?? OutboxRepository::class;

        return $instance;
    }

    /**
     * @param class-string<MessageDispatcher>|MessageDispatcher|null $transactionalMessageDispatcher
     */
    public function withTransactionalMessageDispatcher(null|string|MessageDispatcher $transactionalMessageDispatcher = null): self
    {
        $instance = clone $this;
        $instance->transactionalMessageDispatcher = $transactionalMessageDispatcher ?? SynchronousMessageDispatcher::class;

        return $instance;
    }

    /**
     * @param class-string<MessageDispatcher>|MessageDispatcher|null $synchronousMessageDispatcher
     */
    public function withSynchronousMessageDispatcher(null|string|MessageDispatcher $synchronousMessageDispatcher = null): self
    {
        $instance = clone $this;
        $instance->synchronousMessageDispatcher = $synchronousMessageDispatcher ?? SynchronousMessageDispatcher::class;

        return $instance;
    }

    public function withoutOutboxRepository(): self
    {
        $instance = clone $this;
        $instance->withoutOutboxRepository = true;

        return $instance;
    }

    public function withoutTransactionalMessageDispatcher(): self
    {
        $instance = clone $this;
        $instance->withoutTransactionalMessageDispatcher = true;

        return $instance;
    }

    public function withoutSynchronousMessageDispatcher(): self
    {
        $instance = clone $this;
        $instance->withoutSynchronousMessageDispatcher = true;

        return $instance;
    }

    /**
     * @param class-string<EventSourcedAggregateRoot> $aggregateRootClassName
     */
    public function build(
        string $aggregateRootClassName,
        string $aggregateRootRepositoryInterfaceName,
        string $aggregateRootRepositoryImplementationClassName,
        ?Application $application = null
    ): void {
        $application = $application ?? app();

        $innerInstanceName = $aggregateRootClassName.'.EventSourcedAggregateRootRepository';

        $application->singleton($aggregateRootRepositoryInterfaceName, $aggregateRootRepositoryImplementationClassName);

        $application->singleton($innerInstanceName, EventSourcedAggregateRootRepository::class);

        $application->when($aggregateRootRepositoryImplementationClassName)
            ->needs(AggregateRootRepository::class)
            ->give($innerInstanceName);

        $application->singleton($innerInstanceName, function (Application $app) use ($aggregateRootClassName) {
            /** @var Transaction $transaction */
            $transaction = $app->get(Transaction::class);

            /** @var MessageRepository|class-string<MessageRepository>|null $messageRepository */
            $messageRepository = $this->messageRepository ?? $app->get(MessageRepository::class);

            if (is_string($messageRepository)) {
                /** @var MessageRepository|null $messageRepository */
                $messageRepository = $app->get($messageRepository);
            }

            assert(!is_null($messageRepository), 'Message repository is null.');

            $args = [
                $aggregateRootClassName,
                $transaction,
                $messageRepository,
            ];

            if (!$this->withoutOutboxRepository) {
                if (isset($this->outboxRepository)) {
                    $args['outboxRepository'] = is_object($this->outboxRepository) ? $this->outboxRepository : $app->get($this->outboxRepository);
                } elseif ($app->bound(OutboxRepository::class)) {
                    $args['transactionalMessageDispatcher'] = $app->get(OutboxRepository::class);
                }
            }

            if (!$this->withoutTransactionalMessageDispatcher) {
                if (isset($this->transactionalMessageDispatcher)) {
                    $args['transactionalMessageDispatcher'] = is_object($this->transactionalMessageDispatcher) ? $this->transactionalMessageDispatcher : $app->get($this->transactionalMessageDispatcher);
                } elseif ($app->bound(EventSauceConfiguration::transactionalMessageDispatcherServiceName())) {
                    $args['transactionalMessageDispatcher'] = $app->get(EventSauceConfiguration::transactionalMessageDispatcherServiceName());
                }
            }

            if (!$this->withoutSynchronousMessageDispatcher) {
                if (isset($this->synchronousMessageDispatcher)) {
                    $args['synchronousMessageDispatcher'] = is_object($this->synchronousMessageDispatcher) ? $this->synchronousMessageDispatcher : $app->get($this->synchronousMessageDispatcher);
                } elseif ($app->bound(EventSauceConfiguration::synchronousMessageDispatcherServiceName())) {
                    $args['synchronousMessageDispatcher'] = $app->get(EventSauceConfiguration::synchronousMessageDispatcherServiceName());
                }
            }

            return new EventSourcedAggregateRootRepository(...$args);
        });
    }
}
