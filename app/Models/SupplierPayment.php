<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierPayment extends Model
{
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
