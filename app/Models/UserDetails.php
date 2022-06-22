<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDetails extends Model
{
    use HasFactory;
    protected $table = 'user_details';

    protected $guarded = [];

    public function users()
    {
        return $this->belongsTo(User::class, 'id', 'user_id');
    }

    /**
     * Anonymous scope
     */
    public function scopeForUserId($query, $user_id)
    {
        return $query->where('user_id', $user_id);
    }

    public function metable()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'id'         => 'string',
        'user_id'    => 'string',
        'meta_id'    => 'string',
        'meta_value' => 'string',
    ];
}
