<?php
/*
 * 文章分享 模型
 * */
namespace app\admin\model;
use think\Model;

class ArticlesShare extends Model{

    //重定义表名
    //protected $table="hy_articles_share";

    //主键
    protected $pk='id';

    //自动写入时间戳
    protected $autoWriteTimestamp = true;


    //关联模型
    public function article(){
        return $this->belongsTo('Articles')->bind('title');
    }




}