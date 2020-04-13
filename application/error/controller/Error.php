<?php
/*
 * 客户案例
 * */
namespace app\error\controller;


use think\facade\Request;

class Error
{
    public function _empty()
    {
        if(Request::isAjax()){
            return json(['code'=>404,'msg'=>'您要访问的网页不存在']);
        }else{
            return view('error/404');
        }
    }

}
