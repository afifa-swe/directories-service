<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TreasuryAccount extends Model
{
    use HasFactory;

    protected $table = 'treasury_accounts';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'account',
        'mfo',
        'name',
        'department',
        'currency',
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
