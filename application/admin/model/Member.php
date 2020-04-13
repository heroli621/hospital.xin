<?php
/*
 * 会员 模型
 * */
namespace app\admin\model;


use think\Model;

class Member extends Model{

    //重定义表名
    //protected $table="hy_coupon";

    //主键
    protected $pk='member_id';

    //自动写入时间戳
    protected $autoWriteTimestamp = true;


}