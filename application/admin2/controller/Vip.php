<?php
/*
 * 订单管理
 * */
namespace app\admin2\controller;


use app\admin2\validate\LevelValidate;
use app\admin2\validate\MessageSendValidate;
use app\admin2\validate\RechargeValidate;
use app\admin2\validate\ServersValidate;
use app\admin2\validate\SignInIntegralValidate;
use app\model\BrokerageLogs;
use app\model\EarningsLogs;
use app\model\Goods;
use app\model\Level;
use app\model\Member;
use app\model\MemberHealthadmin2;
use app\model\MemberIntegral;
use app\model\MemberNursing;
use app\model\MemberSubscribe;
use app\model\MemberSubscribeGoods;
use app\model\MemberWithdraw;
use app\model\Orders;
use app\model\RechargeGoods;
use app\model\RechargeMoney;
use app\model\RechargeReturn;
use app\model\Servers;
use app\model\SignInIntegral;
use app\model\Staff;
use app\shop\controller\Wechat;
use think\Db;
use think\Exception;
use think\facade\Request;


class Vip extends BaseUser
{
    //中间件
    protected $middleware = [
        'app\admin2\middleware\Auth' => [
            //无须权限验证
            'except' => ['_empty','uploadPic','levelSave','health','addCombo','addComplimentary','subscribeInfo','deleteSub','nursing','nursingLog','delNursing']
        ],
    ];

    /**********************会员管理***********************/

    //会员列表
    public function index()
    {
        if(Request::isAjax()){
            $map=[['staff','=',0]];
            $listRow=input('limit');
            $where=input('where',[]);
            if(!empty($where)){
                if(!empty($where['member'])){
                    $map[]=['nickname|phone|realname','like',"%{$where['member']}%"];
                }
                if(!empty($where['staff'])){
                    $map[]=['staff','=',1];
                    $map[]=['nickname|phone|realname','like',"%{$where['staff']}%"];
                }
                /*if($where['sm']){
                    $w=$where['sm']==2?1:0;
                    $map[]=['staff','=',$w];
                }*/
                if($where['level']){
                    $map[]=['level_id','=',$where['level']];
                }
            }
            //获取数据
            $data=Member::with(['level','staff'])
                ->where($map)
                ->order('consumption_money desc,recharge_money desc')
                ->paginate($listRow);

            return json(['code'=>0,'msg'=>'','count'=>$data->total(),'data'=>$data->items()]);
        }else{
            //获取会员等级
            $member_level=Db::name('level')
                ->field('level_id,level_name')
                ->order('level_id asc')
                ->select();
            $this->assign('member_level',$member_level);

            return view('index');
        }

    }

    //会员护理产品
    public function nursing(){
            $mid=input('id');
            //获取会员护理产品
            $list=MemberNursing::with(['gname','orders','member'])
                ->where('member_id',$mid)
                ->order('residue_degree desc,create_time desc')
                ->select();
            $this->assign('list',$list);

            return view('nursing');

    }
    //会员护理产品
    public function nursingLog(){
        $nid=input('id');
        //获取会员护理记录
        $list=MemberSubscribeGoods::with(['nursing.gname','subscribe','staff'])
            ->where('member_nursing_id',$nid)
            ->order('create_time desc')
            ->select();
        $this->assign('list',$list);

        return view('nursing_log');

    }

    //会员冻结与解冻
    public function unAndFreeze(){
        $id=input('post.id');

        $info=Member::find($id);
        if(!$info){
            $this->err['msg']='未知参数';
            return json($this->err);
        }
        $new_val=$info['status']==1?0:1;
        $info['status']=$new_val;
        $row=$info->save();
        if($row){
            $this->err['code']=0;
            $this->err['data']=$new_val;
            //添加日志
            $this->addLog(3,($new_val?'解冻':'冻结').'会员【'.$info['nickname'].'】');
        }else{
            $this->err['msg']='网络延迟，稍后再试';
        }

        return json($this->err);
    }

     //会员添加到员工
    public function addStaff(){
        $id=input('post.id');

        $info=Member::find($id);
        if(!$info){
            $this->err['msg']='未知参数';
            return json($this->err);
        }
        $new_val=$info['staff']==1?0:1;
        $info['staff']=$new_val;
        $row=$info->save();
        if($row){
            $this->err['code']=0;
            //添加员工记录
            $save=[
                'member_id'=>$id
            ];
            Staff::create($save);
            //添加日志
            $this->addLog(3,'会员【'.$info['nickname'].'】');
        }else{
            $this->err['msg']='网络延迟，稍后再试';
        }

        return json($this->err);
    }

    //会员信息修改
    public function memberEdit(){
        $mid=input('id');
        if(Request::isAjax()){
            $data=input('post.');
            $data=array_filter($data);
            if(isset($data['birthday'])){
                $times=strtotime($data['birthday']);
                $year=date('Y',$times);
                //计算年龄
                $data['age']=date('Y')-$year;
                //计算星座
                $data['constellation']=$this->getConstellation($times);
                $data['birthday']=$times;
            }
            try{
                $m=Member::find($mid);
                if(!$m){
                    throw new Exception('参数错误');
                }
                $m_name=$m->nickname;
                $m->save($data);
                $this->err=[
                    'code'=>0,
                    'msg'=>'资料保存成功'
                ];
                //添加日志
                $this->addLog(3,"修改会员【{$m_name}】基本资料");
            }catch (Exception $e){
                $this->err['msg']=$e->getMessage();
            }

            return json($this->err);
        }else{
            $info=Member::find($mid);
            $this->assign('info',$info->getData());
            return view('member_info');
        }
    }

    //分配员工
    public function assignStaff(){
        $mid=input('id',0);
        if(Request::isAjax()){
            $staff_id=input('staff_id',0);
            try{
                $staff=Member::find($staff_id);
                if(!$staff){
                    throw new Exception('必须选择一个员工');
                }
                $s_name=$staff->realname;

                $member=Member::find($mid);
                if(!$member){
                    throw new Exception('会员参数错误');
                }
                $m_name=$member->nickname;
                $member->staff_id=$staff_id;
                $re=$member->save();
                if(!$re){
                    throw new Exception('网络延迟，稍后再试');
                }
                $this->err['code']=0;
                $this->err['msg']="分配成功";
                //添加日志
                $this->addLog(3,"会员【{$m_name}】，分配员工【{$s_name}】");
            }catch (Exception $e){
                $this->err['msg']=$e->getMessage();
            }
            return json($this->err);
        }else{
            //获取会员信息
            $member=Db::name('member')
                ->field('id,staff_id,nickname,realname')
                ->find($mid);
            if(!$member){
                $this->_empty();
            }
            $this->assign('member',$member);
            //获取员工信息
            $staffs=Staff::with('name')
                ->select();
            $this->assign('staffs',$staffs);

            return view('assign_staff');
        }
    }

    //体检数据
    public function health(){
        $mid=input('id');
        $data=MemberHealthAdmin::where('member_id',$mid)->column('*','type');
        $this->assign('mid',$mid);
        $this->assign('data',$data);
        //dump($data);
        return view('health');
    }

    //体检数据保存
    public function saveHealth(){
        if(Request::isAjax()){
            $data=input('post.');
            $nt=time();
            $type=1;
            $first=[
                'member_id'=>$data['mid'],
                'type'=>1,
                'skin_type'=>implode(',',$data['skin_type']),
                'venation'=>$data['venation'],
                'skin_color'=>implode(',',$data['skin_color']),
                'elasticity'=>$data['elasticity'],
                'suggest_skin'=>$data['suggest_skin'][1],
                'notum'=>$data['notum'][1],
                'waist'=>$data['waist'][1],
                'arm'=>$data['arm'][1],
                'chest'=>$data['chest'][1],
                'abdomen'=>$data['abdomen'][1],
                'legs'=>$data['legs'][1],
                'suggest_body'=>$data['suggest_body'][1],
                'update_time'=>$nt,
                ];
            if($data['id'][1]){
                $first['id']=$data['id'][1];
                $type=3;
            }else{
                $first['create_time']=$nt;
            }
            $second=[
                'member_id'=>$data['mid'],
                'type'=>2,
                'skin_now'=>isset($data['skin_now'][2])?implode(',',$data['skin_now'][2]):'',
                'suggest_skin'=>$data['suggest_skin'][2],
                'notum'=>$data['notum'][2],
                'waist'=>$data['waist'][2],
                'arm'=>$data['arm'][2],
                'chest'=>$data['chest'][2],
                'abdomen'=>$data['abdomen'][2],
                'legs'=>$data['legs'][2],
                'suggest_body'=>$data['suggest_body'][2],
                'update_time'=>$nt,
            ];
            if($data['id'][2]){
                $second['id']=$data['id'][2];
            }else{
                $second['create_time']=$nt;
            }
            $third=[
                'member_id'=>$data['mid'],
                'type'=>3,
                'skin_now'=>isset($data['skin_now'][3])?implode(',',$data['skin_now'][3]):'',
                'suggest_skin'=>$data['suggest_skin'][3],
                'notum'=>$data['notum'][3],
                'waist'=>$data['waist'][3],
                'arm'=>$data['arm'][3],
                'chest'=>$data['chest'][3],
                'abdomen'=>$data['abdomen'][3],
                'legs'=>$data['legs'][3],
                'suggest_body'=>$data['suggest_body'][3],
                'update_time'=>$nt,
            ];
            if($data['id'][3]){
                $third['id']=$data['id'][3];
            }else{
                $third['create_time']=$nt;
            }
            $save=[
                $first,$second,$third
            ];
            $health=new MemberHealthAdmin();
            $health->saveAll($save);
            $this->err['code']=0;
            $str=$type==1?'添加':'修改';
            $nickname=Db::name('member')->where('id',$data['mid'])->value('nickname');
            $this->err['msg']=$str."会员【".$nickname."】体检数据成功";
            //添加日志
            $this->addLog($type,$this->err['msg']);

        }
        return json($this->err);

    }

    //手动添加消费订单
    public function addExpense(){
        if(Request::isAjax()){
            $data=input('post.');
            //return dump($data);
            try{

                //订单商品
                $goods_data=[];
                //购买商品
                if(isset($data['goods']) && !empty($data['goods'])){
                    foreach ($data['goods'] as $v){
                        $ta=explode(',',$v);
                        $goods_data[]=[
                            'member_id'=>$data['member_id'],
                            'goods_id'=>$ta[0],
                            'goods_spec_id'=>0,
                            'status'=>99,
                            'buy_num'=>$ta[1],
                        ];
                    }
                }
                //赠品
                if(isset($data['complimentary']) && !empty($data['complimentary'])){
                    foreach ($data['complimentary'] as $v){
                        $ta=explode(',',$v);
                        $goods_data[]=[
                            'member_id'=>$data['member_id'],
                            'goods_id'=>$ta[0],
                            'goods_spec_id'=>0,
                            'buy_num'=>$ta[1],
                            'status'=>99,
                            'complimentary'=>1,
                        ];
                    }
                }
                if(empty($goods_data)){
                    throw new Exception('请选择购买的商品或赠品！');
                }
                $balance=isset($data['balance'])?1:0;
                //成交日期
                $deal_time=!empty($data['success_time'])?strtotime($data['success_time'].date(' H:i:s')):time();
                $order_data=[
                    'order_no'=>$this->createOrderNo(1,$deal_time),
                    'member_id'=>$data['member_id'],
                    'money'=>$data['money']*100,
                    'pay_money'=>$data['pay_money']*100,
                    'sale_money'=>$data['sale_money']*100,
                    'pay_type'=>5,
                    'status'=>99,
                    'create_time'=>$deal_time,
                    'pay_time'=>$deal_time,
                    'success_time'=>$deal_time,
                    'message'=>$data['message'],
                    'orders_type'=>1,
                    'costs'=>isset($data['costs'])?1:0,
                    'balance'=>$balance
                ];
                //划拨余额，判断余额是否足够
                if($balance){
                    $old_balance=Db::name('member')->where('id',$data['member_id'])->value('balance');
                    if($order_data['pay_money'] > $old_balance){
                        throw new Exception('会员余额不足本次消费');
                    }
                }
                //插入订单
                $o=Orders::create($order_data);
                if(!$o){
                    throw new Exception('添加订单失败');
                }
                //插入订单商品
                $re_add=$o->goods()->saveAll($goods_data);
                if(!$re_add){
                    throw new Exception('添加订单商品失败');
                }
                $this->err['code']=0;
                $this->err['msg']='手动添加会员【'.Db::name('member')->where('id',$data['member_id'])->value('nickname').'】消费订单成功';
                $this->addLog(1,$this->err['msg']);
            }catch (Exception $e){
                $this->err['msg']=$e->getMessage();
                $this->addLog(1,'手动添加消费订单：'.$this->err['msg'],0);
            }

            return json($this->err);
        }else{
            $id=input('id',0);
            $mid=Db::name('member')->where('id',$id)->value('id');
            if(!$mid){
                return $this->_empty();
            }
            $this->assign('mid',$mid);
            return view('expense_edit');
        }
    }

    //手动添加充值订单
    public function addRecharge(){
        if(Request::isAjax()){
            $data=input('post.');
            try{
                //返现金额
                $return_money=isset($data['balance'])?($data['return_money']*100):0;
                //成交日期
                $deal_time=!empty($data['success_time'])?strtotime($data['success_time'].date(' H:i:s')):time();
                $order_data=[
                    'order_no'=>$this->createOrderNo(0,$deal_time),
                    'member_id'=>$data['member_id'],
                    'money'=>$data['money']*100,
                    'pay_money'=>$data['money']*100,
                    'sale_money'=>$return_money,
                    'pay_type'=>5,
                    'goods_type'=>2,
                    'status'=>99,
                    'create_time'=>$deal_time,
                    'pay_time'=>$deal_time,
                    'success_time'=>$deal_time,
                    'message'=>$data['message'],
                    'orders_type'=>1,
                    'costs'=>isset($data['costs'])?1:0,
                    'balance'=>isset($data['balance'])?1:0,
                ];
                //插入订单
                $o=Orders::create($order_data);
                if(!$o){
                    throw new Exception('添加订单失败');
                }
                //插入临时返现信息
                if($return_money){
                    $oid=$o->id;
                    $temp=[
                        'orders_id'=>$oid,
                        'member_id'=>$data['member_id'],
                        'return_money'=>$return_money,
                        'return_date'=>$data['return_date']>0?$data['return_date']:1,
                    ];
                    Db::name('recharge_orders_temp')->insert($temp);
                }
                $this->err['code']=0;
                $this->err['msg']='手动添加会员【'.Db::name('member')->where('id',$data['member_id'])->value('nickname').'】充值订单成功';
                $this->addLog(1,$this->err['msg']);
            }catch (Exception $e){
                $this->err['msg']=$e->getMessage();
                $this->addLog(1,'手动添加充值订单：'.$this->err['msg'],0);
            }

            return json($this->err);
        }else{
            $id=input('id',0);
            $mid=Db::name('member')->where('id',$id)->value('id');
            if(!$mid){
                return $this->_empty();
            }
            $this->assign('mid',$mid);
            return view('recharge_edit');
        }
    }

    //添加商品页面
    public function addCombo(){
        if(Request::isAjax()){
            $map=[['is_delete','=',0]];
            $listRow=input('limit');
            $goods_name=input('goods_name',0);

            if(!$goods_name){
                $map[]=['goods_name','like',"%{$goods_name}%"];
            }
            //获取数据
            $data=Goods::where($map)
                ->field('id,goods_name')
                ->order('update_time desc')
                ->paginate($listRow);

            return json(['code'=>0,'msg'=>'','count'=>$data->total(),'data'=>$data->items()]);
        }else{

            return view('goods_select');
        }

    }

    //添加赠品页面
    public function addComplimentary(){
        if(Request::isAjax()){
            $map=[['goods_type','=',0],['is_delete','=',0]];
            $listRow=input('limit');
            $goods_name=input('goods_name',0);

            if(!$goods_name){
                $map[]=['goods_name','like',"%{$goods_name}%"];
            }
            //获取数据
            $data=Goods::where($map)
                ->field('id,goods_name')
                ->order('update_time desc')
                ->paginate($listRow);

            return json(['code'=>0,'msg'=>'','count'=>$data->total(),'data'=>$data->items()]);
        }else{

            return view('complimentary_goods');
        }

    }


    /**************************提现审核************************/
    //列表
    public function withdraw(){
        if(Request::isAjax()){
            $map=[];
            $listRow=input('limit');
            $where=input('where',[]);
            if(!empty($where)){
                //会员筛选
                if(!empty($where['member'])){
                    $res=Db::name('member')->whereLike('nickname|phone|realname',"%{$where['member']}%")->column('id');
                    $map[]=$res?['member_id','in',$res]:['member_id','=',-1];
                }
                //单号筛选
                if(!empty($where['apply_no'])){
                    $map[]=['apply_no','like',"{$where['apply_no']}%"];
                }
                //日期筛选
                if(!empty($where['start_stop'])){
                    $ss=explode(" _ ",$where['start_stop']);
                    $map[]=['create_time','between time',$ss];
                }
            }
            //获取数据
            $data=MemberWithdraw::with('member')
                ->where($map)
                ->order('create_time desc')
                ->paginate($listRow);

            return json(['code'=>0,'msg'=>'','count'=>$data->total(),'data'=>$data->items()]);
        }else{
            //获取会员等级
            $member_level=Db::name('level')
                ->field('level_id,level_name')
                ->order('level_id asc')
                ->select();
            $this->assign('member_level',$member_level);

            return view('withdraw');
        }
    }

    //提现审核
    public function audit(){
        if(Request::isAjax()){
            $type=input('type',1);
            $ids=input('ids');
            $des=input('des','');
            $nt=time();
            //开启事务
            Db::startTrans();
            try{
                //重载IDS
                $ids=Db::name('member_withdraw')
                    ->whereIn('id',$ids)
                    ->where('status',0)
                    ->column('id');
                if(!$ids){
                    throw  new Exception('请选择待审核的申请');
                }
                //审核
                $save=[
                    'status'=>$type,
                    'des'=>$des,
                    'audit_time'=>$nt,
                    'auditor'=>session('user.nickname')
                ];
                $row=Db::name('member_withdraw')
                    ->whereIn('id',$ids)
                    ->update($save);
                if(!$row){
                    throw  new Exception('审核申请失败');
                }
                if($row != count($ids)){
                    throw  new Exception('审核申请数据错误');
                }
                //拒绝申请则发生退款行为
                $str='通过';
                if($type==4){
                    $str='拒绝';
                    $data=Db::name('member_withdraw')
                        ->whereIn('id',$ids)
                        ->select();
                    $msg_data=[];//系统消息
                    $earnings_data=[];//收益记录
                    foreach ($data as $v){
                        //返还收益
                        if(!Db::name('member')->where('id',$v['member_id'])->setInc('earnings',$v['money'])){
                            throw new Exception('退还收益失败');
                        }
                        $msg_data[]=[
                            'msg_classify'=>1,
                            'member_id'=>$v['member_id'],
                            'content'=>'您的提现申请被拒绝！提现金额 '.number_format($v['money']/100,2).'元已退还您的账户，请注意查收。提现单号：'.$v['apply_no'],
                            'create_time'=>$nt
                        ];
                        $earnings_data[]=[
                            'member_id'=>$v['member_id'],
                            'money'=>$v['money'],
                            'type'=>1,
                            'des'=>'提现申请被拒绝，退还提现申请金额。提现单号：'.$v['apply_no'],
                            'create_time'=>$nt,
                        ];
                    }
                    //收益记录
                    if(!Db::name('earnings_logs')->insertAll($earnings_data)){
                        throw new Exception('收益记录处理失败');
                    }
                    //发送消息
                    if(!Db::name('message')->insertAll($msg_data)){
                        throw new Exception('系统消息发送失败');
                    }
                }

                $this->err['code']=0;

                //提交事务
                Db::commit();
                //添加日志
                $this->addLog(3,"{$str}提现审核，{$row}条");
            }catch (Exception $e){
                //回退事务
                Db::rollback();
                $this->err['msg']=$e->getMessage();
            }
            return json($this->err);
        }
    }

    //转账
    public function transfer(){
        if(Request::isAjax()){
            $ids=input('ids');
            $nt=time();
            //开启事务
            Db::startTrans();
            try{
                //重载IDS
                $ids=Db::name('member_withdraw')
                    ->whereIn('id',$ids)
                    ->where('status',1)
                    ->column('id');
                if(!$ids){
                    throw  new Exception('请选择待审核的申请');
                }
                //审核
                $save=[
                    'status'=>2,
                    'success_time'=>$nt,
                    'withdraw_people'=>session('user.nickname')
                ];
                $row=Db::name('member_withdraw')
                    ->whereIn('id',$ids)
                    ->update($save);
                if(!$row){
                    throw  new Exception('提现转账失败');
                }

                $this->err['code']=0;

                //提交事务
                Db::commit();
                //添加日志
                $this->addLog(3,"提现转账，{$row}条");
                //模板消息推送

                $members=MemberWithdraw::with('info')->whereIn('id',$ids)->select();
                $wx=new Wechat();
                /*
                 * 所需参数
                 *mid:2,[提现人：{{keyword1}}提现金额：{{keyword2}}],url
                 * */
                $url=url('shop/Vip/withdraw',[],true,true);
                foreach ($members as $info){
                    $arr=[$info['realname'],$info['money'].'元'];

                    $wx->setTemplate(2,$arr,$url);

                    $wx->sendTemplate($info['info']['openid']);
                }

            }catch (Exception $e){
                //回退事务
                Db::rollback();
                $this->err['msg']=$e->getMessage();
            }
            return json($this->err);
        }
    }

    /**************************会员等级*************************/
    //相关前端完成后处理3.16
    //列表
    public function level(){
        if(Request::isAjax()){
            $map=[];
            $where=input('where',[]);
            if(!empty($where['title'])){
                $map[]=['classify_name','like',"%{$where['title']}%"];
            }

            //获取数据
            $data=Level::where($map)->select();

            return json(['code'=>0,'msg'=>'','count'=>count($data),'data'=>$data]);
        }else{
            return view('level');
        }
    }

    //添加页
    public function levelAdd(){

        return view('level_edit');
    }

    //ajax修改字段值
    public function changeLevel()
    {
        $id=input('id');
        $info=Level::where('id',$id)->find();
        if(!$info){
            $this->err['msg']='未知参数';
            return json($this->err);
        }
        $field=input('post.field');
        $value=input('value');
        $validate=new LevelValidate();
        if(!$validate->scene($field)->check([$field=>$value])){
            $this->err['msg']=$validate->getError();
            return json($this->err);
        }
        $info[$field]=$value;
        $row=$info->save();
        if($row){
            $this->err['code']=0;
            switch ($field){
                case "level_id":
                    $str='等级';
                    break;
                case "level_name":
                    $str='等级名称';
                    break;
                case "level_money":
                    $str='等级所需金额';
                    break;
                case "level_condition":
                    $str='等级条件描述';
                    break;
                case "level_interests":
                    $str='等级权益';
                    break;
                case "level_sale":
                    $str='折扣';
                    break;
            }
            //添加日志
            $this->addLog(3,'修改会员等级【'.$info['level_name'].'】的'.$str);
        }else{
            $this->err['msg']='网络延迟，稍后再试';
        }

        return json($this->err);
    }

    //会员等级保存
    public function levelSave(){
        $data=input('post.');
        try{
            //验证规则
            $validate=new LevelValidate();
            if(!$validate->check($data)){
                throw new Exception($validate->getError());
            }
            //添加数据
            $res=Level::create($data);
            if(!$res){
                throw new Exception('网络延迟，稍后再试');
            }
            $this->err['code']=0;
            $this->err['msg']="添加会员等级【{$data['level_name']}】".'成功！';
            //添加日志
            $this->addLog(1,"添加会员等级【{$data['level_name']}】");
        }catch (Exception $e){
            $this->err['msg']=$e->getMessage();
        }

        return json($this->err);
    }

    //会员等级删除
    public function levelDel(){
        $id=input('post.id');
        $info=Level::find($id);
        if(!$info){
            $this->err['msg']='未知的参数';
        }
        $name=$info['level_name'];
        $re=$info->delete();
        if($re){
            $this->err['code']=0;
            $this->err['msg']='删除会员等级【'.$name.'】';
            //添加日志
            $this->addLog(2,'删除会员等级【'.$name.'】');
        }else{
            $this->err['msg']='网络延迟，稍后重试';
        }

        return json($this->err);
    }


    /**************************佣金记录*************************/

    //列表
    public function brokerage(){
        if(Request::isAjax()){
            $map=[];
            $listRow=input('limit');
            $where=input('where',[]);
            if(!empty($where)){
                //会员筛选
                if(!empty($where['member'])){
                    $res=Db::name('member')->whereLike('nickname|phone|realname',"%{$where['member']}%")->column('id');
                    $map[]=$res?['member_id','in',$res]:['member_id','=',-1];
                }
                //订单单号筛选
                if(!empty($where['ono'])){
                    $map[]=['order_no','=',$where['ono']];
                }
                //日期筛选
                if(!empty($where['start_stop'])){
                    $ss=explode(" _ ",$where['start_stop']);
                    $map[]=['create_time','between time',$ss];
                }
            }
            //获取数据
            $data=BrokerageLogs::with(['member','fans'])
                ->where($map)
                ->order('create_time desc')
                ->paginate($listRow);

            return json(['code'=>0,'msg'=>'','count'=>$data->total(),'data'=>$data->items()]);
        }else{
            return view('brokerage');
        }
    }


    /**************************收益记录*************************/

    //列表
    public function earnings(){
        if(Request::isAjax()){
            $map=[];
            $listRow=input('limit');
            $where=input('where',[]);
            if(!empty($where)){
                //会员筛选
                if(!empty($where['member'])){
                    $res=Db::name('member')->whereLike('nickname|phone|realname',"%{$where['member']}%")->column('id');
                    $map[]=$res?['member_id','in',$res]:['member_id','=',-1];
                }
                //单号筛选
                if(!empty($where['apply_no'])){
                    $map[]=['apply_no','like',"{$where['apply_no']}%"];
                }
                //日期筛选
                if(!empty($where['start_stop'])){
                    $ss=explode(" _ ",$where['start_stop']);
                    $map[]=['create_time','between time',$ss];
                }
            }
            //获取数据
            $data=EarningsLogs::with('member')
                ->where($map)
                ->order('create_time desc')
                ->paginate($listRow);

            return json(['code'=>0,'msg'=>'','count'=>$data->total(),'data'=>$data->items()]);
        }else{
            return view('earnings');
        }
    }


    /**************************签到管理*************************/
    //签到设置
    public function signSet(){
        if(Request::isAjax()){
            $listRow=input('limit');
            //获取数据
            $data=SignInIntegral::order('level')
                ->paginate($listRow);

            return json(['code'=>0,'msg'=>'','count'=>$data->total(),'data'=>$data->items()]);
        }else{
            return view('sign_list');
        }
    }

    //签到设置保存
    public function signEdit(){
        if(Request::isAjax()){
            $type=input('type');
            $validate=new SignInIntegralValidate();
            if($type==1){
                //添加
                $data=[
                    'level'=>input('post.level'),
                    'continuous'=>input('post.continuous'),
                    'integral'=>input('post.integral'),
                ];
                try{

                    if(!$validate->check($data)){
                        throw new Exception($validate->getError());
                    }

                    if(!Db::name('sign_in_integral')->insert($data)){
                        throw new Exception('网络延迟，稍后再试');
                    }

                    $this->err['code']=0;
                    $this->err['msg']='添加签到设置等级【'.$data['level'].'级】成功';
                    //添加日志
                    $this->addLog(1,$this->err['msg']);
                }catch (Exception $e){
                    $this->err['msg']=$e->getMessage();
                }

            }else{
                //修改
                $field=input('field');
                $value=input('value');
                $id=input('id');
                try{

                    if(!$validate->scene($field)->check([$field=>$value])){
                        throw new Exception($validate->getError());
                    }
                    $info=SignInIntegral::find($id);
                    $info[$field]=$value;
                    $row=$info->save();
                    if(!$row){
                        throw new Exception('网络延迟，稍后再试');
                    }
                    $str='可获积分';
                    if($field=="continuous"){
                        $str='连续签到天数';
                    }
                    $this->err['code']=0;
                    $this->err['msg']='修改签到设置等级【'.$info['level'].'级】的 '.$str.' 成功';
                    //添加日志
                    $this->addLog(3,$this->err['msg']);
                }catch (Exception $e){
                    $this->err['msg']=$e->getMessage();
                }
            }
            return json($this->err);
        }else{
            //获取下个等级
            $level=Db::name('sign_in_integral')->max('level');
            if(!$level){
                $level=1;
            }else{
                $level++;
                $levels=Db::name('sign_in_integral')->order('level')->column('level');
                for($i=0;$i<count($levels);$i++){
                    if($levels[$i]!=($i+1)){
                        $level=$i+1;
                        break;
                    }
                }
            }
            $this->assign('level',$level);

            return view('sign_edit');
        }
    }

    //删除签到设置
    public function signDel(){
        if(Request::isAjax()){
            $id=input('post.id');
            $info=SignInIntegral::find($id);
            if(!$info){
                $this->err['msg']='未知的参数';
            }
            $level=$info['level'];
            $re=$info->delete();
            if($re){
                $this->err['code']=0;
                $this->err['msg']='删除签到设置等级【'.$level.'】';
                //添加日志
                $this->addLog(2,'删除签到设置等级【'.$level.'】');
            }else{
                $this->err['msg']='网络延迟，稍后重试';
            }

            return json($this->err);
        }
    }

    //签到统计
    public function signIn(){
        if(Request::isAjax()){
            $start_stop=input('start_stop');
            $tm=explode('-',date('Y-m-t',strtotime($start_stop)));
            $data=[];
            $categories=[];
            for($i=1;$i<=$tm[2];$i++){
                $data[]=Db::name('member_sign_in')
                    ->where('year',$tm[0])
                    ->where('month',$tm[1])
                    ->where('day',$i)
                    ->count();
                $categories[]=$i;
            }
            return json(['data'=>['categories'=>$categories,'data'=>$data]]);
        }else{
            $tm=explode('-',date('Y-m-t'));
            $data=[];
            $categories=[];
            for($i=1;$i<=$tm[2];$i++){
                $data[]=Db::name('member_sign_in')
                    ->where('year',$tm[0])
                    ->where('month',$tm[1])
                    ->where('day',$i)
                    ->count();
                $categories[]=$i;
            }
            $this->assign('data',json_encode($data));
            $this->assign('categories',json_encode($categories));
            return view('sign_in');
        }
    }


    /**************************积分记录*************************/

    //列表
    public function integral(){
        if(Request::isAjax()){
            $map=[];
            $member=input('member','');
            $size=input('limit');
            if(!empty($member)){
                $map[]=['nickname|phone|realname','like',"%{$member}%"];
            }
            $data=MemberIntegral::with('member')
                ->where($map)
                ->order('create_time desc')
                ->paginate($size);

            return json(['code'=>0,'msg'=>'','count'=>$data->total(),'data'=>$data->items()]);
        }else{
            return view('integral');
        }
    }


    /**************************发送消息**************************/
    //手动发送消息
    public function message(){
        if(Request::isAjax()){
            $data=input('post.');
            $mobile=input('mobile','All');
            $member_level=input('member_level',0);
            $message=input('message','');
            $nt=time();
            try{
                //验证规则
                $validate=new MessageSendValidate();
                if(!$validate->check($data)){
                    throw new Exception($validate->getError());
                }
                //验证手机
                $u=1;
                if(strtoupper($mobile)!=="ALL"){
                    $u=Db::name('member')
                        ->field('id,nickname')
                        ->where('phone',$mobile)
                        ->find();
                }
                if(!$u){
                    throw new Exception('手机账号不存在');
                }
                //发送消息
                $msg_data=[
                    'msg_classify'=>4,
                    'content'=>$message,
                    'member_level'=>$member_level?$member_level:0,
                    'create_time'=>$nt
                ];
                if(strtoupper($mobile)!=="ALL"){
                    $msg_data['member_id']=$u['id'];
                }
                Db::name('message')->insert($msg_data);


                $this->err['code']=0;
                $this->err['msg']="发送消息成功！";
                //添加日志
                $name=strtoupper($data['mobile'])!=="ALL"?$u['nickname']:'所有人';
                $this->addLog(1,"发送消息给$name");
            }catch (Exception $e){
                $this->err['msg']=$e->getMessage();
            }

            return json($this->err);
        }else{
            //获取会员等级
            $member_level=Db::name('level')->field('level_id,level_name')->order('level_id')->select();
            $this->assign('member_level',$member_level);

            return view('message_send');
        }

    }


    /**************************充值管理*************************/

    //列表
    public function recharge(){
        if(Request::isAjax()){
            $size=input('limit');
            $data=RechargeGoods::where('is_delete',0)
                ->order('create_time desc')
                ->paginate($size);

            return json(['code'=>0,'msg'=>'','count'=>$data->total(),'data'=>$data->items()]);
        }else{
            return view('recharge');
        }
    }

    //添加充值卡
    public function rechargeAdd(){
        if(Request::isAjax()){
            $data=input('post.');
            try{
                $validate=new RechargeValidate();
                if(!$validate->check($data)){
                    throw new Exception($validate->getError());
                }
                //添加
                $re=RechargeGoods::create($data);
                if(!$re){
                    throw new Exception('网络延迟，稍后再试');
                }
                $this->err['code']=0;
                $this->err['msg']='添加充值卡【'.$data['goods_name'].'】成功';
                //添加日志
                $this->addLog(1,$this->err['msg']);
            }catch (Exception $e){
                $this->err['msg']=$e->getMessage();
            }

            return json($this->err);
        }else{
            return view('recharge_add');
        }
    }

    //返现记录
    public function returnCash(){
        if(Request::isAjax()){
            $map=[];
            $listRow=input('limit');
            $where=input('where',[]);
            if(!empty($where)){
                //会员筛选
                if(!empty($where['member'])){
                    $res=Db::name('member')->whereLike('nickname|phone|realname',"%{$where['member']}%")->column('id');
                    $map[]=$res?['member_id','in',$res]:['member_id','=',-1];
                }
                //订单号筛选
                if(!empty($where['ono'])){
                    $map[]=['order_no','like',"{$where['ono']}%"];
                }
                //日期筛选
                if(!empty($where['start_stop'])){
                    $ss=explode(" _ ",$where['start_stop']);
                    $map[]=['create_time','between time',$ss];
                }
            }
            //获取数据
            $data=RechargeMoney::with(['member','recharge'])
                ->where($map)
                ->order('create_time desc')
                ->paginate($listRow);

            return json(['code'=>0,'msg'=>'','count'=>$data->total(),'data'=>$data->items()]);
        }else{
            return view('return_cash');
        }
    }

    //返现日志
    public function returnLogs(){
        $id=input('id');
        $list=RechargeReturn::with('member')
            ->where('recharge_money_id',$id)
            ->order('create_time desc')
            ->select();

        $this->assign('list',$list);

        return view('return_logs');
    }

    //删除充值卡
    public function rechargeDel(){
        if(Request::isAjax()){
            $id=input('id');
            $info=RechargeGoods::find($id);
            try{
                if(!$info){
                    throw new Exception('无效的参数');
                }
                $info->is_delete=1;
                $name=$info->goods_name;
                if(!$info->save()){
                    throw new Exception('网络延迟，稍后再试');
                }
                $this->err['code']=0;
                //添加日志
                $this->addLog(2,'删除充值卡【'.$name.'】');
            }catch (Exception $e){
                $this->err['msg']=$e->getMessage();
            }
            return json($this->err);
        }
    }


    /************************会员预约**************************/

    //预约列表
    public function subscribe(){
        if(Request::isAjax()){
            $map=[
                ['is_delete','<',2],
            ];
            $size=input('limit');
            $where=input('where',[]);
            if(!empty($where)){
                if(!empty($where['member'])){
                    $mids=Db::name('member')->whereLike('realname|nickname|phone',"%{$where['member']}%")->column('id');
                    $map[]=!$mids?['member_id','=',-1]:['member_id','in',$mids];
                }
                if(!empty($where['server_no'])){
                    $map[]=['server_no','=',$where['server_no']];
                }
                if(!empty($where['date_range'])){
                    $range=explode(' _ ',$where['date_range']);
                    $map[]=['reserve_time','between',$range];
                }
            }
            $data=MemberSubscribe::with(['member'])
                ->where($map)
                ->order('create_time desc')
                ->paginate($size);

            return json(['code'=>0,'msg'=>'','count'=>$data->total(),'data'=>$data->items()]);
        }else{
            return view('subscribe');
        }
    }
    //确认预约的产品，分配技师
    public function apportion(){
        $id=input('id',0);
        if(Request::isAjax()){
            $goods=input('goods',[]);
            //return dump($goods);
            try{
                $info=MemberSubscribe::get($id,'member');
                if(!$info){
                    throw new Exception('无效的参数');
                }
                $goods_data=[];$nursing=[];
                //购买商品
                if(count($goods)){
                    foreach ($goods as $v){
                        $temp=[
                            'member_id'=>$info['member_id'],
                            'member_nursing_id'=>$v['member_nursing_id'],
                            'staff_id'=>$v['staff_id'],
                            'cost'=>$v['cost']*100,
                            'num'=>$v['num'],
                            'residue_degree'=>$v['residue_degree'],
                        ];
                        $nursing[$v['member_nursing_id']]['residue_degree']=$v['residue_degree'];
                        if(isset($nursing[$v['member_nursing_id']]['num'])){
                            $nursing[$v['member_nursing_id']]['num']+=$v['num'];
                        }else{
                            $nursing[$v['member_nursing_id']]['num']=$v['num'];
                        }

                        if($v['id'] != 0)
                            $temp['id']=$id;

                        $goods_data[]=$temp;
                    }
                }else{
                    throw new Exception('未选择护理产品');
                }
                foreach ($nursing as $val){
                    if($val['num'] > $val['residue_degree']){
                        throw new Exception('扣减次数超出产品剩余次数');
                    }
                }
                //插入护理商品
                $re_add=$info->goods()->saveAll($goods_data);
                if(!$re_add){
                    throw new Exception('添加护理商品失败');
                }
                $old_status=$info['status'];
                if($old_status==0){
                    $info->status=1;
                    if(!$info->save()){
                        throw new Exception('网络延迟，稍后再试');
                    }
                }

                $this->err=[
                    'code'=>0,
                    'msg'=>'会员预约服务，护理产品配置成功'
                ];
                //添加日志
                $this->addLog(3,$this->err['msg']);

                //模板消息推送
                if($old_status==0){
                    $openid=$info->openid;
                    $realname=$info->realname;
                    $nickname=$info->nickname;
                    $name=$realname?$realname:$nickname;
                    $wx=new Wechat();
                    /*
                     * 所需参数
                     *mid:6,[{{first}}服务时间：{{keyword2}}]
                     * */
                    $arr=['尊敬的'.$name.',您的预约已经受理',$info['reserve_time']];
                    $wx->setTemplate(6,$arr);

                    $wx->sendTemplate($openid);

                }

            }catch (Exception $e){
                $this->err['msg']=$e->getMessage();
            }

            return json($this->err);
        }else{
            $this->assign('id',$id);
            $mid=Db::name('member_subscribe')
                ->where('id',$id)
                ->value('member_id');
            //获取技师
            $staffs=Staff::with('name')
                ->field('member_id')
                ->where('is_technician',1)
                ->select();
            //会员护理产品
            $goods=MemberNursing::with('goods')
                ->where('member_id',$mid)
                ->where('residue_degree','>',0)
                ->select();
            //
            $member_goods=MemberSubscribeGoods::with(['nursing'=>function($query){
                $query->with('gname');
            }])
                ->where('member_subscribe_id',$id)->select();

            $this->assign('goods',$goods);
            $this->assign('member_goods',$member_goods);
            $this->assign('staffs',$staffs);

            return view('apportion');
        }
    }
    //预约项目详情，审核后只能查看不能编辑
    public function subscribeInfo(){
        $id=input('id',0);
        $info=MemberSubscribe::find($id);
        if(!$info){
            return $this->_empty();
        }
        $this->assign('info',$info);
        $member_goods=MemberSubscribeGoods::with(['nursing'=>function($query){
            $query->with('gname');
        },'staff'])
            ->where('member_subscribe_id',$id)->select();


        $this->assign('member_goods',$member_goods);


        return view('subscribe_info');
    }
    //预约服务审核
    public function reserveAudit(){
        if(Request::isAjax()){
            $id=input('id');
            try{
                if(!$id){
                    throw new Exception('无效的参数');
                }
                $info=MemberSubscribe::with(['goods.nursing.gname','member'])->where('status',2)->find($id);
                if(!$info){
                    throw new Exception('无效的参数');
                }
                Db::startTrans();
                $info->auditor=session('user.nickname');
                $info->status=99;
                if(!$info->save()){
                    throw new Exception('网络延迟，稍后再试');
                }
                $goods=$info->goods;
                //扣减产品次数
                foreach ($goods as $v){
                    $nur_tem=MemberNursing::get($v['member_nursing_id']);
                    if($nur_tem->residue_degree <1 || $v['num'] > $nur_tem->residue_degree){
                        throw new Exception('扣减次数超出产品剩余次数，无法通过审核');
                    }
                    $nur_tem->residue_degree-=$v['num'];
                    if(!$nur_tem->save()){
                        throw new Exception('网络延迟，稍后再试');
                    }
                }

                Db::name('member_subscribe_goods')->where('member_subscribe_id',$id)->setField('status',99);
                $this->err=[
                    'code'=>0,
                    'msg'=>'预约订单审核成功，订单已结束'
                ];
                Db::commit();
                //添加日志
                $this->addLog(3,$this->err['msg']);
                //模板消息推送

                $name=$info['realname']?$info['realname']:$info['nickname'];
                $wx=new Wechat();
                /*
                 * 所需参数
                 *mid:4,[first:尊敬的谭娅: 项目名称：keyword1  消费时间：keyword2 remark:您的护理课程剩余3次。]
                 * */
                foreach ($goods as $val){
                    $residue_degree=$val['residue_degree']-$val['num'];
                    $arr=[$name,$val['nursing']['goods_name'],$info['reserve_time'],'您的护理项目剩余'.$residue_degree.'次'];

                    $wx->setTemplate(4,$arr);

                    $wx->sendTemplate($info['openid']);
                }

            }catch (Exception $e){
                Db::rollback();
                $this->err['msg']=$e->getMessage();
            }

        }
        return json($this->err);
    }
    //删除预约服务
    public function deleteSub(){
        if(Request::isAjax()){
            $id=input('id');
            try{
                $m=MemberSubscribe::get($id,'member');
                if(!$m){
                    throw new Exception('无效的参数');
                }
                if($m->status==99){
                    throw new Exception('订单已结束，不能删除');
                }
                if($m->reserve_time > date('Y-m-d H:i')){
                    throw new Exception('预约时间未到，不能删除');
                }
                $name=$m->nickname;
                $m->is_delete=2;
                if(!$m->save()){
                    throw new Exception('网络延迟，稍后再试');
                }
                Db::name('member_subscribe_server')->where('member_subscribe_id',$id)->setField('is_delete',1);
                $this->err['code']=0;
                //添加日志
                $this->addLog(2,"删除会员【{$name}】预约成功");
            }catch (Exception $e){
                $this->err['msg']=$e->getMessage();
            }

            return json($this->err);
        }
    }

    //会员服务
    public function servers(){
        if(Request::isAjax()){
            $size=input('limit',10);
            //获取数据
            $data=Servers::where('is_delete',0)
                ->order('create_time desc')
                ->paginate($size);

            return json(['code'=>0,'msg'=>'','count'=>$data->total(),'data'=>$data->items()]);
        }else{
            return view('servers');
        }
    }

    //服务编辑
    public function serverEdit(){
        if(Request::isAjax()){
            //添加、修改
            $data=input('post.');
            try{
                $type=3;
                if(!$data['id']){
                    $m=new Servers();
                    $type=1;
                }else{
                    $m=Servers::find($data['id']);
                }
                unset($data['id']);
                $validate=new ServersValidate();
                if(!$validate->check($data)){
                    throw new Exception($validate->getError());
                }
                if(!$m->save($data)){
                    throw new Exception('网络延迟，稍后再试');
                }
                $this->err=[
                    'code'=>0,
                    'msg'=>'保存成功'
                ];
                //添加日志
                $this->addLog($type,($type==1?'添加':'修改')."预约服务【{$data['server_name']}】成功");
            }catch (Exception $e){
                $this->err['msg']=$e->getMessage();
            }

            return json($this->err);
        }else{
            //显示编辑页面
            $id=input('id',0);
            if($id){
                $info=Servers::find($id);
                $this->assign('info',$info);
            }
            return view('server_edit');
        }
    }

    //服务删除
    public function serverDel(){
        if(Request::isAjax()){
            $id=input('id');
            try{
                $m=Servers::find($id);
                if(!$m){
                    throw new Exception('无效的参数');
                }
                $m->is_delete=1;
                $name=$m->server_name;
                if(!$m->save()){
                    throw new Exception('网络延迟，稍后再试');
                }
                $this->err['code']=0;
                //添加日志
                $this->addLog(2,"删除预约服务【{$name}】成功");
            }catch (Exception $e){
                $this->err['msg']=$e->getMessage();
            }

            return json($this->err);
        }
    }

    //移除本次预约，会员护理产品
    public function delNursing(){
        if(Request::isAjax()){
            $id=input('post.id');

            $info=MemberSubscribeGoods::get($id);
            try{
                if(!$info){
                    throw new Exception('未知的参数');
                }
                if(!$info->delete()){
                    throw new Exception('网络延迟，稍后重试');
                }
                $this->err['code']=0;
            }catch (Exception $e){
                $this->err['msg']=$e->getMessage();
            }
        }
        return json($this->err);
    }

    /**************************公共*************************/

    /*
     * 生成订单号
     * $goods_type 1，消费；2，充值；
     * */
    public function createOrderNo($goods_type=1,$time=0){
        $hstr=$goods_type==1?'EXP':'REC';
        $mstr=$time?date('YmdHis',$time):date('YmdHis');
        $estr=str_pad(rand(1,999999),6,"0",0);
        return $hstr.$mstr.$estr;
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

    //计算星座
    protected function getConstellation($time)
    {
        $y   = date("Y",$time).'-';
        $userTime = $time;

        $januaryS   = strtotime($y.'01-20 00:00:00');
        $januaryE   = strtotime($y.'02-18 23:59:59');
        $februaryS  = strtotime($y.'02-19 00:00:00');
        $februaryE  = strtotime($y.'03-20 23:59:59');
        $marchS     = strtotime($y.'03-21 00:00:00');
        $marchE     = strtotime($y.'04-19 23:59:59');
        $aprilS     = strtotime($y.'04-20 00:00:00');
        $aprilE     = strtotime($y.'05-20 23:59:59');
        $mayS       = strtotime($y.'05-21 00:00:00');
        $mayE       = strtotime($y.'06-21 23:59:59');
        $juneS      = strtotime($y.'06-22 00:00:00');
        $juneE      = strtotime($y.'07-22 23:59:59');
        $julyS      = strtotime($y.'07-23 00:00:00');
        $julyE      = strtotime($y.'08-22 23:59:59');
        $augustS    = strtotime($y.'08-23 00:00:00');
        $augustE    = strtotime($y.'09-22 23:59:59');
        $septemberS = strtotime($y.'09-23 00:00:00');
        $septemberE = strtotime($y.'10-23 23:59:59');
        $octoberS   = strtotime($y.'10-24 00:00:00');
        $octoberE   = strtotime($y.'11-22 23:59:59');
        $novemberS  = strtotime($y.'11-23 00:00:00');
        $novemberE  = strtotime($y.'12-21 23:59:59');

        if($userTime >= $januaryS && $userTime <= $januaryE){
            $constellation = '水瓶座';
        }elseif($userTime >= $februaryS && $userTime <= $februaryE){
            $constellation = '双鱼座';
        }elseif($userTime >= $marchS && $userTime <= $marchE){
            $constellation = '白羊座';
        }elseif($userTime >= $aprilS && $userTime <= $aprilE){
            $constellation = '金牛座';
        }elseif($userTime >= $mayS && $userTime <= $mayE){
            $constellation = '双子座';
        }elseif($userTime >= $juneS && $userTime <= $juneE){
            $constellation = '巨蟹座';
        }elseif($userTime >= $julyS && $userTime <= $julyE){
            $constellation = '狮子座';
        }elseif($userTime >= $augustS && $userTime <= $augustE){
            $constellation = '处女座';
        }elseif($userTime >= $septemberS && $userTime <= $septemberE){
            $constellation = '天秤座';
        }elseif($userTime >= $octoberS && $userTime <= $octoberE){
            $constellation = '天蝎座';
        }elseif($userTime >= $novemberS && $userTime <= $novemberE){
            $constellation = '射手座';
        }else{
            $constellation = '摩羯座';
        }

        return $constellation;
    }

    public function _empty()
    {
        //把所有城市的操作解析到city方法
        return view('public/404');
    }

}
