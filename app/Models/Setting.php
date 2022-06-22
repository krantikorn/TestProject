<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $hidden = ["id", "created_at", "updated_at"];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'id'            => 'string',
        'key'           => 'string',
        'value'         => 'string',
        'comments'      => 'string',
    ];
}
