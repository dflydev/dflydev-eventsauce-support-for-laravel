<?php

declare(strict_types=1);

namespace Dflydev\EventSauce\SupportForLaravel\Configuration;

use Dflydev\EventSauce\SupportForLaravel\EventSauceConfiguration;
use Illuminate\Contracts\Foundation\Application;

final readonly class ApplicationBoundMessageConsumerConfiguration
{
    private Application $application;

    public function __construct(?Application $application = null)
    {
        $this->application = $application ?? app();
    }

    public function registerNonLazySynchronousMessageConsumer(string ...$messageConsumerClassNames): void
    {
        EventSauceConfiguration::registerNonLazySynchronousMessageConsumer($messageConsumerClassNames, $this->application);
    }

    public function registerSynchronousMessageConsumer(string ...$messageConsumerClassNames): void
    {
        EventSauceConfiguration::registerSynchronousMessageConsumer($messageConsumerClassNames, $this->application);
    }

    public function registerNonLazyAsynchronousMessageConsumer(string ...$messageConsumerClassNames): void
    {
        EventSauceConfiguration::registerNonLazyAsynchronousMessageConsumer($messageConsumerClassNames, $this->application);
    }

    public function registerAsynchronousMessageConsumer(string ...$messageConsumerClassNames): void
    {
        EventSauceConfiguration::registerAsynchronousMessageConsumer($messageConsumerClassNames, $this->application);
    }

    public function registerNonLazyTransactionalMessageConsumer(string ...$messageConsumerClassNames): void
    {
        EventSauceConfiguration::registerNonLazyTransactionalMessageConsumer($messageConsumerClassNames, $this->application);
    }

    public function registerTransactionalMessageConsumer(string ...$messageConsumerClassNames): void
    {
        EventSauceConfiguration::registerTransactionalMessageConsumer($messageConsumerClassNames, $this->application);
    }
}
