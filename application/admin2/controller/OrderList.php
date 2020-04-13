<?php
/*
 * 订单管理
 * */
namespace app\admin2\controller;



use app\admin2\validate\DeliveryValidate;
use app\admin2\validate\ExpressValidate;
use app\model\ComboGoods;
use app\model\ComplimentaryGoods;
use app\model\Delivery;
use app\model\Express;
use app\model\MemberNursing;
use app\model\Orders;
use app\model\OrdersErr;
use app\model\OrdersGoods;
use app\model\OrdersShow;
use app\model\RechargeOrdersTemp;
use app\shop\controller\Wechat;
use think\Db;
use think\Exception;
use think\facade\Request;


class OrderList extends BaseUser
{
    //中间件
    protected $middleware = [
        'app\admin2\middleware\Auth' => [
            //无须权限验证
            'except' => ['uploadPic','_empty','expressSave','goods','deliverySave']
        ],
    ];

    /**********************订单管理***********************/

    //消费订单
    public function expense()
    {
        if(Request::isAjax()){
            $map=[['goods_type','=',1]];
            $listRow=input('limit');
            $where=input('where',[]);
            if(!empty($where)){
                //订单号筛选
                if(!empty($where['ono'])){
                    $map[]=['order_no','=',$where['ono']];
                }
                //支付方式筛选
                if($where['pay_type']){
                    $map[]=['pay_type','=',$where['pay_type']];
                }
                //会员筛选
                if(!empty($where['member'])){
                    $res=Db::name('member')->whereLike('nickname|phone|realname',"%{$where['member']}%")->column('id');
                    $map[]=$res?['member_id','in',$res]:['member_id','=',-1];
                }
                //日期筛选
                if(!empty($where['start_stop'])){
                    $ss=explode(" _ ",$where['start_stop']);
                    $map[]=['create_time','between time',$ss];
                }
                //状态筛选
                if($where['o_status']>-2){
                    $map[]=['status','=',$where['o_status']];
                }
                //渠道筛选
                if($where['o_type']>-1){
                    $map[]=['orders_type','=',$where['o_type']];
                }
            }

            //获取数据
            $data=OrdersShow::with(['member','user'])
                ->where($map)
                ->order('create_time desc')
                ->paginate($listRow);

            return json(['code'=>0,'msg'=>'','count'=>$data->total(),'data'=>$data->items()]);
        }else{

            return view('expense');
        }

    }

    //充值订单
    public function recharge()
    {
        if(Request::isAjax()){
            $map=[['goods_type','=',2]];
            $listRow=input('limit');
            $where=input('where',[]);
            if(!empty($where)){
                //订单号筛选
                if(!empty($where['ono'])){
                    $map[]=['order_no','=',$where['ono']];
                }
                //会员筛选
                if(!empty($where['member'])){
                    $res=Db::name('member')->whereLike('nickname|phone|realname',"%{$where['member']}%")->column('id');
                    $map[]=$res?['member_id','in',$res]:['member_id','=',-1];
                }
                //日期筛选
                if(!empty($where['start_stop'])){
                    $ss=explode(" _ ",$where['start_stop']);
                    $map[]=['create_time','between time',$ss];
                }
                //状态筛选
                if($where['o_status']>-2){
                    $map[]=['status','=',$where['o_status']];
                }
                //渠道筛选
                if($where['o_type']>-1){
                    $map[]=['orders_type','=',$where['o_type']];
                }
            }

            //获取数据
            $data=OrdersShow::with(['member','recharge','rm','rmt','user'])
                ->where($map)
                ->order('create_time desc')
                ->paginate($listRow);

            return json(['code'=>0,'msg'=>'','count'=>$data->total(),'data'=>$data->items()]);
        }else{

            return view('recharge');
        }

    }

    //查看商品列表
    public function goods(){
        $oid=input('id');
        //商品
        $data=OrdersGoods::with(['goods','activity','spec'])
            ->where('orders_id',$oid)
            ->select();
        //dump($data);
        $this->assign('list',$data);

        return view('goods');
    }

    //异常订单
    public function abnormal(){
        if(Request::isAjax()){
            $map=[];
            $listRow=input('limit');
            $where=input('where',[]);
            if(!empty($where)){
                //日期筛选
                if(!empty($where['start_stop'])){
                    $ss=explode(" _ ",$where['start_stop']);
                    $map[]=['create_time','between time',$ss];
                }
            }
            //获取数据
            $data=OrdersErr::with(['member','orders'])
                ->where($map)
                ->order('create_time desc')
                ->paginate($listRow);
            //$data=$data->toArray();
            //dump($data['data'][0]);exit;
            $res=['code'=>0,'msg'=>'','count'=>$data->total(),'data'=>$data->items()];
            return json($res);
        }else{

            return view('abnormal');
        }
    }

    //清除过期订单
    public function del(){
        if(Request::isAjax()){
            $this->err['code']=0;
            $oids=Db::name('orders')->where('status',-1)->column('id');
            if($oids){
                $rows=Db::name('orders')->delete($oids);
                if($rows){
                    Db::name('orders_goods')->whereIn('orders_id',$oids)->delete();
                }
            }

        }
        return json($this->err);
    }

    //签收超时订单
    public function signFor(){
        if(Request::isAjax()){
            $nt=time();
            $t=$nt-10*24*3600;
            $rows=Db::name('orders')
                ->where('status',2)
                ->whereTime('delivery_time','<',$t)
                ->whereTime('delivery_time','<>',0)
                ->update(['success_time'=>$nt,'status'=>99]);
            if($rows){
                $this->err['code']=0;
            }
        }
        return json($this->err);
    }

    //线下签收
    public function offlineSign(){
        if(Request::isAjax()){
            $oid=input('oid');
            $nt=time();
            $rows=Db::name('orders')
                ->where('id',$oid)
                ->update(['delivery_time'=>$nt,'success_time'=>$nt,'status'=>99]);
            if($rows){
                $this->err['code']=0;
                $this->err['msg']='线下签收完成';
            }
        }
        return json($this->err);
    }

    //审核人工添加的订单
    public function audit(){
        if(Request::isAjax()){
            $id=input('id',0);
            $type=input('type/d',0);
            try{
                if(!in_array($type,[5,7])){
                    throw new Exception('无效的参数');
                }
                $t=$type==5?3:2;
                $s='';
                if($type==5){
                    //审核通过
                    $this->err=$this->auditPass($id);
                    $s='审核人工订单【'.$this->err['no'].'】成功';
                }
                if($type==7){
                    //无效订单
                    $this->err=$this->auditRefuse($id);
                    $s='删除无效人工订单【'.$this->err['no'].'】成功';
                }
                if($this->err['code']!=0){
                    throw new Exception($this->err['msg']);
                }
                //添加日志
                $this->addLog($t,$s);
            }catch (Exception $e){
                $this->err['msg']=$e->getMessage();
            }

            return json($this->err);
        }else{
            return $this->_empty();
        }
    }



    /**************************发货管理*************************/
    //发货信息列表
    public function delivery(){
        if(Request::isAjax()){
            $map=[];
            $listRow=input('limit');
            $where=input('where',[]);
            if(!empty($where['title'])){
                //订单号筛选
                if(!empty($where['ono'])){
                    $map[]=['order_no','=',$where['ono']];
                }
                //会员筛选
                if(!empty($where['member'])){
                    $res=Db::name('member')->whereLike('nickname|phone|realname',"%{$where['member']}%")->column('id');
                    $map[]=$res?['member_id','in',$res]:['member_id','=',-1];
                }
                //日期筛选
                if(!empty($where['start_stop'])){
                    $ss=explode(" _ ",$where['start_stop']);
                    $map[]=['create_time','between time',$ss];
                }
            }

            //获取数据
            $data=Delivery::with(['member'])->where($map)->order('create_time desc')->paginate($listRow);

            return json(['code'=>0,'msg'=>'','count'=>$data->total(),'data'=>$data->items()]);
        }else{
            return view('delivery');
        }
    }

    //添加发货信息页
    public function deliveryGoods(){
        $id=input('id');
        $this->assign('oid',$id);
        //物流公司
        $express=Db::name('express')->field('id,express_name')->order('create_time desc')->select();
        $this->assign('express',$express);

        return view('deliver_goods');
    }

    //保存发货信息
    public function deliverySave(){
        $data=input('post.');
        $express_key=$this->getSiteConfig('express_key');
        try{
            //验证规则
            $validate=new DeliveryValidate();
            if(!$validate->check($data)){
                throw new Exception($validate->getError());
            }
            $oinfo=Orders::field('id,order_no,member_id,consignee,phone,province,city,region,specific')->find($data['oid']);
            $save_data=[
                'delivery_no'=>$data['delivery_no'],
                'express_id'=>$data['express_id'],
                'express_no'=>$data['express_no'],
                'com'=>Db::name('express')->where('id',$data['express_id'])->value('com'),
                'order_id'=>$oinfo['id'],
                'member_id'=>$oinfo['member_id'],
                'order_no'=>$oinfo['order_no'],
                'realname'=>$oinfo['consignee'],
                'phone'=>$oinfo['phone'],
                'province'=>$oinfo['province'],
                'city'=>$oinfo['city'],
                'region'=>$oinfo['region'],
                'address'=>$oinfo['specific']
            ];
            //添加数据
            $res=Delivery::create($save_data);
            if(!$res){
                throw new Exception('网络延迟，稍后再试');
            }

            //更改订单状态
            $oinfo->status=2;
            $oinfo->delivery_id=$res->id;
            $oinfo->delivery_time=time();
            $oinfo->save();
            //订阅快递
            if(!empty($data['express_no']) && !empty($express_key)){
                $express=new ExpressApi($express_key);
                $express->poll($save_data);
            }

            $this->err['code']=0;
            $this->err['msg']="订单【{$save_data['order_no']}】".'发货成功！';
            //添加日志
            $this->addLog(1,"订单发货【{$save_data['order_no']}】");
        }catch (Exception $e){
            $this->err['msg']=$e->getMessage();
        }

        return json($this->err);
    }


    /**************************物流公司*************************/

    //列表
    public function express(){
        if(Request::isAjax()){

            $data=Express::order('create_time desc')->select();

            return json(['code'=>0,'msg'=>'','count'=>count($data),'data'=>$data]);
        }else{
            return view('express');
        }
    }

    //添加页
    public function expressEdit(){
        $id=input('id',0);
        if($id){
            $info=Express::find($id);
            $this->assign('info',$info);
        }

        return view('express_edit');
    }

    //保存
    public function expressSave(){
        $data=input('post.');
        try{
            //验证规则
            $validate=new ExpressValidate();
            if(!$validate->check($data)){
                throw new Exception($validate->getError());
            }

            if(!empty($data['id'])){
                $info=Express::find($data['id']);
                if(!$info){
                    throw new Exception('参数错误，文章已被删除');
                }
                $type=3;
                $msg='修改物流公司';

                /****若图片更改，则删除原图****/
                //获取原图
                $old_pic=$info->cover;
                $res=$info->save($data);
                if($res && !empty($old_pic) && $old_pic!=$data['cover']){
                    @unlink('.'.$old_pic);
                }else{
                    throw new Exception('网络延迟，稍后再试');
                }
            }else{
                $type=1;
                $msg='添加物流公司';
                //添加数据
                $res=Express::create($data);
                if(!$res){
                    throw new Exception('网络延迟，稍后再试');
                }
            }

            $this->err['code']=0;
            $this->err['msg']="{$msg}【{$data['express_name']}】".'成功！';
            //添加日志
            $this->addLog($type,"{$msg}【{$data['express_name']}】");
        }catch (Exception $e){
            $this->err['msg']=$e->getMessage();
        }

        return json($this->err);
    }

    //删除
    public function expressDel(){
        $id=input('post.id');
        $info=Express::find($id);
        if(!$info){
            $this->err['msg']='未知的参数';
        }
        $name=$info['express_name'];
        $re=$info->delete();
        if($re){
            $this->err['code']=0;
            $this->err['msg']='删除物流公司【'.$name.'】';
            //添加日志
            $this->addLog(2,'删除物流公司【'.$name.'】');
        }else{
            $this->err['msg']='网络延迟，稍后重试';
        }

        return json($this->err);
    }



    /************************订单统计**************************/


    /**************************公共*************************/

    //人工订单审核通过处理
    protected function auditPass($oid){
        $res=['code'=>-1,'msg'=>'success'];
        $nt=time();
        $order_info=Db::name('orders')->find($oid);
        Db::startTrans();  //开启事务
        try{
            if(!$order_info){
                throw new Exception('订单不存在');
            }
            if(!$order_info['orders_type']){
                throw new Exception('订单不是手动添加无法审核');
            }
            if($order_info['audit']){
                throw new Exception('订单已完成审核无法再次审核');
            }
            $res['no']=$order_info['order_no'];
            //充值处理
            if($order_info['goods_type']==2){
                $now_return_money=0;
                //有返现则做返现处理
                if($order_info['sale_money'] > 0){
                    $card_info=Db::name('recharge_orders_temp')->where('orders_id',$oid)->find();
                    //总返回金额
                    $return_money=$card_info['return_money'];
                    //当前返还金额：return_type返回方式：0，立返；1，月返
                    $now_return_money=$card_info['return_date']>1?intval($return_money/$card_info['return_date']):$return_money;

                    //添加返还金额记录
                    $r_m_d=[
                        'order_no'=>$order_info['order_no'],
                        'member_id'=>$order_info['member_id'],
                        'recharge_goods_id'=>0,
                        'return_type'=>$card_info['return_date']>1?1:0,
                        'return_date'=>$card_info['return_date'],
                        'return_money'=>$card_info['return_money'],
                        'residue_money'=>$return_money-$now_return_money,
                        'residue_date'=>$card_info['return_date']-1,
                        'create_time'=>$nt,
                        'update_time'=>$nt,
                    ];
                    $r_m_id=Db::name('recharge_money')->insertGetId($r_m_d);
                    if(!$r_m_id){
                        throw new Exception('会员充值返现处理失败');
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
                        throw new Exception('会员充值当期返现处理失败');
                    }
                    //删除临时返现信息
                    Db::name('recharge_orders_temp')->where('orders_id',$oid)->delete();
                }
                //充值金额=支付金额+当前返还金额
                $money=$order_info['pay_money']+$now_return_money;
                if($order_info['balance']){
                    //计入余额
                    $re_up=Db::name('member')
                        ->where('id',$order_info['member_id'])
                        ->inc('recharge_money',$order_info['pay_money'])
                        ->inc('return_money',$now_return_money)
                        ->inc('balance',$money)
                        ->update();
                }else{
                    $re_up=Db::name('member')
                        ->where('id',$order_info['member_id'])
                        ->inc('recharge_money',$order_info['pay_money'])
                        ->inc('return_money',$now_return_money)
                        ->update();
                }

                if(!$re_up){
                    throw new Exception('会员充值处理失败');
                }

                //会员等级处理
                if(!$this->memberLevel($order_info['member_id'])){
                    throw new Exception('会员等级处理失败');
                }

            }else{
                //商品
                $goods_list=OrdersGoods::with('goods')
                    ->where('orders_id',$order_info['id'])
                    ->select();
                $nursing_goods=[];
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
                                    'buy_num'=>$val['buy_num']*$v['buy_num'],
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
                                    'buy_num'=>$val['buy_num']*$v['buy_num'],
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
                            'get_type'=>$v['complimentary'],
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
                //增加消费总额
                if(!Db::name('member')->where('id',$order_info['member_id'])->setinc('consumption_money',$order_info['pay_money'])){
                    throw new Exception('增加消费总额失败');
                }
                //扣除余额
                if($order_info['balance']){
                    //划拨余额
                    $balance=Db::name('member')
                        ->where('id',$order_info['member_id'])
                        ->value('balance');
                    if($balance < $order_info['pay_money']){
                        throw new Exception('会员余额不足，审核失败');
                    }
                    if(!Db::name('member')->where('id',$order_info['member_id'])->setDec('balance',$order_info['pay_money'])){
                        throw new Exception('扣除会员余额失败');
                    }

                    //模板消息推送
                    $wx=new Wechat();
                    /*
                     * 所需参数
                     *mid:5,[keyword1=>'消费时间',keyword2=>'消费金额'],url
                     * */
                    $url=url('/orders',[],true,true);
                    $arr=[date('Y年m月d日 H:i'),number_format($order_info['pay_money']/100,2).'元'];
                    $wx->setTemplate(5,$arr,$url);
                    $member_info=Db::name('member')->field('id,balance,integral,openid')->find($order_info['member_id']);
                    $wx->sendTemplate($member_info['openid']);
                }
            }
            //修改订单审核状态
            if(!Db::name('orders')->where('id',$order_info['id'])->setField('audit',session('user.uid'))) {
                throw new Exception('修改订单审核状态失败');
            }

            //事务提交
            Db::commit();
            $res['code']=0;
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
            $res['msg']=$e->getMessage();
        }
        return $res;
    }

    //人工订单无效
    protected function auditRefuse($oid){
        $res=['code'=>-1,'msg'=>'success'];
        $nt=time();
        $order_info=Orders::get($oid);
        Db::startTrans();  //开启事务
        try{
            if(!$order_info){
                throw new Exception('订单不存在');
            }
            if(!$order_info['orders_type']){
                throw new Exception('订单不是手动添加无法删除');
            }
            if($order_info['audit']){
                throw new Exception('订单已完成审核无法删除');
            }
            $res['no']=$order_info['order_no'];
            //充值处理
            if($order_info['goods_type']==2){
                //有返现则做返现处理
                if($order_info['sale_money'] > 0){
                    $re_row=Db::name('recharge_orders_temp')->where('orders_id',$oid)->delete();
                    if(!$re_row){
                        throw new Exception('删除临时返现信息失败');
                    }
                }
            }else{
                //商品
                $goods_row=Db::name('orders_goods')
                    ->where('orders_id',$order_info['id'])
                    ->delete();
                if(!$goods_row){
                    throw new Exception('删除订单商品失败');
                }

            }
            //删除人工订单
            if(!$order_info->delete()) {
                throw new Exception('删除人工订单失败');
            }
            //事务提交
            Db::commit();
            $res['code']=0;
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
            $res['msg']=$e->getMessage();
        }
        return $res;
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

    //上传图片
    public function uploadPic(){
        // 获取表单上传文件 例如上传了001.jpg
        $files = request()->file('images');
        if(!$files){
            $this->err['msg']='文件大小超出服务器限制';
            return json($this->err);
        }
        //大小限制
        $size=$this->getSiteConfig('image_upload_size');
        //类型限制
        $ext=$this->getSiteConfig('image_upload_extension');

        if(is_array($files)){
            foreach ($files as $file){
                // 移动到框架网站根目录/uploads/ 目录下
                $info = $file->validate(['size'=>$size*1024*1024,'ext'=>$ext])->move( './uploads/express');
                if($info){
                    // 成功上传后 获取上传信息
                    // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
                    //dump($info);
                    $pname=str_replace('./','/',$info->getPathName());
                    $this->err['code']=0;
                    $this->err['data'][]=str_replace('\\','/',$pname);

                }else{
                    // 上传失败获取错误信息
                    $this->err['msg']=$file->getError();
                }
            }

        }else{
            // 移动到框架网站根目录/uploads/ 目录下
            $info = $files->validate(['size'=>$size*1024*1024,'ext'=>$ext])->move( './uploads/goods');
            if($info){
                // 成功上传后 获取上传信息
                // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
                //dump($info);
                $pname=str_replace('./','/',$info->getPathName());
                $this->err['code']=0;
                $this->err['data']=str_replace('\\','/',$pname);

            }else{
                // 上传失败获取错误信息
                $this->err['msg']=$files->getError();
            }
        }

        return json($this->err);
    }


    public function _empty()
    {
        //把所有城市的操作解析到city方法
        return view('public/404');
    }

}
