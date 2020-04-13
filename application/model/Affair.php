<?php
/*
 * 事务 模型
 * */
namespace app\model;


use think\Db;
use think\Model;

class Affair extends Model{

    //重定义表名
    //protected $table="rc_affair";

    //主键
    protected $pk='id';


    //自动写入时间戳
    protected $autoWriteTimestamp = true;


}