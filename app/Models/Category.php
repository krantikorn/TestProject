<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'image',
    ];

    protected $hidden = ["created_at", "updated_at"];

    /**
     * Get the category's name.
     *
     * @param  string  $value
     * @return string
     */
    public function getNameAttribute($value)
    {
        return ucfirst($value);
    }

    /**
     * Get the category's name.
     *
     * @param  string  $value
     * @return string
     */
    public function getImageAttribute($value)
    {
        $host = request()->getSchemeAndHttpHost();
        return $host . '/' . $value;
    }

    /**
     * Set the category's name.
     *
     * @param  string  $value
     * @return void
     */
    public function setFirstNameAttribute($value)
    {
        $this->attributes['name'] = ucfirst($value);
    }
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'id'    => 'string',
        'name'  => 'string',
        'image' => 'string',
    ];
}
