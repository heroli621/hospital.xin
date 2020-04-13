<?php
/*
 * 基本框架 控制器
 * */
namespace app\admin2\controller;

use think\Db;

class Framework extends BaseUser
{

    //外部框架
    public function index()
    {
        //渲染用户信息
        $users=session('user');
        //dump($users);exit;
        foreach($users as $key=>$val){
            $this->assign($key,$val);
        }

        return view('index');
    }

    //欢迎界面
    public function welcome()
    {
        //获取数据库类型及版本
        $sql_name=config('database.type');
        $version = Db::query("select version() as ver");
        $mysql_version=strtoupper($sql_name).' '.$version[0]['ver'];
        $this->assign('mysql_version',$mysql_version);

       //订单数
        $orders_num=Db::name('orders')->count();
        $this->assign('orders_num',$orders_num);

        //文章数
        $goods_num=Db::name('goods')->count();
        $this->assign('goods_num',$goods_num);

        //访问数
        $member_num=Db::name('member')->count();
        $this->assign('member_num',$member_num);


        //本周运营数据
        $monday=strtotime('sunday -6day');
        $sunday=strtotime('sunday 23:59:59');
        $res_data=Db::name('visit')
            ->field("FROM_UNIXTIME(create_time,'%Y%m%d') as days,count(id) as num")
            ->where('create_time','between',[$monday,$sunday])
            ->group('days')
            ->select();
        $visit_data=[];
        foreach ($res_data as $v){
            $visit_data[$v['days']]=$v['num'];
        }
        $fd=date('Ymd',$monday);
        $sd=date('Ymd',$sunday);
        while($fd<=$sd){
            if(isset($visit_data[$fd])){
                $ret_data[]=$visit_data[$fd];
            }else{
                $ret_data[]=0;
            }
            $fd=date('Ymd',strtotime($fd.' +1 day'));
        }
        $this->assign('visit_data',json_encode($ret_data));

        return view('welcome');
    }

    public function _empty()
    {
        //把所有城市的操作解析到city方法
        return view('public/404');
    }

}
