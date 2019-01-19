<?php
/*
|--------------------------------------------------------------------------
| 古越龙山后台接口
*/

Route::get('trace/login',['uses'=>'Trace\LoginController@login']);
Route::post('trace/login/un',['uses'=>'Trace\LoginController@unLogin']);
/**
 * "middleware"中间件 ==> 过滤用户未登陆请求
 * 用户登陆后才可访问
 */
Route::group(['prefix'=>'trace','namespace'=>'Trace','middleware' => 'TraceLogin'],function(){

    Route::get('login/detail',['uses'=>'LoginController@loginDetail']);

    //一、项目
    Route::group(['prefix'=>'project'],function(){
        //[1]项目列表
        Route::get('list',['uses'=>'ProjectController@projectList']);
        //[2]项目详情
        Route::get('detail',['uses'=>'ProjectController@projectDetail']);
        //[3]项目增加
        Route::post('add',['uses'=>'ProjectController@projectAdd']);
        //[4]项目更新
        Route::post('edit',['uses'=>'ProjectController@projectEdit']);

        //[5]地区列表
        Route::get('region',['uses'=>'ProjectController@projectRegion']);

        //[6]项目删除
        Route::post('del',['uses'=>'ProjectController@projectDel']);
        //[7]上移排序
        Route::post('up',['uses'=>'ProjectController@projectUp']);
        //[8]下移排序
        Route::post('down',['uses'=>'ProjectController@projectDown']);
    });

    //二、进度
    Route::group(['prefix'=>'process'],function(){

        Route::get('apply/detail',['uses'=>'ProcessController@applyDetail']);

        Route::post('apply/do',['uses'=>'ProcessController@applyDo']);

        Route::post('apply/do2',['uses'=>'ProcessController@applyDo2']);

    });

    //三、项目跟踪
    Route::group(['prefix'=>'trace'],function(){

        Route::get('list',['uses'=>'TraceController@traceList']);

        Route::post('add',['uses'=>'TraceController@traceAdd']);

        Route::post('check',['uses'=>'TraceController@traceCheck']);

        Route::get('check/detail',['uses'=>'TraceController@traceCheckDetail']);

        Route::post('edit',['uses'=>'TraceController@traceEdit']);

        Route::get('get',['uses'=>'TraceController@traceGet']);

    });

    // 项目设备投入上报
    Route::group(['prefix'=>'facility'],function(){

        Route::get('pList',['uses'=>'FacilityController@projectList']);

        Route::get('list',['uses'=>'FacilityController@facilityList']);

        Route::post('add',['uses'=>'FacilityController@facilityAdd']);

        Route::get('addAuth',['uses'=>'FacilityController@facilityAddAuth']);
//        Route::post('check',['uses'=>'FacilityController@facilityCheck']);

        Route::post('edit',['uses'=>'FacilityController@facilityEdit']);

        Route::get('get',['uses'=>'FacilityController@facilityGet']);

    });

    //四、报告
    Route::group(['prefix'=>'report'],function(){

        Route::get('list',['uses'=>'TraceController@reportList']);

        Route::get('export',['uses'=>'TraceController@reportExport']);

    });

    //五、用户权限
    Route::group(['prefix'=>'user'],function(){

        Route::get('list',['uses'=>'UserController@userList']);

        Route::get('detail',['uses'=>'UserController@userDetail']);

        Route::post('add',['uses'=>'UserController@userAdd']);

        Route::post('edit',['uses'=>'UserController@userEdit']);

        Route::post('reset',['uses'=>'UserController@userReset']);

        Route::post('password',['uses'=>'UserController@userPassword']);

    });


    //X、系统模块
    Route::group(['prefix'=>'system'],function(){
        //[1]上传图片
        Route::post('img/upload',['uses'=>'SystemController@imgUpload']);
    });

	

});


?>

