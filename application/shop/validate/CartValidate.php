<?php
/*
 * 购物车 验证器
 * */
namespace app\shop\validate;

use think\Validate;

class CartValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
        'goods_id'  => 'require|number',
        'buy_num'  => 'require|number',
    ];
    
    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [
        'goods_id' => '商品参数错误',
        'buy_num'  => '购买数量错误',
    ];









}
