<?php
/*
 * 基本框架 控制器
 * */
namespace app\admin\controller;

use app\admin\model\LeaveWord;
use think\Db;
use think\facade\Request;

class Framework extends BaseUser
{

    protected $pid=1;
    protected $left_menus=[];
    public function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub
        $this->assign('nav_path',Db::name('menus')->where('id',$this->pid)->value('link'));
        $left_menus=$this->getNav($this->pid);
        $this->left_menus=$left_menus;
        $this->assign("left_menus",$left_menus);

    }

    public function guide(){
        if(count($this->left_menus)){
            $menu=array_shift($this->left_menus);
            return redirect($menu['link']);
        }else{
            return view('public/guide');
        }

    }
    //首页
    public function index()
    {
        //获取数据库类型及版本
       /* $sql_name=config('database.type');
        $version = Db::query("select version() as ver");
        $mysql_version=strtoupper($sql_name).' '.$version[0]['ver'];
        $this->assign('mysql_version',$mysql_version);*/

        //成交订单数
        $orders_num=Db::name('orders')->where('status','>',0)->count();
        $this->assign('orders_num',$orders_num);

        //老师数
        $teacher_num=Db::name('member')->count();
        $this->assign('teacher_num',$teacher_num);
        //短信发送数
        $sms_num=Db::name('sms_logs')->count();
        $this->assign('sms_num',$sms_num);

        //访问数
        $member_num=Db::name('member')->count();
        $this->assign('member_num',$member_num);


        //本周运营数据
        $monday=strtotime('sunday -6day');
        $sunday=strtotime('sunday 23:59:59');
        $res_data=Db::name('orders')
            ->field("FROM_UNIXTIME(create_time,'%Y%m%d') as days,count(order_id) as num")
            ->where('create_time','between',[$monday,$sunday])
            ->where('status','>',0)
            ->group('days')
            ->select();
        $visit_data=[];
        foreach ($res_data as $v){
            $visit_data[$v['days']]=$v['num'];
        }

        $res_money=Db::name('orders')
            ->field("FROM_UNIXTIME(create_time,'%Y%m%d') as days,sum(pay_money) as fee")
            ->where('create_time','between',[$monday,$sunday])
            ->where('status','>',0)
            ->group('days')
            ->select();
        $money_data=[];
        foreach ($res_money as $vo){
            $money_data[$vo['days']]=round($vo['fee']/100,2);
        }
        $fd=date('Ymd',$monday);
        $sd=date('Ymd',$sunday);
        while($fd<=$sd){
            if(isset($visit_data[$fd])){
                $ret_data[]=$visit_data[$fd];
            }else{
                $ret_data[]=0;
            }
            if(isset($money_data[$fd])){
                $fee_data[]=$money_data[$fd];
            }else{
                $fee_data[]=0;
            }
            $fd=date('Ymd',strtotime($fd.' +1 day'));
        }
        $this->assign('visit_data',json_encode($ret_data));
        $this->assign('money_data',json_encode($fee_data));


        $this->assign('nav_title','/manage');

        return view('index');
    }


    //留言
    public function message(){
        if(Request::isAjax()){
            $listRow=input('limit');

            //获取数据
            $data=LeaveWord::order('status desc,create_time desc')
                ->paginate($listRow);

            return json(['code'=>0,'msg'=>'','count'=>$data->total(),'data'=>$data->items()]);
        }else{
            return $this->_empty();
        }
    }

    //留言标记处理
    public function messageDo(){
        if(Request::isAjax()){
            $ids=input('ids');
            Db::name('leave_word')
                ->whereIn('id',$ids)
                ->setField('status',0);
            $this->err['code']=0;
            $this->err['msg']='留言标记处理完成';
            $this->addLog(3,$this->err['msg']);
            return json($this->err);
        }else{
            return $this->_empty();
        }
    }

    public function _empty()
    {
        //把所有城市的操作解析到city方法
        return view('public/404');
    }

}
