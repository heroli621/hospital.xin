<?php
/*
 * 文章模型
 * */
namespace app\model;
use think\Model;

class Articles extends Model{

    //重定义表名
    //protected $table="articles";

    //主键
    protected $pk='id';

    //自动写入时间戳
    //protected $autoWriteTimestamp = true;

    /*public function getReleaseTimeAttr($value)
    {
        return date('Y-m-d',$value);
    }*/

    //关联模型
    public function content(){
        return $this->hasOne('ArticlesContent')->bind(['detail'=>'content']);
    }




}