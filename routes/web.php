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



// Ted specific pages
$app->get('/search', 'TedController@search');
$app->post('/search', 'TedController@search');
$app->get('/view/{slug}', 'TedController@view');


// static pages
$app->get('/', 'TedController@home');
$app->get('/about', 'TedController@about');
$app->get('/disclaimer', 'TedController@disclaimer');
$app->get('/sitemap', 'TedController@sitemap');

// strictly json routes
$app->get('/total', 'TedController@total');
$app->get('/categories', 'TedController@categories');