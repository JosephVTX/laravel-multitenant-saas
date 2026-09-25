<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Adds a public, non-guessable uuid column used for external references and
 * route model binding, while keeping a compact auto-increment primary key.
 */
trait HasUuid
{
    public static function bootHasUuid(): void
    {
        static::creating(function ($model): void {
            if (empty($model->getAttribute('uuid'))) {
                $model->setAttribute('uuid', (string) Str::orderedUuid());
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
