<?php
/*
 * 文章内容 控制器
 * */
namespace app\shop\controller;



use app\model\ActivityShop;
use app\model\Articles;
use think\Controller;
use think\Db;
use think\facade\Request;


class Article extends Controller
{
    public function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub
        //获取商城配置
        if(!session('?config')){
            $config=Db::name('config')->where('type',2)->column('value','varname');
            session('config',$config);
        }
        $this->assign('config',session('config'));
        //添加访问记录
        if(!session('?visitStatus')){
            $this->visitLog();
        }
    }

    //文章列表
    public function index()
    {
        $type=input('type',1);
        $type=$type>3?3:$type;
        if(Request::isAjax()){
            $size=input('size');
            $data=Db::name('articles')
                ->field('id,cover,title,des,create_time,url')
                ->where('type',$type)
                ->where('status',1)
                ->order('top desc,sort desc,create_time desc')
                ->paginate($size,true)
                ->each(function($item){
                    $item['create_time']=date('Y-m-d',$item['create_time']);
                    return $item;
                });
            if(!$data->isEmpty()){
                $res=['code'=>0,'data'=>$data->items()];
            }else{
                $res=['code'=>400,'msg'=>'暂无数据'];
            }
            return json($res);
        }else{
            //类型
            switch ($type){
                case 1:
                    $title='新闻中心';
                    break;
                case 2:
                    $title='核心技术';
                    break;
                default:
                    $title='成功案例';
            }
            $this->assign('type',$type);
            $this->assign('min_title',$title);

            return view('news');
        }
    }
    //文章详情
    public function detail(){
        $id=input('id',0);
        if(!$id)return redirect('/article.html');
        //商品详情
        $info=Articles::with('content')
            ->field('id,title,create_time,keywords')
            ->find($id);

        $this->assign('web_title',$info->title);
        if($info->keywords){
            $this->assign('web_keywords',$info->keywords);
        }

        $this->assign('info',$info);

        return view('show');
    }

    //活动列表
    public function activity()
    {
        if(Request::isAjax()){
            $size=input('size');
            $data=ActivityShop::field('id,cover,activity_title,activity_type,start_time,stop_time,goods_id,sale')
                ->where('status',1)
                ->where('isdelete',0)
                ->where('stop_time','>=',time())
                ->order('top desc,sort desc,create_time desc')
                ->paginate($size,true);
            if(!$data->isEmpty()){
                $res=['code'=>0,'data'=>$data->items()];
            }else{
                $res=['code'=>400,'msg'=>'暂无数据'];
            }
            return json($res);
        }else{

            return view('activity');
        }
    }
    //文章详情
    public function activityDetail(){
        $id=input('id',0);
        if(!$id)return redirect('/article.html');
        //商品详情
        $info=Articles::with('content')
            ->field('id,title,create_time,keywords')
            ->find($id);

        $this->assign('web_title',$info->title);
        if($info->keywords){
            $this->assign('web_keywords',$info->keywords);
        }

        $this->assign('info',$info);

        return view('show');
    }
    //添加访问记录
    protected function visitLog(){
        $data=[
            'ip'=>Request::ip(),
            'session_id'=>session_id(),
            'create_time'=>time()
        ];
        Db::name('visit')->insert($data);
        session('visitStatus',1);
    }

    public function _empty()
    {
        //把所有城市的操作解析到city方法
        return view('public/404');
    }

}