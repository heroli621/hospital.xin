<?php
/*
 * 后台管理员模型
 * */
namespace app\admin\model;
use think\Model;

class User extends Model{

    //重定义表名
    //protected $table="hy_user";

    //主键
    protected $pk='id';

    //自动写入时间戳
    protected $autoWriteTimestamp = true;

    //修改器
    protected function setPasswordAttr($value)
    {
        return md5($value);
    }

    //获取器
    public function getLastLoginTimeAttr($value){
        return $value?date('Y-m-d H:i:s',$value):'';
    }

    //关联模型
    public function role(){
        return $this->belongsToMany('Role','user_role','role_id','user_id');
    }




}