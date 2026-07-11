<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'user_id',
        'tx_ref',
        'transaction_id',
        'amount',
        'currency',
        'status',
             
    ];
    public function user(){ 
        return $this->HasMany(User::class);
    }
}
