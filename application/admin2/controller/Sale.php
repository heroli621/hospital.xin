<?php
/*
 * 销售管理
 * */
namespace app\admin2\controller;


use app\admin2\validate\CouponSendValidate;
use app\admin2\validate\CouponValidate;
use app\admin2\validate\GoodsSpecValidate;
use app\admin2\validate\GoodsValidate;
use app\model\ComboGoods;
use app\model\ComplimentaryGoods;
use app\model\Coupon;
use app\model\CouponGet;
use app\model\CouponUse;
use app\model\Goods;
use app\model\GoodsClassify;
use app\model\GoodsSpec;
use think\Db;
use think\Exception;
use think\facade\Request;


class Sale extends BaseUser
{
    //中间件
    protected $middleware = [
        'app\admin2\middleware\Auth' => [
            //无须权限验证
            'except' => ['uploadPic','_empty','toggleField','saveEdit','classifySave','couponStatus','couponSave','addCombo','delCombo','addComplimentary','delComplimentary','combo']
        ],
    ];

    /**********************商品管理***********************/

    //商品列表
    public function index()
    {
        if(Request::isAjax()){
            $map=[['is_delete','=',0]];

            $listRow=input('limit');
            $where=input('where',[]);
            if(!empty($where)){
                if(!empty($where['title'])){
                    $map[]=['goods_name','like',"%{$where['title']}%"];
                }
                if($where['classify_id']){
                    $map[]=['goods_classify_id','=',$where['classify_id']];
                }
            }

            $type=input('type',0);
            switch($type){
                case 1:
                    $map[]=['is_int','=',0];
                    $map[]=['vip_level','=',0];
                    break;
                case 2:
                    $map[]=['is_int','=',1];
                    break;
                case 3:
                    $map[]=['vip_level','>',0];
                    break;
                case 4:
                    $map[]=['is_new','=',1];
                    break;
                case 5:
                    $map[]=['goods_type','=',1];
                    break;
                case 6:
                    $map[]=['experience','=',1];
                    break;
            }
            //获取数据
            $data=Goods::with(['classify','level'])
                ->withCount('specs')
                ->where($map)
                ->order('update_time desc')
                ->paginate($listRow);

            return json(['code'=>0,'msg'=>'','count'=>$data->total(),'data'=>$data->items()]);
        }else{
            $type=input('type',0);
            $this->assign('type',$type);

            //获取商品分类
            $goods_classify=Db::name('goods_classify')->order('sort desc')->select();
            $this->assign('goods_classify',$goods_classify);


            return view('goods');
        }

    }

    //查看赠品
    public function combo(){
        $id=input('id');
        $type=input('type',0);
        if($type){
            $data=ComboGoods::with('gname')
                ->where('goods_id',$id)
                ->select();
        }else{
            $data=ComplimentaryGoods::with('gname')
                ->where('goods_id',$id)
                ->select();
        }
        $this->assign('data',$data);
        return view('combo');
    }

    //单品编辑页
    public function edit(){
        $type=input('type',0);
        $id=input('id',0);
        if($id){
            $info=Goods::with('content')->find($id);
            $type=$info->goods_type;
            if($type){
                $combos=ComboGoods::with('gname')->where('goods_id',$id)->select();
                $this->assign('combos',$combos);
            }
            if($info['complimentary']){
                $complimentary=ComplimentaryGoods::with('gname')->where('goods_id',$id)->select();
                $this->assign('complimentary',$complimentary);
            }
            $this->assign('info',$info);
        }

        //获取商品分类
        $goods_classify=Db::name('goods_classify')
            ->order('sort desc')
            ->select();
        $this->assign('goods_classify',$goods_classify);

        //获取会员等级
        $member_level=Db::name('level')
            ->field('level_id,level_name')
            ->order('level_id asc')
            ->select();
        $this->assign('member_level',$member_level);
        $v=$type?'goods_combo':'goods_edit';
        return view($v);
    }

    //套餐添加商品页面
    public function addCombo(){
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
            $id=input('id',0);
            $data=[];
            if($id){
                $this->assign('goods_id',$id);
                //获取包含商品列表
                $data=ComboGoods::with('gname')->where('goods_id',$id)->select();

            }
            $this->assign('data',$data);
            return view('goods_select');
        }

    }
    public function delCombo(){
        if(Request::isAjax()){
            $id=input('post.id');

            $info=ComboGoods::get($id,'gname');
            try{
                if(!$info){
                    throw new Exception('未知的参数');
                }
                $title=$info['goods_name'];
                if(!$info->delete()){
                    throw new Exception('网络延迟，稍后重试');
                }
                $this->err['code']=0;
                //添加日志
                $this->addLog(2,'删除套餐内商品【'.$title.'】');
            }catch (Exception $e){
                $this->err['msg']=$e->getMessage();
            }
        }
        return json($this->err);
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
            $id=input('id',0);
            $data=[];
            if($id){
                $this->assign('goods_id',$id);
                //获取包含商品列表
                $data=ComplimentaryGoods::with('gname')->where('goods_id',$id)->select();

            }
            $this->assign('data',$data);
            return view('complimentary_goods');
        }

    }
    //删除赠品
    public function delComplimentary(){
        if(Request::isAjax()){
            $id=input('post.id');

            $info=ComplimentaryGoods::get($id,'gname');
            try{
                if(!$info){
                    throw new Exception('未知的参数');
                }
                $title=$info['goods_name'];
                if(!$info->delete()){
                    throw new Exception('网络延迟，稍后重试');
                }
                $this->err['code']=0;
                //添加日志
                $this->addLog(2,'删除赠品【'.$title.'】');
            }catch (Exception $e){
                $this->err['msg']=$e->getMessage();
            }
        }
        return json($this->err);
    }
    //编辑提交
    public function saveEdit()
    {
        $data=input('post.');
        $id=input('id',0);
        unset($data['file']);
        try{
            $validate=new GoodsValidate();
            if(!$validate->check($data)){
                throw new Exception($validate->getError());
            }
            $complimentarys=[];
            $combos=[];
            $model=new Goods();
            $content=htmlspecialchars_decode($data['content']);
            $data['complimentary']=0;
            if(isset($data['complimentarys'])){
                $data['complimentary']=1;
                $complimentarys=$data['complimentarys'];
                unset($data['complimentarys']);
            }
            if(isset($data['goods_type']) && (!isset($data['combos']) || empty($data['combos']))){
                throw new Exception('套餐商品至少选择一个包含商品');
            }
            if(isset($data['combos'])){
                $combos=$data['combos'];
                unset($data['combos']);
            }
            unset($data['content']);
            unset($data['id']);
            if($id){
                $info=$model->find($id);
                if(!$info){
                    throw new Exception('参数错误，商品已被删除');
                }
                $type=3;
                $msg='修改商品';

                /****若图片更改，则删除原图****/
                //获取原图
                $old_pic=$info->cover;
                $old_img=$info->images;

                $res=$info->save($data);
                $info->content->save(['content'=>$content]);

                if($res){
                    if(!empty($old_pic) && $old_pic!=$data['cover']){
                        @unlink('.'.$old_pic);
                    }
                    if(!empty($old_img) && $old_img!=$data['images']){
                        $old_img_arr=explode(',',$old_img);
                        foreach ($old_img_arr as $v){
                            //删除原图片文件
                            @unlink('.'.$v);
                        }
                    }
                }
                if(!empty($complimentarys)){
                    $sd=[];
                    foreach ($complimentarys as $v){
                        $te=explode(',',$v);
                        $tea=['goods_id'=>$id,'child_id'=>$te[0],'amount'=>$te[1]];
                        if(isset($te[2]) && $te[2])
                            $tea['id']=$te[2];

                        $sd[]=$tea;
                    }
                    $comp=new ComplimentaryGoods();
                    $comp->saveAll($sd);

                }
                if(!empty($combos)){
                    $sd=[];
                    foreach ($combos as $v){
                        $te=explode(',',$v);
                        $tea=['goods_id'=>$id,'child_id'=>$te[0],'amount'=>$te[1]];
                        if(isset($te[2]) && $te[2])
                            $tea['id']=$te[2];

                        $sd[]=$tea;
                    }
                    $comb=new ComboGoods();
                    $comb->saveAll($sd);
                }
            }else{
                $type=1;
                $msg='添加商品';
                $res=$model->save($data);
                $id=$model->id;
                $info=$model->get($id);
                $info->content()->save(['content'=>$content]);
                if(!empty($complimentarys)){
                    $sd=[];
                    foreach ($complimentarys as $v){
                        $te=explode(',',$v);
                        $sd[]=['goods_id'=>$id,'child_id'=>$te[0],'amount'=>$te[1]];
                    }
                    Db::name('complimentary_goods')->insertAll($sd);
                }
                if(!empty($combos)){
                    $sd=[];
                    foreach ($combos as $v){
                        $te=explode(',',$v);
                        $sd[]=['goods_id'=>$id,'child_id'=>$te[0],'amount'=>$te[1]];
                    }
                    Db::name('combo_goods')->insertAll($sd);
                }
            }

            if(!$res){
                throw new Exception('网络延迟，稍后再试');
            }
            $this->err['code']=0;
            $this->err['msg']=$msg."【{$data['goods_name']}】".'成功！';
            //添加日志
            $this->addLog($type,$msg."【{$data['goods_name']}】");
        }catch (Exception $e){
            $this->err['msg']=$e->getMessage();
        }

        return json($this->err);
    }

    //ajax修改状态
    public function toggleField()
    {
        $id=input('post.id');
        $field=input('post.field');
        $info=Goods::find($id);
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
            $this->addLog(3,'修改商品【'.$info['goods_name'].'】'.$field);
        }else{
            $this->err['msg']='网络延迟，稍后再试';
        }

        return json($this->err);
    }

    //ajax修改字段值
    public function changeField()
    {
        $id=input('id');

        $info=Goods::find($id);
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
                case "goods_name":
                    $str='商品名称';
                    break;
                case "price":
                    $str='商品价格';
                    break;
                case "inventory":
                    $str='总库存';
                    break;
                case "integral":
                    $str='兑换积分';
                    break;
            }
            //添加日志
            $this->addLog(3,'修改商品【'.$info['goods_name'].'】'.$str);
        }else{
            $this->err['msg']='网络延迟，稍后再试';
        }

        return json($this->err);
    }

    //删除
    public function del(){

        $id=input('post.id');

        $info=Goods::get($id);
        if(!$info){
            $this->err['msg']='未知的参数';
            return json($this->err);
        }
        $title=$info['goods_name'];
        $info->is_delete=1;
        $re=$info->save();
        if($re){
            $this->err['code']=0;
            //添加日志
            $this->addLog(2,'删除商品【'.$title.'】');
        }else{
            $this->err['msg']='网络延迟，稍后重试';
        }

        return json($this->err);
    }


    /**************************商品分类*************************/

    //列表
    public function classify(){
        if(Request::isAjax()){
            $map=[];
            $listRow=input('limit');
            $where=input('where',[]);
            if(!empty($where['title'])){
                $map[]=['classify_name','like',"%{$where['title']}%"];
            }

            //获取数据
            $data=Db::name('goods_classify')->where($map)->order('create_time desc')->paginate($listRow)->each(function ($item){
                $item['create_time']=date('Y年m月d日',$item['create_time']);
                return $item;
            });

            return json(['code'=>0,'msg'=>'','count'=>$data->total(),'data'=>$data->items()]);
        }else{
            return view('classify');
        }
    }

    //添加页
    public function classifyAdd(){

        return view('classify_edit');
    }

    //ajax修改字段值
    public function changeClassify()
    {
        $id=input('id');

        $info=Db::name('goods_classify')->find($id);
        if(!$info){
            $this->err['msg']='未知参数';
            return json($this->err);
        }
        $field=input('post.field');
        $value=input('value');
        $row=Db::name('goods_classify')->update([$field=>$value,'id'=>$id]);
        if($row){
            $this->err['code']=0;
            switch ($field){
                case "sort":
                    $str='排序';
                    break;
                case "classify_name":
                    $str='标题';
                    break;

            }
            //添加日志
            $this->addLog(3,'修改商品分类【'.$info['classify_name'].'】'.$str);
        }else{
            $this->err['msg']='网络延迟，稍后再试';
        }

        return json($this->err);
    }

    //保存
    public function classifySave(){
        $data=input('post.');
        try{
            //验证规则
            $validate=new \app\admin2\validate\GoodsClassify();
            if(!$validate->check($data)){
                throw new Exception($validate->getError());
            }
            //添加数据
            $res=GoodsClassify::create($data);
            if(!$res){
                throw new Exception('网络延迟，稍后再试');
            }
            $this->err['code']=0;
            $this->err['msg']="添加商品分类【{$data['classify_name']}】".'成功！';
            //添加日志
            $this->addLog(1,"添加商品分类【{$data['classify_name']}】");
        }catch (Exception $e){
            $this->err['msg']=$e->getMessage();
        }

        return json($this->err);
    }

    //删除
    public function classifyDel(){
        $id=input('post.id');
        $info=GoodsClassify::find($id);
        if(!$info){
            $this->err['msg']='未知的参数';
        }
        $name=$info['classify_name'];
        $re=$info->delete();
        if($re){
            $this->err['code']=0;
            $this->err['msg']='删除商品分类【'.$name.'】';
            //添加日志
            $this->addLog(2,'删除商品分类【'.$name.'】');
        }else{
            $this->err['msg']='网络延迟，稍后重试';
        }

        return json($this->err);
    }

    /**************************商品规格************************/

    //列表
    public function spec(){
        if(Request::isAjax()){
            $id=input('goods_id',0);
            $listRow=input('limit');
            $data=GoodsSpec::where('goods_id',$id)->order('price asc')->paginate($listRow);

            return json(['code'=>0,'msg'=>'','count'=>$data->total(),'data'=>$data->items()]);
        }else{
            $id=input('id',0);
            $this->assign('goods_id',$id);

            //判断是否积分商品
            $is_int=Db::name('goods')->where('id',$id)->value('is_int');
            $this->assign('is_int',$is_int);

            return view('spec');
        }
    }

    //ajax修改字段值
    public function changeSpec()
    {
        $id=input('id');

        $info=GoodsSpec::find($id);
        if(!$info){
            $this->err['msg']='未知参数';
            return json($this->err);
        }
        $field=input('post.field');
        $value=input('value');
        $row=$info->save([$field=>$value,'id'=>$id]);
        if($row){
            $this->err['code']=0;
            switch ($field){
                case "price":
                    $str='价格';
                    break;
                case "spec_name":
                    $str='规格名称';
                    break;
                case "inventory":
                    $str='库存';
                    break;
                case "integral":
                    $str='兑换积分';
                    break;
            }
            //添加日志
            $this->addLog(3,'修改商品规格【'.$info['spec_name'].'】'.$str);
        }else{
            $this->err['msg']='网络延迟，稍后再试';
        }

        return json($this->err);
    }

    //保存商品规格
    public function specSave(){
        $data=input('post.');
        try{
            //验证规则
            $validate=new GoodsSpecValidate();
            if(!$validate->check($data)){
                throw new Exception($validate->getError());
            }
            //添加数据
            $res=GoodsSpec::create($data);
            if(!$res){
                throw new Exception('网络延迟，稍后再试');
            }
            $this->err['code']=0;
            $this->err['msg']="添加商品规格【{$data['spec_name']}】".'成功！';
            //添加日志
            $this->addLog(1,"添加商品规格【{$data['spec_name']}】");
        }catch (Exception $e){
            $this->err['msg']=$e->getMessage();
        }

        return json($this->err);
    }

    //删除
    public function specDel(){
        $id=input('post.id');
        $info=GoodsSpec::find($id);
        if(!$info){
            $this->err['msg']='未知的参数';
        }
        $name=$info['spec_name'];
        $re=$info->delete();
        if($re){
            $this->err['code']=0;
            $this->err['msg']='删除商品规格【'.$name.'】';
            //添加日志
            $this->addLog(2,'删除商品规格【'.$name.'】');
        }else{
            $this->err['msg']='网络延迟，稍后重试';
        }

        return json($this->err);
    }

    /**************************优惠券*************************/

    //列表
    public function coupon(){
        if(Request::isAjax()){
            $listRow=input('limit');

            $data=Coupon::where('isdelete',0)->order('status desc')->paginate($listRow);

            return json(['code'=>0,'msg'=>'','count'=>$data->total(),'data'=>$data->items()]);
        }else{
            return view('coupon');
        }
    }

    //添加页
    public function couponAdd(){
        //获取会员等级
        $member_level=Db::name('level')
            ->field('level_id,level_name')
            ->order('level_id asc')
            ->select();
        $this->assign('member_level',$member_level);


        return view('coupon_edit');
    }

    //保存
    public function couponSave(){
        $data=input('post.');
        try{
            //验证规则
            $validate=new CouponValidate();
            if(!$validate->check($data)){
                throw new Exception($validate->getError());
            }
            //添加数据
            $res=Coupon::create($data);
            if(!$res){
                throw new Exception('网络延迟，稍后再试');
            }
            $this->err['code']=0;
            $this->err['msg']="添加优惠券【{$data['coupon_name']}】".'成功！';
            //添加日志
            $this->addLog(1,"添加优惠券【{$data['coupon_name']}】");
        }catch (Exception $e){
            $this->err['msg']=$e->getMessage();
        }

        return json($this->err);
    }

    //ajax修改状态
    public function couponStatus()
    {
        $id=input('post.id');

        $info=Db::name('coupon')->find($id);
        if(!$info){
            $this->err['msg']='未知参数';
            return json($this->err);
        }
        if($info['status']==-1){
            $this->err['msg']='优惠券已过期';
            return json($this->err);
        }
        $status=$info['status']==1?0:1;
        $row=Db::name('coupon')->update(['status'=>$status,'id'=>$id]);
        if($row){
            $this->err['code']=0;
            $this->err['data']=$status;
            //添加日志
            $this->addLog(3,'修改优惠券状态【'.$info['coupon_name'].'】');
        }else{
            $this->err['msg']='网络延迟，稍后再试';
        }

        return json($this->err);
    }

    //删除
    public function couponDel(){
        $id=input('post.id');
        $info=Coupon::find($id);
        if(!$info){
            $this->err['msg']='未知的参数';
        }
        $name=$info['coupon_name'];
        $info->isdelete=1;
        $re=$info->save();
        if($re){
            $this->err['code']=0;
            $this->err['msg']='删除优惠券【'.$name.'】';
            //添加日志
            $this->addLog(2,'删除优惠券【'.$name.'】');
        }else{
            $this->err['msg']='网络延迟，稍后重试';
        }

        return json($this->err);
    }

    //领取记录
    public function couponGet(){
        if(Request::isAjax()){
            $map=[];
            $where=input('where',[]);
            if(!empty($where)){
                if(!empty($where['name'])){
                    $res=Db::name('member')->whereLike('nickname|phone|realname',"%{$where['name']}%")->column('id');
                    $member_where=$res?$res:-1;
                }
                if(isset($member_where)){
                    $map[]=['member_id','in',$member_where];
                }
                if(!empty($where['title'])){
                    $map[]=['coupon_id','in',Db::name('coupon')->whereLike('coupon',"%{$where['title']}%")->column('id')];
                }
            }
            $listRow=input('limit');
            $data=CouponGet::with(['coupon','member','memberUse'])->where($map)->order('create_time desc')->paginate($listRow);

            return json(['code'=>0,'msg'=>'','count'=>$data->total(),'data'=>$data->items()]);
        }else{
            return view('coupon_get');
        }

    }

    //使用记录
    public function couponUse(){
        if(Request::isAjax()){
            $map=[];
            $where=input('where',[]);
            if(!empty($where)){
                if(!empty($where['name'])){
                    $res=Db::name('member')->whereLike('nickname|phone|realname',"%{$where['name']}%")->column('id');
                    $member_where=$res?$res:-1;
                }
                if(isset($member_where)){
                    $map[]=['member_id','in',$member_where];
                }
                if(!empty($where['title'])){
                    $map[]=['coupon_id','in',Db::name('coupon')->whereLike('coupon',"%{$where['title']}%")->column('id')];
                }
            }
            $listRow=input('limit');
            $data=CouponUse::with(['coupon','member','couponGet','orders'])->where($map)->order('create_time desc')->paginate($listRow);

            return json(['code'=>0,'msg'=>'','count'=>$data->total(),'data'=>$data->items()]);
        }else{
            return view('coupon_use');
        }
    }

    //手动发送优惠券
    public function couponSend(){
        if(Request::isAjax()){
            $data=input('post.');
            try{
                //验证规则
                $validate=new CouponSendValidate();
                if(!$validate->check($data)){
                    throw new Exception($validate->getError());
                }
                //验证手机
                $uid=[];
                if(strtoupper($data['mobile'])!=="ALL"){
                    $uid[]=Db::name('member')
                        ->where('phone',$data['mobile'])
                        ->value('id');
                }else{
                    $where=[['status','=',1],['staff','=',0]];
                    if($data['member_level']){
                        $where[]=['level_id','=',$data['member_level']];
                    }
                    $uid=Db::name('member')
                        ->where($where)
                        ->column('id');
                }
                if(!$uid){
                    throw new Exception('手机账号不存在');
                }
                //发送优惠券
                $uid_len=count($uid)-1;
                $add_data=[];
                $t=time();
                $rows=0;
                $validity_time=Db::name('coupon')
                    ->where('id',$data['coupon_id'])
                    ->value('validity_time');
                $validity=$validity_time?($t+$validity_time*24*3600):0;
                for ($i=0;$i <= $uid_len;$i++){
                    $add_data[]=[
                        'coupon_id'=>$data['coupon_id'],
                        'member_id'=>$uid[$i],
                        'validity'=>$validity,
                        'des'=>$data['message'],
                        'create_time'=>$t
                        ];
                    if($i!=0 || $i%100==0 || $i==$uid_len){
                        $rows+=Db::name('coupon_get')->insertAll($add_data);
                        $add_data=[];
                    }
                }
                //发送消息
                $msg_data=[
                    'msg_classify'=>1,
                    'content'=>$validity?$data['message']." 优惠券请在 {$validity_time}天 内使用。":$data['message'],
                    'member_level'=>$data['member_level']?$data['member_level']:0,
                    'create_time'=>$t
                ];
                if(strtoupper($data['mobile'])!=="ALL"){
                    $msg_data['member_id']=$uid[0];
                }
                Db::name('message')->insert($msg_data);


                $this->err['code']=0;
                $this->err['msg']="发送优惠券成功！";
                //添加日志
                $this->addLog(1,"发送优惠券【{$rows}】张");
            }catch (Exception $e){
                $this->err['msg']=$e->getMessage();
            }

            return json($this->err);
        }else{
            //获取优惠券
            $coupons=Db::name('coupon')
                ->field('id,coupon_name')
                ->where('status','<>',-1)
                ->select();
            $this->assign('coupons',$coupons);

            //获取会员等级
            $member_level=Db::name('level')->field('level_id,level_name')->order('level_id')->select();
            $this->assign('member_level',$member_level);

            return view('coupon_send');
        }

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
                $info = $file->validate(['size'=>$size*1024*1024,'ext'=>$ext])->move( './uploads/goods');
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
