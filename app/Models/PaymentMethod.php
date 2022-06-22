<?php

namespace App\Models;

use App\Models\User;
use Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
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
        'id'             => 'string',
        'user_id'        => 'string',
        'payment_type'   => 'string',
        'title'          => 'string',
        'ending'         => 'string',
        'type'           => 'string',
        'stripe_cust_id' => 'string',
        'paypal_email'   => 'string',
        'authorization'  => 'string',
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

        static::addGlobalScope('paymentMethod', function (Builder $builder) {
            $builder->where('user_id', Auth::user()->id);
        });
    }
}
