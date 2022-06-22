<?php

namespace App\Models;

use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class UserCategory extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected $hidden = ["created_at", "updated_at"];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    /*protected static function booted()
    {
        $userId = Auth::user()->id;
        static::addGlobalScope('checkUser', function (Builder $builder) use ($userId) {
            $builder->where('user_id', $userId);
        });
    }*/

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'id'          => 'string',
        'user_id'     => 'string',
        'category_id' => 'string',
    ];
}
