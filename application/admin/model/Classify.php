<?php
/*
 * 商品分类 模型
 * */
namespace app\admin\model;


use think\Model;
use think\model\concern\SoftDelete;

class Classify extends Model{
    //软删除
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    //重定义表名
    //protected $table="hy_classify";

    //主键
    protected $pk='classify_id';

    //自动写入时间戳
    protected $autoWriteTimestamp = true;

    //关联
    public function p(){
        return $this->belongsTo("Classify","parent_id","classify_id")->bind(['parent_name'=>'classify_name']);
    }

}