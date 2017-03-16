<?php

/**
 * Created by Netbeans.
 * User: caipeichao
 * Date: 1/16/14
 * Time: 9:40 PM
 */

namespace Api\Controller;

use Addons\Avatar\AvatarAddon;
//use Addons\LocalComment\LocalCommentAddon;
//use Addons\Favorite\FavoriteAddon;
use Addons\Tianyi\TianyiAddon;

class IndexController extends ApiController {

    private $user;

    public function _initialize() {
        
        $this->user = A("User");
    }
    
     /*
     * 用户支付接口
     * $ptype 支付类型  1支付宝  2微信
     * $orderid订单ID
     * otype   订单类型  1  开户订单  2续费订单
     */

    public function paytype($ptype = 2, $orderid = 0, $otype = 0) {
        if (IS_POST) {
            if (empty($orderid) || empty($otype)) {
                $this->not_strerr();
            }


            $reslist = M("OrderList")->where(array('order_id' => $orderid))->find();
            if (!$reslist) {
                $this->not_dataerr();
            }

            if ($reslist['account_id']) {
                $ress = M('UserAccount')->where(array('id' => $reslist['account_id']))->find();
                $reslist['users'] = $ress;
            }


            if ($otype == 2) {
                $total_fee = 380 * $reslist['xf_yeah'] * 100;
                $reslist['body'] = "用户" . $reslist['users']['name'] . "续费";
            }
//        print_r($reslist);
//        exit;
//            $goodname = M("shop")->where(array('id' => $reslist['goods_id']))->find();


            switch ($ptype) {
                case 1:
                    import('Common.Alipay.aop.AopClient', APP_PATH, '.php');
                    import('Common.Alipay.aop.request.AlipayTradeAppPayRequest', APP_PATH, '.php');

                    $aop = new \AopClient();

                    $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
                    $aop->appId = '2016052101425840';
                    $aop->rsaPrivateKeyFilePath = 'http://112.74.81.17/key/rsa_private_key.pem';
//                    $aop->rsaPublicKeyFilePath = 'http://112.74.81.17/key/rsa_public_key.pem';
                    $aop->alipayPublicKey = $this->alipay_public_key;
                    $aop->apiVersion = '1.0';
                    $aop->postCharset = 'UTF-8';
                    $aop->format = 'json';
                    $request = new \AlipayTradeAppPayRequest();
                    $request->setBizContent("{" .
                            "    \"out_trade_no\":\"20150320010101001\"," .
                            "    \"scene\":\"bar_code,wave_code\"," .
                            "    \"auth_code\":\"28763443825664394\"," .
                            "    \"subject\":\"Iphone6 16G\"," .
                            "    \"seller_id\":\"2088102146225135\"," .
                            "    \"total_amount\":88.88," .
                            "    \"discountable_amount\":8.88," .
                            "    \"undiscountable_amount\":80.00," .
                            "    \"body\":\"Iphone6 16G\"," .
                            "      \"goods_detail\":[{" .
                            "                \"goods_id\":\"apple-01\"," .
                            "        \"alipay_goods_id\":\"20010001\"," .
                            "        \"goods_name\":\"ipad\"," .
                            "        \"quantity\":1," .
                            "        \"price\":2000," .
                            "        \"goods_category\":\"34543238\"," .
                            "        \"body\":\"特价手机\"," .
                            "        \"show_url\":\"http://www.alipay.com/xxx.jpg\"" .
                            "        }]," .
                            "    \"operator_id\":\"yx_001\"," .
                            "    \"store_id\":\"NJ_001\"," .
                            "    \"terminal_id\":\"NJ_T_001\"," .
                            "    \"alipay_store_id\":\"2016041400077000000003314986\"," .
                            "    \"extend_params\":{" .
                            "      \"sys_service_provider_id\":\"2088511833207846\"," .
                            "      \"hb_fq_num\":\"3\"," .
                            "      \"hb_fq_seller_percent\":\"100\"" .
                            "    }," .
                            "    \"timeout_express\":\"90m\"," .
                            "    \"royalty_info\":{" .
                            "      \"royalty_type\":\"ROYALTY\"," .
                            "        \"royalty_detail_infos\":[{" .
                            "                    \"serial_no\":1," .
                            "          \"trans_in_type\":\"userId\"," .
                            "          \"batch_no\":\"123\"," .
                            "          \"out_relation_id\":\"20131124001\"," .
                            "          \"trans_out_type\":\"userId\"," .
                            "          \"trans_out\":\"2088101126765726\"," .
                            "          \"trans_in\":\"2088101126708402\"," .
                            "          \"amount\":0.1," .
                            "          \"desc\":\"分账测试1\"," .
                            "          \"amount_percentage\":\"100\"" .
                            "          }]" .
                            "    }," .
                            "    \"sub_merchant\":{" .
                            "      \"merchant_id\":\"19023454\"" .
                            "    }" .
                            "  }");
                    $result = $aop->execute($request);

                    $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
                    $resultCode = $result->$responseNode->code;
                    if (!empty($resultCode) && $resultCode == 10000) {
                        echo "成功";
                    } else {
                        echo "失败";
                    }
                   

                    break;

                case 2:
                // dump(123213);exit;
                    import('Common.Wxpay.lib.WxPay#Config', APP_PATH, '.php');
                    import('Common.Wxpay.lib.WxPay#Api', APP_PATH, '.php');
                    $input = new \WxPayUnifiedOrder();
                    $input->SetBody("佳居信息科技" . $reslist['body']);
                    $input->SetAttach("佳居信息科技" . $reslist['order_id']);
                    $input->SetDetail(json_encode($reslist['body']));
                    $input->SetOut_trade_no($reslist['order_id']);
                    $input->SetTotal_fee($total_fee); //$res['price'] * 100
                    $input->SetTime_start(date("YmdHis"));
                    $input->SetTime_expire(date("YmdHis", time() + 600));
                    $input->SetGoods_tag("0");
                    $input->SetNotify_url("http://www.lshcn.com/Shop/orderpay/notify/");
                    $input->SetTrade_type("APP");
                    $pay = new \WxPayApi();
					//dump($input);exit;
                    $result = $pay->unifiedOrder($input);
					if($result['return_code']=='FAIL'){
						$this->not_posterr($result['return_msg']);
					}
	
                    $singns = new \WxPayJsApiPay();
                    $singns->SetAppid($result['appid']);
                    $singns->SetPartnerid($result['mch_id']);
                    $singns->SetPrepayid($result['prepay_id']);
                    $singns->SetPackage("Sign=WXPay");
                    $singns->SetNonceStr($result['nonce_str']);
                    $singns->SetTimeStamp(NOW_TIME);
                    $singns->SetSign();

                    $singns = object_array($singns);

                    foreach ($singns as $key => $value) {
                        $resss['signdata'] = $value;
                    }

                    $info = array();
                    $info['status'] = 1;
                    $info['msg'] = "支付订单创建成功";
                    $info['data'] = $resss;
                    $this->apiSuccess(TRUE, NULL, $info);
                    break;
                    $info = array();
                    $info['status'] = 1;
                    $info['msg'] = "支付订单创建成功";
                    $info['data'] = $result;
                    $this->apiSuccess(TRUE, NULL, $info);
                    break;
            }
        } else {
            $this->not_posterr();
        }
    }

    private function not_posterr($str='非法请求!') {
        $info = array();
        $info['msg'] = $str;
        $info['status'] = 101;
        $this->apiError(FALSE, null, $info);
    }

    private function not_strerr() {
        $info = array();
        $info['msg'] = '参数错误!';
        $info['status'] = 0;
        $this->apiError(FALSE, null, $info);
    }

    private function not_dataerr() {
        $info = array();
        $info['msg'] = '数据不存在!';
        $info['status'] = 0;
        $this->apiError(FALSE, null, $info);
    }
    ##首页轮播资源、广告位调用

    public function index_turn() {
		
		//首页轮播
        $position = M('AdvPos')->where(array('name' => 'index_turn'))->find(); //根据在前台添加的位置标签取得该位置的ID
		$field = array('title','data','url');
        $turn = M('Adv')->where('pos_id=' . $position['id'])->field(array('title','data','url','end_time'))->select(); //取得该轮播位置的图片
        $time = time();
        foreach ($turn as $k => $v) {
			$turn[$k]['data'] = json_decode($v['data'],true);
            $turn[$k]['data'] = get_table_field($turn[$k]['data']['pic'], 'id', 'path', 'Picture');
            if ($v['end_time'] < $time) {
                unset($turn[$k]);
                continue;
            }
        }

        $info['status'] = 1;
        $info['info'] = array('turn' => $turn);
        $this->apiSuccess('true', NULL, $info);


      
    }

	public function score_turn(){
		
	//积分商城轮播
        $position = M('AdvPos')->where(array('name' => 'score_turn'))->find(); //根据在前台添加的位置标签取得该位置的ID
        $turn = M('Adv')->where('pos_id=' . $position['id'])->field(array('title','data','url','end_time'))->select(); //取得该轮播位置的图片
        $time = time();
        foreach ($turn as $k => $v) {
			$turn[$k]['data'] = json_decode($v['data'],true);
            $turn[$k]['data'] = get_table_field($turn[$k]['data']['pic'], 'id', 'path', 'Picture');
            if ($v['end_time'] < $time) {
                unset($turn[$k]);
                continue;
            }
        }
		
		$info['status'] = 1;
        $info['info'] = array('turn' => $turn);
        $this->apiSuccess('true', NULL, $info);

	}
        
        /*
         * APP启动图
         */
        public function appStartTurn(){
            	
	//APP启动图
        $position = M('AdvPos')->where(array('name' => 'APP_start_turn'))->find(); //根据在前台添加的位置标签取得该位置的ID
        $turn = M('Adv')->where('pos_id=' . $position['id'])->field(array('title','data','url','end_time'))->select(); //取得该轮播位置的图片
        $time = time();
        foreach ($turn as $k => $v) {
	    $turn[$k]['data'] = json_decode($v['data'],true);
            $turn[$k]['data'] = get_table_field($turn[$k]['data']['pic'], 'id', 'path', 'Picture');
            if ($v['end_time'] < $time) {
                unset($turn[$k]);
                continue;
            }
        }
		
	$info['status'] = 1;
        $info['info'] = array('turn' => $turn);
        $this->apiSuccess('true', NULL, $info);
        }
        
   /*
    * 商家列表
    */
   public function storeList(){
       /* $list = M('StoreList')->where(array('status'=>1))->select();
       foreach ($list as $k => $v){
           $list[$k]['servicetime'] = date('m-d h:i:s',$v['servicetime']);
           $list[$k]['createtime'] = date('m-d h:i:s',$v['createtime']);
           $list[$k]['ico'] = get_table_field($v['ico'],'id','path','Picture');
           $list[$k]['QR_code_id'] = get_table_field($v['QR_code_id'],'id','path','Picture');
		   ##图片地址转换
		   $pic = explode(',',$v['store_show_id']);
		   foreach($pic as $c){
			   $pic[$k] = get_table_field($c,'id','path','Picture');
		   }
		   $list[$k]['store_show_id'] = $pic;
           ##将商家地址经纬度转换成X和Y
           $dress = explode(',', $v['coord']);
            $list[$k]['X'] = $dress[0];
            $list[$k]['Y'] = $dress[1];
               ##转换地址
            $dressturn = explode(',', $v['address']);
            $p = get_table_field($dressturn[0],'id','name','District');
            $c = get_table_field($dressturn[1],'id','name','District');
            $d = get_table_field($dressturn[2],'id','name','District');
	        $list[$k]['address'] = $p.$c.$d.$v['address_detail'];
       } */
       
       //查询优化by lw
       $where = array('status'=>1);
       $where = whereAddTableName($where, 'ne_store_list');
       $list = M('StoreList')      
       ->join(" ne_picture t1 on ne_store_list.ico = t1.id","left")
       ->join(" ne_picture t2 on ne_store_list.QR_code_id = t2.id","left")
       ->where($where)
       ->field('ne_store_list.*,t1.path ico,t2.path QR_code_id')
       ->select();
       
       
       $pic_id_arr = [];
       $dressturn_id_arr = [];
       foreach ($list as $k => $v){
           $list[$k]['servicetime'] = date('m-d h:i:s',$v['servicetime']);
           $list[$k]['createtime'] = date('m-d h:i:s',$v['createtime']);
           
           ##将商家地址经纬度转换成X和Y
           $dress = explode(',', $v['coord']);
           $list[$k]['X'] = $dress[0];
           $list[$k]['Y'] = $dress[1];
           
           ##图片地址转换
           $pic = explode(',',$v['store_show_id']);
           foreach($pic as $c){           
           		$pic_id_arr[] = $c;
           }   
           $list[$k]['store_show_id'] = $pic;
          
           ##转换地址
           $dressturn = explode(',', $v['address']);
           foreach ($dressturn as $d){
           		$dressturn_id_arr[] = $d;
           }           
           $list[$k]['address'] = $dressturn;
       }

       $pic_list = M('Picture')->where(array('id'=>array('in', $pic_id_arr)))->field(array('id','path'))->select();
       $dressturn_list = M('District')->where(array('id'=>array('in', $dressturn_id_arr)))->field(array('id','name'))->select();
       foreach ($list as $k => $v){

			##图片地址转换
       		foreach ($list[$k]['store_show_id'] as $pic_k => $pic_id){
       		 	foreach ($pic_list as $pic_arr){
       		 		if($pic_id == $pic_arr['id']){
       		 			$list[$k]['store_show_id'][$pic_k] = $pic_arr['path'];
       		 		}
       		 	}
       		 } 

       		 ##转换地址
       		 $address = '';
       		 foreach ($list[$k]['address'] as $addr_k => $addr_id){
       		 	foreach ($dressturn_list as $dressturn_arr){
       		 		if($addr_id == $dressturn_arr['id']){
       		 			$address .= $dressturn_arr['name'];
       		 		}
       		 	}
       		 }
       		 $list[$k]['address'] = $address . $v['address_detail'];
       }
       
       
       $info = array();
       $info['status'] = 1;
       $info['data'] = $list;
       $this->apiSuccess(TRUE, NULL, $info);
   }
   /*
    * 商家详细信息
    */
   public function storeInfo($id=0){
           if($id == 0){
            $info = array();
            $info['status'] = 0;
            $info['info'] = '参数不能为空';
            $this->apiError(false, null, $info);
       }else{
           $msg = M('StoreList')->where(array('id'=>$id))->find();
		   
		   ##将商家地址经纬度转换成X和Y
            $dress = explode(',', $msg['coord']);
            $msg['X'] = $dress[0];
            $msg['Y'] = $dress[1];
            ##转换地址
	        $dressturn = explode(',', $msg['address']);
            $p = get_table_field($dressturn[0],'id','name','District');
            $c = get_table_field($dressturn[1],'id','name','District');
            $d = get_table_field($dressturn[2],'id','name','District');

	    $msg['address'] = $p.$c.$d.$msg['address_detail'];
		$msg['ico'] = get_table_field($msg['ico'],'id','path','Picture');
            ##商家图片展示
            $show_id = explode(',', $msg['store_show_id']);
            foreach ($show_id as $k =>$v){
                $show_id[$k] = get_table_field($v,'id','path','Picture');
            }
            $msg['store_show_id'] = $show_id;
           if($msg){
                $info = array();
                $info['status'] = 1;
                $info['data'] = $msg;
                $this->apiSuccess(TRUE, NULL, $info);
           }else{
               
                $info = array();
                $info['status'] = 0;
                $info['msg'] = '没有此数据';
                $this->apiError(TRUE, NULL, $info);
           }         
       }     
   }
  
   /*
     * 积分商城设备列表
     * $min  积分筛选最小值
     * $max  积分筛选最大值
     */

    public function scoreShop($min = '',$max = '') {
        if(!empty($min) && !empty($max)){
            $where['score'] = array('between',array($min,$max));
        }
        if(!empty($min) && empty($max)){
            $where['score'] = array('egt',$min);
        }
        $where['status'] = 1;
        
         /*$list = M('ScoreShop')->where($where)->order('score desc')->select();
        foreach ($list as $k => $v) {
            $list[$k]['type'] = get_table_field($v['type'],'id','name','DeviceType');
            $list[$k]['pic'] = get_table_field($v['pic'],'id','path','Picture');
			$list[$k]['price'] = $list[$k]['score'];
        } */

        //查询优化 by lw             
        $where = whereAddTableName($where, 'ne_score_shop');        
        $list = M('ScoreShop')
        ->join(" ne_device_type t1 on ne_score_shop.type = t1.id","left")
        ->join(" ne_picture t2 on ne_score_shop.pic = t2.id","left")
        ->where($where)
        ->field('ne_score_shop.*,ne_score_shop.score price,t1.name type,t2.path pic')
        ->order('ne_score_shop.score desc')
        ->select();
        
        if ($list) {
            $info = array();
            $info['status'] = 1;
            $info['info'] = '获取数据成功';
            $info['data'] = $list;
            $this->apiSuccess(true, null, $info);
        } else {
            $info = array();
            $info['status'] = 0;
            $info['info'] = '数据错误';
            $this->apiError(false, null, $info);
        }
    }
 /*
     * 用户设备列表
     * 
     */ 

    public function mydevice($uid = "") {
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
        /*$list = M('Equipment')->where($where)->order('createtime desc')->select();
         foreach ($list as $k => $v) {
         $list[$k]['e_type'] = M('Device')->where(array('id'=>$v['e_type']))->getField('name');
         $list[$k]['installuid'] = M('UcenterMember')->where(array('id'=>$v['installuid']))->getField('username');
         $list[$k]['pic'] = get_table_field($v['pic'],'id','path','Picture');
         if(!(in_array_case($uid,str2arr($v['member'])))){
         unset($list[$k]);
         continue;
         }
         } */
        
        //优化查询 by lw
        $where = whereAddTableName($where, 'ne_equipment');
        $list = M('Equipment')
        ->join(" ne_device t1 on ne_equipment.e_type = t1.id","left")
        ->join(" ne_ucenter_member t2 on ne_equipment.installuid = t2.id","left")
        ->join(" ne_picture t3 on ne_equipment.pic = t3.id","left")
        ->where($where)
        ->field('ne_equipment.*,t1.name e_type,t2.username installuid,t3.path pic')
        ->order('ne_equipment.createtime desc')
        ->select();

        if(!$list) {
            $info = array();
            $info['status'] = 0;
            $info['info'] = '数据错误';
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
        $info['status'] = 1;
        $info['info'] = '获取数据成功';
        if(count($list) == 1){
            $info['data'][] = ArrMd2Ud($list);//只有一条数据时要把数组转换成二维数组
        }else{
            foreach ($list as $key => $value) {
                $lists[]=$value;
            }
            $info['data'] = $lists;
        }
        $this->apiSuccess(true, null, $info);
      
    }
	
	 //用户设备布撤防状态设置
    public function setDeviceStatus($e_id="",$uid="",$isOnline=""){
		if(IS_POST){		
            if(empty($e_id) || empty($uid) || empty($isOnline)){
                $info = array();
                $info['msg'] = '参数不能为空';
                $info['status'] = 0;
                $this->apiError(FALSE,null,$info);
            }else{
                $check = M('Equipment')->where(array('e_id'=>$e_id))->find();
                if(!$check){
                    $info = array();
                    $info['msg'] = '找不到该设备';
                    $info['status'] = 0;
                    $this->apiError(FALSE,null,$info);
                }
                $da['is_online'] = $isOnline;
    			$res = M('Equipment')->where(array('e_id'=>$e_id))->save($da);
                if($res){
    				 ##消息推送
                    if($isOnline == 1){
                        $title = '布防成功';
                    }
                     if($isOnline == -1){
                        $title = '撤防成功';
                    }
                    $content = '您设备序列号为'.$e_id.'的设备于'.date("Y-m-d H:i:s", NOW_TIME).$title;
                    $this->user->JpushMessage($title, $content, $uid);
                    $info = array();
                    $info['msg'] = '状态修改成功';
                    $info['status'] = 1;
                    $this->apiSuccess(TRUE,NULL,$info);
                }else{
                    $info = array();
                    $info['msg'] = '状态修改失败，状态参数不能与原参数相同';
                    $info['status'] = 0;
                    $this->apiError(FALSE,NULL,$info);
                }
            }
    	}else{
			$info = array();
            $info['msg'] = '非法请求';
            $info['status'] = 101;
            $this->apiError(FALSE,NULL,$info);
    	}
    }
	
	
    //用户添加设备
    public function adddevice($uid,$id,$pwd){
        
        if(empty($uid)){
            $info = array();
            $info['status'] = 0;
            $info['info'] = '没有用户UID,请登录';
            $this->apiError(false, null, $info);
        }        
        if(empty($id)){
            $info = array();
            $info['status'] = -1;
            $info['info'] = '设备序列号不能为空';
            $this->apiError(false, null, $info);die;
        }
        if(empty($pwd)){
            $info = array();
            $info['status'] = -1;
            $info['info'] = '设备密码不能为空';
            $this->apiError(false, null, $info);die;
        }
            
        $check = M('Equipment')->where(array('e_id'=>$id))->find();
        if(empty($check)){            
            $info = array();
            $info['status'] = -1;
            $info['info'] = '暂无此序列号设备';
            $this->apiError(false, null, $info);
        }    
            
        ##检查该设备是否被激活
        if($check['status'] == 0 ){
            $info = array();
            $info['status'] = -1;
            $info['info'] = '设备尚未激活，请联系管理员激活';
            $this->apiError(false, null, $info);die;
        }
        ##检查密码是否正确
        if($pwd != $check['e_pwd']){
            $info = array();
            $info['status'] = -1;
            $info['info'] = '设备密码错误';
            $this->apiError(false, null, $info);die;
        }
        
        //查是否已存在
        $authRule = M('EquipmentAuthRule')->where(array('e_id'=>$check['id'], 'uid'=>$uid))->find();
        if(!$authRule){
            ##不存在，则把当前用户添加到此设备的权限中
            $data = array();
            $data['uid'] = $uid;
            $data['e_id'] = $check['id'];
            $data['is_setPwd'] = 0;
            $data['is_online'] = 1;
            $data['createtime'] = time();
            $data['status'] = 1;
            $add = M('EquipmentAuthRule')->add($data);        
            if(!$add){
                $info = array();
                $info['status'] = -1;
                $info['info'] = '添加失败请重试';
                $this->apiError(false, null, $info);
            }
        }else {
            //存在则更新
            $data = array();
            $data['is_setPwd'] = 0;
            $data['is_online'] = 1;
            $data['createtime'] = time();
            $data['status'] = 1;
            $add = M('EquipmentAuthRule')->where(array('id'=>$authRule['id']))->save($data);
            if(!$add){
                $info = array();
                $info['status'] = -1;
                $info['info'] = '添加失败请重试';
                $this->apiError(false, null, $info);
            }
        }
        
        $e_id = $check['id'];//设备ID
        
        ##更新设备表中的member
        $da = array();
        $da['member'] = $this->getDeviceMemberList($e_id);
        $auto = M('Equipment')->where(array('id'=>$e_id))->save($da);
        
        $info = array();
        $info['status'] = 1;
        $info['info'] = '添加成功';
        $this->apiSuccess(TRUE, null, $info);        
    }
    
    //获取制定设备成员列表
    private function getDeviceMemberList($e_id){
        $member_list_str = '';
        if(empty($e_id)){
            return $member_list_str;
        }
        $list = M('EquipmentAuthRule')->where(array('e_id' => $e_id))->order('e_id asc')->field('uid')->select();
        foreach ($list as $v){
            $member_list_str .= ',' .$v['uid'];
        }
        $member_list_str = ltrim($member_list_str, ',');
        return $member_list_str;
    }
    
	/*
	* 保存设备最后视频画面截图
	*/
	public function saveScreen($id,$pic){
		if(empty($id) && empty($uid) && empty($pic)){
				$info = array();
                $info['status'] = -1;
                $info['info'] = '参数错误';
                $this->apiError(false, null, $info);
		}else{
			$data['pic'] = $pic;
			$check = M('Equipment')->where(array('id'=>$id))->find();
			if(empty($check)){
				$info = array();
                $info['status'] = -1;
                $info['info'] = '没有该设备';
                $this->apiError(false, null, $info);
			}else{
				$res = M('Equipment')->where(array('id'=>$id))->save($data);
				if($res){
					$info = array();
					$info['status'] = 1;
					$info['info'] = '画面截图保存成功';
					$this->apiSuccess(false, null, $info);
				}else{
					$info = array();
					$info['status'] = -1;
					$info['info'] = '画面截图保存失败';
					$this->apiError(false, null, $info);
				}	
			}
		}
	}

	/*
	* 故障报修
	*/
	public function repair($uid,$content,$type){
		if(empty($uid) || empty($type)){
					$info = array();
					$info['status'] = -1;
					$info['info'] = '参数为空';
					$this->apiError(false, null, $info);
		}else{
			$data['uid'] = $uid;
			if(!empty($content)){
				$data['content'] = $content;
			}		
			$data['type'] = $type;
			$data['createtime'] = time();
			
			$add = M('RepairLog')->add($data);
			if($add){
				$info = array();
					$info['status'] = 1;
					$info['info'] = '提交成功';
					$this->apiSuccess(true, null, $info);
			}else{
				$info = array();
					$info['status'] = -1;
					$info['info'] = '提交失败';
					$this->apiError(false, null, $info);
			}
		}
	}
	
	/*
	*故障类型
	*/
	public function repair_type(){
		$type = M('RepairType')->where(array('status'=>1))->select();
		foreach($type as $k => $v){
			
		}
			$info = array();
            $info['status'] = 1;
            $info['info'] = '数据拉取成功';
			$info['data'] = $type;
            $this->apiSuccess(true, null, $info);
	}
        
        
    /*
     * 购物车
     */    
    public function addCar($id,$num,$price,$uid){
        if(empty($id) || empty($num) || empty($price) || empty($uid)){
            $info = array();
            $info['msg'] = '参数丢失，请检查';
            $info['status'] = 0;
            $this->apiError(FALSE,null, $info);
        }else{
            #检查该用户是否添加过该商品
            $check = M('ShopCar')->where(array('good_id'=>$id,'uid'=>$uid))->find();
            if(!$check){
                $data['good_id'] = $id;
                $data['good_num'] = $num;
                $data['single_price'] = $price;
                $data['total_price'] = $num * $price;
                $data['uid'] = $uid;
                $data['createtime'] = time();
                $res = M('ShopCar')->add($data);
                if($res){
                    $info = array();
                    $info['msg'] = '添加成功';
                    $info['status'] = 1;
                    $this->apiSuccess(true,NULL,$info);
                }else{
                    $info = array();
                    $info['msg'] = '添加失败';
                    $info['status'] = 0;
                    $this->apiError(FALSE,NULL,$info);
                }
            }else{
                ##如果该用户在自己购物车中添加过该商品并没有结算，那么就增加数量
                $data['good_num'] = $check['good_num'] + $num;
                $data['total_price'] = $price * $data['good_num'];
                $data['createtime'] = time();
                $update = M('ShopCar')->where(array('good_id'=>$id,'uid'=>$uid))->save($data);
                if($update){
                    $info = array();
                    $info['msg'] = '添加成功';
                    $info['status'] = 1;
                    $this->apiSuccess(true,NULL,$info);
                }else{
                    $info = array();
                    $info['msg'] = '添加失败';
                    $info['status'] = 0;
                    $this->apiError(FALSE,NULL,$info);
                }
            }
        }
    }
    /*
     * 购物车商品数量变化
     * $id 购物车商品ID
     * $uid 用户uid
     * $num 更改后的商品数量
     */
    public function shopCarChange($id,$uid,$num){
        if(empty($id) || empty($num)){
            $info = array();
            $info['msg'] = '参数丢失，请检查';
            $info['status'] = 0;
            $this->apiError(FALSE,null, $info);
        }else{
            $res = M('ShopCar')->where(array('id'=>$id,'uid'=>$uid))->find();
            if($res && $num > 0){
                $data['good_num'] = $num;
                $data['total_price'] = $res['single_price'] * $num;
                $save = M('ShopCar')->where(array('id'=>$id))->save($data);
                if($save){
                    $info = array();
                    $info['msg'] = '数量修改成功';
                    $info['status'] = 1;
                    $this->apiSuccess(true,NULL,$info);
                }else{
                    $info = array();
                    $info['msg'] = '添数量修改失败';
                    $info['status'] = 0;
                    $this->apiError(FALSE,NULL,$info);
                }
            }
            
        }
    }
    /*
     * 获取积分充值比例
     */
    public function getScoreScale(){
        $scale = M('Config')->where(array('name'=>'SCORE_PAY','stauts'=>1))->find();
        if($scale){
            $config = explode(',', $scale['value']);
            $data['score'] = $config[0];
            $data['money'] = $config[1];
            $info = array();
            $info['msg'] = '数据获取成功';
            $info['data'] = $data;
            $info['status'] = 1;
            $this->apiSuccess(TRUE,NULL.$info);
        }else{
            $info = array();
            $info['msg'] = '数据获取失败';
            $info['status'] = 0;
            $this->apiError(FALSE,NULL,$info);
        }
    }
    
    /*
     * 订单类型：1-维修订单；2-兑换订单；3-报装订单 4续费订单   订单
     * $uid 用户UID
     * $types 订单类型  订单类型：1-维修订单；2-兑换订单；3-报装订单 4续费订单
     * $money 充值需要的金额
     * $e_id//设备ID
     * 
     * 开户订单参数 
     * $name,$mobile,$address,$addDetail,$servicePrice,$serviceTime
     */
    public function create_order($uid="",$types=1,$time=1,$carId="",$e_id=1,$name="",$mobile="",$address="",$addDetail="",$servicePrice="",$serviceTime="",$content="",$wx_type=""){
      if(IS_POST){
        if(empty($uid) || empty($types)){
            $info = array();
            $info['msg'] = '参数丢失，请检查';
            $info['status'] = 0;
            $this->apiError(FALSE,null, $info);
        }else{
            $usernames=get_table_field($uid,'uid','nickname','Member');
            switch ($types) {
            case 1:
                if(empty($content) || $e_id == 1){
                    $info = array();
                    $info['msg'] = '参数错误，请检查';
                    $info['status'] = 101;
                    $this->apiError(FALSE,NULL,$info);
                }
                 //保存的数据 
                $data['content'] = $content;
                $data['e_id'] = $e_id;
                $data['wx_type'] = $wx_type;
                
                //查询 的数据 
                $fdata['uid']=$uid;
                $fdata['wx_status']=1;
                $fdata['type']=$types;
                $fdata['e_id'] = $e_id;
                
                //返回的数据 
                $resdata['time']=$time;
                $resdata['types']=$types;
                $resdata['e_id'] = $e_id;
                
                $su_text="维修订单创建成功!";
                $er_text="维修订单创建失败!";

                    $title="维修订单提醒" ;   
                    $content=$usernames."在" .date("Y-m-d H:i:s", time())."创建了"."维修订单";
                    $stypes='wx';
                break;
            case 2:
                if(empty($carId) || empty($name) || empty($mobile) || empty($address) || empty($addDetail)){
                    $info = array();
                    $info['msg'] = '参数错误，请检查';
                    $info['status'] = 101;
                    $this->apiError(FALSE,NULL,$info);
                }

                ##转换$carId为数组
                $c_id = str2arr($carId);//explode(',', $carId);
				$data = array();
                $address = str2arr($address);//explode(',', $address);
                $orderids=\Think\String::uuid($data['uid']);
                

                foreach ($c_id as $v){
                    $shopcat=M("ShopCar")->where(array('uid'=>$uid,'id'=>$v))->find();
                    if($shopcat){
                        $data['good_id'] = $shopcat['good_id'];
                        $data['price'] = $shopcat['single_price'];
                        $data['num'] = $shopcat['good_num'];
                        $data['total_price']+=($data['price']*$data['num']);
                        $data['uid'] = $uid;
                        $data['name'] = $name;
                         ##APP传过来的为文字，转换成后台需要的数据格式
                           
                            if(is_array($address)){
                                $data['province'] = $address[0];
                                $data['city'] = $address[1];
                                $data['district'] = $address[2];
                                $data['address'] = $data['province'].$data['city'].$data['district'] ;
                            }else{
                                $info = array();
                                $info['msg'] = '地址参数错误，请检查';
                                $info['status'] = 10100;
                                $this->apiError(FALSE,NULL,$info);
                            }
                            

                        
                        
                        $data['mobile'] = $mobile;
                        $data['addDetail'] = $addDetail;
                        // $data['total_price'] = get_table_field($v,'id','total_price','ShopCar');
                        $data['type'] = $types;
                        $data['dh_status'] = 1;
                        $data['createtime'] = NOW_TIME;
                        $data['order_id']=$orderids;
                        // dump($data);
                        $insert = M('OrderList')->add($data); 
                        // dump(M('OrderList'));exit;
                    }
                    
                           
                }

                if($insert){

                    // TODO:这里你要推送一条兑换成功信息
                     $datas['goods_id'] = $shopcat['good_id'];
                    $datas['order_id']=\Think\String::uuid($data['uid']);  
                    $datas['price'] = $shopcat['single_price'];
                    $datas['num'] =$shopcat['good_num'];
                    $datas['uid'] = $uid;
                    $datas['createtime'] = NOW_TIME;
                    // dump($data);exit;
                    M('OrderShop')->add($datas); 



                    ##算出本次需要支付的总金额
                    $map = array('id' => array('in', arr2str($c_id)) );
                    $need_pay_money = M('ShopCar')->where($map)->sum('total_price');
                    //扣除用户积分
                    M("member")->where(array('uid' =>$uid))->setDec('score1',$need_pay_money); 
                    
                    $scoredata['uid']=$uid;
                    $scoredata['ip']=ipton(get_client_ip());
                    $scoredata['type']=4;  //兑换消费
                    $scoredata['action']="user_score";
                    $scoredata['value']=-$need_pay_money;
                    $scoredata['create_time']=NOW_TIME;
                    $scoredata['remark']=$usernames."在" .date("Y-m-d H:i:s", time())."兑换了".$need_pay_money."积分商品。";

                    M('ScoreLog')->add($scoredata); 
                    $title="积分消费提醒" ;   
                    $content=$usernames."在" .date("Y-m-d H:i:s", time())."兑换了".$need_pay_money."积分商品。";
                    $stypes='dh';
                    $this->user->JpushMessage($title,$content,$uid,9999,$stypes);

                    ##删除购物车中的数据
                    $del = M('ShopCar')->where($map)->delete();
                    $info = array();
                    $info['msg'] = '创建兑换订单成功';
                    $info['status'] = 1;
                    $info['data'] = array('need_pay_money'=>$need_pay_money);
                    $this->apiSuccess(TRUE,NULL,$info);
                }else{
                    $info = array();
                    $info['msg'] = '创建兑换订单失败';
                    $info['status'] = 111;
                    $this->apiError(FALSE, NULL, $info);die;
                }
                
                break;
            case 3:
                 if(empty($name) ||empty($mobile) || empty($address) || empty($addDetail) || empty($servicePrice) || empty($serviceTime)){
                    $info = array();
                    $info['msg'] = '参数错误，请检查';
                    $info['status'] = 301;
                    $this->apiError(FALSE,NULL,$info);
                }
                
                $user=A("User");
                $res=$user->addAccount($uid,$name,$mobile,$address,$addDetail,$servicePrice,$serviceTime);//开户订单用户创建
                if($res){
                    $data['account_id'] = $res;
                    $data['kh_yeah'] = $serviceTime;
                    $data['kh_money'] = $servicePrice;
                    $data['name'] = $name;
                    $data['mobile'] = $mobile;
                    $data['address'] = $address;
                    $data['addDetail'] = $addDetail;
                    $data['province'] = $province;
                    $data['city'] = $city;
                    $data['district'] = $district;
                    $data['kh_status'] = 0;

                    //返回的数据 
                    $resdata['kh_yeah']=$serviceTime;
                    $resdata['kh_money']=$servicePrice;
                    $resdata['account_id'] = $data['account_id'];

                    $su_text="开户订单创建成功!";
                    $er_text="开户订单创建失败!";
                    $title="开户订单提醒" ;   
                    $content=$usernames."在" .date("Y-m-d H:i:s", time())."创建了"."开户订单";
                    $stypes='kh';
                }else{
                    $info = array();
                    $info['msg'] = '开户失败，请检查';
                    $info['status'] = 0;
                    $this->apiError(FALSE,NULL,$info);
                }
                
                
                
                break;
            case 4://续费订单数据 
                if(empty($e_id) || empty($time)){
                    $info = array();
                    $info['msg'] = '参数丢失，请检查';
                    $info['status'] = 401;
                    $this->apiError(FALSE,null, $info);
                }
                //保存的数据 
                $data['xf_yeah'] = $time;
                $data['xf_status'] = 0;
                $data['e_id'] = $e_id;
                
                //查询 的数据 
                $fdata['uid']=$uid;
                $fdata['xf_status']=0;
                $fdata['type']=$types;
                $fdata['e_id'] = $e_id;
                
                //返回的数据 
                $resdata['time']=$time;
                $resdata['types']=$types;
                $resdata['e_id'] = $e_id;
                
                $su_text="续费订单创建成功!";
                $er_text="续费订单创建失败!";
                 $title="续费订单提醒" ;   
                $content=$usernames."在" .date("Y-m-d H:i:s", time())."创建了"."续费订单";
                $stypes='xf';
                break;
        }
//            $reslist = M('OrderList')->where($fdata)->find();
                $this->user->JpushMessage($title,$content,$uid,9999,$stypes);
                //公共参数 
                if($types != 2){
                    $data['uid'] = $uid;
                    $data['type'] = $types;
                    $data['createtime'] = NOW_TIME;
                    $data['order_id']=\Think\String::uuid($data['uid']);
                    $res = M('OrderList')->add($data);
                }
                
                if($res){
                    $result= M('OrderList')->where(array('id'=>$res))->find();

                    $resdata['orderid']=$result['order_id'];
                    $info = array();
                    $info['msg'] = $su_text;
                    $info['status'] = 1;
                    $info['data'] = $resdata;
                    $this->apiSuccess(TRUE,NULL,$info);
                }else{
                    $info = array();
                    $info['msg'] = $er_text;
                    $info['status'] = 0;
                    $this->apiError(FALSE, NULL, $info);
                }
            
        }
         }else{
            $info = array();
            $info['msg'] = '非法请求!';
            $info['status'] = 0;
            $this->apiError(FALSE,null, $info);
         }
    }

    /*
    * 确认收货改变订单状态
    * $uid 用户UID
     * $types 订单类型  订单类型：1-维修订单；2-兑换订单；3-报装订单 4续费订单
     * $status 状态 1审核中  2，跟进  3，完成   
     *$order_id   订单ID
    */

    public function Confirm_re($types=1,$status=1,$uid=0,$order_id=0){
        if(IS_POST){

            if(empty($uid) || empty($types)){
            $info = array();
            $info['msg'] = '参数丢失，请检查';
            $info['status'] = 0;
                $this->apiError(FALSE,null, $info);
            }else{
                $map['uid']=$uid;
                $map['types']=$types;
                $map['order_id']=$order_id;
                $result= M('OrderList')->where($map)->find();
                if($result){
                    $data['dh_status']=$status;
                    $data['update_time']=NOW_TIME;
                     $data['user_create_time']=NOW_TIME;
                      $data['user_log']=1;
                    $result= M('OrderList')->where($map)->save($data);

                    $info = array();
                    $info['msg'] = "确认收货完成";
                    $info['status'] = 1;
                    $this->apiSuccess(TRUE,NULL,$info);
                }else{
                    $info = array();
                    $info['msg'] = '该订单不存在!';
                    $info['status'] = 1404;
                    $this->apiError(FALSE,null, $info);
                }

            }

        }

    }
    
    
    /*
     * 积分充值下单
     * $uid 用户UID
     * $score 充值积分
     * $money 充值需要的金额
     */
    public function buyScore($uid="",$types=1){
      if(IS_POST){
          
     
        if(empty($uid) || empty($types)){
            $info = array();
            $info['msg'] = '参数丢失，请检查';
            $info['status'] = 0;
            $this->apiError(FALSE,null, $info);
        }else{
            switch ($types) {
            case 1:
                $score=300;
                $money=30;
                break;
            case 2:
                $score=500;
                $money=50;
                break;
            case 3:
                $score=1000;
                $money=100;
                break;
        }
            $data['uid'] = $uid;
            $data['score'] = $score;
            $data['money'] = $money;
            $data['createtime'] = time();
            $data['status'] = 0;
            $data['orderid']=\Think\String::uuid($data['uid']);
            $res = M('ScorePayLog')->add($data);
            if($res){
                $result= M('ScorePayLog')->where(array('id'=>$res))->find();
                $info = array();
                $info['msg'] = '充值订单创建成功';
                $info['status'] = 1;
                $info['data'] = array('orderid'=>$result['orderid'],'money'=>$money,'score'=>$score);
                $this->apiSuccess(TRUE,NULL,$info);
            }else{
                $info = array();
                $info['msg'] = '充值订单创建失败';
                $info['status'] = 0;
                $this->apiError(FALSE, NULL, $info);
            }
        }
         }else{
            $info = array();
            $info['msg'] = '非法请求!';
            $info['status'] = 0;
            $this->apiError(FALSE,null, $info);
         }
    }
    
    private function scorepay($orderid=0,$uid=0,$status=0) {
        $check = M('ScorePayLog')->where(array('orderid'=>$orderid,'uid'=>$uid))->find();
        
            if(!$check){
                $info = array();
                $info['msg'] = '该订单不存在';
                $info['status'] = 0;
                $info['err_code']=20001;
                $this->apiError(FALSE, NULL, $info);
            }else{
                if($check['status'] == 1){
                    $info = array();
                    $info['msg'] = '该订单已完成';
                    $info['status'] = 0;
                    $info['err_code']=20002;
                    $this->apiError(FALSE, NULL, $info);
                    exit;
                }
                $data['status'] = $status;
                $update = M('ScorePayLog')->where(array('orderid'=>$orderid,'uid'=>$uid))->save($data);
                if($update){
                    M("member")->where(array('uid'=>$uid))->setInc('score1',$check['score']);
                    $info = array();
                    $info['msg'] = '操作成功';
                    $info['status'] = 1;
                    $this->apiSuccess(TRUE,NULL,$info);
                }else{
                    $info = array();
                    $info['msg'] = '操作失败';
                    $info['status'] = 0;
                    $info['err_code']=20004;
                    $this->apiError(FALSE, NULL, $info);
                }
                
            }
            
    }
    /*
     * 接收用户支付结果
     * $uid 用户uid
     * $id 订单id
     * $status 支付返回结果
     * $types 积分 1，续费 2   开户3
     */
    public function savePayStatus($uid=0,$id=0,$status=0,$types=1){
        if(IS_POST){
            if(empty($uid) || empty($id) || empty($types)){
                $info = array();
                $info['msg'] = '参数丢失，请检查';
                $info['status'] = 0;
                $this->apiError(FALSE,null, $info);
            }else{
                switch ($types) {
                    case 1:
                        return $this->scorepay($id, $uid, $status);
                        break;
                    case 2:
                        $user=A("User");  
                        return $user->renew($id, $uid, $status);
                        break;
                    case 3:
                        $user=A("User");  
                        return $user->updataAccount($uid,$id, $status);
                        break;
                    default:
                        return false;
                        break;
                }

            }
        }else{
            $this->not_posterr();
        }
        
    }
    /*
     * 用户购物车
     */

    public function carList($uid){
        if(empty($uid)){
            $info = array();
            $info['msg'] = '请登录后再查看您的购物车';
            $info['status'] = 0;
        }else{
            $list = M('ShopCar')->where(array('uid'=>$uid))->select();
			$total_price = M('ShopCar')->where(array('uid'=>$uid))->sum('total_price');
            if($list){
                foreach($list as $k => $v){
                    $list[$k]['good_name'] = get_table_field($v['good_id'],'id','name','ScoreShop');
                    $pic = get_table_field($v['good_id'],'id','pic','ScoreShop');
                    $list[$k]['pic'] = get_table_field($pic,'id','path','Picture');
                    $list[$k]['score'] = get_table_field($v['good_id'],'id','score','ScoreShop');
                }
                $info = array();
                $info['msg'] = '数据拉去成功';
                $info['stauts'] = 1;
                $info['data'] = $list;
		        $info['totalPrice'] = $total_price;
                $this->apiSuccess(TRUE,NULL,$info);
            }else{
                $info = array();
                $info['msg'] = '您的购物车空空如也，去挑选商品吧';
                $info['status'] = 0;
                $this->apiError(FALSE,NULL,$info);
            }
            
        }
    }
	
	 /*
     * 购物车商品删除
     */
    
    public function delCarList($id=null,$uid=null){
        if(empty($id) || empty($uid)){
            $info = array();
            $info['msg'] = '参数错误';
            $info['status'] = 0;
            $this->apiError(FALSE,NULL, $info);
        }else{
            $del = M('ShopCar')->where(array('id'=>$id,'uid'=>$uid))->delete();
            if($del){
                $info = array();
                $info['msg'] = '删除成功';
                $info['status'] = 1;
                $this->apiSuccess(TRUE,NULL,$info);
            }else{
                $info = array();
                $info['msg'] = '没有该商品';
                $info['status'] = 0;
                $this->apiError(FALSE,NULL, $info);
            }
        }
    }
	
/*
     * 积分商城商品详情
     */
    public function shopDetail($id = 0){
        if(empty($id)){
            $info = array();
            $info['msg'] = '参数错误';
            $info['status'] = 0;
            $this->apiError(FALSE,NULL, $info);
        }else{
            $detail = M('ScoreShop')->where(array('id'=>$id))->find();
            if(!$detail){
                $info = array();
                $info['msg'] = '参数错误';
                $info['status'] = 0;
                $this->apiError(FALSE,NULL, $info);
            }else{
                $detail['pic'] = get_table_field($detail['pic'],'id','path','Picture');
				$detail['price'] = $detail['score'];
                $info = array();
                $info['msg'] = '数据拉去成功';
                $info['status'] = 1;
                $info['data'] = $detail;
                $this->apiSuccess(TRUE,NULL,$info);
            }
        }
    }
    
    /*
    * 设备权限---成员列表
    * eid  设备id
    * uid  用户uid
    */
   public function deviceAuthRule($id,$uid){
       if(empty($id) || empty($uid)){
           $info = array();
           $info['msg'] = '参数错误';
           $info['status'] = 0;
           $this->apiError(FALSE,NULL,$info);
       }else{
         $check = M('Equipment')->where(array('id'=>$id))->find();
         if($check){
             $adminName = get_table_field($check['root_id'],'id','mobile','UcenterMember');//取得管理员名称
			 $root_isonline = $check['root_isonline'];
             $memberList = M('EquipmentAuthRule')->where(array('e_id'=>$id,'status'=>1))->field(array('id','uid','is_setPwd','is_online'))->select();//取得该设备成员列表
             foreach ($memberList as $k => $v){
                 $memberList[$k]['nickname'] = get_table_field($v['uid'],'id','mobile','UcenterMember');
             }
             $info = array();
             $info['msg'] = '信息拉取成功';
             $info['status'] = 1;
             $this->apiSuccess(TRUE,NULL,array(
                 'adminNickname' => $adminName,
                 'memberList' => $memberList,
				 'root_isonline'=>$root_isonline,
             ));
         }else{
            $info = array();
            $info['msg'] = '没有该设备';
            $info['status'] = 0;
            $this->apiError(FALSE,NULL,$info);
         }
       }
   }
   /*
    * 设备权限-成员权限
    * id 成员id
    */
   public function memberAuth($id){
       if(empty($id)){
           $info = array();
           $info['msg'] = '参数错误';
           $info['status'] = 0;
           $this->apiError(FALSE,NULL,$info);
       }else{
           $auth = M('EquipmentAuthRule')->where(array('id'=>$id,'status'=>1))->find();
           if($auth){
               $info = array();
               $info['msg'] = '数据拉取成功';
               $info['status'] = 1; 
               $info['data'] = $auth;
               $this->apiSuccess(TRUE,NULL,$info);
           }else{
                $info = array();
                $info['msg'] = '没有该成员或权限账户已被禁用';
                $info['status'] = 0;
                $this->apiError(FALSE,NULL,$info);
           }
       }
   }
   /*
    * 设备删除
    * $uid  用户UID
    * $id   设备id
    */
   public function delOwnDevice($uid = "",$id = ""){
	   if(IS_POST){
		   if(empty($uid) || empty($id)){
			   $info = array();
			   $info['msg'] = '参数丢失';
			   $info['status'] = 0;
			   $this->apiError(FALSE,NULL,$info);
           }else{
			   ##检查操作用户是不是管理员
			   $check = M('Equipment')->where(array('id'=>$id))->find();
			   if(empty($check)){
				    $info = array();
					$info['msg'] = '设备已删除';
					$info['status'] = 101;
					$this->apiError(FALSE,NULL,$info);die;
			   }else{
				   if($uid == $check['root_id']){
					$info = array();
					$info['msg'] = '您是此设备管理员，不能进行此设备删除操作';
					$info['status'] = 102;
					$this->apiError(FALSE,NULL,$info);die;
				   }
				   $member = explode(',',$check['member']);
				   if(in_array($uid,$member)){
					   ##删除member中此uid
					   unset($member[array_search($uid,$member)]);
					   $new_member['member'] = implode(',',$member);
					   $new_data = M('Equipment')->where(array('id'=>$id))->save($new_member);
					   if($new_data){
						   ##删除权限表中此成员的权限
						   $del_auth = M('EquipmentAuthRule')->where(array('e_id'=>$id,'uid'=>$uid))->delete();
						   $info = array();
						   $info['msg'] = '删除成功';
						   $info['status'] = 1;
						   $this->apiSuccess(true,NULL,$info);
					   }else{
						   $info = array();
						   $info['msg'] = '删除失败';
						   $info['status'] = 1;
						   $this->apiError(false,NULL,$info);
					   }
				   }else{
						   $info = array();
						   $info['msg'] = '您不是此设备的成员';
						   $info['status'] = 101;
						   $this->apiError(false,NULL,$info);
				   }
			   }
		   }
	   }
       
   }
   /*
   * 设备权限--管理员删除该成员
   * id 被删除成员ID
   * e_id 设备ID
   * uid 当前操作用户uid
   */
   public function deleteAuthMember($id,$e_id,$uid){
       if(empty($id) || empty($e_id) || empty($uid)){
           $info = array();
           $info['msg'] = '参数错误';
           $info['status'] = 0;
           $this->apiError(FALSE,NULL,$info);
       }else{
           ##检查当前操作用户是不是管理员
           $checkIsAdmin = M('Equipment')->where(array('id'=>$e_id))->field(array('id','root_id','member'))->find();
           ##检查该成员是否已经删除（防止网络延迟带来的删除错误）
           $member = M('EquipmentAuthRule')->where(array('id'=>$id,'status'=>1))->find();
           if($uid == $checkIsAdmin['root_id']){
               if(!$member){
                    $info = array();
                    $info['msg'] = '该用户权限已删除';
                    $info['status'] = 0;
                    $this->apiError(FALSE,NULL,$info);die;
               }
               ##删除设备表中member字段里面该用户的uid
               $memberUid = explode(',', $checkIsAdmin['member']);
               foreach ($memberUid as $k => $v){
                   if($member['uid'] == $v){
                    unset($memberUid[$k]);
                    continue;
		 }
               }
               ##删除权限表中的用户权限
               $del = M('EquipmentAuthRule')->where(array('id'=>$id))->delete();
               ##重新把剩余的UID变成字符串
               $newMemberUid = implode(',', $memberUid);
               $newAuth = M('Equipment')->where(array('id'=>$e_id))->save(array('member'=>$newMemberUid));
               if($del && $newAuth){
                    $info = array();
                    $info['msg'] = '删除成功';
                    $info['status'] = 1; 
                    $this->apiSuccess(TRUE,NULL,$info);
               }else{
                    $info = array();
                    $info['msg'] = '删除失败，请重试';
                    $info['status'] = 0;
                    $this->apiError(FALSE,NULL,$info);
               }
           }else{
                    $info = array();
                    $info['msg'] = '你不是该设备的管理员';
                    $info['status'] = 0;
                    $this->apiError(FALSE,NULL,$info);
           }
       }
   }
    /*
    * 设备权限--成员权限设置-是否允许修改设备密码
    *  id 被赋予权限成员ID（权限表中的ID）
    *  e_id 设备ID
    *  uid 当前操作用户uid
    */
   public function memberAuthSetPwd($id,$e_id,$uid){
        if(empty($id) || empty($e_id) || empty($uid)){
           $info = array();
           $info['msg'] = '参数错误';
           $info['status'] = 0;
           $this->apiError(FALSE,NULL,$info);
       }else{
           ##检查当前操作用户是不是管理员
           $checkIsAdmin = M('Equipment')->where(array('id'=>$e_id))->field(array('id','root_id'))->find();
           $member = M('EquipmentAuthRule')->where(array('e_id'=>$checkIsAdmin['id'],'status'=>1))->field(array('id'))->select();
           if($uid == $checkIsAdmin['root_id']){
               ##根据数据库中该用户当前的权限来设置
               $select =  M('EquipmentAuthRule')->where(array('id'=>$id))->getField('is_setPwd');
               if($select == 0){
                   ##打开修改设备密码权限
                   $data['is_setPwd'] = 1;
                   $update = M('EquipmentAuthRule')->where(array('id'=>$id))->save($data);
                    if($update){
                        $info = array();
                        $info['msg'] = '权限打开成功';
                        $info['status'] = 1; 
                        $this->apiSuccess(TRUE,NULL,$info);
                    }else{
                        $info = array();
                        $info['msg'] = '设置失败，请重试';
                        $info['status'] = 0;
                        $this->apiError(FALSE,NULL,$info);
                    }
                }
                
              if($select == 1){
                   ##关闭修改设备密码权限
                   $data['is_setPwd'] = 0;
                   $update = M('EquipmentAuthRule')->where(array('id'=>$id))->save($data);
                    if($update){
                        $info = array();
                        $info['msg'] = '权限关闭成功';
                        $info['status'] = 1; 
                        $this->apiSuccess(TRUE,NULL,$info);
                    }else{
                        $info = array();
                        $info['msg'] = '设置失败，请重试';
                        $info['status'] = 0;
                        $this->apiError(FALSE,NULL,$info);
                    }
                }
           }else{
                    $info = array();
                    $info['msg'] = '你不是该设备的管理员或该成员账户已禁用';
                    $info['status'] = 0;
                    $this->apiError(FALSE,NULL,$info);
           }
       }
   }
   
//   private function err_code(){
//       
//   }

}
