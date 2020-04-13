<?php
/*
 * 订单 模型
 * */
namespace app\admin\model;


use think\Model;

class Orders extends Model{

    //重定义表名
    //protected $table="hy_orders";

    //主键
    protected $pk='order_id';

    //自动写入时间戳
    protected $autoWriteTimestamp = true;



}