<?php
/*
 * 消息已读 模型
 * */
namespace app\model;
use think\Model;

class MessageRead extends Model{

    //重定义表名
    //protected $table="rc_member";

    //主键
    protected $pk='id';

    //自动写入时间戳
    protected $autoWriteTimestamp = true;


}