<?php
/*
 * 课程列表模型
 * */
namespace app\admin\model;
use think\Model;

class Menus extends Model{

    //重定义表名
    //protected $table="hy_menus";

    //主键
    protected $pk='id';

    //自动写入时间戳
    protected $autoWriteTimestamp = true;


}