<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostedName extends Model
{
    use HasFactory;

    protected $table = 'posted_names';

    protected $fillable = [
        'name',
        'nickname',
        'babyname_id'
    ];

    public function babyName()
    {
        return $this->belongsTo(BabyName::class, 'babyname_id');
    }
}
