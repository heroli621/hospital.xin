<?php
/*
 * 服务预约 控制器
 * */
namespace app\shop\controller;


use app\model\Member;
use app\model\MemberSubscribe;
use app\model\MemberSubscribeGoods;
use app\model\MemberSubscribeServer;
use think\Db;
use think\Exception;
use think\facade\Request;


class Servers extends Base
{

    /*************************项目服务**************************/
    //预约页面
    public function subscribe(){
        //获取服务项目
        $projects=Db::name('servers')
            ->field('id,server_name')
            ->where('is_delete',0)
            ->order('update_time desc')
            ->select();
        $this->assign('projects',$projects);

        //获取预约时间
        $range=$this->getSiteConfig('member_subscribe_time_range');
        $range=explode('-',$range);
        $t=strtotime($range[0]);
        $stop_time=strtotime($range[1]);
        $times=[];$i=1;
        do{
            $times[]=['id'=>$i,'value'=>date('H:i',$t)];
            $t+=15*60;
            $i++;
        }while($t<=$stop_time);
        $this->assign('times',json_encode($times));

        //预约日期
        $dates=[];
            //中文周数组
        $week=['星期日','星期一','星期二','星期三','星期四','星期五','星期六'];
            //最大预约天数
        $max_day=$this->getSiteConfig('member_subscribe_max_day');
            //今日日期
        $now=date('Y-m-d');
            //休息日
        $rest_day=$this->getSiteConfig('member_subscribe_ban');
        $rest_day=explode(',',$rest_day);
        //转换用户输入的周日7为程序识别的0
        $key=array_search("7",$rest_day);
        if($key){
            $rest_day[$key]=0;
        }
            //节假日
        $holiday=$this->getSiteConfig('member_subscribe_holiday');
        $holiday=explode(',',$holiday);

        for($j=1;$j<=$max_day;$j++){
            $now=strtotime($now.' +1 day');
            $this_t=date('w',$now);
            $now=date('Y-m-d',$now);
            if(!empty($rest_day) && in_array($this_t,$rest_day)){
                $j--;
                continue;
            }
            if(!empty($holiday) && in_array($now,$holiday)){
                $j--;
                continue;
            }

            $dates[]=[
                'id'=>$j,
                'value'=>$now.' '.$week[$this_t]
            ];
        }
        $this->assign('dates',json_encode($dates));

        return view('appoint');
    }

    //预约列表
    public function mySubscribe(){
        $type=input('type',1);
        $status=[1,3,2];
        $map=[['member_id','=',session('member.mid')]];
        if(!in_array($type,$status)){
            $type=1;
        }
        switch ($type){
            case 1:
                $map[]=['status','=',0];
                break;
            case 3:
                $map[]=['status','=',99];
                break;
            default:
                $map[]=['status',['=',1],['=',2],'or'];
        }
        if(Request::isAjax()){
            $size=input('size');
            $data=MemberSubscribe::with(['goods'=>['nursing.gname','staff']])
                ->where($map)
                ->order('create_time desc')
                ->paginate($size,true);
            if(!$data->isEmpty()){
                $res=['code'=>0,'data'=>$data->items()];
            }else{
                $res=['code'=>400,'msg'=>'暂无数据'];
            }
            return json($res);
        }else{

            $this->assign('type',$type);
            return view('my_appoint');
        }
    }

    //预约处理
    public function reserve(){
        if(Request::isAjax()){
            $time=trim(input('reserve_time',0));
            $ids=trim(input('servers_ids',0));
            $remark=trim(input('remark',''));
            $pass=trim(input('password',''));
            $mid=session('member.mid');
            $nt=time();
            try{
                if(!$ids){
                    throw new Exception('请您选择一个预约项目');
                }
                if(!$time){
                    throw new Exception('请您选择一个预约时间');
                }
                //验证交易密码
                $re=$this->checkPasswordAction($pass,$mid);
                if($re['code']!=0){
                    throw new Exception($re['msg']);
                }
                //验证是否已经预约过相同时段
                $time=explode(" ",$time);
                $reserve_time=$time[0]." ".$time[2];
                $info=Db::name('member_subscribe')
                    ->where('member_id',$mid)
                    ->where('reserve_time',$reserve_time)
                    ->find();
                if($info){
                    throw new Exception('您已预约过相同的时间段服务。<br>如需更改订单,请点击“我的预约”');
                }

                //组装服务项目数组

                $servers=Db::name('servers')
                    ->whereIn('id',$ids)
                    ->column('server_name');

                //组装预约数据
                $save=[
                    'server_no'=>'RES'.date('YmdHis').$mid,
                    'member_id'=>$mid,
                    'remark'=>$remark,
                    'reserve_time'=>$reserve_time,
                    'server_name'=>implode(',',$servers)
                ];
                MemberSubscribe::create($save);

                $this->err['code']=0;
                $this->err['msg']='恭喜您，预约项目成功！';
            }catch (Exception $e){
                $this->err['msg']=$e->getMessage();
            }

        }
        return json($this->err);
    }

    //评价
    public function comment(){
        $id=input('id');
        if(Request::isAjax()){
            $data=input('post.servers');
            $save=[];$sid=[];$msg_data=[];$nt=time();
            $info=MemberSubscribe::find($id);
            try {
                if(!$info){
                    throw new Exception('订单发生错误喽');
                }
                foreach ($data as $v) {
                    $save[] = [
                        'server_no' => $info->server_no,
                        'member_id' => $info->member_id,
                        'member_subscribe_goods_id' => $v['goods_id'],
                        'grade' => $v['grade'],
                        'comment' => $v['comment'],
                        'staff_id' => $v['staff_id'],
                        'create_time' => $nt
                    ];
                    $sid[] = $v['staff_id'];
                }
                //添加评价记录
                $res=Db::name('server_evaluate')->insertAll($save);
                if (!$res) {
                    throw new Exception('操作的朋友太多啦，稍后再试哦');
                }
                //修改订单评论状态
                $info->is_comment=1;
                if(!$info->save()){
                    throw new Exception('操作的朋友太多啦，稍后再试哦');
                }
                //发送消息给相关员工
                    //员工id去重
                $sid=array_unique($sid);
                if(!empty($sid)){
                    foreach ($sid as $v){
                        if($v){
                            $msg_data[]=[
                                'msg_classify'=>4,
                                'member_id'=>$v,
                                'content'=>'您的服务用户已评价，感谢您的辛勤付出。',
                                'create_time'=>$nt
                            ];
                        }
                    }
                }
                if(!empty($msg_data)){
                    Db::name('message')->insertAll($msg_data);
                }

                $this->err['code'] = 0;
            }catch (Exception $e){
                $this->err['msg'] =$e->getMessage();
            }
            return json($this->err);
        }else{
            //获取服务项目
            $server_list=MemberSubscribeGoods::with(['nursing.gname','staff'])
                ->where('member_subscribe_id',$id)
                ->select();
            $this->assign('server_list',$server_list);
            $this->assign('id',$id);

            return view('comment');
        }
    }

    //删除预约
    public function deleteSub(){
        if(Request::isAjax()){
            $id=input('id');
            $info=MemberSubscribe::where('member_id',session('member.mid'))->find($id);
            try {
                if(!$info){
                    throw new Exception('订单发生错误喽');
                }
                //删除订单
                $info->is_delete=1;
                if(!$info->save()){
                    throw new Exception('操作的朋友太多啦，稍后再试哦');
                }

                $this->err['code'] = 0;
                $this->err['msg'] ='您的预约订单已删除';
            }catch (Exception $e){
                $this->err['msg'] =$e->getMessage();
            }
        }
        return json($this->err);
    }

    //取消预约
    public function removeSub(){
        if(Request::isAjax()){
            $id=input('id');
            $info=MemberSubscribe::where('member_id',session('member.mid'))
                ->where('status','<',2)
                ->get($id,'goods');
            try {
                if(!$info){
                    throw new Exception('亲，已服务的订单无法取消哦');
                }
                //预约时间前2小时不能取消
                $t=strtotime($info['reserve_time'])-time();
                if($t < 7200){
                    throw new Exception('亲，服务即将开始无法取消哦');
                }
                //删除订单
                $info->goods;
                if(!$info->together('goods')->delete()){
                    throw new Exception('操作的朋友太多啦，稍后再试哦');
                }

                $this->err['code'] = 0;
                $this->err['msg'] ='您的预约取消成功';
            }catch (Exception $e){
                $this->err['msg'] =$e->getMessage();
            }
        }
        return json($this->err);
    }

    //我的工作
    public function work(){
        $type=input('type',1);
        $status=[1,3,2];
        if(!in_array($type,$status)){
            $type=1;
        }

        if(Request::isAjax()){
            $mid=session('member.mid');
            $size=input('size');
            $map=[['staff_id','=',$mid],['is_delete','=',0]];
            switch ($type){
                case 1:
                    $map[]=['status','=',0];
                    break;
                case 2:
                    $map[]=['status','=',1];
                    break;
                default:
                    $map[]=['status','=',99];
            }
            $data=MemberSubscribeGoods::with(['subscribe','member','nursing.gname'])
                ->where($map)
                ->order('status asc,create_time desc')
                ->paginate($size,true);
            if(!$data->isEmpty()){
                $res=['code'=>0,'data'=>$data->items()];
            }else{
                $res=['code'=>400,'msg'=>'暂无数据'];
            }
            return json($res);
        }else{
            $this->assign('type',$type);
            return view('work');
        }

    }

    //完成服务
    public function successSub(){
        if(Request::isAjax()){
            $id=input('id');
            $info=Db::name('member_subscribe_goods')
                ->where('status','=',0)
                ->find($id);
            try {
                if(!$info){
                    throw new Exception('发生错误喽，请刷新页面重试');
                }
                //更改项目状态
                $re=Db::name('member_subscribe_goods')->update(['update_time'=>time(),'id'=>$id,'status'=>1]);
                if(!$re){
                    throw new Exception('操作的朋友太多啦，稍后再试哦');
                }
                //更改订单状态
                $total=Db::name('member_subscribe_goods')
                    ->where('status','=',0)
                    ->where('member_subscribe_id','=',$info['member_subscribe_id'])
                    ->count();
                if(!$total){
                    Db::name('member_subscribe')
                        ->where('id',$info['member_subscribe_id'])
                        ->setField('status',2);
                }

                $this->err['code'] = 0;
                $this->err['msg'] ='服务项目已经完成，等待审核中……';
            }catch (Exception $e){
                $this->err['msg'] =$e->getMessage();
            }
        }
        return json($this->err);
    }
    //查看我服务评价
    public function myComment(){
        $id=input('id');
        $info=Db::name('server_evaluate')->where('member_subscribe_goods_id',$id)->find();
        $this->assign('info',$info);

        return view('my_comment');
    }

    /*************************员工跟进************************/
    public function follow(){
        if(Request::isAjax()){
            $mid=session('member.mid');
            $size=input('size');

            $data=Member::with('level')
                ->where('staff_id',$mid)
                ->order('create_time desc')
                ->paginate($size,true);
            if(!$data->isEmpty()){
                $res=['code'=>0,'data'=>$data->items()];
            }else{
                $res=['code'=>400,'msg'=>'暂无数据'];
            }
            return json($res);
        }else{
            return view('follow');
        }
    }

    public function followList(){
        $mid=input('id');
        if(Request::isAjax()){
            $size=input('size');
            $data=Db::name('member_follow')
                ->where('member_id',$mid)
                ->order('follow_time desc')
                ->paginate($size,true)->each(function ($item){
                    $item['follow_time']=date('Y年m月d日',$item['follow_time']);
                    return $item;
                });
            if(!$data->isEmpty()){
                $res=['code'=>0,'data'=>$data->items()];
            }else{
                $res=['code'=>400,'msg'=>'暂无数据'];
            }
            return json($res);
        }else{
            $this->assign('mid',$mid);
            return view('follow_list');
        }
    }

    //增加跟进信息
    public function followEdit(){
        $mid=input('id');
        if(Request::isAjax()){
            $staff_id=session('member.mid');
            $follow_time=input('follow_time','');
            $content=trim(input('content',''));
            $nt=time();
            $follow_time=empty($follow_time)?$nt:strtotime($follow_time);
            $res=[
                'code'=>1,
                'msg'=>'增加跟进信息成功！'
            ];
            try{
                if(empty($content)){
                    throw new Exception('跟进记录内容不能为空');
                }

                $data=[
                    'member_id'=>$mid,
                    'staff_id'=>$staff_id,
                    'content'=>$content,
                    'follow_time'=>$follow_time,
                    'create_time'=>$nt
                ];
                if(!Db::name('member_follow')->insert($data)){
                    throw new Exception('网络延时，稍后重试');
                }
                $res['code']=0;
            }catch (Exception $e){
                $res['msg']=$e->getMessage();
            }



            return json($res);
        }else{
            $this->assign('mid',$mid);
            return view('follow_edit');
        }
    }















    public function _empty()
    {
        //把所有城市的操作解析到city方法
        return view('public/404');
    }

}
