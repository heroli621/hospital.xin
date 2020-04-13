<?php
/*
 * 角色 验证器
 * */
namespace app\admin\validate;

use think\Validate;

class RoleValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
        'name'  => 'require|max:50',
        'sort'  => 'number',
        'des'  => 'max:200',
    ];
    
    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [
        'name.require' => '角色名称必须填写',
        'name.max'     => '角色名称最多不能超过50个字符',
        'sort.number'     => '排序值只能是整数',
        'des'     => '描述信息不能超过200字符',
    ];
    /*
        //验证场景
        protected $scene = [
            //'edit'  =>  ['username','mail','des'],      ];

       /* //自定义验证password
        protected function password($value,$rule,$data=[]){
            if(preg_match($rule,$value)){
                return true;
            }

            return false;
        }*/






}
