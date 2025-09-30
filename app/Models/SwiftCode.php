<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasOwner;
use Illuminate\Support\Str;

class SwiftCode extends Model
{
    use HasFactory;
    use HasOwner;

    protected $table = 'swift_codes';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'swift_code',
        'bank_name',
        'country',
        'city',
        'address',
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
