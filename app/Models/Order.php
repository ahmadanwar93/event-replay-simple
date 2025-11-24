<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $casts = [
        'total' => 'decimal:2', // we want to cast the float into string
    ];

    protected $fillable = [
        'product_id',
        'quantity',
        'total',
        'status',
    ];

    public function emailLogs()
    {
        return $this->hasMany(EmailLog::class);
    }

    public function inventoryTransactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}
