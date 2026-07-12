<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiChatBot extends Model
{
    protected $table = 'chat';
    protected $fillable = [
        
        'user_id',
        'ip_address',
        'image_path',
        'user_prompt',
        'ai_response',
             
    ];
    protected function casts(): array
    {
        return [
            'image_path' => 'array',
            'ai_response' => 'array',
        ];
    }
    private function user(){
        return $this->belongsTo(User::class);
    }
}
