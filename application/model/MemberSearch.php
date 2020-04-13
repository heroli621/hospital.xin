<?php
/*
 * 会员搜索 模型
 * */
namespace app\model;
use think\Model;

class MemberSearch extends Model{

    //重定义表名
    //protected $table="rc_member_search";

    //主键
    protected $pk='id';

    //自动写入时间戳
    protected $autoWriteTimestamp = true;

    public function insertNew($member_id,$keywords){
        $keys=$this->where('member_id',$member_id)
            ->where('keywords',$keywords)
            ->value('id');
        if(!$keys){
            $total=$this->where('member_id',$member_id)->count();
            if($total>=10){
                $res=$this->where('member_id',$member_id)
                    ->order('create_time asc')
                    ->limit(1)
                    ->find();
                $res->delete();
            }
            $this->create(['member_id'=>$member_id,'keywords'=>$keywords]);
        }
        return true;
    }

}