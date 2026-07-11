<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscriptions extends Model
{
    protected $fillable = [
        'sub_name', 
        'price', 
        'duration',
        'status'  
    ];
    public function user(){ 
        return $this->HasMany(User::class);
    }
}
