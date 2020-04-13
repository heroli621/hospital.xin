<?php
/*
 * 支付
 * */
namespace app\shop\controller;

use app\model\ComboGoods;
use app\model\ComplimentaryGoods;
use app\model\GoodsShop;
use app\model\GoodsSpec;
use app\model\MemberNursing;
use app\model\OrdersGoods;
use think\Controller;
use think\Db;
use think\Exception;
use wxpay\example\JsApiPay;
use wxpay\example\WxPayConfig;
use wxpay\lib\WxPayApi;
use wxpay\lib\WxPayException;
use wxpay\lib\WxPayOrderQuery;
use wxpay\lib\WxPayUnifiedOrder;

class Pay extends Controller
{
    protected $appId='';
    protected $appSecret='';
    protected $wxAuth=[];
    protected $wxUserInfo=[];

    //支付
    public function jsapi($id=0)
    {
        $oid=input('post.order_id',$id);
        $order_info=Db::name('orders')->where('status',0)->find($oid);
        if(!$order_info){
            return view('public/err',['err_msg'=>'订单已处理，请重新下单','type'=>1]);
        }
        //无现金支付
        if(!$order_info['pay_money'] && $order_info['goods_type']==1){
            return $this->noPay($oid);
        }
        $nt=time();
        $jsApiParameters=[];
        //①、获取用户openid
        try{

            $tools = new JsApiPay();
            //$openId = $tools->GetOpenid();
            $openId=Db::name('member')->where('id',$order_info['member_id'])->value('openid');
            $notify_url=url('/notify',[],false,true);
            //②、统一下单
            $com_name=session('?config.com_name')?session('config.com_name'):' 
百莲凯女子SPA护肤馆';
            $input = new WxPayUnifiedOrder();
            $input->SetBody($com_name.'-微商城购物');
            //$input->SetAttach("test");
            $input->SetOut_trade_no($order_info['order_no']);//订单号
            $input->SetTotal_fee($order_info['pay_money']);
            $input->SetTime_start(date("YmdHis"));
            $input->SetTime_expire(date("YmdHis", $nt + 2*3600));
            $input->SetNotify_url($notify_url);
            //$input->SetGoods_tag("test");
            $input->SetTrade_type("JSAPI");
            $input->SetOpenid($openId);
            $config = new WxPayConfig();
            $order = WxPayApi::unifiedOrder($config, $input);
            //dump($order);exit;

            $jsApiParameters = $tools->GetJsApiParameters($order);

        }catch(WxPayException $e) {
            //添加异常订单记录
            $err_data=[
                'orders_id'=>$order_info['id'],
                'member_id'=>$order_info['member_id'],
                'des'=>$e->getMessage(),
                'create_time'=>$nt
            ];
            Db::name('orders_err')->insert($err_data);
        }

        $this->assign('jsApiParameters',$jsApiParameters);



        return view('wx');
    }

    //无现金（余额）支付接口
    public function noPay($id=0){
        $oid=input('order_id',$id);
        $order_info=Db::name('orders')->where('status',0)->find($oid);
        //验证订单
        if(!$order_info){
            return view('public/err',['err_msg'=>'订单已处理，请重新下单','type'=>1]);
        }
        //个人余额、积分
        $member_info=Db::name('member')->field('id,balance,integral,openid')->find($order_info['member_id']);
        //有支付金额
        if($order_info['pay_money'] > 0){
            if($member_info['balance'] < $order_info['pay_money'] || $order_info['goods_type']==2){
                //余额不足或充值订单跳转微信支付
                return $this->jsapi($oid);
            }
        }

        //订单商品
        $goods_list=OrdersGoods::with('goods')
            ->where('orders_id',$oid)
            ->select();


        //当前时间戳
        $nt=time();
        $is_send_template=false;
        //发起事务
        Db::startTrans();
        try{
            //扣除余额
            if($order_info['pay_money'] > 0){
                if(!Db::name('member')->where('id',$member_info['id'])->setDec('balance',$order_info['pay_money'])){
                    throw new Exception('扣除余额失败');
                }
                $is_send_template=true;
            }

            //扣除积分
            if($order_info['is_int']){
                if($order_info['integral'] > $member_info['integral']){
                    throw new Exception('您的积分不足');
                }
                if(!Db::name('member')->where('id',$member_info['id'])->setDec('integral',$order_info['integral'])){
                    throw new Exception('扣除积分失败');
                }
                //添加积分记录
                $integral_log=[
                    'member_id'=>$member_info['id'],
                    'integral'=>$order_info['integral'],
                    'type'=>2,
                    'des'=>'兑换商品',
                    'old_integral'=>$member_info['integral'],
                    'surplus_integral'=>$member_info['integral']-$order_info['integral'],
                    'create_time'=>$nt
                ];
                if(!Db::name('member_integral')->insert($integral_log)){
                    throw new Exception('添加积分记录失败');
                }
            }

            //统一支付完成处理
            $re=$this->ordersDispose($order_info,$goods_list,$nt);
            if($re['code']!==0){
                throw new Exception($re['msg']);
            }
            //支付方式
            $pay_type=$order_info['is_int']?6:2;
            if(!Db::name('orders')->where('id',$oid)->setField('pay_type',$pay_type)){
                throw new Exception('错误的支付方式');
            }
            //提交事务
            Db::commit();

            //模板消息推送
            if($is_send_template){
                $wx=new Wechat();
                /*
                 * 所需参数
                 *mid:5,[keyword1=>'消费时间',keyword2=>'消费金额'],url
                 * */
                $url=url('/orders',[],true,true);
                $arr=[date('Y年m月d日 H:i'),number_format($order_info['pay_money']/100,2).'元'];
                $wx->setTemplate(5,$arr,$url);
                $wx->sendTemplate($member_info['openid']);
            }
            $err_msg='恭喜您！您的订单成功支付！！！';
            $this->assign('url','/mine.html');
        }catch (Exception $e){
            //事务回退
            Db::rollback();
            //添加异常订单记录
            $err_data=[
                'orders_id'=>$order_info['id'],
                'member_id'=>$order_info['member_id'],
                'des'=>$e->getMessage(),
                'create_time'=>$nt
            ];
            Db::name('orders_err')->insert($err_data);
           $err_msg=$e->getMessage();
        }

        $this->assign('type',1);
        $this->assign('err_msg',$err_msg);


        return view('public/err');
    }

    //支付回调
    public function notify(){
        //返回微信接口初始数据WxPayNotifyResults
        $return_msg='Ok';
        $return_code='SUCCESS';
        $nt=time();
        try {
            //获取通知的数据返回数组
            $xml = file_get_contents("php://input");

            if (empty($xml)) {
                //如果没有数据，直接返回失败
                throw new WxPayException('no data');
            }
            $result=simplexml_load_string($xml);
            //$result = json_decode($xml,true);
            //商户业务逻辑
            if($result->return_code=='SUCCESS' && $result->transaction_id){

                //查询订单
                $query_order_res=$this->queryOrder($result->transaction_id);
                if($query_order_res){
                    //订单存在且支付
                    $order_no=$result->out_trade_no;
                    //订单信息
                    $order_info=Db::name('orders')
                        ->where('status','<',1)
                        ->where('order_no',$order_no)
                        ->find();

                    if(!$order_info){
                        throw new WxPayException('order error');
                    }
                    //充值处理
                    if($order_info['goods_type']==2){
                        Db::startTrans();
                        try{
                        //充值卡id
                        $card_id=$order_info['recharge_goods_id'];
                        //充值卡详情
                        $card_info=Db::name('recharge_goods')->find($card_id);
                        //总返回金额
                        $return_money=$card_info['return_money'];
                        $now_return_money=$return_money;
                        //有返现则做返现处理
                        if($return_money > 0){
                            //当前返还金额：return_type返回方式：0，立返；1，月返
                            $now_return_money=$card_info['return_type']==0?$return_money:intval($return_money/$card_info['return_date']);

                            //添加返还金额记录
                            $r_m_d=[
                                'order_no'=>$order_info['order_no'],
                                'member_id'=>$order_info['member_id'],
                                'recharge_goods_id'=>$card_info['id'],
                                'return_type'=>$card_info['return_type'],
                                'return_date'=>$card_info['return_date'],
                                'return_money'=>$card_info['return_money'],
                                'residue_money'=>$return_money-$now_return_money,
                                'residue_date'=>$card_info['return_date']-1,
                                'create_time'=>$nt,
                                'update_time'=>$nt,
                            ];
                            $r_m_id=Db::name('recharge_money')->insertGetId($r_m_d);
                            if(!$r_m_id){
                                throw new WxPayException('会员充值返现处理失败');
                            }
                            //添加当期返还记录
                            $r_r_d=[
                                'member_id'=>$order_info['member_id'],
                                'recharge_money_id'=>$r_m_id,
                                'return_money'=>$now_return_money,
                                'return_now'=>1,
                                'return_date'=>$card_info['return_date'],
                                'create_time'=>$nt,
                            ];
                            if(!Db::name('recharge_return')->insert($r_r_d)){
                                throw new WxPayException('会员充值当期返现处理失败');
                            }

                        }
                        //充值金额=支付金额+返还$card_info['return_type']金额
                        $money=$order_info['pay_money']+$now_return_money;
                        $re_up=Db::name('member')
                            ->where('id',$order_info['member_id'])
                            ->inc('recharge_money',$order_info['pay_money'])
                            ->inc('return_money',$now_return_money)
                            ->inc('balance',$money)
                            ->update();
                        if(!$re_up){
                            throw new WxPayException('会员充值处理失败');
                        }

                        //会员等级处理
                        if(!$this->memberLevel($order_info['member_id'])){
                            throw new WxPayException('会员等级处理失败');
                        }
                        $msg_content='恭喜您，充值成功！充值'.($order_info['pay_money']/100).'元';
                        if($return_money >0){
                            $msg_content.='，获得返现总额：'.($return_money/100).'元,当期返现：'.($now_return_money/100).'元，共 '.$card_info['return_date'].' 期。';
                        }
                        $msg_data[]=[
                            'msg_classify'=>1,
                            'member_id'=>$order_info['member_id'],
                            'content'=>$msg_content,
                            'create_time'=>$nt
                        ];

                        //修改订单状态
                        if(!Db::name('orders')->where('id',$order_info['id'])->where('status','<',1)->update(['status'=>99,'pay_time'=>$nt,'success_time'=>$nt])) {
                            throw new WxPayException('修改订单状态失败');
                        }

                        //发送消息
                        if(!Db::name('message')->insertAll($msg_data)){
                            throw new Exception('消息发送失败');
                        }
                        //事务提交
                        Db::commit();
                        }catch (Exception $e){
                            //事务回退
                            Db::rollback();
                            //添加异常订单记录
                            $err_data=[
                                'orders_id'=>$order_info['id'],
                                'member_id'=>$order_info['member_id'],
                                'des'=>$e->getMessage(),
                                'create_time'=>$nt
                            ];
                            Db::name('orders_err')->insert($err_data);
                            $return_code='ERROR';
                            $return_msg='sorry';
                        }

                    }else{
                        //订单商品
                        $goods_list=OrdersGoods::with('goods')
                            ->where('orders_id',$order_info['id'])
                            ->select();

                        //统一支付完成处理
                        $re=$this->ordersDispose($order_info,$goods_list,$nt);
                        if($re['code']!==0){
                            //添加异常订单记录
                            $err_data=[
                                'orders_id'=>$order_info['id'],
                                'member_id'=>$order_info['member_id'],
                                'des'=>$re['msg'],
                                'create_time'=>$nt
                            ];
                            Db::name('orders_err')->insert($err_data);
                            $return_code='ERROR';
                            $return_msg='sorry';
                        }
                    }
                    /*****************添加分销记录与佣金*********************/
                    //分销信息
                    $distribution=Db::name('member_distribution')->where('member_id',$order_info['member_id'])->find();
                    if(!$distribution){
                        $distribution=[
                            'up_one_id'=>0,
                            'up_two_id'=>0,
                            'member_id'=>$order_info['member_id'],
                        ];
                    }
                    //订单金额
                    $order_money=$order_info['pay_money'];
                    if($order_money > 0 && ($distribution['up_one_id'] || $distribution['up_two_id'])){
                        $distribution_data=[];
                        $dis_data=[
                            'child_id'=>$order_info['member_id'],
                            'order_no'=>$order_info['order_no'],
                            'order_money'=>$order_money,
                            'create_time'=>$nt
                        ];
                        if($distribution['up_one_id']){
                            //分成比例
                            $brokerage_ratio=$this->getSiteConfig('one_level');
                            //佣金
                            $dis_data['brokerage']=floor($order_money*$brokerage_ratio/100);
                            $dis_data['member_id']=$distribution['up_one_id'];
                            $dis_data['level']=1;
                            $dis_data['ratio']=$brokerage_ratio;
                            $distribution_data[]=$dis_data;
                            $msg_data[]=[
                                'msg_classify'=>3,
                                'member_id'=>$distribution['up_one_id'],
                                'content'=>'恭喜您，获得：'.($dis_data['brokerage']/100).'元奖励金',
                                'create_time'=>$nt
                            ];
                            //添加收益记录
                            $earnings_data[]=[
                                'member_id'=>$distribution['up_one_id'],
                                'money'=>$dis_data['brokerage'],
                                'type'=>1,
                                'des'=>'一级粉丝佣金收益',
                                'create_time'=>$nt,
                            ];
                            //添加佣金
                            if(!Db::name('member')->where('id',$distribution['up_one_id'])->setInc('earnings',$dis_data['brokerage'])){
                                throw new Exception('一级分销佣金处理失败');
                            }
                        }
                        if($distribution['up_two_id']){
                            //分成比例
                            $brokerage_ratio=$this->getSiteConfig('two_level');
                            //佣金
                            $dis_data['brokerage']=floor($order_money*$brokerage_ratio/100);
                            $dis_data['member_id']=$distribution['up_two_id'];
                            $dis_data['level']=2;
                            $dis_data['ratio']=$brokerage_ratio;
                            $distribution_data[]=$dis_data;
                            $msg_data[]=[
                                'msg_classify'=>3,
                                'member_id'=>$distribution['up_two_id'],
                                'content'=>'恭喜您，获得：'.($dis_data['brokerage']/100).'元奖励金',
                                'create_time'=>$nt
                            ];
                            //添加收益记录
                            $earnings_data[]=[
                                'member_id'=>$distribution['up_two_id'],
                                'money'=>$dis_data['brokerage'],
                                'type'=>1,
                                'des'=>'二级粉丝佣金收益',
                                'create_time'=>$nt,
                            ];
                            //添加佣金
                            if(!Db::name('member')->where('id',$distribution['up_two_id'])->setInc('earnings',$dis_data['brokerage'])){
                                throw new Exception('二级分销佣金处理失败');
                            }
                        }
                        if(!Db::name('brokerage_logs')->insertAll($distribution_data)){
                            throw new Exception('分销记录处理失败');
                        }
                        if(!Db::name('earnings_logs')->insertAll($earnings_data)){
                            throw new Exception('收益记录处理失败');
                        }
                    }
                    //模板消息推送
                    $wx=new Wechat();
                    /*
                     * 所需参数
                     *mid:1,[消费金额：keyword2,订单号码：keyword3,推送时间：keyword4],url
                     * */
                    $url=url('/orders',[],true,true);
                    $arr=[number_format($order_info['pay_money']/100,2).'元',$order_info['order_no'],date('Y年m月d日 H:i')];
                    $wx->setTemplate(1,$arr,$url);
                    $openid=Db::name('member')->where('id',$order_info['member_id'])->value('openid');
                    $wx->sendTemplate($openid);
                }else{
                    throw new WxPayException('query error');
                }
            }else{
                throw new WxPayException('data error');
            }
        } catch (WxPayException $e){
            $return_msg = $e->getMessage();
            //添加异常订单记录
            $err_data=[
                'orders_id'=>0,
                'member_id'=>0,
                'des'=>$return_msg,
                'create_time'=>$nt
            ];
            Db::name('orders_err')->insert($err_data);
            $return_code='ERROR';
        }
        //返回success给微信支付接口
        $re_xml=xml(['return_code'=>$return_code,'return_msg'=>$return_msg]);
        return $re_xml;
    }

    //邀请
    public function invite(){
        $mid=input('mid');
        echo $mid;
        $uri=urlencode(url("/register",[],true,true));
        $appId=$this->getSiteConfig('app_id');
        $url= 'https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$appId.'&redirect_uri='.$uri.'&response_type=code&scope=snsapi_userinfo&state=m'.$mid.'#wechat_redirect';
        return redirect($url);
    }

    //注册
    public function register(){
        $up_member_id=substr(input('state'),1);
        $com_tel=$this->getSiteConfig('com_tel');
        $code=input('code');
        $wx=Db::name('wechat')->find(1);
        $this->appId=$wx['app_id'];
        $this->appSecret=$wx['app_secret'];
        $nt=time();
        $re=$this->getAccessToken($code);
        if($re !== true){
            //dump($this->wxAuth);
            $this->assign('errmsg',json_encode($this->wxAuth));
            return view("public/errmsg");
        }
        $member=Db::name('member')
            ->field('id mid,nickname,realname,phone,openid,headimg,status')
            ->where('openid',$this->wxAuth['openid'])
            ->find();
        if($member){
            //登录
            if($member['status']){
                session('isLogin',1);
                session('member',$member);
            }else{
                $this->assign('errmsg','抱歉您的账户已被冻结，若有问题可联系：'.$com_tel);
                return view("public/errmsg");
            }
        }else{
            //注册
            $wx_user=$this->getUserInfo();
            if($wx_user !== true){
                //dump($wx_user);
                $this->assign('errmsg',$re);
                return view("public/errmsg");
            }
            //组装数据
            $member_data=[
                'openid'=>$this->wxUserInfo['openid'],
                'nickname'=>$this->wxUserInfo['nickname'],
                'realname'=>$this->wxUserInfo['nickname'],
                'phone'=>'',
                'address'=>$this->wxUserInfo['province'].$this->wxUserInfo['city'],
                'sex'=>$this->wxUserInfo['sex'],
                'headimg'=>$this->wxUserInfo['headimgurl'],
                'safe'=>substr(md5($this->wxUserInfo['openid'].$nt),rand(0,25),6),
                'create_time'=>$nt,
            ];
            //注册信息
            $mid=Db::name('member')->insertGetId($member_data);
            if($mid){
                $member_data['mid']=$mid;
                session('isLogin',1);
                session('member',$member_data);
            }
            //分销关系
            $res=Db::name('member_distribution')->where('member_id',$mid)->find();
            if(!$res){
                if($up_member_id){
                    $staff=Db::name('member')->where('id',$up_member_id)->value('staff');
                    if(!$staff){
                        $two_distribution_id=Db::name('member_distribution')
                            ->where('member_id',$up_member_id)
                            ->value('up_one_id');
                        $save=[
                            'member_id'=>$mid,
                            'up_one_id'=>$up_member_id,
                            'up_two_id'=>$two_distribution_id,
                            'create_time'=>$nt
                        ];
                    }else{
                        Db::name('member')->where('id',$mid)->setField('staff_id',$up_member_id);
                        $save=['member_id'=>$mid,'create_time'=>$nt];
                    }
                }else{
                    $save=['member_id'=>$mid,'create_time'=>$nt];

                }
                Db::name('member_distribution')->insert($save);
            }

        }
        return redirect('/');
    }

    //商品预览
    public function detail(){
        $back_url=input('back_url','/');
        $this->assign('back_url',$back_url);

        $id=input('id',0);

        if(!$id)return redirect('/');

        $nt=time();
        //商品详情
        $info=GoodsShop::with(['content','activity','level'])
            ->find($id)->toArray();

        //dump($info);exit;
        $this->assign('info',$info);

        $mid=session('member.mid');




        $sale_price=$info['price'];

        $this->assign('sale_price',$sale_price);

        //销量
        $sale_num=Db::name('orders_goods')
            ->where('goods_id',$id)
            ->sum('buy_num');
        $this->assign('sale_num',$sale_num);

        //优惠券
        $this->assign('coupons',[]);
        //dump($coupons);exit;

        //商品规格
        $goods_spec=GoodsSpec::where('goods_id',$id)->select();

        $this->assign('goods_spec',$goods_spec);

        //评价
        $this->assign('evaluate',[]);

        //评分
        $grade=Db::name('goods_evaluate')->where('goods_id',$id)->avg('grade');
        $grade=$grade?$grade:5;
        $this->assign('grade',number_format($grade,1));

        //评价总数
        $evaluate_num=Db::name('goods_evaluate')
            ->where('goods_id',$id)->count();
        $this->assign('evaluate_num',$evaluate_num);

        //会员等级
        $this->assign('member_level',0);

        //已购商品数量
        $this->assign('buy_goods_num',0);

        return view('show');
    }

    /*
     *支付成功统一处理
     *
     * $order_info->订单信息
     * $goods_list->订单商品信息
     * $distribution->分销信息
     * $nt->当前时间戳
     * */
    protected function ordersDispose($order_info,$goods_list,$nt){
        $res=['code'=>-1,'msg'=>'success'];
        //发起事务
        Db::startTrans();
        try{
            $msg_data=[];$nursing_goods=[];
            //库存处理
            foreach ($goods_list as $v){
                //库存处理
                $this->stockProcessing($v['goods_id'],$v['buy_num'],$v['goods_spec_id']);
                //赠品处理
                if($v['goods']['complimentary']){
                    $comps=ComplimentaryGoods::with('goods')
                        ->where('goods_id',$v['goods_id'])
                        ->select();
                    //处理库存
                    foreach ($comps as $val){
                        $this->stockProcessing($val['child_id'],$val['amount']*$v['buy_num']);
                        if($val['goods']['nursing_num']){
                            //护理产品
                            $nursing_num_temp=$val['goods']['nursing_num']*$val['amount']*$v['buy_num'];
                            $nursing_goods[]=[
                                'order_id'=>$order_info['id'],
                                'member_id'=>$order_info['member_id'],
                                'goods_id'=>$val['goods']['id'],
                                'nursing_num'=>$val['goods']['nursing_num'],
                                'buy_num'=>$val['amount']*$v['buy_num'],
                                'amount'=>$nursing_num_temp,
                                'residue_degree'=>$nursing_num_temp,
                                'get_type'=>1,
                                'experience'=>$v['goods']['experience']
                            ];
                        }
                    }
                }
                //套餐产品处理
                if($v['goods']['goods_type']){
                    $combs=ComboGoods::with('goods')
                        ->where('goods_id',$v['goods_id'])
                        ->select();
                    //处理库存
                    foreach ($combs as $val){
                        $this->stockProcessing($val['child_id'],$val['amount']*$v['buy_num']);
                        if($val['goods']['nursing_num']){
                            //护理产品
                            $nursing_num_temc=$val['goods']['nursing_num']*$val['amount']*$v['buy_num'];
                            $nursing_goods[]=[
                                'order_id'=>$order_info['id'],
                                'member_id'=>$order_info['member_id'],
                                'goods_id'=>$val['goods']['id'],
                                'nursing_num'=>$val['goods']['nursing_num'],
                                'buy_num'=>$val['amount']*$v['buy_num'],
                                'amount'=>$nursing_num_temc,
                                'residue_degree'=>$nursing_num_temc,
                                'experience'=>$v['goods']['experience']
                            ];
                        }
                    }
                }
                if($v['goods']['nursing_num']){
                    //护理产品
                    $nursing_goods[]=[
                        'order_id'=>$order_info['id'],
                        'member_id'=>$order_info['member_id'],
                        'goods_id'=>$v['goods']['id'],
                        'nursing_num'=>$v['goods']['nursing_num'],
                        'buy_num'=>$v['buy_num'],
                        'amount'=>$v['goods']['nursing_num']*$v['buy_num'],
                        'residue_degree'=>$v['goods']['nursing_num']*$v['buy_num'],
                        'experience'=>$v['goods']['experience']
                    ];
                }
            }
            //添加护理产品记录
            if(count($nursing_goods)){
                $nursing=new MemberNursing();
                $r=$nursing->saveAll($nursing_goods);
                if(!$r){
                    throw new Exception('护理商品处理失败');
                }
            }

            //发送用户消息
            $msg_data[]=[
                'msg_classify'=>2,
                'member_id'=>$order_info['member_id'],
                'content'=>'恭喜您，订单号：'.$order_info['order_no'].' 支付成功！',
                'create_time'=>$nt
            ];
            if(!Db::name('message')->insertAll($msg_data)){
                throw new Exception('系统消息发送失败');
            }
            //优惠券使用记录
            if($order_info['discounts_type'] && $order_info['coupon_id']){
                $coupon_id=Db::name('coupon_get')->where('id',$order_info['coupon_id'])->value('coupon_id');
                $coupon_use=[
                    'coupon_get_id'=>$order_info['coupon_id'],
                    'member_id'=>$order_info['member_id'],
                    'coupon_id'=>$coupon_id,
                    'orders_id'=>$order_info['id'],
                    'create_time'=>$nt,
                ];
                if(!Db::name('coupon_use')->insert($coupon_use)){
                    throw new Exception('扣除优惠券失败');
                }
            }

            //修改订单状态
            if(!Db::name('orders')->where('id',$order_info['id'])->where('status','<',1)->update(['status'=>1,'pay_time'=>$nt])){
                throw new Exception('修改订单状态失败');
            }

            //增加消费总额
            if(!Db::name('member')->where('id',$order_info['member_id'])->setinc('consumption_money',$order_info['pay_money'])){
                throw new Exception('增加消费总额失败');
            }
            //提交事务
            Db::commit();
            $res['code']=0;
        }catch (Exception $ex){
            //事务回退
            Db::rollback();
            $res['msg']=$ex->getMessage();
        }

        return $res;
    }
    //查询订单
    public function queryOrder($transaction_id)
    {
        $input = new WxPayOrderQuery();
        $input->SetTransaction_id($transaction_id);

        $config = new WxPayConfig();
        $result = WxPayApi::orderQuery($config, $input);
        if(array_key_exists("return_code", $result)
            && array_key_exists("result_code", $result)
            && $result["return_code"] == "SUCCESS"
            && $result["result_code"] == "SUCCESS")
        {
            return true;
        }
        return false;

    }

    //获取指定网站配置
    protected function getSiteConfig($var_name){
        if(empty($var_name)){
            return false;
        }
        return Db::name('config')->where('varname',$var_name)->value('value');
    }

    protected function getAccessToken($code){

        $url='https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$this->appId.'&secret='.$this->appSecret.'&code='.$code.'&grant_type=authorization_code';
        $res=json_decode(file_get_contents($url),true);
        if(!isset($res['errcode'])){
            $this->wxAuth=$res;
            return true;
        }else{
            return $res['errmsg'];
        }
    }

    protected function getUserInfo(){
        $url='https://api.weixin.qq.com/sns/userinfo?access_token='.$this->wxAuth['access_token'].'&openid='.$this->wxAuth['openid'].'&lang=zh_CN';
        $res=json_decode(file_get_contents($url),true);
        if(!isset($res['errcode'])){
            $this->wxUserInfo=$res;
            return true;
        }else{
            return $res['errmsg'];
        }
    }

    //会员等级处理
    public function memberLevel($mid){
        //会员等级需求
        $level=Db::name('level')->order('level_id desc')->column('level_money','level_id');
        //累充金额、已有等级
        $member=Db::name('member')->field('level_id,recharge_money')->find($mid);
        //新等级
        $new_level=$member['level_id'];
        foreach ($level as $k=>$v){
            if($member['recharge_money'] > $v){
                $new_level=$k;
                break;
            }
        }
        if($new_level != $member['level_id']){
            $nt=time();
            $res=Db::name('member')
                ->where('id',$mid)
                ->update(['level_id'=>$new_level,'update_time'=>$nt]);
            $save=[
                'member_id'=>$mid,
                'old_level'=>$member['level_id'],
                'new_level'=>$new_level,
                'recharge_money'=>$member['recharge_money'],
                'create_time'=>$nt
            ];
            Db::name('level_up')->insert($save);
            return $res?true:false;
        }
        return true;
    }

    //库存处理
    protected function stockProcessing($gid,$n,$sid=0){
        //总库存
        Db::name('goods')->where('id',$gid)->setDec('inventory',$n);
        //规格库存
        if($sid){
           Db::name('goods_spec')->where('id',$sid)->setDec('inventory',$n);
        }
    }




}
