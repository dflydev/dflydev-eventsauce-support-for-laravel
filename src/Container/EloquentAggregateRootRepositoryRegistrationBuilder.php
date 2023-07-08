<?php

declare(strict_types=1);

namespace Dflydev\EventSauce\SupportForLaravel\Container;

use Dflydev\EventSauce\Support\MessagePreparation\DefaultMessagePreparation;
use Dflydev\EventSauce\Support\Transaction\Transaction;
use Dflydev\EventSauce\SupportForLaravel\AggregateRoot\EloquentAggregateRoot;
use Dflydev\EventSauce\SupportForLaravel\AggregateRoot\EloquentAggregateRootRepository;
use Dflydev\EventSauce\SupportForLaravel\EventSauceConfiguration;
use EventSauce\EventSourcing\AggregateRootRepository;
use EventSauce\EventSourcing\MessageDecorator;
use EventSauce\EventSourcing\MessageDispatcher;
use EventSauce\EventSourcing\MessageRepository;
use EventSauce\EventSourcing\SynchronousMessageDispatcher;
use EventSauce\MessageOutbox\OutboxRepository;
use Illuminate\Contracts\Foundation\Application;

final class EloquentAggregateRootRepositoryRegistrationBuilder
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
     * @var MessageDecorator|class-string<MessageDecorator>|null
     */
    private MessageDecorator|string|null $messageDecorator;

    /**
     * @var MessageDispatcher|class-string<MessageDispatcher>|null
     */
    private MessageDispatcher|string|null $transactionalMessageDispatcher;

    /**
     * @var MessageDispatcher|class-string<MessageDispatcher>|null
     */
    private MessageDispatcher|string|null $synchronousMessageDispatcher = null;
    private bool $withoutMessageRepository = false;
    private bool $withoutOutboxRepository = false;
    private bool $withoutTransactionalMessageDispatcher = false;
    private bool $withoutSynchronousMessageDispatcher = false;
    private bool $withoutMessageDecorator = false;

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
     * @param class-string<MessageDecorator>|MessageDecorator|null $messageDecorator
     */
    public function withMessageDecorator(null|string|MessageDecorator $messageDecorator = null): self
    {
        $instance = clone $this;
        $instance->messageDecorator = $messageDecorator ?? MessageDecorator::class;

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

    public function withoutMessageRepository(): self
    {
        $instance = clone $this;
        $instance->withoutMessageRepository = true;

        return $instance;
    }

    public function withoutOutboxRepository(): self
    {
        $instance = clone $this;
        $instance->withoutOutboxRepository = true;

        return $instance;
    }

    public function withoutMessageDecorator(): self
    {
        $instance = clone $this;
        $instance->withoutMessageDecorator = true;

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
     * @param class-string<EloquentAggregateRoot> $aggregateRootClassName
     */
    public function build(
        string $aggregateRootClassName,
        string $aggregateRootRepositoryInterfaceName,
        string $aggregateRootRepositoryImplementationClassName,
        ?Application $application = null
    ): void {
        $application = $application ?? app();

        $innerInstanceName = EventSauceConfiguration::eventSauceRepositoryServiceName($aggregateRootClassName);

        $application->singleton($aggregateRootRepositoryInterfaceName, $aggregateRootRepositoryImplementationClassName);

        $application->singleton($innerInstanceName, EloquentAggregateRootRepository::class);

        $application->when($aggregateRootRepositoryImplementationClassName)
            ->needs(AggregateRootRepository::class)
            ->give($innerInstanceName);

        $application->singleton($innerInstanceName, function (Application $app) use ($aggregateRootClassName) {
            /** @var Transaction $transaction */
            $transaction = $app->get(Transaction::class);

            $args = [
                $aggregateRootClassName,
                $transaction,
            ];

            if (!$this->withoutMessageRepository) {
                if (isset($this->messageRepository)) {
                    $args['messageRepository'] = is_object($this->messageRepository) ? $this->messageRepository : $app->get($this->messageRepository);
                } elseif ($app->bound(MessageRepository::class)) {
                    $args['messageRepository'] = $app->get(MessageRepository::class);
                }
            }

            if (!$this->withoutOutboxRepository) {
                if (isset($this->outboxRepository)) {
                    $args['outboxRepository'] = is_object($this->outboxRepository) ? $this->outboxRepository : $app->get($this->outboxRepository);
                } elseif ($app->bound(OutboxRepository::class)) {
                    $args['outboxRepository'] = $app->get(OutboxRepository::class);
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

            /** @var array{'messageDecorator'?: MessageDecorator|null} $messagePreparationArgs */
            $messagePreparationArgs = [];

            if (!$this->withoutMessageDecorator) {
                if (isset($this->messageDecorator)) {
                    $messagePreparationArgs['messageDecorator'] = is_object($this->messageDecorator) ? $this->messageDecorator : $app->get($this->messageDecorator);
                } elseif ($app->bound(MessageDecorator::class)) {
                    $messagePreparationArgs['messageDecorator'] = $app->get(MessageDecorator::class);
                }
            }

            if (count($messagePreparationArgs)) {
                $args['messagePreparation'] = new DefaultMessagePreparation(...$messagePreparationArgs);
            }

            return new EloquentAggregateRootRepository(...$args);
        });
    }
}
