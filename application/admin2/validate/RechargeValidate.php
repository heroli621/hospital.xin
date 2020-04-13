<?php
/*
 * 充值卡 验证器
 * */
namespace app\admin2\validate;

use think\Validate;

class RechargeValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
        'goods_name'  => 'require|max:255',
        'money'  => 'number',
        'return_money'  => 'number',
        'return_date'  => 'number|max:2',
    ];
    
    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [
        'goods_name.require' => '充值卡名称必须填写',
        'goods_name.max'     => '充值卡名称过长',
        'money'     => '充值金额只能填写整数',
        'return_money'     => '返现金额只能填写整数',
        'return_date.number'  => '返现期数只能填写整数',
        'return_date.max'     => '返现期数过长',
    ];

    //验证场景
   /* protected $scene = [
        'edit'  =>  ['username','mail','des'],
        'save'  =>  ['username','psd','mail','des'],
    ];*/

    //自定义验证password
    protected function isEmpty($value,$data){
        if(empty($data['url'])){
            return true;
        }
        return false;
    }






}
