<?php
/*
 * 优惠券 验证器
 * */
namespace app\admin2\validate;

use think\Validate;

class CouponValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
        'coupon_name'  => 'require|max:100',
        'sale_money'  => 'require|number',
    ];
    
    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [
        'coupon_name.require' => '优惠券名称必须填写',
        'coupon_name.max'     => '优惠券名称最多不能超过100个字符',
        'sale_money.require'     => '优惠金额必须填写',
        'sale_money.number'     => '优惠金额只能填写数字',
    ];









}
