<?php
/*
 * 后台角色 模型
 * */
namespace app\admin\model;
use think\Model;

class Role extends Model{

    //重定义表名
    //protected $table="role";

    //主键
    protected $pk='id';

    //自动写入时间戳
    //protected $autoWriteTimestamp = true;


    //关联模型
    public function permission(){
        return $this->belongsToMany('Permission','role_permission');
    }




}