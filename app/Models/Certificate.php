<?php

namespace App\Models;

use App\Models\User;
use Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $hidden = ["user_id", "created_at", "updated_at"];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'id'        => 'string',
        'user_id'   => 'string',
        'institute' => 'string',
        'title'     => 'string',
        //'summary'    => 'string',
        'year'      => 'string',
        'location'  => 'string',
    ];

    //association
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Anonymous scope
     */
    public function scopeCurrent($query, $user_id = '')
    {
        $id = empty($user_id) ? Auth::user()->id : $user_id;
        return $query->where('user_id', $id);
    }
}
