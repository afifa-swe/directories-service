<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasOwner;
use Illuminate\Support\Str;

class BudgetHolder extends Model
{
    use HasFactory;
    use HasOwner;

    protected $table = 'budget_holders';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'tin',
        'name',
        'region',
        'district',
        'address',
        'phone',
        'responsible',
        'created_by',
        'updated_by',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }
}
