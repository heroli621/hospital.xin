<?php
/*
 * 内容管理 控制器
 * type:1,新闻中心；2，核心技术；3，成功案例；4，优惠活动；
 * */
namespace app\admin2\controller;

use app\admin2\validate\ActivityValidate;
use app\model\Activity;
use app\model\Articles;
use think\Db;
use think\Exception;
use think\facade\Request;


class Article extends BaseUser
{
    //中间件
    protected $middleware = [
        'app\admin2\middleware\Auth' => [
            //无须权限验证
            'except' => ['uploadPic','_empty','changeArticle','saveEdit','changeActivity','activitySave','activityField']
        ],
    ];

    //文章列表
    public function index()
    {
        if(Request::isAjax()){
            $type=input('type',0);
            $listRow=input('limit');
            $where=input('where',[]);
            $map=[];
            if(!empty($where['title'])){
                $map[]=['title','like',"%{$where['title']}%"];
            }
            if($type){
                $map[]=['type','=',$type];
            }
            //获取数据
            $data=Db::name('articles')->where($map)->order('update_time desc')->paginate($listRow)->each(function ($item){
                $item['create_time']=date('Y年m月d日',$item['create_time']);
                return $item;
            });

            return json(['code'=>0,'msg'=>'','count'=>$data->total(),'data'=>$data->items()]);
        }else{
            $type=input('type',0);

            $this->assign('type',$type);

            return view('index');
        }

    }

    //修改页
    public function edit(){

        $id=input('id',0);
        if($id){
            $info=Articles::with('content')->find($id);
            $this->assign('info',$info);
        }

        return view('edit');
    }

    //编辑提交
    public function saveEdit()
    {
        $data=input('post.');
        unset($data['images']);
        try{
            $validate=new \app\admin2\validate\Articles();
            if(!$validate->check($data)){
                throw new Exception($validate->getError());
            }
            $save_data=[
                'title'=>$data['title'],
                'writer'=>$data['writer']?$data['writer']:session('config.web_name'),
                'cover'=>$data['pic'],
                'type'=>$data['type'],
                'url'=>trim($data['url']),
                'keywords'=>trim($data['keywords']),
                'des'=>$data['description'],
                'update_time'=>time()
            ];
            if(empty($data['create_time'])){
                $save_data['create_time']=time();
            }else{
                $save_data['create_time']=strtotime($data['create_time'].date(' H:i:s'));
            }
            $article=new Articles();
            $content=htmlspecialchars_decode($data['content']);
            if(!empty($data['id'])){
                $info=$article->find($data['id']);
                if(!$info){
                    throw new Exception('参数错误，文章已被删除');
                }
                $type=3;
                $msg=$data['type']==1?'修改文章':'修改单页';

                /****若图片更改，则删除原图****/
                //获取原图
                $old_pic=$info->cover;


                $res=$info->save($save_data);
                $info->content->save(['content'=>$content]);

                if($res && !empty($old_pic) && $old_pic!=$data['pic']){
                    //删除原图片文件
                    @unlink('.'.$old_pic);
                }
            }else{
                $type=1;
                $msg=$data['type']==1?'添加文章':'添加单页';
                $res=$article->save($save_data);
                $id=$article->id;
                $info=$article->get($id);
                $info->content()->save(['content'=>$content]);
            }

            if(!$res){
                throw new Exception('网络延迟，稍后再试');
            }
            $this->err['code']=0;
            $this->err['msg']=$msg."【{$data['title']}】".'成功！';
            //添加日志
            $this->addLog($type,$msg."【{$data['title']}】");
        }catch (Exception $e){
            $this->err['msg']=$e->getMessage();
        }

        return json($this->err);
    }

    //ajax修改状态
    public function changeArticle()
    {
        $id=input('post.id');
        $field=input('post.field');
        $info=Articles::find($id);
        if(!$info){
            $this->err['msg']='未知参数';
            return json($this->err);
        }
        $new_val=$info[$field]==1?0:1;
        $info[$field]=$new_val;
        $row=$info->save();
        if($row){
            $this->err['code']=0;
            $this->err['data']=$new_val;
            //添加日志
            $this->addLog(3,'修改文章【'.$info['title'].'】'.$field);
        }else{
            $this->err['msg']='网络延迟，稍后再试';
        }

        return json($this->err);
    }

    //ajax修改字段值
    public function changeField()
    {
        $id=input('id');

        $info=Db::name('articles')->find($id);
        if(!$info){
            $this->err['msg']='未知参数';
            return json($this->err);
        }
        $field=input('post.field');
        $value=input('value');
        $row=Db::name('articles')->update([$field=>$value,'id'=>$id]);
        if($row){
            $this->err['code']=0;
            switch ($field){
                case "sort":
                    $str='排序';
                    break;
                case "title":
                    $str='标题';
                    break;
                case "writer":
                    $str='作者';
                    break;

            }
            //添加日志
            $this->addLog(3,'修改新闻资讯【'.$info['title'].'】'.$str);
        }else{
            $this->err['msg']='网络延迟，稍后再试';
        }

        return json($this->err);
    }

    //删除
    public function del(){

        $id=input('post.id');

        $info=Articles::get($id,'content');
        if(!$info){
            $this->err['msg']='未知的参数';
            return json($this->err);
        }
        $title=$info->title;
        $pic_url='.'.$info->cover;
        $info->content;     //手动加载关联模型，此步骤必须，否则无法关联删除。
        $re=$info->together('content')->delete();
        if($re){
            $this->err['code']=0;
            //删除图片文件
            @unlink($pic_url);
            //添加日志
            $this->addLog(2,'删除文章【'.$title.'】');
        }else{
            $this->err['msg']='网络延迟，稍后重试';
        }

        return json($this->err);
    }


    /****************************优惠活动*****************************/

    //列表
    public function activity(){
        if(Request::isAjax()){
            $listRow=input('limit');
            $where=input('where',[]);
            $map=[['isdelete','=',0]];
            $goods_id=[];
            $em=true;
            if(!empty($where)){
                if(!empty($where['title'])){
                    $map[]=['activity_title','like',"%{$where['title']}%"];
                }
                if(!empty($where['ac_ty'])){
                    $map[]=['activity_type','=',$where['ac_ty']];
                }
                if(!empty($where['goods'])){
                    $goods_id=Db::name('goods')->whereLike('goods_name',"%{$where['goods']}%")->column('id');
                    if(!$goods_id){
                        $em=false;
                    }
                }
            }
            if($em){
                //获取数据
                if(empty($goods_id)){
                    $data=Activity::with(['goods'])
                        ->where($map)
                        ->field('content,cover',true)
                        ->order('create_time desc')
                        ->paginate($listRow);
                }else{
                    $data=Activity::hasWhere('goods',[['Goods.id','in',$goods_id]])
                        ->with('goods')
                        ->field('Activity.id,activity_title,activity_type,sale,goods_id,start_time,stop_time,limits,Activity.status,Activity.sort,Activity.top,Activity.create_time')
                        ->where('isdelete',0)
                        //->order('create_time desc')
                        ->paginate($listRow);
                }
                //dump($data);
                $list=[
                    'total'=>$data->total(),
                    'items'=>$data->items()
                ];
            }else{
                $list=[
                    'total'=>0,
                    'items'=>[]
                ];
            }
            $res=['code'=>0,'msg'=>'','count'=>$list['total'],'data'=>$list['items']];
            return json($res);
        }else{
            $type=input('type',0);

            $this->assign('type',$type);

            return view('activity');
        }
    }

    //添加
    public function activityAdd(){
        $goods_id=input('id',0);
        if(!$goods_id){
            return $this->error('商品参数错误');
        }
        $this->assign('goods_id',$goods_id);

        //判断商品是否已有正在进行中的活动
        $re=Db::name('activity')
            ->where('goods_id',$goods_id)
            ->where('stop_time','>',time())
            ->find();

        if($re){
            return $this->activityEdit();
        }

        //获取优惠券
        $coupons=Db::name('coupon')
            ->field('id,coupon_name')
            ->where('status','<>',-1)
            ->select();
        $this->assign('coupons',$coupons);


        return view('activity_add');
    }
    //编辑
    public function activityEdit(){
        $id=input('id',0);
        if(!$id){
            return $this->error('商品参数错误');
        }

        //判断商品是否已有正在进行中的活动
        $info=Db::name('activity')->find($id);


        $this->assign('info',$info);


        return view('activity_edit');
    }

    //保存
    public function activitySave(){
        $data=input('post.');
        unset($data['images']);
        try{
            if(isset($data['id'])){
                $info=Activity::find($data['id']);
                if(!$info){
                    throw new Exception('无效参数');
                }
                $title=$info->activity_title;
                $info->cover=$data['cover'];
                $info->content=htmlspecialchars_decode($data['content']);
                if(!$info->save()){
                    throw new Exception('网络延迟，稍后再试');
                }
                $str='修改';
            }else{
                //验证
                $validate=new ActivityValidate();
                if(!$validate->check($data)){
                    throw new Exception($validate->getError());
                }
                $data['content']=htmlspecialchars_decode($data['content']);
                //保存
                $res=Activity::create($data);
                if(!$res){
                    throw new Exception('网络延迟，稍后再试');
                }
                $title=$data['activity_title'];
                $str='添加';
            }

            $this->err['code']=0;
            $this->err['msg']="{$str}优惠活动【{$title}】".'成功！';
            //添加日志
            $this->addLog(1,"{$str}优惠活动【{$title}】");
        }catch (Exception $e){
            $this->err['msg']=$e->getMessage();
        }

        return json($this->err);
    }

    //ajax修改状态
    public function changeActivity()
    {
        $id=input('post.id');
        $field=input('post.field');
        $info=Activity::find($id);
        if(!$info){
            $this->err['msg']='未知参数';
            return json($this->err);
        }
        $new_val=$info[$field]==1?0:1;
        $info[$field]=$new_val;
        $row=$info->save();
        if($row){
            $this->err['code']=0;
            $this->err['data']=$new_val;
            //添加日志
            $this->addLog(3,'修改优惠活动【'.$info['activity_title'].'】'.$field);
        }else{
            $this->err['msg']='网络延迟，稍后再试';
        }

        return json($this->err);
    }

    //ajax修改字段值
    public function activityField()
    {
        $id=input('id');

        $info=Activity::find($id);
        if(!$info){
            $this->err['msg']='未知参数';
            return json($this->err);
        }
        $field=input('post.field');
        $value=input('value');
        $info[$field]=$value;
        $row=$info->save();
        if($row){
            $this->err['code']=0;
            switch ($field){
                case "sort":
                    $str='排序';
                    break;
                case "activity_title":
                    $str='活动标题';
                    break;
            }
            //添加日志
            $this->addLog(3,'修改优惠活动【'.$info['activity_title'].'】'.$str);
        }else{
            $this->err['msg']='网络延迟，稍后再试';
        }

        return json($this->err);
    }

    //删除
    public function activityDel(){

        $id=input('post.id');

        $info=Activity::get($id);
        if(!$info){
            $this->err['msg']='未知的参数';
            return json($this->err);
        }
        $title=$info['activity_title'];
        $pic_url='.'.$info->cover;
        $info['isdelete']=1;
        $re=$info->save();
        if($re){
            $this->err['code']=0;
            //删除图片文件
            @unlink($pic_url);
            //添加日志
            $this->addLog(2,'删除优惠活动【'.$title.'】');
        }else{
            $this->err['msg']='网络延迟，稍后重试';
        }

        return json($this->err);
    }

    /*****************************公共部分**************************/
    //上传图片
    public function uploadPic(){
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('images');
        if(!$file){
            $this->err['msg']='文件大小超出服务器限制';
            return json($this->err);
        }
        //大小限制
        $size=$this->getSiteConfig('image_upload_size');
        //类型限制
        $ext=$this->getSiteConfig('image_upload_extension');
        // 移动到框架网站根目录/uploads/ 目录下
        $info = $file->validate(['size'=>$size*1024*1024,'ext'=>$ext])->move( './uploads/article');
        if($info){
            // 成功上传后 获取上传信息
            // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
            //dump($info);
            $pname=str_replace('./','/',$info->getPathName());
            $this->err['code']=0;
            $this->err['data']=str_replace('\\','/',$pname);

        }else{
            // 上传失败获取错误信息
            $this->err['msg']=$file->getError();
        }
        return json($this->err);
    }


    public function _empty()
    {
        //把所有城市的操作解析到city方法
        return view('public/404');
    }

}
