<?php
/*
 * 收货地址 验证器
 * */
namespace app\shop\validate;

use think\Validate;

class AddressValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
        'realname'  => 'require',
        'phone'  => 'require|isPhone',
        'area'  => 'require',
        'info'  => 'require',
    ];

	//自定义验证
	protected function isPhone($value){
       if(preg_match('/^1[345678][0-9]{9}$/',$value) || preg_match('/^0[0-9]{2,3}\-[0-9]{7,8}$/',$value)){
            return true;
        }
        return false;
    }
    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [
        'realname' => '请输入收货人',
        'phone'  => '请输入正确的联系电话',
        'area'  => '所在地区必须选择',
        'info'  => '请输入详细地址',
    ];









}
