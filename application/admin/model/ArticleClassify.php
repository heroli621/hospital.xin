<?php
/*
 * 文章分类 模型
 * */
namespace app\admin\model;
use think\Model;

class ArticleClassify extends Model{

    //重定义表名
    //protected $table="hy_article_classify";

    //主键
    protected $pk='article_classify_id';

    //自动写入时间戳
    protected $autoWriteTimestamp = true;

    public function parent(){
        return $this->belongsTo("ArticleClassify","parent_id","article_classify_id")->bind(['parent_classify_name'=>'classify_name']);
    }


}