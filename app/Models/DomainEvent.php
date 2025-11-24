<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DomainEvent extends Model
{
    use HasFactory;
    public $timestamps = false; // we will manage our timestamps manually, else will get updated_at column not found

    protected $fillable = [
        'event_type',
        'aggregate_type',
        'aggregate_id',
        'payload',
        'metadata',
        'created_at',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'processed_at' => 'datetime', // since this is a custom timestamp column, have to custom casting
    ];

    public static function boot()
    {
        parent::boot();

        static::updating(function () {
            return false;  // Events cannot be updated
        });

        static::deleting(function () {
            return false;  // Events cannot be deleted
        });
    }
}
