<?php

declare(strict_types=1);

namespace Dflydev\EventSauce\SupportForLaravel\Eloquent;

use Dflydev\EventSauce\Support\Identity\IdentityGeneration;

/**
 * @method static string aggregateRootIdColumnName()
 */
trait UuidAsPrimaryKey
{
    public function initializeUuidAsPrimaryKey(): void
    {
        $this->incrementing = false;
        $this->keyType = 'string';

        $this->primaryKey = static::aggregateRootIdColumnName();

        if (!array_key_exists(static::aggregateRootIdColumnName(), $this->casts)) {
            $this->casts[static::aggregateRootIdColumnName()] = static::aggregateRootIdClassName();
        }
    }

    public static function bootUuidAsPrimaryKey(): void
    {
        static::creating(function (self $model) {
            $primaryKey = $model->getKeyName();

            if (!$model->$primaryKey) {
                /** @phpstan-var class-string<IdentityGeneration> $class */
                $class = $model::aggregateRootIdClassName();

                $model->{$primaryKey} = $class::generate();
            }
        });
    }

    public function getKeyType(): string
    {
        return $this->keyType;
    }

    public function getIncrementing(): bool
    {
        return $this->incrementing;
    }

    public function getForeignKey()
    {
        return $this->primaryKey;
    }
}
