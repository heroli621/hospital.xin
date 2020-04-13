<?php
/*
 * 网站配置 验证器
 * */
namespace app\admin2\validate;

use think\Validate;

class Site extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
        'var_name'  => 'require|unique:config,varname|max:64',
        'name'  => 'require|max:256',
        'value'  => 'require',
        'model'  => 'require'
    ];
    
    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [
        'var_name.require' => '变量名称必须填写',
        'var_name.max'     => '变量名称最多不能超过64个字符',
        'var_name.unique'     => '变量名称已经存在',
        'name.require'     => '配置名称必须填写',
        'name.max'     => '配置名称最多不能超过64个字符',
        'value.require'     => '配置值必须填写',
        'model.require'     => '配置类型必须选择'
    ];

    //验证场景
    protected $scene = [
        'edit'  =>  ['name','value','model'],
    ];

   /* //自定义验证password
    protected function password($value,$rule,$data=[]){
        if(preg_match($rule,$value)){
            return true;
        }

        return false;
    }*/



}
