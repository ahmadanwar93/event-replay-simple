<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;

    protected $table = 'inventory'; // we named it inventory because inventories makes no sense grammar wise
    protected $primaryKey = 'product_id';
    public $incrementing = false;
    protected $keyType = 'string'; // still need these even if we are using uuid

    protected $fillable = ['product_id', 'name', 'stock'];
}
