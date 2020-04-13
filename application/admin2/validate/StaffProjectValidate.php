<?php
/*
 * 员工薪资项目记录 验证器
 * */
namespace app\admin2\validate;

use think\Validate;

class StaffProjectValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
        'member_id'  => 'require',
        'project_id'  => 'require',
        'fee'  => 'require|float',
        'month'  => 'require',
    ];
    
    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [
        'member_id' => '至少选择一个员工',
        'project_id'     => '项目必须选择',
        'fee.require'     => '金额必须填写',
        'fee.float'     => '金额只能填写数字',
        'month'     => '手工费必须填写'
    ];

    protected $scene=[
        'edit'=>['fee','month']
    ];

}
