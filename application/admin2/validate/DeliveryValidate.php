<?php
/*
 * 发货单 验证器
 * */
namespace app\admin2\validate;

use think\Validate;

class DeliveryValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
        'express_id'  => 'require|number',
        'oid'  => 'require|number',
    ];
    
    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [
        'express_id.require' => '物流公司必须选择',
        'express_id.number'     => '无效的物流公司',
        'oid' => '订单参数错误'
    ];









}
