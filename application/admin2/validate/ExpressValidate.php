<?php
/*
 * 物流公司 验证器
 * */
namespace app\admin2\validate;

use think\Validate;

class ExpressValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
        'express_name'  => 'require|max:64|unique:express',
        'com'  => 'require|max:64',
    ];
    
    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [
        'coupon_name.require' => '物流公司名称必须填写',
        'coupon_name.max'     => '物流公司名称最多不能超过64个字符',
        'com.require'     => '公司编码必须填写',
        'com.max'     => '公司编码最多不能超过64个字符',
    ];









}
