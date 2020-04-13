<?php
/*
 * 分类导航 模型
 * */
namespace app\admin\model;


use think\Model;
use think\model\concern\SoftDelete;

class Navi extends Model{
    //软删除
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    //重定义表名
    //protected $table="hy_navi";

    //主键
    protected $pk='navi_id';

    //自动写入时间戳
    protected $autoWriteTimestamp = true;

    //关联
    public function p(){
        return $this->belongsTo("Navi","parent_id","navi_id")->bind(['parent_name'=>'navi_name']);
    }

}