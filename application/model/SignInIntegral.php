<?php
/*
 * 签到积分设置 模型
 * */
namespace app\model;
use think\Model;

class SignInIntegral extends Model{

    //重定义表名
    //protected $table="rc_sign_in_integral";

    //主键
    protected $pk='id';

    //自动写入时间戳
    protected $autoWriteTimestamp = true;


}