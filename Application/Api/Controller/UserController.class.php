<?php

/**
 * Created by PhpStorm.
 * User: caipeichao
 * Date: 1/16/14
 * Time: 9:40 PM
 */

namespace Api\Controller;

use Addons\Avatar\AvatarAddon;
//use Addons\LocalComment\LocalCommentAddon;
//use Addons\Favorite\FavoriteAddon;
use Addons\Tianyi\TianyiAddon;
use Vendor\JPush\src\JPush\JPush;
use Vendor\JPush\src\JPush\core\JPushException;
use Vendor\JPush\src\JPush\core\PushPayload;

class UserController extends ApiController {

    public function changePassword($old_password, $new_password) {
        $this->requireLogin();
        //检查旧密码是否正确
        $this->verifyPassword($this->getUid(), $old_password);
        //更新用户信息
        $model = D('User/UcenterMember');
        $data = array('password' => $new_password);
        $data = $model->create($data);
        if (!$data) {
            $this->apiError(0, $this->getRegisterErrorMessage($model->getError()));
        }
        $model->where(array('id' => $this->getUid()))->save($data);
        //返回成功信息
        clean_query_user_cache($this->getUid(), 'password'); //删除缓存
        D('user_token')->where('uid=' . $this->getUid())->delete();

        $this->apiSuccess("密码修改成功");
    }

    private function getImageFromForm() {0
.        $image = $_FILES['image'];
        if (!$image) {
            $this->apiError(1103, '图像不能为空');
        }
        return $image;
    }
	
	 private function not_posterr(){
            $info = array();
            $info['msg'] = '非法请求!';
            $info['status'] = 0;
            $this->apiError(FALSE,null, $info);
    }

    /**
     * 上传头像并裁剪保存。
     * @param null $crop 字符串。格式为x,y,width,height，单位为像素
     */
    public function uploadAvatar($crop = null) {
        $this->requireLogin();
        //读取上传的图片
        $image = $this->getImageFromForm();
        //保存临时头像、裁剪、保存头像
        $uid = $this->getUid();
        $addon = new AvatarAddon();
        $result = $addon->upload($uid, $image, $crop);
        if (!$result) {
            $this->apiError(0, $addon->getError());
        }
        //返回成功消息
        $this->apiSuccess('头像保存成功');
    }

    /**
     * 上传临时头像
     */
    public function uploadTempAvatar($uid = '' ,$images = '') {
        $this->requireLogin();
        //读取上传的图片
        $image = $this->getImageFromForm();
        //保存临时头像
        $uid = $this->getUid();
        $addon = new AvatarAddon();
        $result = $addon->uploadTemp($uid, $image);




        if (!$result) {
            $this->apiError(0, $addon->getError());
        }
        //获取临时头像
        $image = $addon->getTempAvatar($uid);
        //返回成功消息
        $this->apiSuccess('头像保存成功', null, array('image' => $image));
    }

    /*
     * 推送消息（极光推送）
     * --》推送消息先入库然后推送
     * $title  推送消息标题
     * $content 推送内容
     * $uid 推送目标用户uid
     * 
     */
    public function JpushMessage($title,$content,$uid,$types,$stypes){
                ##发送消息给用户（第三方推送《极光推送》）
                ##消息内容入库
                $msg['from_id'] = 1;//系统消息
                $msg['title'] = $title;
                $msg['content'] = $content;//'尊敬的'.get_table_field($uid,'id','username','UcenterMember').'用户，您已下单成功';
                $msg['create_time'] = time();
                $msg['status'] = 1;
                $add_msg = M('MessageContent')->add($msg);//消息内容入副表
                $message['content_id'] = $add_msg;
                $message['from_uid'] = 1;
                $message['to_uid'] = $uid;
                $message['create_time'] = time();
                $message['is_read'] = 0;
                $message['last_toast'] = 0;
                $message['status'] = 1;
                $message['msg_status'] = $types;
                $message['msg_types'] = $stypes;
                $add_message = M('Message')->add($message);//消息内容入正表
                ##消息内容发送第三方
                $br = '<br/>';
                $appKey = 'd2e30eae64c44116c61745dd';
                $masterSecret = 'a4f8c3c4827fafb1353b870c';
                $mobile = get_table_field($uid,'id','mobile','UcenterMember');
                $JPush = new \Vendor\JPush\src\JPush\JPush($appKey, $masterSecret);
                // 完整的推送示例,包含指定Platform,指定Alias,Tag,指定iOS,Android notification,指定Message等
                    $result = $JPush->push()
                        ->setPlatform(array('ios', 'android'))
                        ->addAlias($mobile)
                      //->addTag(array('tag1', 'tag2'))
                      //->setPlatform('all')
                      //->addAllAudience('all')
                        ->setNotificationAlert($title)
                        ->addAndroidNotification($title, $title, 1, array("uid"=>$uid, "key2"=>"value2"))
                        ->addIosNotification($title, 'iOS sound', JPush::DISABLE_BADGE, true, 'iOS category', array("uid"=>$uid, "key2"=>"value2"))
                        ->setMessage($content, $title, 'type', array("uid"=>$uid, "key2"=>"value2"))
                        ->setOptions(100000, 3600, null, false)
                        ->send();             
    }

    /**
     * 裁剪，保存头像
     * @param null $crop
     */
    public function applyAvatar($crop = null) {
        $this->requireLogin();
        //裁剪、保存头像
        $addon = new AvatarAddon();
        $result = $addon->apply($this->getUid(), $crop);
        if (!$result) {
            $this->apiError(0, $addon->getError());
        }
        //返回成功消息
        $this->apiSuccess('头像保存成功');
    }
    
    /*获取当前用户个人信息*/
    public function getProfile($uid = null) {
        //默认查看自己的详细资料
        if (!$uid) {
            $this->requireLogin();
            $uid = $this->getUid();
        }
        //读取数据库中的用户详细资料
        $map = array('uid' => $uid);
        $user1 = D('Home/Member')->where($map)->find();
        $user2 = D('User/UcenterMember')->where(array('id' => $uid))->find();

        //获取头像信息
        $pic = get_table_field($uid,'id','pic','UcenterMember');
        $avatar_url = get_table_field($pic,'id','path','Picture');
        $avatar_url =$avatar_url?$avatar_url:"";
        //缩略头像
        $avatar128_path = getThumbImage($avatar_path, 128);
        $avatar128_path = '/' . $avatar128_path['src'];
        $avatar128_url = getRootUrl() . $avatar128_path;

        //获取等级
        $title = D('Ucenter/Title')->getTitle($user1['score']);
        ##用户关注商品统计
        $goods_follow = M('goods_follow')->where(array('uid' => $uid))->count();
        ##用户关注店铺统计
        $shop_follow = M('store_follow')->where(array('uid' => $uid))->count();
        //只返回必要的详细资料
        $this->apiSuccess("获取成功", null, array(
            'uid' => $uid,
            'avatar_url' => $avatar_url,
            'avatar128_url' => $avatar128_url,
	        'pic'=>'./Uploads/renwu01_03.png',
            'signature' => $user1['signature'],
            'email' => $user2['email'],
            'mobile' => $user2['mobile'],
            'score' => $user1['score1'],
            'money' => $user1['tox_money'],
            'name' => $user1['nickname'],
            'sex' => $this->encodeSex($user1['sex']),
            'birthday' => $user1['birthday'],
            'title' => $title,
            'username' => $user2['username'],
            'goods_follow' => $goods_follow,
            'shop_follow' => $shop_follow,
        ));
    }
    
      /**
     * 我的订单
     * @param 
       * $uid 用户id
       * $type 订单类型：1-维修订单；2-兑换订单；3-报装订单 4续费订单
       * status 1,2,3
       * $page=1  页码
       * $r=10   每页条数
     */
    public function MyOrder($uid=0,$type=0,$status = 0,$page=1,$r=10){
         if (empty($uid) || empty($type)) {
                $info = array();
                $info['status'] = 0;
                $info['info'] = '参数错误，检查';
                $this->apiError(false, null, $info);
        }else{
                         switch ($type) {
                            case 1:
                                $map['wx_status'] =$status>1?$status:array('elt',$status);
                                $field='id,uid,content,type,wx_type,createtime,wx_status';
                                break;
                            case 2:
                                $map['dh_status'] = $status>2?$status:array('elt',$status);
                                $field='id,uid,content,type,createtime,dh_status,good_id,num,price';
                                 break;
                            case 3:
                                $map['kh_status'] =$status>1?$status:array('elt',$status);
                                $field='id,uid,content,type,createtime,kh_status,account_id';
                                 break;
                        }
      
                        $map['uid'] = $uid;
                        $map['type'] = $type;
      
                        $map['uid'] = $uid;
                        $map['type'] = $type;
                        $scount=M('OrderList')->page($page, $r)->order('id desc')->where($map)->count();
			            $order_list = M('OrderList')->page($page, $r)->order('id desc')->where($map)->field($field)->select();

                        if($type==3){
                            foreach ($order_list as $key => $value) {
                                $order_list[$key]['user']=M("UserAccount")->where(array('id'=>$value['account_id']))->find();
                                $order_list[$key]['pic'] ="/public/images/logo.png";
                            }
                            
                        }
                       
                        if($type==2){
                            foreach ($order_list as $key => $value) {
                                $shop=M("ScoreShop")->where(array('id'=>$value['good_id']))->find();
                                
                                $order_list[$key]['shop_name']=$shop['name'];
                                $order_list[$key]['shop_pic']=get_cover($shop['pic'],'path');
                               
                            }
                            
                        }
                       

			 if ($order_list) {
				$info = array();
				$info['status'] = 1;
				$info['info'] = '订单查询成功';
				$info['data'] = $order_list;
                $info['page'] = $scount>$r?$page + 1:0;
				$this->apiSuccess(true, null, $info);
			} else {
				$info = array();
				$info['status'] = -1;
				$info['info'] = '没有订单';
                $info['page'] = 0;
				$this->apiError(false, null, $info);
			}
		}
        
    }
	  /*
     * 订单详情
     * $id
     * $type 订单类型  订单类型：1-维修订单；2-兑换订单；3-报装订单(开户) 4续费订单
     * $uid  用户ID
     */
    public function orderInfo($id=0,$uid = 0,$type = 0){
        if($id == 0 || $uid == 0 || $type == 0){
            $info = array();
            $info['info'] = '缺少参数';
            $info['status'] = 0;
            $this->apiError(FALSE,null, $info);
        }
        ##根据type来调出不同订单的信息
        
        if($type == 1){
            ## $wx_status -1:取消维修服务；0-未处理；1已处理
            $field = array('id','uid','order_id','e_id','type','account_id','content','wx_mark','repair_uid','wx_status','createtime','wx_type','createtime');
        }
        if($type == 2){
            ## $dh_status    -1-订单关闭；1:兑换成功；2：配送中；3：已完成
            $field = array('id','uid','order_id','type','e_id','account_id','good_id','price','num','total_price','createtime','dh_status','createtime','address','addDetail','name','mobile');
        }
        if($type == 3){
            ## $kh_status   -1：订单关闭； 1：未付款；2：已付款
            $field = array('id','uid','order_id','type','e_id','account_id','kh_money','kh_yeah','kh_status','createtime');
        }
        if($type == 4){
            ## 1：订单关闭；1-未付款；2：已支付
            $field = array('id','uid','order_id','type','e_id','account_id','xf_yeah','xf_status','createtime');
        }
        $where['id'] = $id;
        $where['type'] = $type;
        $where['uid'] = $uid;
        $order_Info =M('OrderList')->where($where)->field($field)->find();

       if($type==1 || $type==3){
         $ress = M('OrderCommit')->where(array('uid'=>$order_Info['uid']))->find();
         $order_Info['order_commit'] =$ress;
         $order_Info['order_commit']['AgentID']=get_table_field($ress['user_id'],'id','AgentID','InstallMember');
       }

       if($type==3){
         $ress = M('UserAccount')->where(array('id'=>$order_Info['account_id']))->find();
         $order_Info['userinfo'] =$ress;  
         $order_Info['pic'] ="/public/images/logo.png";
       }

       if($type==2){
         $shop=M("ScoreShop")->where(array('id'=>$order_Info['good_id']))->select();
         // $shop=M("Member")->where(array('uid'=>$order_Info['uid']))->field('')->find();
         foreach ($shop as $key => $value) {
            $order_Info['shopinfo'][$key] =$value;  
            $order_Info['shopinfo'][$key]['shop_pic'] =get_cover($value['pic'],'path');
            $order_Info['shopinfo'][$key]['num'] =$order_Info['num'];
            $order_Info['shopinfo'][$key]['price'] =$order_Info['price'];
         }
         
       }
        
        
		$order_Info['e_id'] = get_table_field($order_Info['e_id'],'id','e_id','Equipment');
        
        if($order_Info){
            $info = array();
            $info['msg'] = '订单信息拉取成功';
            $info['status'] = 1;
            $info['data'] = $order_Info;
            $this->apiSuccess(TRUE,NULL,$info);
        }else{
            $info = array();
            $info['info'] = '暂无此订单';
            $info['status'] = 0;
            $this->apiError(FALSE,null, $info);
        }
    }
	
	/*
     * 工单提交
     * $id 订单ID
     * $score 评分
     * $user_id 员工编号
     */
    public function order_commit($id = 0,$score = 0,$user_id = 0){
        if(IS_POST){
            ##根据ID查询订单
            $orderInfo = M('OrderList')->where(array('id'=>$id))->find();
            if(empty($orderInfo)){
              $info = array();
              $info['msg'] = '没有此订单';
              $info['status'] = 0;
              $this->apiError('0',NULL,$info);die;
            }
            ##检查员工工号是否存在
            $check_member = M('InstallMember')->where(array('AgentID'=>$user_id,'status'=>1))->find();
            if(empty($check_member)){
                $info = array();
                $info['msg'] = '此工号不存在或此工号员工已离职';
                $info['status'] = 202;
                $this->apiError('101',NULL,$info);die;
            }
            ##取需要的参数
            $data['order_id'] = $orderInfo['order_id'];
            $data['uid'] = $orderInfo['uid'];
            $data['type'] = $orderInfo['type'];//订单类型：1-维修订单；2-兑换订单；3-报装订单 4续费订单
            $data['user_id'] = $check_member['id'];
			$data['e_id'] = get_table_field($orderInfo['e_id'],'id','e_id','Equipment');
            $data['score'] = $score;
            $data['update_time'] = time();

            $ress = M('OrderCommit')->where(array('uid'=>$orderInfo['uid'],'user_id'=>$check_member['id']))->find();
            if(!$ress){
                $res = M('OrderCommit')->add($data);
            }else{
                $res = M('OrderCommit')->where(array('uid'=>$orderInfo['uid'],'user_id'=>$check_member['id']))->save($data);
            }
            

            if($res){
                switch ($orderInfo['type']) {
                    case 1:
                        $savedata['wx_status']=3;
                        $savedata['updata_time']=NOW_TIME;
                        break;
                    case 2:
                        $savedata['dh_status']=3;
                        $savedata['updata_time']=NOW_TIME;
                        break; 
                    case 3:
                        $savedata['kh_status']=3;
                        $savedata['updata_time']=NOW_TIME;
                        break;
                    case 4:
                        $savedata['xh_status']=3;
                        $savedata['updata_time']=NOW_TIME;
                        break;
                }
                 M('OrderList')->where(array('order_id'=>$orderInfo['order_id'],'user_id'=>$check_member['id']))->save($savedata);
                $info = array();
                $info['data']['score'] = $score;
                $info['data']['user_id'] = $user_id;
                $info['msg'] = '工单提交成功';
                $info['status'] = 1;
                $this->apiSuccess(TRUE,NULL,$info);
            }else{
                $info = array();
                $info['msg'] = '提交失败，请重试';
                $info['status'] = 0;
                $this->apiError(FALSE,NULL,$info);
            }
        }else{
            $this->not_posterr();
        }
    }
    /*
     * 用户消息列表
     * uid   用户UID
     */
    public function myMessageList($uid){
        if(empty($uid)){
            $info = array();
            $info['info'] = '缺少参数';
            $info['status'] = 0;
            $this->apiError(FALSE,null, $info);
        }
        
        /* $list = M('Message')->where(array('to_uid'=>$uid))->select();
        foreach($list as $k => $v){
        	$content = M('MessageContent')->where(array('id'=>$v['content_id']))->find();
        	$list[$k]['title'] = $content['title'];
        	$list[$k]['content'] = $content['content'];
        	$list[$k]['time'] = date('m-d h:i:s',$content['create_time']);
        	$pic = get_table_field($v['from_uid'],'id','pic','UcenterMember');
        	$list[$k]['avatar_url'] = get_table_field($pic,'id','path','Picture');
        } */
        
        //查询优化 by lw
        $where = array('to_uid'=>$uid);
        $where = whereAddTableName($where, 'ne_message');
        $list = M('Message')
        ->join(" ne_message_content t1 on ne_message.content_id = t1.id","left")
        ->join(" ne_ucenter_member t2 on ne_message.from_uid = t2.id","left")
        ->join(" ne_picture t3 on t2.pic = t3.id","left")
        ->where($where)
        ->field('ne_message.*,t1.title title,t1.content content,t1.create_time time,t2.pic,t3.path avatar_url')
        ->select();
        foreach($list as $k => $v){
        	$list[$k]['time'] = date('m-d h:i:s',$v['create_time']);
        }        
        
        $info = array();
        $info['msg'] = '消息列表拉去成功';
        $info['status'] = 1;
        $info['data'] = $list;
        $this->apiSuccess(TRUE,NULL,$info);        
    }
    /*
     * 消息列表--消息详情
     * id  消息 id
     * uid  用户uid
     */
    public function myMessageInfo($id,$uid){
        if(empty($id) || empty($uid)){
            $info = array();
            $info['info'] = '缺少参数';
            $info['status'] = 0;
            $this->apiError(FALSE,null, $info);
        }else{
            $msgInfo = M('MessageContent')->where(array('id'=>$id))->find();
            if($msgInfo){
                $info = array();
                $info['msg'] = '获取成功';
                $info['status'] =1;
                $info['data'] = $msgInfo;
                $this->apiSuccess(TRUE,NULL,$info);
            }else{
                $info = array();
                $info['msg'] = '没有该消息，请检查';
                $info['status'] = 0;
                $this->apiError(FALSE.NULL, $info);
            }
        }
    }

    
     /*
    * 积分记录
    */
   public function scoreLog($uid){
       if(empty($uid)){
            $info = array();
            $info['status'] = 0;
            $info['info'] = '参数不能为空';
            $this->apiError(false, null, $info);
       }else{
           $list = M('ScoreLog')->where(array('uid'=>$uid))->field(array('id','uid','type','value','create_time'))->select();         
           if($list){
                foreach ($list as $k => $v){
               
                 }
                $info = array();
                $info['status'] = 1;
                $info['data'] = $list;
                $this->apiSuccess(TRUE, NULL, $info);
           }else{
                $info = array();
                $info['status'] = 0;
                $info['msg'] = '没有此数据';
                $this->apiError('0', NULL, $info);
           }
       }
   }
    /*
     * 推广信息
     * uid 用户uid，用来获取当前用户的优惠码
     */
    public function generalize($uid){
        if(empty($uid)){
            $info = array();
            $info['info'] = '缺少参数';
            $info['status'] = 0;
            $this->apiError(FALSE,null, $info);
            
        }else{
            $msg = M('Generalize')->where(array('status'=>1))->find();//推广信息
            $code = get_table_field($uid,'id','code','UcenterMember');
            $info = array();
            $info['msg'] = '数据拉去成功';
            $info['status'] = 1;
            $info['data']  = array(
                'msg' =>$msg,
                'code'=>$code,
                );
            $this->apiSuccess(TRUE,NULL,$info);
        }
    }
    
    /*
     * 报警信息
     */
    public function callPoliceMessage($uid){
        if(empty($uid)){
            $info = array();
            $info['info'] = '缺少参数';
            $info['status'] = 0;
            $this->apiError(FALSE,null, $info);
        }
        
        $list = M('DeviceCallPoliceLog')->where(array('uid'=>$uid))->order('createtime desc')->select();//报警信息
        $info = array();
        if($list){
            foreach ($list as $k => $v){
                $list[$k]['createtime'] = date('m-d h:i:s',$v['createtime']);
            }
            $info['data']  = $list;
            $info['msg'] = '数据拉取成功';
            $info['status'] = 1;
            $this->apiSuccess(TRUE,NULL,$info);
        }else{
            $info['data']  =array();
            $info['msg'] = '暂时没有数据';
            $info['status'] = 0;
            $this->apiSuccess(FALSE,null, $info);
        }
        
          
    }
    /*
     * 用户帮助
     */
    public function userHelp(){

            $list = M('Help')->where(array('status'=>1))->select();//帮助信息
            if(!empty($list)){
                foreach ($list as $k => $v){
                    
                } 
                $info = array();
                $info['msg'] = '数据拉取成功';
                $info['status'] = 1;
                $info['data']  =$list;
                $this->apiSuccess(TRUE,NULL,$info);
            }else{
                $info = array();
                $info['status'] = 0;
                $info['info'] = '暂无内容';
                $this->apiError(FALSE,null, $info);
            }
           

    }
    /*
     * 积分充值记录
     * uid  用户UID
     */
    public function userBuyScore($uid){
        if(empty($uid)){
            $info = array();
            $info['info'] = '缺少参数';
            $info['status'] = 0;
            $this->apiError(FALSE,null, $info);            
        }
            
        $list = M('ScorePayLog')->where(array('uid'=>$uid))->order('createtime desc')->select();//支付信息
        foreach ($list as $k => $v){
            $list[$k]['createtime'] = date('m-d h:i:s',$v['createtime']);
        }
        $info = array();
        $info['msg'] = '数据拉取成功';
        $info['status'] = 1;
        $info['data']  = $list;
        $this->apiSuccess(TRUE,NULL,$info);
    }
    /*
     * 用户设备续费
     *  $id 订单ID
     *  $uid 用户UID
     *  $time 续费时间
     */
    public function renew($id,$uid,$time){
        if(empty($id) || empty($uid) || empty($time)){
            $info = array();
            $info['status'] = 104;
            $info['info'] = '缺少参数';
            $this->apiError(FALSE,null, $info);
        }
        $reslist = M('OrderList')->where(array('order_id'=>$id))->find();
        if($reslist){
        
            $check = M("Equipment")->where(array('id'=>$reslist['e_id'],'uid'=>$uid))->find();
            if(!$check){
                $info = array();
                $info['status'] = 202;
                $info['info'] = '没有该设备';
                $this->apiError(FALSE,null, $info);
            }else{
                $check['servicetime']=$check['servicetime']?date('Y-m-d H:i:s', $check['servicetime']):date('Y-m-d H:i:s', NOW_TIME);
                $add_serviceTime =date('Y-m-d', strtotime ("+{$time} year", strtotime($check['servicetime'])));//续费后的日期
                $data['servicetime'] = strtotime($add_serviceTime);
                $res =  M("Equipment")->where(array('id'=>$check['id'],'uid'=>$uid))->save($data);
                M('OrderList')->where(array('order_id'=>$id,'uid'=>$uid,'e_id'=>$reslist['e_id']))->save(array('xh_sttus'=>1));
                if($res){
                    ##发送消息给用户（第三方推送《极光推送》）
                    ##消息内容入库
                    $msg['from_id'] = 1;//系统消息
                    $msg['title'] = '设备续费';
                    $msg['content'] = '尊敬的'.get_table_field($uid,'id','username','UcenterMember').'用户，您设备编号为'.$check['e_id'].'续费'.$time.'年成功';
                    $msg['create_time'] = time();
                    $msg['status'] = 1;
                    $add_msg = M('MessageContent')->add($msg);//消息内容入副表
                    $message['content_id'] = $add_msg;
                    $message['from_uid'] = 1;
                    $message['to_uid'] = $uid;
                    $message['create_time'] = time();
                    $message['is_read'] = 0;
                    $message['last_toast'] = 0;
                    $message['status'] = 1;
                    $add_message = M('Message')->add($message);//消息内容入正表
                    ##消息内容发送第三方
                    $br = '<br/>';
                    $appKey = 'd2e30eae64c44116c61745dd';
                    $masterSecret = 'a4f8c3c4827fafb1353b870c';
                    $mobile = get_table_field($uid,'id','mobile','UcenterMember');
                    $JPush = new \Vendor\JPush\src\JPush\JPush($appKey, $masterSecret);
                    // 完整的推送示例,包含指定Platform,指定Alias,Tag,指定iOS,Android notification,指定Message等
                        $result = $JPush->push()
                            ->setPlatform(array('ios', 'android'))
                            ->addAlias($mobile)
                                //->addTag(array('tag1', 'tag2'))
                          //->setPlatform('all')
                          //->addAllAudience('all')
                            ->setNotificationAlert('智能家居续费')
                            ->addAndroidNotification('Hi, android notification', 'notification title', 1, array("uid"=>$uid, "key2"=>"value2"))
                            ->addIosNotification("Hi, iOS notification", 'iOS sound', JPush::DISABLE_BADGE, true, 'iOS category', array("key1"=>"value1", "key2"=>"value2"))
                            ->setMessage($msg['content'], $msg['title'], 'type', array("uid"=>$uid, "key2"=>"value2"))
                            ->setOptions(100000, 3600, null, false)
                            ->send();             
                      //  echo 'Result=' . json_encode($result) . $br;
                      //  $res = json_encode($result,TRUE);
                       // $msg_id = explode(',', $res)[1];
                        $info = array();
                $info['info'] = '续费成功,消息已发送';
                $info['status'] = 1;
                $this->apiSuccess(TRUE,null, $info);
                }else{
                     $info['info'] = '续费失败';
                    $info['status'] = 303;
                    $this->apiError(FALSE,null, $info);
                }
            }
                
        }else{
                $info = array();
                $info['status'] = 304;
                $info['info'] = '订单不存在!';
                $this->apiError(FALSE,null, $info);
        }
    }
    
    /*
     * 用户定时布防、撤防
     */
    public function setMonitoringTime($id,$uid,$startTime,$endTime){
        if(empty($id) || empty($uid) || empty($startTime) || empty($endTime)){
            $info = array();
            $info['msg'] = '参数错误，请检查';
            $info['status'] = 0;
            $this->apiError(FALSE,NULL,$info);
        }else{
            $check = M('Equipment')->where(array('id'=>$id,'uid'=>$uid))->find();
            if(!$check){
                $data['timing_monitoring_start_time'] = $startTime;
                $data['timing_monitoring_end_time'] = $endTime;
                $res = M('Equipment')->where(array('id'=>$id,'uid'=>$uid))->save($data);
                if($rs){
                    $info = array();
                    $info['msg'] = '布撤防时间设置成功';
                    $info['status'] = 1;
                    $this->apiSuccess(TRUE,NULL,$info);
                }else{                   
                    $info = array();
                    $info['msg'] = '布撤防时间设置失败';
                    $info['status'] = 0;
                    $this->apiError(FALSE,NULL,$info);
                }        
            }
        }
    }
    
    /*
     * 用户开户
    */
    
    public function addAccount($uid,$name,$mobile,$address,$addDetail,$servicePrice,$serviceTime){
            $data['uid'] = $uid;
            $data['name'] = $name;
            $data['mobile'] = $mobile;
            ##APP传过来的为文字，转换成后台需要的数据格式
           $address = str2arr($address);//explode(',', $address);
                        if(is_array($address) && count($address)==3){
                            $data['address'] =  $address[0]. $address[1]. $address[2] ;
                        }else{
                            $info = array();
                            $info['msg'] = '地址参数错误，请检查';
                            $info['status'] = 10100;
                            $this->apiError(FALSE,NULL,$info);
                        }

            $data['address_detail'] = $addDetail;
            $data['service_price'] = $servicePrice;
            $data['service_time'] = $serviceTime;
            $data['createtime'] = time();
            $data['status'] = 1;
            $add = M('UserAccount')->add($data);
            if($add){
                return $add;
//                ##添加到订单列表中
//                $orderData[''] = 
//                $info = array();
//                $info['msg'] = '开户成功';
//                $info['status'] = 1;
//                $this->apiSuccess(TRUE, NULL, $info);
            }else{
                return false;
            }
     
    }
    
    //更新开户状态
    public function updataAccount($uid=0,$orderid=0,$status=0){
        
         $reslist = M('OrderList')->where(array('uid'=>$uid,'order_id'=>$orderid))->find();
         if($reslist){
             M('OrderList')->where(array('order_id'=>$id,'uid'=>$uid))->save(array('kh_status'=>$status));
             $res = M('UserAccount')->where(array('uid'=>$reslist['uid'],'id'=>$reslist['account_id']))->save(array('status'=>$status));
             if($res){
                $info = array();
                $info['msg'] = '开户成功';
                $info['status'] = 1;
                $info['data'] = $res;
                $this->apiSuccess(TRUE, NULL, $info);
             }
         }else{
                    $info = array();
                    $info['msg'] = '订单不存在!';
                    $info['status'] = 0;
                    $this->apiError(FALSE,NULL,$info);
         }
            
     
    }
	 
    /*
     * 户头列表
     */
    public function accountList($uid){
        if(empty($uid)){
            $info = array();
            $info['msg'] = '参数错误，请检查';
            $info['status'] = 0;
            $this->apiError(FALSE,NULL,$info);
        }else{
            $list = M('UserAccount')->where(array('uid'=>$uid,'status'=>1))->select();
            if($list){
                foreach ($list as $k =>$v){
                
                }
                $info = array();
                $info['msg'] = '列表拉取成功';
                $info['status'] = 1;
                $info['data'] = $list;
                $this->apiSuccess(TRUE,NULL, $info);
            }else{
                $info = array();
                $info['msg'] = '户头列表为空';
                $info['status'] = 0;
                $this->apiError(FALSE,NULL,$info);
            }
            
        }
    }
	
	  /*
     * 户头详细信息
     */
    public function accountInfo($id,$uid){
        if(empty($id) || empty($uid)){
            $info = array();
            $info['msg'] = '缺少参数，请检查';
            $info['status'] = 0;
            $this->apiError(FALSE,NULL,$info);
        }else{
            $accountInfo = M('UserAccount')->where(array('id'=>$id,'uid'=>$uid))->find();//户头信息
            $account_id = $accountInfo['id'];
            ##已有设备
            $own_device_list = M('DeviceType')->where(array('status'=>1))->select();
            foreach ($own_device_list as $k => $v){
                $own_device_list[$k]['count'] = M('Equipment')->where(array('account_id'=>$account_id,'uid'=>$uid,'e_type'=>$v['id']))->count();
            }
            if($accountInfo){
                $info['msg'] = '数据拉取成功';
                $info['status'] = 1;
                $info['data'] = array('accountInfo'=>$accountInfo,'Devicecount'=>$own_device_list);
                $this->apiSuccess(TRUE,NULL,$info);
            }else{
                $info = array();
                $info['msg'] = '没有该户头，请检查';
                $info['status'] = 0;
                $this->apiError(FALSE,NULL,$info);
            }
        }
    }
	
	/*
     * 加装设备列表
     */
    public function deviceList(){
        $list = M('Device')->where(array('status'=>1))->field(array('id','name','type','price'))->select();
        foreach($list as $k =>$v){
            $list['e_type'] = get_table_field($v['type'],'id','name','DeviceType');
        }
        if($list){
                $info = array();
                $info['msg'] = '数据拉取成功';
                $info['status'] = 1;
                $info['data'] = $list;
                $this->apiSuccess(TRUE,NULL,$info);
        }else{
                $info = array();
                $info['msg'] = '数据拉取失败';
                $info['status'] = 0;
                $this->apiError(FALSE,NULL,$info);
        }
    }
	/*
     * 加装设备列表
     */
    public function adddeviceList(){
        $list = M('Device')->where(array('status'=>1))->field(array('id','name','type','price'))->select();
        foreach($list as $k =>$v){
            $list[$k]['type'] = get_table_field($v['type'],'id','name','DeviceType');
        }
        if($list){
                $info = array();
                $info['msg'] = '数据拉取成功';
                $info['status'] = 1;
                $info['data'] = $list;
                $this->apiSuccess(TRUE,NULL,$info);
        }else{
                $info = array();
                $info['msg'] = '数据拉取失败';
                $info['status'] = 0;
                $this->apiError(FALSE,NULL,$info);
        }
    }
	
	    /**
     * 生成唯一订单号
     */
    public function build_order($uid,$good_id)
    {
        $no = date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8).$uid.$good_id.date('His');
        //检测是否存在
        $info = M('OrderList')->where(array('order_id'=>$no))->find();
		if(empty($info)){
			return $no;	

		}else{
			$this->build_order();
		}       
    }
	
	 /*
     * 加装下单
     */
    public function addDeviceOrder($file){
		$msg = json_decode($file,true);
		$uid = $msg['uid'];
		$data = $msg['data'];
		$account_id = $msg['account_id'];
        if(empty($uid)){
            $info=array();
            $info['msg'] = '请登录后再下单';
            $info['status'] = 0;
            $this->apiError(FALSE,NULL,$info);
        }
        if(empty($data)){
            $info=array();
            $info['msg'] = '您没有挑选设备';
            $info['status'] = 0;
            $this->apiError(FALSE,NULL,$info);
        }
        $check = M('UcenterMember')->where(array('status'=>1,'id'=>$uid))->find();//检测用户uid是否存在，防止非法下单
        if(!$check){
            $info=array();
            $info['msg'] = '账户没有激活或者没有此账户';
            $info['status'] = 0;
            $this->apiError(FALSE,NULL,$info);
        }else{
            foreach ($data as $k => $v){
                $add['good_id'] = $v['good_id'];
                $add['price'] = get_table_field($v['good_id'],'id','price','Device'); 
                $add['num'] = $v['num'];
                $add['uid'] = $uid;
				$good_id = $v['good_id'];
                $add['order_id'] = $this->build_order($uid,$good_id);
                $add['total_price'] = $add['price'] * $add['num'];
                $add['type'] = 2;
                $add['account_id'] = $account_id;
                $add['createtime'] = time();
                $add['status'] = 0;
		$result = M('OrderList')->add($add);
                     ##添加到用户设备表
                    $createtime['uid'] = $uid; 
                    $createtime['e_type'] = get_table_field($v['good_id'],'id','type','Device'); 
                    $createtime['e_name'] = get_table_field($uid,'uid','nickname','Member').'的设备'; 
                    $createtime['account_id'] = $account_id;
                    $createtime['createtime'] = time(); 
                    $add_serviceTime = date('Y',  time()) + 1 . '-' .date('m-d H:i:s',  time());//续费后的日期                    
                    $createtime['servicetime'] =strtotime($add_serviceTime); 
                    $createtime['root_id'] = $uid; 
                    $createtime['member'] = $uid; 
 
            }
            
            if($result || $add){
                 ##发送消息给用户（第三方推送《极光推送》）
                ##消息内容入库
                $msg['from_id'] = 1;//系统消息
                $msg['title'] = '设备加装';
                $msg['content'] = '尊敬的'.get_table_field($uid,'id','username','UcenterMember').'用户，您已下单成功';
                $msg['create_time'] = time();
                $msg['status'] = 1;
                $add_msg = M('MessageContent')->add($msg);//消息内容入副表
                $message['content_id'] = $add_msg;
                $message['from_id'] = 1;
                $message['to_id'] = $uid;
                $message['create_time'] = time();
                $message['is_read'] = 0;
                $message['last_toast'] = 0;
                $message['status'] = 1;
                $add_message = M('Message')->add($message);//消息内容入正表
                ##消息内容发送第三方
                $br = '<br/>';
                $appKey = 'd2e30eae64c44116c61745dd';
                $masterSecret = 'a4f8c3c4827fafb1353b870c';
                $mobile = get_table_field($uid,'id','mobile','UcenterMember');
                $JPush = new \Vendor\JPush\src\JPush\JPush($appKey, $masterSecret);
                // 完整的推送示例,包含指定Platform,指定Alias,Tag,指定iOS,Android notification,指定Message等
                    $result = $JPush->push()
                        ->setPlatform(array('ios', 'android'))
                        ->addAlias($mobile)
                            //->addTag(array('tag1', 'tag2'))
                      //->setPlatform('all')
                      //->addAllAudience('all')
                        ->setNotificationAlert('设备加装')
                        ->addAndroidNotification('设备加装下单成功', '设备加装', 1, array("uid"=>$uid, "key2"=>"value2"))
                        ->addIosNotification("设备加装下单成功", 'iOS sound', JPush::DISABLE_BADGE, true, 'iOS category', array("key1"=>"value1", "key2"=>"value2"))
                        ->setMessage($msg['content'], $msg['title'], 'type', array("uid"=>$uid, "key2"=>"value2"))
                        ->setOptions(100000, 3600, null, false)
                        ->send();             
                $info = array();
                $info['msg'] = '下单成功';
                $info['status'] = 1;
                $this->apiSuccess(TRUE,null,$info);
            }else{
                $info = array();
                $info['msg'] = '下单失败，请重试';
                $info['status'] = 0;
                $this->apiError(FALSE,NULL,$info);
            }
            
        }
    }
    
    /*
     * 积分兑换下单
     * $file 字符串 
     */
    
    public function addScoreShopOrder($file){
        $msg = json_decode($file,true);
		$uid = $msg['uid'];
		$data = $msg['data'];
        if(empty($uid)){
            $info=array();
            $info['msg'] = '请登录后再下单';
            $info['status'] = 0;
            $this->apiError(FALSE,NULL,$info);
        }
        if(empty($data)){
            $info=array();
            $info['msg'] = '请选择商品';
            $info['status'] = 0;
            $this->apiError(FALSE,NULL,$info);
        }
        $check = M('UcenterMember')->where(array('status'=>1,'id'=>$uid))->find();//检测用户uid是否存在，防止非法下单
        if(!$check){
            $info=array();
            $info['msg'] = '账户没有激活或者没有此账户';
            $info['status'] = 0;
            $this->apiError(FALSE,NULL,$info);
        }else{
            ##请选择商品
            $orderInfo = M()->where()->select();
            foreach ($data as $k => $v){
                $add['good_id'] = $v['good_id'];
                $add['price'] = get_table_field($v['good_id'],'id','price','Device'); 
                $add['num'] = $v['num'];
                $add['uid'] = $uid;
				$good_id = $v['good_id'];
                $add['order_id'] = $this->build_order($uid,$good_id);
                $add['total_price'] = $add['price'] * $add['num'];
                $add['type'] = 2;
                $add['account_id'] = $account_id;
                $add['createtime'] = time();
                $add['status'] = 0;
		$result = M('OrderList')->add($add);
                if($result){
                     ##添加到用户设备表
                    $createtime['uid'] = $uid; 
                    $createtime['e_type'] = get_table_field($v['good_id'],'id','type','Device'); 
                    $createtime['e_name'] = get_table_field($uid,'uid','nickname','Member').'的设备'; 
                    $createtime['account_id'] = $account_id;
                    $createtime['createtime'] = time(); 
                    $add_serviceTime = date('Y',  time()) + 1 . '-' .date('m-d H:i:s',  time());//续费后的日期                    
                    $createtime['servicetime'] =strtotime($add_serviceTime); 
                    $createtime['root_id'] = $uid; 
                    $add = M('Equipment')->add($createtime);
                }
            }
            
            if($result || $add){
                ##发送消息给用户（第三方推送《极光推送》）
                ##消息内容入库
                $msg['from_id'] = 1;//系统消息
                $msg['title'] = '设备加装';
                $msg['content'] = '尊敬的'.get_table_field($uid,'id','username','UcenterMember').'用户，您已下单成功';
                $msg['create_time'] = time();
                $msg['status'] = 1;
                $add_msg = M('MessageContent')->add($msg);//消息内容入副表
                $message['content_id'] = $add_msg;
                $message['from_id'] = 1;
                $message['to_id'] = $uid;
                $message['create_time'] = time();
                $message['is_read'] = 0;
                $message['last_toast'] = 0;
                $message['status'] = 1;
                $add_message = M('Message')->add($message);//消息内容入正表
                ##消息内容发送第三方
                $br = '<br/>';
                $appKey = 'd2e30eae64c44116c61745dd';
                $masterSecret = 'a4f8c3c4827fafb1353b870c';
                $mobile = get_table_field($uid,'id','mobile','UcenterMember');
                $JPush = new \Vendor\JPush\src\JPush\JPush($appKey, $masterSecret);
                // 完整的推送示例,包含指定Platform,指定Alias,Tag,指定iOS,Android notification,指定Message等
                    $result = $JPush->push()
                        ->setPlatform(array('ios', 'android'))
                        ->addAlias($mobile)
                            //->addTag(array('tag1', 'tag2'))
                      //->setPlatform('all')
                      //->addAllAudience('all')
                        ->setNotificationAlert('设备加装')
                        ->addAndroidNotification('设备加装下单成功', '设备加装', 1, array("uid"=>$uid, "key2"=>"value2"))
                        ->addIosNotification("设备加装下单成功", 'iOS sound', JPush::DISABLE_BADGE, true, 'iOS category', array("key1"=>"value1", "key2"=>"value2"))
                        ->setMessage($msg['content'], $msg['title'], 'type', array("uid"=>$uid, "key2"=>"value2"))
                        ->setOptions(100000, 3600, null, false)
                        ->send();             
                $info = array();
                $info['msg'] = '下单成功';
                $info['status'] = 1;
                $this->apiSuccess(TRUE,null,$info);
            }else{
                $info = array();
                $info['msg'] = '下单失败，请重试';
                $info['status'] = 0;
                $this->apiError(FALSE,NULL,$info);
            }
            
        }
    }




    /*
     * 个人中心设备设置
     */
    public function userCenterSet($uid){
        if(empty($uid)){
            $info = array();
            $info['status'] = 0;
            $info['info'] = '没有用户UID,请登录';
            $this->apiError(false, null, $info);
        }
        
        //查有权限的设备id列表
        $resauth = M('EquipmentAuthRule')->where(array('uid'=>$uid))->field('e_id')->select();
        if(!$resauth){
            $info = array();
            $info['status'] = 0;
            $info['info'] = '您当前没有权限。';
            $this->apiError(false, null, $info);die;
        }
        $e_id_arr = [];
        foreach ($resauth as $rv){
            $e_id_arr[] = $rv['e_id'];
        }
        
        $where['status'] = 1;
        $where['id'] = array('in',$e_id_arr);
        
        $where = whereAddTableName($where, 'ne_equipment');
        $list = M('Equipment')        
        ->join(" ne_picture t3 on ne_equipment.pic = t3.id","left")
        ->where($where)
        ->field('ne_equipment.*,t3.path pic')
        ->order('ne_equipment.createtime desc')
        ->select();
        
        if(!$list) {
            $info = array();
            $info['status'] = 0;
            $info['info'] = '该用户还没有设备';
            $this->apiError(false, null, $info);
        }
        
        foreach ($list as $k => $v) {
            /* if(!(in_array_case($uid,str2arr($v['member'])))){
             unset($list[$k]);
             continue;
             } */
            $list[$k]['pic'] = !empty($v['pic']) ? $v['pic'] : false;
        }
               
        $info = array();
        $info['msg'] = '数据拉取成功';
        $info['status'] = 1;
        $info['data'] = $list;
        $this->apiSuccess(TRUE,NULL,$info);            
    }
    
    /*
     * 个人中心设备设置-->设备参数设置
     */

    public function userSystemSet($id = "",$uid=""){
        if(empty($id) || empty($uid)){
            $info = array();
            $info['msg'] = '缺少参数，请检查';
            $info['status'] = 0;
            $this->apiError(FALSE,NULL,$info);
        }else{
            $list = M('Equipment')->where(array('id'=>$id))->find();
			##查该用户的权限
			$auth = M('EquipmentAuthRule')->where(array('e_id'=>$id,'uid'=>$uid))->find();
            if($list){
                $info = array();
                $info['msg'] = '数据拉取成功';
                $info['status'] = 1;
                $this->apiSuccess(TRUE,NULL,array(
                    'pic'=> get_table_field($list['pic'],'id','path','Picture'),
                    'name'=>$list['e_name'],
                    'e_id'=>$list['e_id'],
                    'address'=>$list['address'],
					'auth' => $auth,
                ));
            }else{
                $info = array();
                $info['msg'] = '该用户还没有设备';
                $info['status'] = 0;
                $this->apiError(FALSE,NULL,$info);
            }
        }
    }
    
    /*
     * 个人中心设备设置-->修改设备名称
     */    
    public function updateDeviceName($id,$uid,$name){
        if(empty($id) || empty($uid) || empty($name)){
            $info = array();
            $info['msg'] = '缺少参数，请检查';
            $info['status'] = 0;
            $this->apiError(FALSE,NULL,$info);
        }
        
        $where = array('id'=>$id);
        if(!is_numeric($id)){
            $where = array('e_id'=>$id); //非数字，则查e_id
        }
        
        $data = M('Equipment')->where($where)->find();
        if(!$data){
            $info = array();
            $info['msg'] = '设备不存在';
            $info['status'] = 0;
            $this->apiError(FALSE,NULL,$info);
        }
        
        if($data['uid'] != $uid){
            $info = array();
            $info['msg'] = '非管理员,不能修改';
            $info['status'] = 0;
            $this->apiError(FALSE,NULL,$info);
        }
        
        $update = array();
        $update['e_name'] = $name;
        $res = M('Equipment')->where(array('id'=>$data['id']))->save($update);
        if($res){
            $info = array();
            $info['msg'] = '设备名称修改成功';
            $info['status'] = 1;
            $this->apiSuccess(TRUE,NULL,$info);
        }else{
            $info = array();
            $info['msg'] = '设备名称修改失败，请检查';
            $info['status'] = 0;
            $this->apiError(FALSE,NULL,$info);
        }        
    }
	
	/*
     * 个人中心设备设置-->修改设备密码
     */
    public function updateDevicePassword($id,$uid,$oldPwd,$newPwd){
        if(empty($id) || empty($uid)){
            $info = array();
            $info['msg'] = '缺少参数，请检查';
            $info['status'] = 0;
            $this->apiError(FALSE,NULL,$info);
        }        
        if(empty($oldPwd)){
            $info = array();
            $info['msg'] = '原始密码不能为空';
            $info['status'] = 0;
            $this->apiError(FALSE,NULL,$info);
        }
        if(empty($newPwd)){
            $info = array();
            $info['msg'] = '新密码不能为空';
            $info['status'] = 0;
            $this->apiError(FALSE,NULL,$info);
        }
        
        $where = array('id'=>$id);
        if(!is_numeric($id)){
            $where = array('e_id'=>$id); //非数字，则查e_id
        }        
        
        $check = M('Equipment')->where($where)->getField('id,uid,e_pwd');
        if(!$check){
            $info = array();
            $info['msg'] = '设备不存在';
            $info['status'] = 0;
            $this->apiError(FALSE,NULL,$info);
        }
        if($uid != $check['uid']){
            $info = array();
            $info['msg'] = '非管理员，不能修改';
            $info['status'] = 0;
            $this->apiError(FALSE,NULL,$info);
        }
        if($oldPwd != $check['e_pwd']){
            $info = array();
            $info['msg'] = '原始密码错误';
            $info['status'] = 0;
            $this->apiError(FALSE,NULL,$info);
        }       
       
        
        $data = array();
        $data['e_pwd'] = $newPwd;
        $res = M('Equipment')->where(array('id'=>$check['id']))->save($data);
        if($res){
            $info = array();
            $info['msg'] = '设备密码修改成功';
            $info['status'] = 1;
            $this->apiSuccess(TRUE,NULL,$info);
        }else{
            $info = array();
            $info['msg'] = '设备密码修改失败，请重试';
            $info['status'] = 0;
            $this->apiError(FALSE,NULL,$info);
        }   
    }
    /*
     * 用户头像保存
     * uid  用户uid
     * pic  图片地址id
     */
    
    public function saveUserImage($uid,$pic){
        if(empty($uid) || empty($pic)){
            $info = array();
            $info['msg'] = '缺少参数，请检查';
            $info['status'] = 0;
            $this->apiError(FALSE,NULL,$info);
        }else{
            $data['pic'] = $pic;
            $save = M('UcenterMember')->where(array('id'=>$uid))->save($data);
            if($save){
                $info = array();
                $info['msg'] = '头像保存成功';
                $info['status'] = 1;
                $this->apiSuccess(TRUE,NULL,$info);
            }else{
                $info = array();
                $info['msg'] = '头像保存失败';
                $info['status'] = 0;
                $this->apiError(FALSE,NULL,$info);
            }
        }
    }
    /*
     * 删除用户设备 
     * $id  设备表ID
     * uid  用户UID
     */
    public function delUserEquiment($id,$uid){
        if(empty($id) || empty($uid)){
            $info = array();
            $info['msg'] = '缺少参数，请检查';
            $info['status'] = 0;
            $this->apiError(FALSE,NULL,$info);
        }
         
        $where = array('id'=>$id);
        if(!is_numeric($id)){
            $where = array('e_id'=>$id); //非数字，则查e_id
        }
            
        ##检查当前设备是否存在
        $check = M('Equipment')->where($where)->find();
        if(!$check){
            $info = array();
            $info['msg'] = '没有该设备';
            $info['status'] = 0;
            $this->apiError(FALSE,NULL,$info);
        }
        if($check['root_id'] == $uid){
            $info = array();
            $info['msg'] = '管理员不能删除当前数据';
            $info['status'] = 0;
            $this->apiError(FALSE,NULL,$info);
        }
        
        $e_id = $check['id'];//设备ID  
        ##删除设备权限表中该用户的权限
        $res = M('EquipmentAuthRule')->where(array('e_id'=>$e_id,'uid'=>$uid))->delete();
        if($res){
            
            ##更新设备表中的member
            $da = array();
            $da['member'] = $this->getDeviceMemberList($e_id);
            $auto = M('Equipment')->where(array('id'=>$e_id))->save($da);
            
            $info = array();
            $info['msg'] = '成员删除成功';
            $info['status'] = 1;
            $this->apiSuccess(TRUE,NULL,$info);
        }else{
            $info = array();
            $info['msg'] = '设备成员中没有该成员';
            $info['status'] = 0;
            $this->apiError(FALSE,NULL,$info);
        }  
        
    }
    
    //获取制定设备成员列表
    private function getDeviceMemberList($e_id){
        $member_list_str = '';
        if(empty($e_id)){
            return $member_list_str;
        }
        $list = M('EquipmentAuthRule')->where(array('e_id' => $e_id))->field('uid')->order('e_id asc')->select();
        foreach ($list as $v){
            $member_list_str .= ',' .$v['uid'];
        }
        $member_list_str = ltrim($member_list_str, ',');
        return $member_list_str;
    }
    
    /*
     * 管理员转让
     * $id 设备ID
     * $uid 当前操作请求用户UID
     * $to_uid 目标成员uid
     */
    public function moveAdmin($id,$uid,$to_uid){
        if(empty($id) || empty($uid)){
                $info = array();
                $info['msg'] = '参数丢失，请检查';
                $info['status'] = 0;
                $this->apiError(FALSE,NULL,$info);
        }else{
            ##检查该UID用户是否为该设备的管理员
            $check_admin = M('Equipment')->where(array('id'=>$id,'root_id'=>$uid))->find();
            if(!$check_admin){
                $info = array();
                $info['msg'] = '对不起，您没有此权限';
                $info['status'] = 0;
                $this->apiError(FALSE,NULL,$info);
            }else{
               ##检查被转让管理员的用户是否为该设备的成员
                $check_into_member = M('EquipmentAuthRule')->where(array('e_id'=>$id,'uid'=>$to_uid))->find();
                if(empty($check_into_member)){
                    $info = array();
                    $info['msg'] = '该设备暂无成员，无法进行此操作';
                    $info['status'] = 0;
                    $this->apiError(FALSE,NULL,$info);
                }
                ##被转让的用户在该设备的成员列表中，则执行管理员转让
                $data['root_id'] = $to_uid;
                $move_admin = M('Equipment')->where(array('id'=>$id,'root_id'=>$uid))->save($data);
                ##在权限表中删除被转让成员的权限，把未转让之前该设备管理员添加到成员权限表中（不会继承被转让的成员的权限）
                $del_member_rule = M('EquipmentAuthRule')->where(array('e_id'=>$id,'uid'=>$to_uid))->delete();
                ##原管理员添加成员权限表中的数据
                $newDate['uid'] = $uid;
                $newDate['e_id'] = $id;
                $newDate['is_setPwd'] = 0;
                $newDate['is_online'] = 0;
                $newDate['createtime'] = time();
                $newDate['status'] = 1;
                $add_admin_rule = M('EquipmentAuthRule')->add($newDate);
                ##判断三个条件是否都已经成立
                if($move_admin && $del_member_rule && $add_admin_rule){
                    ##调用极光推送想原管理员发信息
                    $title = '管理员转让通知';
                    $content = '尊敬的'.get_table_field($uid,'id','username','UcenterMember').'用户，您已成功转让管理员给'.get_table_field($to_uid,'id','username','UcenterMember').'成员';
                    $this->JpushMessage($title, $content, $uid);
                    ##调用极光推送给该目标成员发送消息
                    $content_member = '尊敬的'.get_table_field($to_uid,'id','username','UcenterMember').'用户，您已成为设备序列号为'.get_table_field($id,'id','e_id','Equipment').'的管理员';
                    $this->JpushMessage($title, $content_member, $to_uid);
                    $info = array();
                    $info['msg'] = '管理员转让成功';
                    $info['status'] = 1;
                    $this->apiSuccess(TRUE,NULL,$info);
                }else{
                    $info = array();
                    $info['msg'] = '管理员转让失败，请重试';
                    $info['status'] = 0;
                    $this->apiError(TRUE,NULL,$info);
                }
                
            }
            
            
        }
    }
    
    /*
     * 微信分享后增加用户积分
     * uid 用户uid
     */
    public function wechatShareScore($uid){
        if(empty($uid)){
            $info = array();
            $info['msg'] = '参数丢失';
            $info['status'] = 0;
            $this->apiError(TRUE,NULL,$info);
        }else{
            ##查询该用户是不是第一次操作分享微信
            $check = M('UcenterMember')->where(array('id'=>$uid))->field(array('wechat_share_time'))->find();
         
            ##取得用户当前的积分值
            $score = M('Member')->where(array('uid'=>$uid))->field(array('score1'))->find();
            if(empty($check['wechat_share_time'])){
                ##第一次进行维信分享操作
                ##取得微信分享增加积分的阀值
                $score_num = M('ScoreRule')->where(array('tag'=>'WCHAT_SHARE_SCORE'))->field(array('value','rule_time'))->find();
                $createtime['wechat_share_time'] = NOW_TIME;//积分变更时间
                $add_score = M('Member')->where(array('uid'=>$uid))->setInc("score1",$score_num['value']);
                $add_time = M('UcenterMember')->where(array('id'=>$uid))->save($createtime);
                if($add_score || $add_time){

                    $usernames=get_table_field($uid,'uid','nickname','Member');
                    $scoredata['uid']=$uid;
                    $scoredata['ip']=ipton(get_client_ip());
                    $scoredata['type']=5;  //微信分享
                    $scoredata['action']="wechat_score";
                    $scoredata['value']=$score_num['value'];
                    $scoredata['create_time']=NOW_TIME;
                    $scoredata['remark']=$usernames."在" .date("Y-m-d H:i:s", NOW_TIME)."通过微信分享，获得".$score_num['value']."积分。";

                    M('ScoreLog')->add($scoredata);     
                    $this->JpushMessage('积分消费提醒',$usernames."在" .date("Y-m-d H:i:s", NOW_TIME)."通过微信分享，获得".$score_num['value']."积分。",$uid);
                    $info = array();
                    $info['msg'] = '积分增加'.$score_num['value'].'分';
                    $info['status'] = 1;
                    $this->apiSuccess(TRUE,NULL,$info);
                }else{
                    $info = array();
                    $info['msg'] = '积分增加失败';
                    $info['status'] = 0;
                    $this->apiError(TRUE,NULL,$info);
                }
            }else{
                ##检查时间是否达到分享增加积分的阀值
                ##算出上次分享时间与现在时间的时间差               
                $past = $check['wechat_share_time']; // Some timestamp in the past
                $diff = NOW_TIME- $past;
                $errand = round(($diff)/3600/24);//time2Units($diff);
                $score_num = M('ScoreRule')->where(array('tag'=>'WCHAT_SHARE_SCORE'))->field(array('value','rule_time'))->find();
                

                if($errand >= $score_num['rule_time']){
                     ##取得微信分享增加积分的阀值
                    
                    $createtime['wechat_share_time'] = NOW_TIME;//积分变更时间
                    $add_score = M('Member')->where(array('uid'=>$uid))->setInc("score1",$score_num['value']);
                    $add_time = M('UcenterMember')->where(array('id'=>$uid))->save($createtime);
                    if($add_score || $add_time){
                        $usernames=get_table_field($uid,'uid','nickname','Member');
                    $scoredata['uid']=$uid;
                    $scoredata['ip']=ipton(get_client_ip());
                    $scoredata['type']=5;  //微信分享
                    $scoredata['action']="wechat_score";
                    $scoredata['value']=$score_num['value'];
                    $scoredata['create_time']=NOW_TIME;
                    $scoredata['remark']=$usernames."在" .date("Y-m-d H:i:s", NOW_TIME)."通过微信分享，获得".$score_num['value']."积分。";

                    M('ScoreLog')->add($scoredata);     
                    $this->JpushMessage('积分消费提醒',$usernames."在" .date("Y-m-d H:i:s", NOW_TIME)."通过微信分享，获得".$score_num['value']."积分。",$uid);
                   
                        $info = array();
                        $info['msg'] = '积分增加'.$score_num['value'].'分';
                        $info['status'] = 1;
                        $this->apiSuccess(TRUE,NULL,$info);
                    }else{
                        $info = array();
                        $info['msg'] = '积分增加失败';
                        $info['status'] = 0;
                        $this->apiError(TRUE,NULL,$info);
                    }
                }else{
                        $info = array();
                        $info['msg'] = '积分增加失败，还不到时间哦！';
                        $info['status'] = 0;
                        $this->apiError(TRUE,NULL,$info);
                }
            }
        }
    }
	
	 /*
     * 设备登记
     * $username  DDNS 帐号
     * $userpwd  DDNS 密码
     * $vertype  网络摄像机型号
     * $language  语言版本
     * $dtype    0
     * $tcpport 网络摄像机访问端口
     * $lanip   网络摄像机局域网 IP 地址
     * $cmdport  网络摄像机命令端口
     * $rtspport 网络摄像机 RTSP 端口
     * $dataport 网络摄像机数据端口
     * 返回内容:(返回内容为字符串)
       “Update-OK” :登记成功 “ERRIDS_SERVER_NOAUTH”:没有权限 “ERRIDS_SERVER_NOID”:帐号不存在 
     * “ERRIDS_SERVER_OVER”:帐号已过期 “ERRIDS_SERVER_ERR_IDDISABLE”:帐号已被禁用 “ERRIDS_SERVER_ERR_PARAM”:参数错误
     */
    public function userip($username="",$userpwd="",$vertype="",$language="",$dtype="",$tcpport="",$lanip="",$cmdport="",$rtspport="",$dataport=""){
        if(IS_GET){
            $data['username'] = $username;
            $data['userpwd'] = $userpwd;
            $data['vertype'] = $vertype;
            $data['language'] = $language;
            $data['dtype'] = $dtype;
            $data['tcpport'] = $tcpport;
            $data['lanip'] = $lanip;
            $data['cmdport'] = $cmdport;
            $data['rtspport'] = $rtspport;
            $data['dataport'] = $dataport;
            $data['createtime'] = time();
            $res = M('DeviceCallPoliceLog')->add($data);
            if($res){             
                echo 'Update-OK';
            }else{
               echo "ERRIDS_SERVER_ERR_PARAM";
            }
        }else{
                //$info = array();
                //$info['msg'] = '非法请求';
                //$info['status'] = 401;
                //$this->apiError(TRUE,NULL,$info);
            echo 'ERRIDS_SERVER_NOAUTH';
        }
       
    }
    /*
     * 设备报警
     * 指定返回指令
     * OK 服务器正常收到
     * ERRIDS_SERVER_OFFLINE  账号不存在或账号不在线
     * ERRIDS_SERVER_ERR_PARAM  参数错误
     * IDS_SERVER_ERR_ONALARM  服务发生错误
     */
    public function alarm($username='',$userpwd='',$uid='',$rea='',$io=''){
        if(IS_GET){
            $data['username'] = $username;
            $data['userpwd'] = $userpwd;
            $data['uid'] = $uid;
            $data['res'] = $rea;
            $data['io'] = $io;
            $data['createtime'] = time();
            $res = M('Alarm')->add($data);
            if($res){
                $e_id=strSplit($uid);
                $res=M("equipment")->where(array('e_id'=>$e_id))->find();
                
                ##消息推送
                $title = '设备报警';
                $content = '您设备编号为'.$e_id.'的设备于'.date("Y-m-d H:i:s", NOW_TIME).'触发报警';
                $uid = $res['uid'];
                $this->JpushMessage($title, $content, $uid);
                ##消息入消息列表
                    # 1.入消息副表
                       $msg['from_id'] = 1;
                       $msg['title'] = $title;
                       $msg['content'] = $content;
                       $msg['create_time'] = time();
                       $msg['status'] = 1;
                       $add_msg_content = M('MessageContent')->add($msg);
                    #  2.消息入正表
                       $msg1['content_id'] = $add_msg_content;
                       $msg1['from_uid'] = 1;
                       $msg1['to_uid'] = 115;
                       $msg1['create_time'] = time();
                       $msg1['is_read'] = 0;
                       $msg1['last_toast'] = 0;
                       $msg1['status'] = 1;
                       $add_msg = M('Message')->add($msg1);
               echo "OK";
            }else{
                echo "ERRIDS_SERVER_ERR_PARAM";
            }
        }else{
            echo 'IDS_SERVER_ERR_ONALARM';
                //$info = array();
                //$info['msg'] = '非法请求';
                //$info['status'] = 401;
                //$this->apiError(TRUE,NULL,$info);
        }
    }

    public function setProfile($uid = '',$signature = null, $email = null, $name = null, $sex = null, $birthday = null) {
        $this->requireLogin();
        //获取用户编号
        $uid = $this->getUid();
        //将需要修改的字段填入数组
        $fields = array();
        if ($signature !== null)
            $fields['signature'] = $signature;
        if ($email !== null)
            $fields['email'] = $email;
        if ($name !== null)
            $fields['name'] = $name;
        if ($sex !== null)
            $fields['sex'] = $sex;
        if ($birthday !== null)
            $fields['birthday'] = $birthday;

        foreach ($fields as $key => $field) {
            clean_query_user_cache($this->getUid(), $key); //删除缓存
        }
        //将字段分割成两部分，一部分属于ucenter，一部分属于home
        $split = $this->splitUserFields($fields);
        $home = $split['home'];
        $ucenter = $split['ucenter'];
        //分别将数据保存到不同的数据表中
        if ($home) {
            /* if (isset($home['sex'])) {
              $home['sex'] = $this->decodeSex($home['sex']);
              } */
            $home['uid'] = $uid;
            $model = D('Home/Member');
            $home = $model->create($home);
            $result = $model->where(array('uid' => $uid))->save($home);
            if (!$result) {
                $this->apiError(0, '设置失败，请检查输入格式!');
            }
        }
        if ($ucenter) {
            $model = D('User/UcenterMember');
            $ucenter['id'] = $uid;
            $ucenter = $model->create($ucenter);
            $result = $model->where(array('id' => $uid))->save($ucenter);
            if (!$result) {
                $this->apiError(0, '设置失败，请检查输入格式!');
            }
        }
        //返回成功信息
        $this->apiSuccess("设置成功!");
    }
    /*
     * 修改密码
     */

    public function submitPassword($uid = '',$old = '',$password='',$repassword='') {
        //获取参数
        if (IS_POST) {
            $password = I('post.old');
            empty($password) && $this->error('请输入原密码');
            $data['password'] = I('post.password');
            empty($data['password']) && $this->error('请输入新密码');
            $repassword = I('post.repassword');
            empty($repassword) && $this->error('请输入确认密码');
        } else {
            $password = $_REQUEST['post.old'];
            empty($password) && $this->error('请输入原密码');
            $data['password'] = $_REQUEST['post.password'];
            empty($data['password']) && $this->error('请输入新密码');
            $repassword = $_REQUEST['post.repassword'];
            empty($repassword) && $this->error('请输入确认密码');
        }
        if ($data['password'] !== $repassword) {
           // $this->error('您输入的新密码与确认密码不一致');
            $info =array();
            $info['status']= 0;
            $info['info']='您输入的新密码与确认密码不一致';
            $this->apiError('true',null, $info);
        }

        $Api = new UserApi();
        $res = $Api->updateInfo(UID, $password, $data);
        if ($res['status']) {
            $info =array();
            $info['status']= 1;
            $info['info']='密码修改成功';
            $this->apiSuccess('true',null, $info);
        } else {
            $this->apiError($res['info']);
        }
    }
  
     public function setProfiles($uid = '',$username = null,$nickname=null, $sex = null, $birthday = null) {
        //$this->requireLogin();
        //获取用户编号
        //$uid = $this->getUid();
        $uid = $_REQUEST['uid'];
        
        //将需要修改的字段填入数组
        $fields = array();
        if ($username !== null)
            $fields['username'] = $username;
        if ($nickname !== null)
            $fields['nickname'] = $nickname;
        if ($email !== null)
            $fields['email'] = $email;
       if ($sex !== null)
            $fields['sex'] = $sex;
        if ($birthday !== null)
            $fields['birthday'] = $birthday;

        foreach ($fields as $key => $field) {
            clean_query_user_cache($this->getUid(), $key); //删除缓存
        }
        //将字段分割成两部分，一部分属于ucenter，一部分属于home
        $split = $this->splitUserFields($fields);
        $home = $split['home'];
        $ucenter = $split['ucenter'];
        //分别将数据保存到不同的数据表中
        if ($home) {
            /* if (isset($home['sex'])) {
              $home['sex'] = $this->decodeSex($home['sex']);
              } */
            $home['uid'] = $uid;
            $model = D('Home/Member');
            $home = $model->create($home);
            $result = $model->where(array('uid' => $uid))->save($home);
            if (!$result) {
                $this->apiError(0, '设置失败，请检查输入格式!');
            }
        }
        if ($ucenter) {
            $model = D('User/UcenterMember');
            $ucenter['id'] = $uid;
            $ucenter = $model->create($ucenter);
            $result = $model->where(array('id' => $uid))->save($ucenter);
            if (!$result) {
                $this->apiError(0, '设置失败，请检查输入格式!');
            }
        }
        //返回成功信息
        
        $info['status'] = 0 ;
        $info['info']= "设置成功";
        $this->apiSuccess('true' ,null ,$info);
    }

    // 后台预警
    public function yujin(){
            $msg=M("Alarm")->where(array('status'=>0))->select();
            $count=M("Alarm")->where(array('status'=>0))->count();
            if($msg){
                $info['status'] = 1 ;
                $info['info']= "发现新的报警";
                $info['data']= $msg;
                $info['count']= $count;
                $this->apiSuccess('true' ,null ,$info);
            }else{
                $this->apiError(0, 'false');
            }
    }

    //取消预警
    public function yujin_c(){
            $msg=M("Alarm")->where(array('status'=>0))->save(array('status'=>1));
            if($msg){
                $info['status'] = 1 ;
                $info['info']= "ok";
                $info['data']= $msg;
                $this->apiSuccess('true' ,null ,$info);
            }else{
                $this->apiError(0, 'false');
            }
    }

    //订单通知
    public function order_msg(){
            $msg=M("Message")->where(array('msg_status'=>9999))->select();
            $count=M("Message")->where(array('msg_status'=>9999))->count();
            if($msg){
                $info['status'] = 1 ;
                $info['info']= "有新的订单";
                $info['data']= $msg;
                $info['count']= $count;
                $this->apiSuccess('true' ,null ,$info);
            }else{
                $this->apiError(0, 'false');
            }
    }

    //取消通知
    public function order_msg_c(){
            $res=M("Message")->where(array('msg_status'=>9999))->find();
            $msg=M("Message")->where(array('msg_status'=>9999))->save(array('msg_status'=>1));
            if($msg){
                $info['status'] = 1 ;
                $info['info']= "ok";
                $info['data']= $res;
                $this->apiSuccess('true' ,null ,$info);
            }else{
                $this->apiError(0, 'false');
            }
    }

    /*
     * 删除消息
     * $id  设备表ID
     * uid  用户UID
     */
    public function del_message($id=0,$uid=0){
        $content_id=$msg=M("Message")->where(array('id'=>$id,'to_uid'=>$uid))->find();       
        if($content_id){
            $msg=M("Message")->where(array('id'=>$id,'to_uid'=>$uid))->delete();
            $res=M("MessageContent")->where(array('id'=>$content_id['content_id']))->delete();
            if($res){
                $info['status'] = 1;
                $info['info']= "删除成功";
                $info['data']= $res;
                $this->apiSuccess('true' ,null ,$info);
            }else{
                $this->apiError(0, '信息删除失败');
            }
        }else{
            $this->apiError(0, '信息不存在');
        }
    }
    
    
}
