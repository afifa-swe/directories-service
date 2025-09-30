<?php

namespace App\Models\Traits;

trait HasOwner
{
    public static function bootHasOwner()
    {
        static::creating(function ($model) {
            if (is_null($model->created_by) && auth()->check()) {
                $model->created_by = auth()->id();
            }
            if (is_null($model->updated_by) && auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
    }
}
