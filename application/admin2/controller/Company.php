<?php
/*
 * 员工管理
 * */
namespace app\admin2\controller;


use app\admin2\validate\AffairValidate;
use app\admin2\validate\ClockingValidate;
use app\admin2\validate\StaffLevelValidate;
use app\model\admin2;
use app\model\Affair;
use app\model\ClockingIn;
use app\model\ServerEvaluate;
use app\model\Staff;
use app\model\StaffLevel;
use think\Db;
use think\Exception;
use think\facade\Request;
use think\facade\Validate;


class Company extends BaseUser
{
    //中间件
    protected $middleware = [
        'app\admin2\middleware\Auth' => [
            //无须权限验证
            'except' => ['_empty','uploadPic']
        ],
    ];

    /**********************员工管理***********************/

    //员工列表
    public function staff()
    {
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
            }
            //获取数据
            $data=Staff::with(['member','level'])
                ->where($map)
                ->order('create_time desc')
                ->paginate($listRow);

            return json(['code'=>0,'msg'=>'','count'=>$data->total(),'data'=>$data->items()]);
        }else{

            return view('staff');
        }

    }

    //编辑员工
    public function staffEdit(){
        if(Request::isAjax()){
            $data=input('post.');
            if(isset($data['servers_id'])){
                $data['servers_id']=implode(',',$data['servers_id']);
            }
            try{
                if(!Validate::isRequire($data['push_money'])){
                    throw new Exception('提成比例必须填写');
                }
                if(!Validate::isFloat($data['push_money'])){
                    throw new Exception('提成比例只能填写数字');
                }
                $info=Staff::get($data['id'],'name');
                unset($data['id']);
                $level=$info['staff_level'];
                //dump($data);exit;
                $name=$info['realname'];
                if(!$info->save($data)){
                    throw new Exception('网络延迟，稍后再试');
                }
                $this->err['code']=0;
                $str=$level?'修改':'激活';
                $this->err['msg']="员工【{$name}】{$str}成功！";
                //添加日志
                $this->addLog(3,$this->err['msg']);
            }catch (Exception $e){
                $this->err['msg']=$e->getMessage();
            }
            return json($this->err);
        }else{
            $id=input('id');
            //详情
            $info=Db::name('staff')->find($id);
            //员工等级
            $staff_level=Db::name('staff_level')
                ->field('level,level_name')
                ->order('level')
                ->select();

            $this->assign('staff_level',$staff_level);
            $this->assign('info',$info);


            return view('staff_edit');
        }
    }

    //删除员工
    public function removeStaff(){
        $id=input('post.id');

        $info=Staff::get($id,'member');
        if(!$info){
            $this->err['msg']='未知参数';
            return json($this->err);
        }
        $name=$info->realname;
        $mid=$info->member_id;
        $row=$info->delete();
        if($row){
            $this->err['code']=0;
            //变更会员状态
            Db::name('member')->update(['staff'=>0,'id'=>$mid,'update_time'=>time()]);
            //添加日志
            $this->addLog(2,'删除员工【'.$name.'】');
        }else{
            $this->err['msg']='网络延迟，稍后再试';
        }

        return json($this->err);
    }

    //员工转系统管理员
    public function toAdmin(){
        if(Request::isAjax()){
            $id=input('post.id');
            $info=Staff::get($id,'member');
            if(!$info){
                $this->err['msg']='未知参数';
                return json($this->err);
            }
            $info['status']=1;
            $row=$info->save();
            if($row){
                $this->err['code']=0;
                //添加系统管理员
                $save=[
                    'mobile'=>$info['phone'],
                    'nickname'=>$info['realname'],
                    'password'=>substr($info['phone'],-6)
                ];
                Admin::create($save);
                //添加日志
                $this->err['msg']="员工【{$save['nickname']}】转系统管理员成功";
                $this->addLog(3,$this->err['msg']);
            }else{
                $this->err['msg']='网络延迟，稍后再试';
            }
        }
        return json($this->err);
    }

    //员工等级
    public function level(){
        if(Request::isAjax()){
            //获取数据
            $data=StaffLevel::order('create_time desc')->select();

            return json(['code'=>0,'msg'=>'','count'=>count($data),'data'=>$data]);
        }else{

            return view('level');
        }
    }

    //添加页
    public function levelAdd(){
        if(Request::isAjax()){
            $data=input('post.');
            try{
                //验证规则
                $validate=new StaffLevelValidate();
                if(!$validate->check($data)){
                    throw new Exception($validate->getError());
                }
                //添加数据
                $res=StaffLevel::create($data);
                if(!$res){
                    throw new Exception('网络延迟，稍后再试');
                }
                $this->err['code']=0;
                $this->err['msg']="添加员工等级【{$data['level_name']}】".'成功！';
                //添加日志
                $this->addLog(1,$this->err['msg']);
            }catch (Exception $e){
                $this->err['msg']=$e->getMessage();
            }
            return json($this->err);
        }else{
            //获取下个等级
            $level=Db::name('staff_level')->max('level');
            if(!$level){
                $level=1;
            }else{
                $level++;
                $levels=Db::name('staff_level')->order('level')->column('level');
                for($i=0;$i<count($levels);$i++){
                    if($levels[$i]!=($i+1)){
                        $level=$i+1;
                        break;
                    }
                }
            }
            $this->assign('level',$level);

            return view('level_edit');
        }

    }

    //ajax修改字段值
    public function changeLevel()
    {
        $id=input('id');
        $info=StaffLevel::find($id);
        if(!$info){
            $this->err['msg']='未知参数';
            return json($this->err);
        }
        $field=input('post.field');
        $value=input('value');
        $validate=new StaffLevelValidate();
        if(!$validate->scene($field)->check([$field=>$value])){
            $this->err['msg']=$validate->getError();
            return json($this->err);
        }
        $info[$field]=$value;
        $row=$info->save();
        if($row){
            $this->err['code']=0;
            switch ($field){
                case "pay":
                    $str='基本薪资';
                    break;
                case "level_name":
                    $str='等级名称';
                    break;
            }
            //添加日志
            $this->addLog(3,'修改员工等级【'.$info['level_name'].'】的'.$str);
        }else{
            $this->err['msg']='网络延迟，稍后再试';
        }

        return json($this->err);
    }
    
    //删除
    public function levelDel(){
        $id=input('post.id');
        $info=StaffLevel::where('id',$id)->find();
        if(!$info){
            $this->err['msg']='未知的参数';
        }
        $name=$info['level_name'];
        $re=$info->delete();
        if($re){
            $this->err['code']=0;
            $this->err['msg']='删除员工等级【'.$name.'】';
            //添加日志
            $this->addLog(2,'删除员工等级【'.$name.'】');
        }else{
            $this->err['msg']='网络延迟，稍后重试';
        }

        return json($this->err);
    }

    //服务评价
    public function evaluate(){
        if(Request::isAjax()){
            $map=[];
            $listRow=input('limit');
            $where=input('where',[]);
            if(!empty($where)){
                if(!empty($where['staff'])){
                    $w=[
                        ['staff','=',1],
                        ['nickname|phone|realname','like',"%{$where['staff']}%"]
                    ];
                    $sids=Db::name('member')->where($w)->column('id');
                    $map[]=!$sids?['staff_id','=',-1]:['staff_id','in',$sids];
                }
            }
            //获取评价数据
            $data=ServerEvaluate::with(['staff','server','member'])
                ->where($map)
                ->paginate($listRow);

            return json(['code'=>0,'msg'=>'','count'=>$data->total(),'data'=>$data->items()]);
        }else{

            return view('evaluate');
        }
    }


    /**************************考勤管理************************/
    //列表
    public function clockingIn(){
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
                if(!empty($where['affair'])){

                    $map[]=['affair_id','=',$where['affair']];
                }
                if(!empty($where['date_range'])){
                    $range=[
                        $where['date_range'].'-1 00:00:00'
                        ];
                    $range[]=date('Y-m-t 23:59:59',strtotime($where['date_range']));
                    $map[]=['start_time','between',$range];
                }
            }
            //获取数据
            $data=ClockingIn::with(['member','affair'])
                ->where($map)
                ->order('create_time desc')
                ->paginate($listRow);

            return json(['code'=>0,'msg'=>'','count'=>$data->total(),'data'=>$data->items()]);
        }else{
            $affairs=Db::name('affair')
                ->field('id,affair_name')
                ->where('is_delete',0)
                ->order('update_time desc')
                ->select();

            $this->assign('affairs',$affairs);

            return view('clocking_in');
        }
    }

    //添加考勤
    public function addClockingIn(){
        $id=input('id',0);
        if(Request::isAjax()){
            $data=input('post.');
            try{
                //验证
                $validate=new ClockingValidate();
                if(!$validate->check($data)){
                    throw new Exception($validate->getError());
                }
                $data['operation_staff']=session('user.nickname');
                if($id){
                    //修改
                    $m=ClockingIn::find($id);
                    unset($data['id']);
                    if(!$m->save($data)){
                        throw new Exception('网络延迟，稍后再试');
                    };
                    $str='修改';
                }else{
                    //添加
                    if(!ClockingIn::create($data)){
                        throw new Exception('网络延迟，稍后再试');
                    }
                    $str='添加';
                }
                $name=Db::name('member')
                    ->where('id',$data['member_id'])
                    ->value('realname');
                $this->err['code']=0;
                $this->err['msg']=$str.'员工【'.$name.'】考勤成功';
                //添加日志
                $this->addLog(1,$this->err['msg']);
            }catch (Exception $e){
                $this->err['msg']=$e->getMessage();
            }
            return json($this->err);
        }else{
            if($id){
                $info=ClockingIn::get($id,'member');
                $this->assign('info',$info);
            }else{
                $staffs=Staff::with(['member'])
                    ->order('create_time desc')
                    ->select();
                $this->assign('staffs',$staffs);
            }

            $affairs=Db::name('affair')
                ->field('id,affair_name')
                ->where('is_delete',0)
                ->order('update_time desc')
                ->select();

            $this->assign('affairs',$affairs);



            return view('clocking_edit');
        }
    }

    //删除考勤
    public function removeClockingIn(){
        if(Request::isAjax()){
            $id=input('id');
            $info=ClockingIn::find($id);
            try{
                if(!$info){
                    throw new Exception('未知的参数');
                }
                $re=$info->delete();
                if(!$re){
                    throw new Exception('网络延迟，稍后重试');
                }
                $this->err['code']=0;
                $this->err['msg']='删除员工考勤';
                //添加日志
                $this->addLog(2,$this->err['msg']);
            }catch (Exception $e){
                $this->err['msg']=$e->getMessage();
            }
        }
        return json($this->err);
    }

    //事务管理
    public function affair(){
        if(Request::isAjax()){
            $data=Affair::where('is_delete',0)
                ->order('create_time desc')
                ->select();
            return json(['code'=>0,'msg'=>'','count'=>count($data),'data'=>$data]);
        }else{
            return view('affair');
        }
    }

    //添加事务
    public function affairAdd(){
        if(Request::isAjax()){
            $data=input('post.');
            try{
                //验证
                $validate=new AffairValidate();
                if(!$validate->check($data)){
                    throw new Exception($validate->getError());
                }
                if(!Affair::create($data)){
                    throw new Exception('网络延迟，稍后再试');
                }
                $this->err['code']=0;
                $this->err['msg']='添加事务【'.$data['affair_name'].'】成功';
                //添加日志
                $this->addLog(1,$this->err['msg']);
            }catch (Exception $e){
                $this->err['msg']=$e->getMessage();
            }
            return json($this->err);
        }else{
            return view('affair_edit');
        }
    }
    //修改事务
    public function changeAffair(){
        if(Request::isAjax()){
            $id=input('id');
            $info=Affair::find($id);
            try{
                if(!$info){
                    throw new Exception('未知参数');
                }
                $field=input('post.field');
                $value=input('value');
                //验证
                $validate=new AffairValidate();
                if(!$validate->scene($field)->check([$field=>$value])){
                    throw new Exception($validate->getError());
                }
                $info[$field]=$value;
                if(!$info->save()){
                    throw new Exception('网络延迟，稍后再试');
                }

                $this->err['code']=0;
                $this->err['msg']='修改事务名称【'.$info['affair_name'].'】成功';
                //添加日志
                $this->addLog(1,$this->err['msg']);
            }catch (Exception $e){
                $this->err['msg']=$e->getMessage();
            }
            return json($this->err);
        }
    }
    //删除事务
    public function affairDel(){
        if(Request::isAjax()){
            $id=input('id');
            $info=Affair::find($id);
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
                $this->err['msg']='删除事务'.$info->affair_name;
                //添加日志
                $this->addLog(2,$this->err['msg']);
            }catch (Exception $e){
                $this->err['msg']=$e->getMessage();
            }
        }
        return json($this->err);
    }


    /**************************公共*************************/

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
