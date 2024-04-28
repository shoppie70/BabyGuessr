<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BabyName extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'baby_names';

    protected $fillable = [
        'name',
        'sex'
    ];
}
