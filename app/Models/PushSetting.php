<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PushSetting extends Model
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
        'id'      => 'string',
        'user_id' => 'string',
        'status'  => 'string',
    ];

    //association
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
