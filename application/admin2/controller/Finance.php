<?php
/*
 * 财务管理
 * */
namespace app\admin2\controller;



use app\admin2\validate\FinanceProjectValidate;
use app\admin2\validate\StaffProjectValidate;
use app\model\FinanceProject;
use app\model\Staff;
use app\model\StaffProject;
use think\Db;
use think\Exception;
use think\facade\Request;


class Finance extends BaseUser
{
    //中间件
    protected $middleware = [
        'app\admin2\middleware\Auth' => [
            //无须权限验证
            'except' => ['_empty']
        ],
    ];

    /**************************财务管理*************************/
    //员工薪资项目记录
    public function staff(){
        if(Request::isAjax()){
            $map=[];
            $listRow=input('limit');
            $where=input('where',[]);
            if(!empty($where)){
                if(!empty($where['member'])){
                    $w=[
                        ['staff','=',1],
                        ['nickname|phone|realname','like',"%{$where['member']}%"]
                    ];
                    $mids=Db::name('member')->where($w)->column('id');
                    $map[]=!$mids?['member_id','=',-1]:['member_id','in',$mids];
                }
                if(!empty($where['month'])){
                    $map[]=['month','=',$where['month']];
                }
            }
            //获取数据
            $data=StaffProject::with('member')
                ->where($map)
                ->order('create_time desc')
                ->paginate($listRow);

            return json(['code'=>0,'msg'=>'','count'=>$data->total(),'data'=>$data->items()]);
        }else{


            return view('staff');
        }
    }

    //工薪资项目编辑
    public function staffEdit(){
        $id=input('id',0);
        if(Request::isAjax()){
            $data=input('post.');
            unset($data['id']);
            try{
                if($id){
                    //修改
                        //验证
                    $validate=new StaffProjectValidate();
                    if(!$validate->scene('edit')->check($data)){
                        throw new Exception($validate->getError());
                    }
                    $info=StaffProject::get($id,'member');
                    $realname=$info->realname;
                    $re=$info->save($data);
                    $str='修改员工【'.$realname.'】';
                }else{
                    //添加
                    //验证
                    $validate=new StaffProjectValidate();
                    if(!$validate->check($data)){
                        throw new Exception($validate->getError());
                    }
                    $p=Db::name('finance_project')->field('id project_id,project_name,type')->find($data['project_id']);
                    if(!$p){
                        throw new Exception('参数错误');
                    }
                    $save=array_merge($data,$p);
                    $s_ids=$save['member_id'];
                    unset($save['member_id']);
                    //dump($s_ids);
                    $saves=[];
                    foreach ($s_ids as $v){
                        $tem_arr=$save;
                        $tem_arr['member_id']=$v;
                        $saves[]=$tem_arr;
                    }
                    //dump($saves);
                    $m=new StaffProject();
                    $re=$m->allowField(true)->saveAll($saves);
                    $str='添加员工';
                }
                if(!$re){
                    throw new Exception('网络延迟，稍后再试');
                }
                $this->err['code']=0;
                $this->err['msg']=$str.'薪资项目成功';
                //添加日志
                $this->addLog(1,$this->err['msg']);
            }catch (Exception $e){
                $this->err['msg']=$e->getMessage();
            }
            return json($this->err);
        }else{
            if($id){
                $info=StaffProject::get($id,'member');
                $this->assign('info',$info);
            }else{
                //员工
                $staffs=Staff::with('member')->select();
                $this->assign('staffs',$staffs);
                //项目
                $projects=Db::name('finance_project')
                    ->where('is_delete',0)
                    ->order('create_time desc')
                    ->select();
                $this->assign('projects',$projects);
            }
            return view('staff_edit');
        }
    }

    //删除员工薪资项目记录
    public function staffDel(){
        if(Request::isAjax()){
            $id=input('id');
            $info=StaffProject::get($id,'member');
            try{
                if(!$info){
                    throw new Exception('未知的参数');
                }
                $pname=$info->project_name;
                $sname=$info->realname;
                $re=$info->delete();
                if(!$re){
                    throw new Exception('网络延迟，稍后重试');
                }
                $this->err['code']=0;
                $this->err['msg']='删除员工【'.$sname.'】薪资项目【'.$pname.'】';
                //添加日志
                $this->addLog(2,$this->err['msg']);
            }catch (Exception $e){
                $this->err['msg']=$e->getMessage();
            }
        }
        return json($this->err);
    }
    //审核
    public function auditStaffProject(){
        if(Request::isAjax()){
            $type=input('type',0);
            $id=input('id');
            $des=input('des','');
            try{
                //查询记录是否存在
                $info=StaffProject::get($id,'member');
                if(!$info){
                    throw  new Exception('参数错误');
                }

                $info->des=$des;
                $info->status=$type?1:-1;
                $info->auditer=session('user.nickname');

                $row=$info->save();
                if(!$row){
                    throw  new Exception('审核申请失败');
                }

                $this->err['code']=0;
                $str=$type?'通过':'拒绝';
                $this->err['msg']="{$str}员工【{$info['realname']}】薪资项目【{$info['project_name']}】审核";
                //添加日志
                $this->addLog(3,$this->err['msg']);
            }catch (Exception $e){
                $this->err['msg']=$e->getMessage();
            }
        }
        return json($this->err);
    }

    //薪资项目
    public function project(){
        if(Request::isAjax()){

            $data=FinanceProject::order('create_time desc')->select();

            return json(['code'=>0,'msg'=>'','count'=>count($data),'data'=>$data]);
        }else{

            return view('');
        }
    }

    //薪资项目编辑
    public function projectEdit(){
        $id=input('id',0);
        if(Request::isAjax()){
            $data=input('post.');
            unset($data['id']);
            try{
                //验证
                $validate=new FinanceProjectValidate();
                if(!$validate->check($data)){
                    throw new Exception($validate->getError());
                }
                if($id){
                    //修改
                    $info=FinanceProject::find($id);
                    $re=$info->save($data);
                    $str='修改';
                }else{
                    //添加
                    $re=FinanceProject::create($data);
                    $str='添加';
                }
                if(!$re){
                    throw new Exception('网络延迟，稍后再试');
                }
                $this->err['code']=0;
                $this->err['msg']=$str.'薪资项目【'.$data['project_name'].'】成功';
                //添加日志
                $this->addLog(1,$this->err['msg']);
            }catch (Exception $e){
                $this->err['msg']=$e->getMessage();
            }
            return json($this->err);
        }else{
            if($id){
                $info=Db::name('finance_project')->find($id);
                $this->assign('info',$info);
            }
            return view('project_edit');
        }
    }

    //删除项目
    public function projectDel(){
        if(Request::isAjax()){
            $id=input('id');
            $info=FinanceProject::find($id);
            try{
                if(!$info){
                    throw new Exception('未知的参数');
                }
                $info->is_delete=1;
                $re=$info->save();
                if(!$re){
                    throw new Exception('网络延迟，稍后重试');
                }
                $this->err['code']=0;
                $this->err['msg']='删除薪资项目【'.$info->project_name.'】';
                //添加日志
                $this->addLog(2,$this->err['msg']);
            }catch (Exception $e){
                $this->err['msg']=$e->getMessage();
            }
        }
        return json($this->err);
    }


    /*生成工资表
   *工资表默认生成上月工资
   */
    public function createPaySheet(){
        //最终数组
        $data=[];
        $data=cache('staff_pay_data');
        //获取薪资项目
        $projects=FinanceProject::where('is_delete',0)->select();
        $this->assign('projects',$projects);
        //上月日期
        $month=date('Y-m',strtotime('last month'));
        $this->assign('month',$month);
        if(!$data){
            //获取员工条件
            $staff_w=[['staff_level','>',0]];
            //满勤奖金额
            $staff_full_award=$this->getSiteConfig('staff_full_award')*100;


            //日期范围
            $range=[
                $month.'-1 00:00:00',
                date('Y-m-t 23:59:59',strtotime($month))
            ];


            //获取员工数据
            $staffs=Staff::with(['name'])
                ->where($staff_w)
                ->order('create_time')
                ->select();
            if($staffs){
                foreach($staffs as $v){
                    //应发工资
                    $should_pay=0;
                    //扣减工资
                    $abatement_pay=0;
                    //基本工资
                    $base_pay=$v['staff_level']?Db::name('staff_level')->where('level',$v['staff_level'])->value('pay'):0;
                    $should_pay+=$base_pay;
                    //考勤
                    $clocking_pay=Db::name('clocking_in')
                        ->where('member_id',$v['member_id'])
                        ->where('start_time','between',$range)
                        ->sum('fee');

                    $abatement_pay+=$clocking_pay;
                    //绩效
                    $mid=Db::name('member')->where('staff_id',$v['member_id'])->column('id');
                    if(!$mid){$mid=-1;}
                    $recharge_pay=Db::name('orders')
                        ->where('costs',1)
                        ->whereTime('success_time','between',$range)
                        ->where('status',99)
                        ->where(function ($query){
                            //非手动订单或已审核的手动订单
                            $query->whereOr('audit','>',0)->whereOr('orders_type','=',0);
                        })
                        ->whereIn('member_id',$mid)
                        ->sum('pay_money');
                    $performance_pay=intval($recharge_pay/100*$v['push_money'],2);
                    $should_pay+=$performance_pay;
                    //手工费
                    $handwork_pay=Db::name('member_subscribe_goods')
                        ->where('status',99)
                        ->where('staff_id',$v['member_id'])
                        ->whereTime('update_time','between',$range)
                        ->sum('cost');
                    $should_pay+=$handwork_pay;
                    //个人全勤奖
                    $this_staff_full_award=$clocking_pay?0:$staff_full_award;
                    $should_pay+=$this_staff_full_award;
                    //组装数据
                    $data[$v['member_id']]=[
                        'realname'=>$v['realname'],//员工姓名
                        'phone'=>$v['phone'],//员工手机号
                        'base_pay'=>$base_pay/100,//基本工资
                        'push_money'=>$v['push_money'],//提成比例
                        'full_award'=>$this_staff_full_award/100,//满勤奖
                        'clocking_pay'=>$clocking_pay/100,2,//考勤
                        'performance_pay'=>$performance_pay/100,//绩效提成
                        'handwork_pay'=>$handwork_pay/100,//手工费
                    ];
                    if(!empty($projects)){
                        foreach ($projects as $val){
                            $project_pay=Db::name('staff_project')
                                ->where('status',1)
                                ->where('project_id',$val['id'])
                                ->where('member_id',$v['member_id'])
                                ->where('month',$month)
                                ->value('fee');
                            $project_pay=!$project_pay?0:$project_pay;
                            if($val['type']==1){
                                $should_pay+=$project_pay;
                            }else{
                                $abatement_pay+=$project_pay;
                            }
                            $data[$v['member_id']]['project'][]=[
                                'fee'=>$project_pay/100,
                                'type'=>$val['type']
                            ];
                        }
                    }
                    $data[$v['member_id']]['abatement_pay']=$abatement_pay/100;
                    $data[$v['member_id']]['should_pay']=$should_pay/100;
                    //实发工资
                    $data[$v['member_id']]['actual_pay']=($should_pay-$abatement_pay)/100;
                }
                cache('staff_pay_data',$data,3600);
            }
        }

        //dump($data[1]['project']);exit;
        $this->assign('data',$data);
        $this->assign('total',count($data));

        return view('pay_sheet');


    }

    /**************************公共*************************/

    public function _empty()
    {
        //把所有城市的操作解析到city方法
        return view('public/404');
    }

}
