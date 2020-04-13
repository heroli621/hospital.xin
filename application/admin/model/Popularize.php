<?php
/*
 * 推广 模型
 * */
namespace app\admin\model;


use think\Model;

class Popularize extends Model{

    //重定义表名
    //protected $table="hy_popularize";

    //主键
    protected $pk='id';

    //自动写入时间戳
    protected $autoWriteTimestamp = true;


    public function m(){
        return $this->belongsTo("Member","member_id","member_id")->bind(['member_name'=>'nickname']);
    }
    public function c(){
        return $this->belongsTo("Member","child_id","member_id")->bind(['child_name'=>'nickname']);
    }
    public function p(){
        return $this->belongsTo("Member","parent_id","member_id")->bind(['parent_name'=>'nickname']);
    }

}