<?php

declare(strict_types=1);

namespace Dflydev\EventSauce\SupportForLaravel;

use Dflydev\EventSauce\Support\LazyMessageConsumption\LazyMessageConsumer;
use Dflydev\EventSauce\Support\MessagePreparation\MessagePreparation;
use Dflydev\EventSauce\Support\Transaction\Transaction;
use Dflydev\EventSauce\SupportForLaravel\Container\EloquentAggregateRootRepositoryRegistrationBuilder;
use Dflydev\EventSauce\SupportForLaravel\Container\EventSourcedAggregateRootRepositoryRegistrationBuilder;
use EventSauce\EventSourcing\ClassNameInflector;
use EventSauce\EventSourcing\DefaultHeadersDecorator;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageConsumer;
use EventSauce\EventSourcing\MessageDecorator;
use EventSauce\EventSourcing\MessageDecoratorChain;
use EventSauce\EventSourcing\MessageDispatcher;
use EventSauce\EventSourcing\MessageRepository;
use EventSauce\EventSourcing\Serialization\MessageSerializer;
use EventSauce\EventSourcing\Serialization\PayloadSerializer;
use EventSauce\EventSourcing\SynchronousMessageDispatcher;
use EventSauce\MessageOutbox\IlluminateOutbox\IlluminateOutboxRepository;
use EventSauce\MessageOutbox\IlluminateOutbox\IlluminateTransactionalMessageRepository;
use EventSauce\MessageOutbox\OutboxRelay;
use EventSauce\MessageOutbox\OutboxRepository;
use EventSauce\MessageRepository\IlluminateMessageRepository\IlluminateUuidV4MessageRepository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Foundation\Application;
use LogicException;

final class EventSauceConfiguration
{
    public static function synchronousMessageDispatcherServiceName(): string
    {
        return MessageDispatcher::class.'.synchronous';
    }

    public static function asynchronousMessageDispatcherServiceName(): string
    {
        return MessageDispatcher::class.'.asynchronous';
    }

    public static function transactionalMessageDispatcherServiceName(): string
    {
        return MessageDispatcher::class.'.transactional';
    }

    public static function consumesMessagesSynchronouslyTagName(): string
    {
        return MessageConsumer::class.'.synchronous';
    }

    public static function consumesMessagesAsynchronouslyTagName(): string
    {
        return MessageConsumer::class.'.asynchronous';
    }

    public static function consumesMessagesTransactionallyTagName(): string
    {
        return MessageConsumer::class.'.transactional';
    }

    public static function registerMessageConsumer(string $tag, string|array $messageConsumerClassName, ?bool $lazy = true, ?Application $application = null): void
    {
        $application = $application ?? app();

        $messageConsumerClassNames = is_array($messageConsumerClassName) ? $messageConsumerClassName : [$messageConsumerClassName];

        foreach ($messageConsumerClassNames as $messageConsumerClassName) {
            $application->singleton($messageConsumerClassName);

            if ($lazy) {
                $lazyServiceName = $messageConsumerClassName.'.madeLazy';
                $application->singleton($lazyServiceName, fn (Container $container) => new LazyMessageConsumer($container, $messageConsumerClassName));
                $application->tag($lazyServiceName, $tag);
            } else {
                $application->tag($messageConsumerClassName, $tag);
            }
        }
    }

    public static function registerNonLazySynchronousMessageConsumer(string|array $messageConsumerClassName, ?Application $application = null): void
    {
        self::registerMessageConsumer(
            self::consumesMessagesSynchronouslyTagName(),
            $messageConsumerClassName,
            lazy: false,
            application: $application
        );
    }

    public static function registerSynchronousMessageConsumer(string|array $messageConsumerClassName, ?Application $application = null): void
    {
        self::registerMessageConsumer(
            self::consumesMessagesSynchronouslyTagName(),
            $messageConsumerClassName,
            lazy: true,
            application: $application
        );
    }

    public static function registerNonLazyAsynchronousMessageConsumer(string|array $messageConsumerClassName, ?Application $application = null): void
    {
        self::registerMessageConsumer(
            self::consumesMessagesAsynchronouslyTagName(),
            $messageConsumerClassName,
            lazy: false,
            application: $application
        );
    }

    public static function registerAsynchronousMessageConsumer(string|array $messageConsumerClassName, ?Application $application = null): void
    {
        self::registerMessageConsumer(
            self::consumesMessagesAsynchronouslyTagName(),
            $messageConsumerClassName,
            lazy: true,
            application: $application
        );
    }

    public static function registerNonLazyTransactionalMessageConsumer(string|array $messageConsumerClassName, ?Application $application = null): void
    {
        self::registerMessageConsumer(
            self::consumesMessagesTransactionallyTagName(),
            $messageConsumerClassName,
            lazy: false,
            application: $application
        );
    }

    public static function registerTransactionalMessageConsumer(string|array $messageConsumerClassName, ?Application $application = null): void
    {
        self::registerMessageConsumer(
            self::consumesMessagesTransactionallyTagName(),
            $messageConsumerClassName,
            lazy: true,
            application: $application
        );
    }

    public static function registerSynchronousMessageDispatcher(?Application $application = null): void
    {
        $application = $application ?? app();

        $application->singleton(self::synchronousMessageDispatcherServiceName(), function () use ($application) {
            /** @var MessageConsumer[] $messageConsumers */
            $messageConsumers = $application->tagged(self::consumesMessagesSynchronouslyTagName());

            return new SynchronousMessageDispatcher(...$messageConsumers);
        });
    }

    public static function registerTransactionalMessageDispatcher(?Application $application = null): void
    {
        $application = $application ?? app();

        $application->singleton(self::transactionalMessageDispatcherServiceName(), function () use ($application) {
            /** @var MessageConsumer[] $messageConsumers */
            $messageConsumers = $application->tagged(self::consumesMessagesTransactionallyTagName());

            return new SynchronousMessageDispatcher(...$messageConsumers);
        });
    }

    public static function registerAsynchronousMessageDispatcher(?Application $application = null): void
    {
        $application = $application ?? app();

        $application->singleton(OutboxRelay::class);

        $application
            ->when(OutboxRelay::class)
            ->needs(MessageConsumer::class)
            ->give(function (Container $application) {
                /** @var MessageDispatcher $asynchronousMessageDispatcher */
                $asynchronousMessageDispatcher = $application->get(self::asynchronousMessageDispatcherServiceName());

                return new class($asynchronousMessageDispatcher) implements MessageConsumer {
                    public function __construct(private readonly MessageDispatcher $messageDispatcher)
                    {
                    }

                    public function handle(Message $message): void
                    {
                        $this->messageDispatcher->dispatch($message);
                    }
                };
            });

        $application->singleton(self::asynchronousMessageDispatcherServiceName(), function () use ($application) {
            /** @var MessageConsumer[] $messageConsumers */
            $messageConsumers = $application->tagged(self::consumesMessagesAsynchronouslyTagName());

            return new SynchronousMessageDispatcher(...$messageConsumers);
        });
    }

    public static function registerMessageRepository(string $class, ?Application $application = null): void
    {
        $application = $application ?? app();

        switch ($class) {
            case IlluminateUuidV4MessageRepository::class:
                $application->singleton(MessageRepository::class, IlluminateUuidV4MessageRepository::class);
                $application->singleton(IlluminateUuidV4MessageRepository::class);
                $application->when(IlluminateUuidV4MessageRepository::class)
                    ->needs('$tableName')
                    ->giveConfig('eventsauce.message_repository.table_name');

                break;

            default:
                throw new LogicException(sprintf('Unknown Message Repository class "%s" specified', $class));
        }
    }

    public static function registerMessageOutboxRepository(string $class, ?Application $application = null): void
    {
        $application = $application ?? app();

        switch ($class) {
            case IlluminateOutboxRepository::class:
                $application->singleton(OutboxRepository::class, IlluminateOutboxRepository::class);
                $application->when(IlluminateOutboxRepository::class)
                    ->needs('$tableName')
                    ->giveConfig('eventsauce.message_outbox.table_name');

                $application->singleton(IlluminateTransactionalMessageRepository::class);
                $application->when(IlluminateTransactionalMessageRepository::class)
                    ->needs(MessageRepository::class)
                    ->give(IlluminateUuidV4MessageRepository::class);

                break;

            default:
                throw new LogicException(sprintf('Unknown Message Outbox class "%s" specified', $class));
        }
    }

    public static function registerTransaction(string $class, ?Application $application = null): void
    {
        $application = $application ?? app();

        $application->singleton(Transaction::class, $class);
    }

    public static function registerMessageSerializer(string $class, ?Application $application = null): void
    {
        $application = $application ?? app();

        $application->singleton(MessageSerializer::class, $class);
    }

    public static function registerClassNameInflector(?string $class = null, ?Application $application = null): void
    {
        if (!$class) {
            return;
        }

        $application = $application ?? app();

        $application->singleton(ClassNameInflector::class, $class);
    }

    public static function registerPayloadSerializer(?string $class = null, ?Application $application = null): void
    {
        if (!$class) {
            return;
        }

        $application = $application ?? app();

        $application->singleton(PayloadSerializer::class, $class);
    }

    public static function registerMessageDecorator(?Application $application = null): void
    {
        $application = $application ?? app();

        $application->singleton(DefaultHeadersDecorator::class);
        $application->tag(DefaultHeadersDecorator::class, MessageDecorator::class);

        $application->singleton(MessageDecorator::class, function () use ($application) {
            /** @var MessageDecorator[] $messageDecorators */
            $messageDecorators = $application->tagged(MessageDecorator::class);

            return new MessageDecoratorChain(...$messageDecorators);
        });
    }

    public static function registerMessagePreparation(?string $class = null, ?Application $application = null): void
    {
        if (!$class) {
            return;
        }

        $application = $application ?? app();

        $application->singleton(MessagePreparation::class, $class);
    }

    public static function eloquentRepositoryRegistration(): EloquentAggregateRootRepositoryRegistrationBuilder
    {
        return EloquentAggregateRootRepositoryRegistrationBuilder::new();
    }

    public static function eventSourcedRepositoryRegistration(): EventSourcedAggregateRootRepositoryRegistrationBuilder
    {
        return EventSourcedAggregateRootRepositoryRegistrationBuilder::new();
    }
}
