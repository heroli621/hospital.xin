<?php
/*
 * 事务 模型
 * */
namespace app\model;


use think\Model;

class MemberHealthAdmin extends Model{

    //重定义表名
    protected $table="rc_member_health";

    //主键
    protected $pk='id';


    //自动写入时间戳
    protected $autoWriteTimestamp = true;

   /* public function getSkinTypeAttr($value){
        return explode(',',$value);
    }
    public function getVenationAttr($value){
        return explode(',',$value);
    }
    public function getSkinColorAttr($value){
        return explode(',',$value);
    }
    public function getElasticityAttr($value){
        return explode(',',$value);
    }
    public function getSkinNowAttr($value){
        return explode(',',$value);
    }*/


}