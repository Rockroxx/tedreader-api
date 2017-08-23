<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/


$app->get('/total', 'TedController@total');

$app->get('/', 'TedController@search');
$app->get('/search', 'TedController@search');
$app->post('/search', 'TedController@search');

$app->get('/view/{slug}', 'TedController@view');

$app->get('/about', function(\Illuminate\Http\Request $request){
    if(!$request->isJson()){
        return view("master", ['category_list' => app('cache')->remember('categories', 60, function(){
            return app('db')->table('categories')->select('code', 'name')->get();
        })]);
    }
});

$app->get('/disclaimer', function(\Illuminate\Http\Request $request){
    if(!$request->isJson()){
        return view("master", ['category_list' => app('cache')->remember('categories', 60, function(){
            return app('db')->table('categories')->select('code', 'name')->get();
        })]);
    }
});

$app->get('/home', function(\Illuminate\Http\Request $request){
    if(!$request->isJson()){
        return view("master", ['category_list' => app('cache')->remember('categories', 60, function(){
            return app('db')->table('categories')->select('code', 'name')->get();
        })]);
    }
});
$app->get('/sitemap', function(\Illuminate\Http\Request $request){
    if(!$request->isJson()){
        return view("master", ['category_list' => app('cache')->remember('categories', 60, function(){
            return app('db')->table('categories')->select('code', 'name')->get();
        })]);
    }
});

$app->get('/categories', function(){
    return json_encode(app('db')->table('categories')->get());
});