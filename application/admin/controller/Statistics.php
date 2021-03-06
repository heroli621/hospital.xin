<?php
/*
 * 数据统计 控制器
 * */
namespace app\admin\controller;

use think\Db;
use think\facade\Request;

class Statistics extends BaseUser
{

    //中间件
    protected $middleware = [
        'app\admin\middleware\Auth' => [
            //无须权限验证
            'except' => ['guide','_empty']
        ],
    ];
    protected $nav_capital='admin/statistics/capital';
    protected $nav_orders='admin/statistics/orders';
    protected $nav_member='admin/statistics/member';
    protected $pid=7;
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

    //资金统计
    public function capital()
    {
        if(Request::isAjax()){
            $range=input('range_time','');
            if($range){
                list($st,$et)=explode(' - ',$range);
                $w_arr=$this->getDateArr($st,$et);//获取主要参数
                $g_str=$w_arr['group'];      //分组条件
                $d_str=$w_arr['format'];      //FROM_UNIXTIME 函数格式
                $date_arr=$w_arr['arr'];   //日期数组
                //日期范围
                $time_range=[$st.' 00:00:00',$et.' 23:59:59'];

                //消费统计
                $expense_data=[];
                $temp_data1=[];
                //获取日期范围内消费订单（日期分组）
                $expense=Db::name('orders')
                    ->field("FROM_UNIXTIME(pay_time,'{$d_str}') {$g_str},sum(pay_money) money")
                    ->whereTime('pay_time',$time_range)
                    ->where('status',99)
                    ->where('pay_money','>',0)
                    ->where('order_type',1)
                    ->group($g_str)
                    ->select();
                if(count($expense)){
                    //组装成 日期=>值 的数组
                    foreach ($expense as $e){
                        $tem_s=substr($d_str,-1);
                        $tem_key=date($tem_s,strtotime($e[$g_str]));
                        $temp_data1[$tem_key]=$e['money']/100;
                    }
                    //生成最终数组
                    foreach ($date_arr as $d){
                        $expense_data[]=isset($temp_data1[$d])?$temp_data1[$d]:0;
                    }
                }

                //提现统计
                $withdraw_data=[];
                $temp_data3=[];
                //获取日期范围内的提现订单（日期分组）
                $withdraw=Db::name('member_withdraw')
                    ->field("FROM_UNIXTIME(success_time,'{$d_str}') {$g_str},sum(money) money")
                    ->whereTime('success_time',$time_range)
                    ->where('status',2)
                    ->group($g_str)
                    ->select();
                if(count($withdraw)){
                    //组装成 日期=>值 的数组
                    foreach ($withdraw as $w){
                        $tem_s=substr($d_str,-1);
                        $tem_key=date($tem_s,strtotime($w[$g_str]));
                        $temp_data3[$tem_key]=$w['money']/100;
                    }
                    //生成最终数组
                    foreach ($date_arr as $d){
                        $withdraw_data[]=isset($temp_data3[$d])?$temp_data3[$d]:0;
                    }
                }

                //充值统计
                $recharge_data=[];
                $temp_data2=[];
                //获取日期范围内充值订单（日期分组）
                $recharge=Db::name('orders')
                    ->field("FROM_UNIXTIME(pay_time,'{$d_str}') {$g_str},sum(pay_money) money")
                    ->whereTime('pay_time',$time_range)
                    ->where('status',99)
                    ->where('pay_money','>',0)
                    ->where('order_type',2)
                    ->group($g_str)
                    ->select();
                if(count($recharge)){
                    //组装成 日期=>值 的数组
                    foreach ($recharge as $r){
                        $tem_s=substr($d_str,-1);
                        $tem_key=date($tem_s,strtotime($r[$g_str]));
                        $temp_data2[$tem_key]=$r['money']/100;
                    }
                    //生成最终数组
                    foreach ($date_arr as $d){
                        $recharge_data[]=isset($temp_data2[$d])?$temp_data2[$d]:0;
                    }
                }

                //渲染
                $this->err['code']=0;
                $this->err['msg']='success';
                $this->err['expense']=$expense_data;
                $this->err['recharge']=$recharge_data;
                $this->err['withdraw']=$withdraw_data;
                $this->err['re']=$recharge;
                $this->err['data']=$date_arr;
            }
            return json($this->err);
        }else{
            $this->assign('nav_title',$this->nav_capital);
            return view('capital');
        }

    }

    //订单统计
    public function orders()
    {
        if(Request::isAjax()){
            $range=input('range_time','');
            if($range){
                list($st,$et)=explode(' - ',$range);
                //日期范围
                $time_range=[$st.' 00:00:00',$et.' 23:59:59'];

                //班级统计
                $term_data=[];//图表数据
                $term=[];//班级名称
                $tids=[];
                //获取日期范围内成交的班级信息
                $terms=Db::name('orders')
                    ->field("term_id,count(id) num")
                    ->whereTime('pay_time',$time_range)
                    ->where('status',99)
                    ->where('pay_money','>',0)
                    ->where('order_type',1)
                    ->group('term_id')
                    ->select();
                if(count($terms)){
                    foreach ($terms as $t){
                        //获取班级名称
                        $name=Db::name('term')->where('id',$t['term_id'])->value('term_name');
                        $tids[]=$t['term_id'];//此数组供讲师统计使用，避免一次查询
                        $term[]=$name;
                        $term_data[]=[
                            'name'=>$name,
                            'value'=>$t['num']
                        ];

                    }
                }

                //区域统计
                $area=[];
                $province_data=[];
                //查询出时间段内成交学员id
                $mids=Db::name('orders')
                    ->whereTime('pay_time',$time_range)
                    ->where('status',99)
                    ->where('pay_money','>',0)
                    ->where('order_type',1)
                    ->group('member_id')
                    ->column('member_id');
                if($mids){
                    //获取学员区域信息
                    $area=Db::name('member')
                        ->whereIn('id',$mids)
                        ->group('province')
                        ->column('province');
                    foreach ($area as $a){
                        //获取区域内学员id
                        $mid=Db::name('member')
                            ->where('province',$a)
                            ->column('id');
                        //获取学员订单数
                        $value=Db::name('orders')
                            ->whereTime('pay_time',$time_range)
                            ->where('status',99)
                            ->where('pay_money','>',0)
                            ->where('order_type',1)
                            ->whereIn('member_id',$mid)
                            ->count();
                        //图表数据
                        $province_data[]=[
                            'name'=>$a,
                            'value'=>$value
                        ];
                    }
                }

                //讲师统计
                $teacher=[];
                $teacher_data=[];
                if(count($terms)){
                    //获取班级内课程id
                    $cid=Db::name('term_course')->whereIn('term_id',$tids)->column('course_id');
                    if($cid){
                        //获取讲师
                        $teacher=Db::name('course')->whereIn('id',$cid)->column('teacher');
                        if($teacher){
                            foreach ($teacher as $te){
                                //获取讲师对应的课程id
                                $course_id=Db::name('course')->where('teacher',$te)->column('id');
                                //获取课程对应的班级id
                                $term_id=Db::name('term_course')->whereIn('course_id',$course_id)->column('term_id');
                                //组装图表数据
                                $teacher_data[]=[
                                    'name'=>$te,
                                    'value'=>$terms=Db::name('orders')
                                        ->whereTime('pay_time',$time_range)
                                        ->where('status',99)
                                        ->where('pay_money','>',0)
                                        ->where('order_type',1)
                                        ->whereIn('term_id',$term_id)
                                        ->count()
                                ];
                            }
                        }
                    }

                }
                //渲染
                $this->err['code']=0;
                $this->err['msg']='success';
                $this->err['term']=$term;
                $this->err['term_data']=$term_data;
                $this->err['area']=$area;
                $this->err['province']=$province_data;
                $this->err['teacher']=$teacher;
                $this->err['teacher_data']=$teacher_data;
            }
            return json($this->err);
        }else{
            $this->assign('nav_title',$this->nav_orders);
            return view('orders');
        }
    }

    //学员统计
    public function member()
    {
        if(Request::isAjax()){
            $range=input('range_time','');
            if($range){
                list($st,$et)=explode(' - ',$range);
                $w_arr=$this->getDateArr($st,$et);//获取主要参数
                $g_str=$w_arr['group'];      //分组条件
                $d_str=$w_arr['format'];      //FROM_UNIXTIME 函数格式
                $date_arr=$w_arr['arr'];   //日期数组
                //日期范围
                $time_range=[$st.' 00:00:00',$et.' 23:59:59'];

                //注册统计
                $register_data=[];
                $temp_data1=[];
                //获取日期范围内注册用户（日期分组）
                $register=Db::name('member')
                    ->field("FROM_UNIXTIME(create_time,'{$d_str}') {$g_str},count(id) num")
                    ->whereTime('create_time',$time_range)
                    ->group($g_str)
                    ->select();
                if(count($register)){
                    //组装成 日期=>值 的数组
                    foreach ($register as $v){
                        $tem_s=substr($d_str,-1);
                        $tem_key=date($tem_s,strtotime($v[$g_str]));
                        $temp_data1[$tem_key]=$v['num'];
                    }
                    //生成最终数组
                    foreach ($date_arr as $d){
                        $register_data[]=isset($temp_data1[$d])?$temp_data1[$d]:0;
                    }
                }

                //性别统计
                $sex_data=[];
                $temp_data2=[];
                //获取日期范围内注册用户（性别分组）
                $sex=Db::name('member')
                    ->field("sex,count(id) num")
                    ->whereTime('create_time',$time_range)
                    ->group('sex')
                    ->select();
                if(count($sex)){
                    //组装成 性别=>值 的数组
                    foreach ($sex as $v){
                        $temp_data2[$v['sex']]=$v['num'];
                    }
                    //生成最终数组
                    $sex_data=[
                        isset($temp_data2[1])?$temp_data2[1]:0,
                        isset($temp_data2[2])?$temp_data2[2]:0,
                        isset($temp_data2[3])?$temp_data2[3]:0,
                    ];
                }

                //付费统计
                //获取日期范围内注册用户（消费总额>0）
                $vip1=Db::name('member')
                    ->whereTime('create_time',$time_range)
                    ->where('expendamount','>',0)
                    ->count();
                //获取日期范围内注册用户（消费总额=0）
                $vip2=Db::name('member')
                    ->whereTime('create_time',$time_range)
                    ->where('expendamount',0)
                    ->count();
                $vip_data=[
                    $vip1,$vip2
                ];

                //区域统计
                $province_data=[];
                //获取日期范围内注册用户（省份分组）
                $area=Db::name('member')
                    ->whereTime('create_time',$time_range)
                    ->group('province')
                    ->column('province');
                if(count($area)){
                    foreach ($area as $k=>$a){
                        //组装图表数据
                        $province_data[]=[
                            'value'=>Db::name('member')
                                ->whereTime('create_time',$time_range)
                                ->where('province',$a)
                                ->count(),
                            'name'=>$a?$a:'未知'
                        ];
                        if(!$a){
                            $area[$k]='未知';
                        }
                    }
                }

                //渲染
                $this->err['code']=0;
                $this->err['msg']='success';
                $this->err['register']=$register_data;
                $this->err['sex']=$sex_data;
                $this->err['vip']=$vip_data;
                $this->err['data']=$date_arr;
                $this->err['area']=$area;
                $this->err['province']=$province_data;
            }
            return json($this->err);
        }else{
            $this->assign('nav_title',$this->nav_member);
            return view('member');
        }

    }

    //获取日期数组
    protected function getDateArr($st,$et){
        $g_str='';      //分组条件
        $d_str='';      //FROM_UNIXTIME 函数格式
        $date_arr=[];   //日期数组
        $et=strtotime($et.' 23:59:59');
        if(strtotime($st.' +1 year')<$et){
            //大于1年的，按年份统计
            $g_str='year';
            $d_str='%Y';
            //处理日期数组
            $tem_st=strtotime($st);
            $tem_et=$et;
            while($tem_st<=$tem_et){
                $date_arr[]=date('Y',$tem_st);
                $tem_st=strtotime(date('Y-m-d',$tem_st).' +1 year');
            }
        }elseif(strtotime($st.' +1 month')<$et){
            //大于1个月的，按月份统计
            $g_str='month';
            $d_str='%Y%m';
            //处理日期数组
            $tem_st=strtotime($st);
            $tem_et=$et;
            while($tem_st<=$tem_et){
                $date_arr[]=date('m',$tem_st);
                $tem_st=strtotime(date('Y-m-d',$tem_st).' +1 month');
            }
        }else{
            //按天统计
            $g_str='day';
            $d_str='%Y%m%d';
            //处理日期数组
            $tem_st=strtotime($st);
            $tem_et=$et;
            while($tem_st<=$tem_et){
                $date_arr[]=date('d',$tem_st);
                $tem_st=strtotime(date('Y-m-d',$tem_st).' +1 day');
            }
        }
        return ['group'=>$g_str,'format'=>$d_str,'arr'=>$date_arr];
    }

    public function _empty()
    {
        //把所有城市的操作解析到city方法
        return view('public/404');
    }

}
