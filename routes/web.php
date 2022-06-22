<?php

use App\Http\Livewire\Crud;
use App\Http\Livewire\Categories;
use App\Http\Livewire\Course;
use App\Http\Livewire\Bookings;
use App\Http\Livewire\Settings;
use App\Http\Livewire\Teacher;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
 */

Route::get('/', function () {
    return view('auth.login');
});

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('users', Crud::class)->name('users');
    Route::get('courses', Course::class)->name('courses');
    Route::get('instructors', Teacher::class)->name('instructors');
    Route::get('category', Categories::class)->name('category');
    Route::get('bookings', Bookings::class)->name('bookings');
    Route::get('settings', Settings::class)->name('settings');
});

Route::get('reset/password/{id}', [UserController::class, 'getPassword']); // Matches "/api/reset/password

Route::post('update/password', [UserController::class, 'updatePassword']); // Matches "/api/update/password
Route::get('/agreement', function () { return view('agreement'); });
Route::get('/privacy', function () { return view('privacy'); });
Route::get('/terms', function () { return view('termssite'); });
