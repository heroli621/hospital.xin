<?php
/*
 * 优惠券发送 验证器
 * */
namespace app\admin2\validate;

use think\Validate;

class CouponSendValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
        'coupon_id'  => 'require',
        'mobile'  => 'require',
        'message'  => 'require',
    ];
    
    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [
        'coupon_id' => '优惠券必须选择',
        'mobile'  => '会员手机必须填写',
        'message'     => '消息内容必须填写'
    ];









}
