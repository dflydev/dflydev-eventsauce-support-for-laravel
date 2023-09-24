<?php

declare(strict_types=1);

namespace Dflydev\EventSauce\SupportForLaravel\Testing;

use Dflydev\EventSauce\Support\LazyMessageDispatching\LazyMessageDispatcher;
use Dflydev\EventSauce\SupportForLaravel\EventSauceConfiguration;
use EventSauce\EventSourcing\InMemoryMessageRepository;
use EventSauce\EventSourcing\MessageConsumer;

/**
 * @method void afterApplicationCreated(callable $callback)
 *
 * @property \Illuminate\Foundation\Application $app
 */
trait IlluminateMessageConsumerTesting
{
    private InMemoryMessageRepository $messageRepositoryForMessageConsumerTesting;

    /**
     * @return MessageConsumer[]
     */
    abstract public static function getMessageConsumerClassesToTest(): array;

    public function configureMessageConsumerTesting(): void
    {
    }

    protected function setInMemoryMessageRepositoryForMessageConsumerTesting(InMemoryMessageRepository $messageRepositoryForMessageConsumerTesting): void
    {
        if (isset($this->messageRepositoryForMessageConsumerTesting)) {
            throw new \LogicException('Message repository for message consumer testing already set.');
        }

        $this->messageRepositoryForMessageConsumerTesting = $messageRepositoryForMessageConsumerTesting;
    }

    protected function getInMemoryMessageRepositoryForMessageConsumerTesting(): InMemoryMessageRepository
    {
        if (!isset($this->messageRepositoryForMessageConsumerTesting)) {
            $this->messageRepositoryForMessageConsumerTesting = new InMemoryMessageRepository();
        }

        return $this->messageRepositoryForMessageConsumerTesting;
    }

    /**
     * @before
     */
    public function configureContainerForMessageConsumerTesting(): void
    {
        $this->afterApplicationCreated(function () {
            $transactionalMessageDispatcher = new LazyMessageDispatcher($this->app, ...$this->getMessageConsumerClassesToTest());

            EventSauceConfiguration::configureForTesting($this->app)
                ->fakeMessageRepository($this->getInMemoryMessageRepositoryForMessageConsumerTesting())
                ->fakeMessageDispatching()
                ->fakeTransactionalMessageDispatcher($transactionalMessageDispatcher);

            $this->configureMessageConsumerTesting();
        });
    }
}
