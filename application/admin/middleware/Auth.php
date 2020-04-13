<?php
/*
 * 登录、验证 控制器
 * */
namespace app\admin\middleware;

use gmars\rbac\Rbac;
use think\Db;
use think\Request;


class Auth
{
    public function handle(Request $request, \Closure $next)
    {
        //验证权限
        if(session('user.status')< 99){
            $rbacObj = new Rbac();
            $request_path=$request->module().'/'.$request->controller().'/'.$request->action();
            $request_path=strtolower($request_path);
            if(!$rbacObj->can($request_path)){
                if($request->isAjax()){
                    $err=[
                        'code'=>403,
                        'msg'=>'没有相关权限！'
                    ];
                    json($err)->send();exit;
                }else{
                    view("public/403")->send();exit;
                }
            }
        }elseif(session('user.status')< 100){
            $request_path=$request->module().'/'.$request->controller().'/'.$request->action();
            $request_path=strtolower($request_path);
            $can=Db::name('menus')->where('link',$request_path)->find();
            if($can && $can['status']!=1){
                if($request->isAjax()){
                    $err=[
                        'code'=>403,
                        'msg'=>'没有相关权限！'
                    ];
                    json($err)->send();exit;
                }else{
                    view("public/403")->send();exit;
                }
            }
        }
        return $next($request);
    }

}
