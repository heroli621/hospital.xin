<?php
/*
 * 薪资项目 验证器
 * */
namespace app\admin2\validate;

use think\Validate;

class FinanceProjectValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
        'project_name'  => 'require|max:20',

    ];
    
    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [
        'project_name.require' => '项目名称必须填写',
        'project_name.max'     => '项目名称最多不能超过120个字符',
    ];



}
