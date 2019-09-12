<?php

/* Route for Home Controller */

Route::get('/inventory', 'ManageController@index');
Route::get('/inventory/{option}/{pub_id}/{ad_id}', 'ManageController@process');
Route::get('/advertiser/inventory/{pub_id}', 'ManageController@adver_inventory');
Route::get('/advertiser/media/dashboard', 'ManageController@new_media_layout');

?>
