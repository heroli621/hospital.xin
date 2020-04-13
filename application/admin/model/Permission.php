<?php
namespace app\admin\model;
use think\Model;

class Permission extends Model{

    //重定义表名
    //protected $table="permission";

    //主键
    protected $pk='id';

    //自动写入时间戳
    //protected $autoWriteTimestamp = true;


    //关联模型
    /*public function Permission(){
        return $this->belongsTo('permission')->bind('name');
    }*/




}