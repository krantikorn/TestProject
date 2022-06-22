<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Builder;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultstringLength(191);
        Builder::macro('search', function($fields, $string, $array = '') {
            if($array) {
                $where = $this->where(function ($q) use ($fields, $string) {
                    foreach ($fields as $field) {
                        $q->orWhere($field, 'like', "%{$string}%");
                    }
                });
                return $string ? $where : $this;
            }
            return $string ? $this->where($field, 'like', '%'.$string.'%') : $this;
        });
    }
}
