<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
//$default_module=config('default_module');
$default_module='admin';


Route::get('/', $default_module.'/Index/index');

/*********************************后台路由*******************************/


//登录控制器路由
Route::get('system', 'admin/Index/index');//登录页
Route::get('verify', 'admin/Index/verify');//验证码页
Route::post('login', 'admin/Index/login');//提交登录处理
Route::post('loginOut', 'admin/Index/loginOut');//提交登出处理

//首页控制器路由
Route::get('manage', 'admin/Framework/index');//主页框架


/**********************************后台路由**********************************/

 /*
 *
 *
 *
 */
