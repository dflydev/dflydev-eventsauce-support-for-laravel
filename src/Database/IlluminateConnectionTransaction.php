<?php

declare(strict_types=1);

namespace Dflydev\EventSauce\SupportForLaravel\Database;

use Dflydev\EventSauce\Support\Transaction\Transaction;
use Illuminate\Database\ConnectionInterface;

final readonly class IlluminateConnectionTransaction implements Transaction
{
    public function __construct(private ConnectionInterface $connection)
    {
    }

    public function begin(): void
    {
        $this->connection->beginTransaction();
    }

    public function commit(): void
    {
        $this->connection->commit();
    }

    public function rollBack(): void
    {
        $this->connection->rollBack();
    }
}
