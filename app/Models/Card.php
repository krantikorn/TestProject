<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User;
use Auth;

class Card extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $hidden = ["id", "user_id", "created_at", "updated_at"];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'id'            => 'string',
        'user_id'       => 'string',
        'last'          => 'string',
        'type'          => 'string',
        'name'          => 'string',
        'primary'       => 'string',
        'customer_id'   => 'string',
    ];

    //association
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Anonymous scope
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('cards', function (Builder $builder) {
            $builder->where('user_id', Auth::user()->id);
        });
    }
    
}
