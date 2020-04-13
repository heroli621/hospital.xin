<?php
/*
 * 会员角色申请 模型
 * */
namespace app\admin\model;


use think\Model;

class MemberRoleApply extends Model{

    //重定义表名
    //protected $table="hy_member_role_apply";

    //主键
    protected $pk='id';

    //自动写入时间戳
    protected $autoWriteTimestamp = true;


    public function member(){
        return $this->belongsTo("Member","member_id","member_id")->bind(["nickname","mobile"]);
    }

}