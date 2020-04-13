<?php
/*
 * 文章内容模型
 * */
namespace app\model;
use think\Model;

class ArticlesContent extends Model{

    //重定义表名
    //protected $table="articles_content";

    //主键
    protected $pk='id';

    //自动写入时间戳
    //protected $autoWriteTimestamp = true;


    //关联模型
    public function articles(){
        return $this->belongsTo('Articles')->bind('title');
    }




}