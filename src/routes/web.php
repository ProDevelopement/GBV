<?php

Route::group(['namespace' => 'ProDevelopement\GermanBankVerification\Http\Controllers'], function () {
    Route::get('/gbv/admin', 'AutoPopulateController@index');
    Route::post('/gbv/admin', 'AutoPopulateController@autopopulate');
    Route::post('/gbv/api', 'AutoPopulateController@apiClient');
});
