<?php
/*
 * 客户案例
 * */
namespace app\admin\controller;


class Error
{
    public function _empty()
    {
        //把所有城市的操作解析到city方法
        return view('public/404');
    }

}
