<?php

declare(strict_types=1);

namespace Dflydev\EventSauce\SupportForLaravel\AggregateRoot;

use Dflydev\EventSauce\Support\AggregateRoot\EventedAggregateRootBehaviour;
use EventSauce\EventSourcing\AggregateRoot;
use EventSauce\EventSourcing\AggregateRootId;
use Illuminate\Database\Eloquent\Model;

/**
 * @template T1 of AggregateRoot
 * @template T2 of AggregateRootId
 *
 * @property int $aggregate_root_version
 *
 * @method static \Illuminate\Database\Eloquent\Builder query()
 * @method static string aggregateRootIdColumnName()
 */
trait EloquentAggregateRootBehaviour
{
    /**
     * @use EventedAggregateRootBehaviour<T2>
     */
    use EventedAggregateRootBehaviour;

    public function initializeEloquentAggregateRootBehaviour(): void
    {
        // dump(['initializeEloquentAggregateRootBehaviour:in' => static::class, 'casts' => $this->casts]);
        // foreach ($this->casts as $cast) {
        //     if (!is_string($cast)) {
        //         continue;
        //     }
        //
        //     if (!class_exists($cast)) {
        //         continue;
        //     }
        //
        //     $interfaces = class_implements($cast) ?: [];
        //
        //     if (!in_array(StaticCastable::class, $interfaces)) {
        //         continue;
        //     }
        //
        //     /** @var StaticCastable $cast */
        //     $this->casts[static::aggregateRootIdColumnName()] = $cast::castUsing();
        // }
        // dump(['initializeEloquentAggregateRootBehaviour:out' => static::class, 'casts' => $this->casts]);

        // foreach (self::registerIdentityCasts() as $attribute => $identityClassName) {
        //     $implementedClasses = class_implements($identityClassName) ?: [];

        // if (in_array(CasterAware::class, $implementedClasses)) {
        //     /** @var CasterAware $identityClassName */
        //     $this->casts[$attribute] = $identityClassName::castUsing();
        //
        //     continue;
        // }
        //
        // if (in_array(CastsAttributes::class, $implementedClasses)) {
        //     /** @var CastsAttributes $identityClassName */
        //     $this->casts[$attribute] = $identityClassName::class;
        //
        //     continue;
        // }

        // $this->casts[$attribute] = new class() implements CastsAttributes {
        //     public function get(Model $model, string $key, mixed $value, array $attributes): mixed
        //     {
        //         return $value;
        //     }
        //
        //     public function set(Model $model, string $key, mixed $value, array $attributes): mixed
        //     {
        //         return $value;
        //     }
        // };
        // }
    }

    public static function bootEloquentAggregateRootBehaviour(): void
    {
        static::creating(self::syncAggregateRootVersion(...));
        static::updating(self::syncAggregateRootVersion(...));
    }

    public static function findByAggregateRootId(AggregateRootId $aggregateRootId): EloquentAggregateRoot
    {
        /** @var EloquentAggregateRoot<T1,T2>|null $aggregateRoot */
        $aggregateRoot = static::query()
            ->where(self::aggregateRootIdColumnName(), '=', $aggregateRootId->toString())
            ->first();

        assert(!is_null($aggregateRoot), 'Aggregate Root not found');

        return $aggregateRoot;
    }

    /**
     * @phpstan-return T2
     */
    public function aggregateRootId(): AggregateRootId
    {
        return $this->{static::aggregateRootIdColumnName()};
    }

    public function persistAggregateRoot(): void
    {
        $this->save();
    }

    /**
     * @param EloquentAggregateRoot&Model $model
     */
    public static function syncAggregateRootVersion(EloquentAggregateRoot $model): void
    {
        $model->setAttribute('aggregate_root_version', $model->aggregateRootVersion());
        $model->setAttribute('aggregate_root_version', $model->aggregateRootVersion());
    }
}
