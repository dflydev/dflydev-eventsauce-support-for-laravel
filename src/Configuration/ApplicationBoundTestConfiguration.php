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

namespace Dflydev\EventSauce\SupportForLaravel\Configuration;

use Dflydev\EventSauce\SupportForLaravel\EventSauceConfiguration;
use EventSauce\EventSourcing\MessageDispatcher;
use EventSauce\EventSourcing\MessageRepository;
use Illuminate\Contracts\Foundation\Application;

final readonly class ApplicationBoundTestConfiguration
{
    private Application $application;

    public function __construct(?Application $application = null)
    {
        $this->application = $application ?? app();
    }

    public function fakeSynchronousMessageDispatcher(?MessageDispatcher $messageDispatcher = null): self
    {
        EventSauceConfiguration::fakeSynchronousMessageDispatcher($messageDispatcher, $this->application);

        return $this;
    }

    public function fakeTransactionalMessageDispatcher(?MessageDispatcher $messageDispatcher = null): self
    {
        EventSauceConfiguration::fakeTransactionalMessageDispatcher($messageDispatcher, $this->application);

        return $this;
    }

    public function fakeAsynchronousMessageDispatcher(?MessageDispatcher $messageDispatcher = null): self
    {
        EventSauceConfiguration::fakeAsynchronousMessageDispatcher($messageDispatcher, $this->application);

        return $this;
    }

    public function fakeMessageRepository(?MessageRepository $messageRepository = null): self
    {
        EventSauceConfiguration::fakeMessageRepository($messageRepository, $this->application);

        return $this;
    }

    public function fakeMessageDispatching(): self
    {
        return $this
            ->fakeSynchronousMessageDispatcher()
            ->fakeAsynchronousMessageDispatcher()
            ->fakeTransactionalMessageDispatcher();
    }
}
