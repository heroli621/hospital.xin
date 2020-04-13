<?php
/*
 * 订单(重显示模型)
 * */
namespace app\model;


use think\Db;
use think\Model;

class OrdersShow extends Model{

    //重定义表名
    protected $table="rc_orders";

    //主键
    protected $pk='id';

    //自动写入时间戳
    protected $autoWriteTimestamp = true;

    //订单金额
    public function getMoneyAttr($value){
        return number_format($value/100,2);
    }
    //实际支付金额
    public function getPayMoneyAttr($value){
        return number_format($value/100,2);
    }
    //优惠立减金额
    public function getSaleMoneyAttr($value){
        return number_format($value/100,2);
    }
    //快递金额
    public function getExpressFeeAttr($value){
        return number_format($value/100,2);
    }

    // 支付方式
    public function getPayTypeAttr($value){
        $new_val='其他';
        switch ($value){
            case 1:
                $new_val='微信';
                break;
            case 2:
                $new_val='余额';
                break;
            case 3:
                $new_val='支付宝';
                break;
            case 4:
                $new_val='网银';
                break;
            case 5:
                $new_val='现金';
                break;
            case 6:
                $new_val='积分';
                break;
        }
        return $new_val;
    }
    //支付时间
    public function getPayTimeAttr($value){
        return $value?date('Y-m-d H:i:s',$value):'';
    }
    //发货时间
    public function getSendTimeAttr($value){
        return $value?date('Y-m-d H:i:s',$value):'';
    }
    //交易完成时间
    public function getSuccessTimeAttr($value){
        return $value?date('Y-m-d H:i:s',$value):'';
    }
    //优惠券
    public function getCouponIdAttr($value,$data){
        if($data['discounts_type'] && $value){
            $info=Db::table('rc_coupon')
                ->alias('a')
                ->field('a.id,a.coupon_name')
                ->leftJoin("rc_coupon_get g","a.id=g.coupon_id and g.id=$value")
                ->find();
            return $info['coupon_name'];
        }
        return '';
    }

    //关联模型
    //会员
    public function member(){
        return $this->belongsTo('Member')->bind('nickname');
    }

    //订单商品
    public function goods(){
        return $this->hasMany('OrdersGoods');
    }

    //充值卡
    public function recharge(){
        return $this->belongsTo('RechargeGoods');
    }

    //充值返现订单
    public function rm(){
        return $this->hasOne('RechargeMoney','order_no','order_no');
    }

    //充值订单临时返现信息
    public function rmt(){
        return $this->hasOne('RechargeOrdersTemp','orders_id','id');
    }

    //审核人员
    public function user(){
        return $this->belongsTo('Admin','audit','id');
    }

}