<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


$group = [
    'middleware' => 'api',
    'namespace' => 'App\Http\Controllers'
];


Route::group(
    $group,
    function ($router) {
        $province = 'ProductController';
        Route::resource('/product', $province);
    }
);
Route::get('storage/{filename}', function ($filename) {
    return Image::make(storage_path('public/') .  $filename)->response();
});
