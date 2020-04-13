<?php
/*
 * 预约服务 验证器
 * */
namespace app\admin2\validate;

use think\Validate;

class ServersValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
        'server_name'  => 'require|max:255',
        //'fee'  => 'require|float',
        //'cost'  => 'require|float',
        'des'  => 'max:512',
    ];
    
    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [
        'server_name.require' => '服务名称必须填写',
        'server_name.max'     => '服务名称最多不能超过255个字符',
        //'fee.require'     => '服务费必须填写',
        //'fee.float'     => '服务费只能填写数字',
        //'cost.require'     => '手工费必须填写',
        //'cost.float'     => '手工费只能填写数字',
        'des'     => '说明不能超过500字符',
    ];


}
