<?php
/*
 * 商品规格 验证器
 * */
namespace app\admin2\validate;

use think\Validate;

class GoodsSpecValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
        'spec_name'  => 'require|max:255',
        'inventory'  => 'require|number',
        'price'  => 'requireCallback:hasIntegral|number',
        'integral'  => 'requireCallback:hasPrice|integer',
    ];
    
    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [
        'spec_name.require'     => '规格名称必须填写',
        'spec_name.max'     => '规格名称不能大于255字符',
        'inventory.require'     => '库存数量必须填写',
        'inventory.number'     => '库存数量只能填写数字',
        'price.requireCallback'     => '商品价格必须填写',
        'price.number'     => '商品价格只能填写数字',
        'integral.requireCallback'     => '兑换积分必须填写',
        'integral.integer'     => '商品兑换积分只能填写整数',
    ];

    //自定义验证
    protected function hasPrice($value,$data){
        if(!isset($data['price']) ||empty($data['price'])){
            return true;
        }
        return false;
    }
    protected function hasIntegral($value,$data){
        if(!isset($data['integral']) || $data['integral']==''){
            return true;
        }
        return false;
    }

}
