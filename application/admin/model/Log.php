<?php
/*
 * 后台操作日志 模型
 * */
namespace app\admin\model;


use think\Model;

class Log extends Model{

    //重定义表名
    //protected $table="hy_log";

    //主键
    protected $pk='id';

    //自动写入时间戳
    protected $autoWriteTimestamp = true;

    //自动完成
    protected $insert=['ip','uid'];
    //修改器
    protected function setUidAttr()
    {
        return session('user.uid');
    }
    protected function setIpAttr()
    {
        return request()->ip();
    }
    //获取器（类型）
    public function getTypeAttr($value){
        $arr=[1=>'增加',2=>'删除',3=>'修改',4=>'登录',5=>'退出'];
        return $arr[$value];
    }
    //获取器(状态)
    public function getStatusAttr($value){
        $arr=[0=>'失败',1=>'成功'];
        return $arr[$value];
    }

    //关联模型
    public function admin(){
        return $this->belongsTo('User','uid')->bind('nickname');
    }




}