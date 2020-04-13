<?php
/*
 * 权限 验证器
 * */
namespace app\admin2\validate;

use think\Validate;

class Permission extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
        'name'  => 'require|max:50',
        'path'  => 'require|max:100|unique:permission',
        'des'  => 'max:200',
    ];
    
    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [
        'name.require' => '权限节点名称必须填写',
        'name.max'     => '权限节点名称最多不能超过50个字符',
        'path.require'     => '权限路径必须填写',
        'path.unique'     => '权限路径已经存在',
        'path.max'     => '权限路径不能超过100字符',
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
