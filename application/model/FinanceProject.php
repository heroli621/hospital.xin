<?php
/*
 * 预约服务 模型
 * */
namespace app\model;


use think\Db;
use think\Model;

class FinanceProject extends Model{

    //重定义表名
    //protected $table="rc_finance_project";

    //主键
    protected $pk='id';


    //自动写入时间戳
    protected $autoWriteTimestamp = true;



}