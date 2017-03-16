<?php
/**
 * Created by PhpStorm.
 * User: caipeichao
 * Date: 1/15/14
 * Time: 4:17 PM
 */

namespace Api\Controller;
//use Addons\ResetByEmail\ResetByEmailAddon;
use Think\Controller;
use User\Api\UserApi;
use Addons\Tianyi\TianyiAddon;

class PublicController extends ApiController {
    public function login($username, $password) {
        //登录单点登录系统
        $result = $this->api->login($username, $password, 3); //1表示登录类型，使用用户名登录,2-->邮箱登陆，3-->手机号码登录
        if($result <= 0) {
            $message = $this->getLoginErrorMessage($result);
            $code = $this->getLoginErrorCode($result);
            $this->apiError($code,$message);
        } else {
            $uid = $result;
			##更改设备管理员为该用户的在离线状态
			$da['root_isonline'] = 1;
			$change_line = M('Equipment')->where(array('root_id'=>$uid))->save($da);
        }
        //登录前台
        $model = D('Home/Member');
        $result = $model->login($uid);
        if(!$result) {
            $message = $model->getError();
            $this->apiError(604,$message);
        }
        //返回成功信息
        $extra = array();
        $extra['session_id'] = session_id();
        $extra['uid'] = $uid;
        $this->apiSuccess("登录成功", null, $extra);
    }
     //请求并发送短信验证码
    public function send($to){
        //初始化必填
        $options['accountsid'] = C('ACCOUNTSID'); //填写自己的
        $options['token'] = C('TOKEN'); //填写自己的
        //初始化 $options必填
        $ucpass = new \ORG\Ucpaas($options);
                
                //随机生成6位验证码
        srand((double)microtime()*1000000);//create a random number feed.
        $ychar="0,1,2,3,4,5,6,7,8,9";
        $list=explode(",",$ychar);
        for($i=0;$i<6;$i++){
        $randnum=rand(0,8); // 10+26;
        $authnum.=$list[$randnum];
        }
        //短信验证码（模板短信）,默认以65个汉字（同65个英文）为一条（可容纳字数受您应用名称占用字符影响），超过长度短信平台将会自动分割为多条发送。分割后的多条短信将按照具体占用条数计费。
        $appId = C('APPID');  //填写自己的
        $to = $to;
        $templateId = C('TEMPLATEID');
        $param=$authnum;
        $arr=$ucpass->templateSMS($appId,$to,$templateId,$param);
        if (substr($arr,21,6) == 000000) {
            //如果成功就，这里只是测试样式，可根据自己的需求进行调节
            //将手机号保存在session中
            saveMobileInSession($to);
            session('SMScode', NULL);
            $info['status'] = 1;
            $info['SMScode'] = $param;
            $info['session_id'] = session_id();
            $info['msg'] = '短信验证码已发送成功，请注意查收短信';
            $this->apiSuccess('发送成功',NULL,$info);          
        }else{
            //如果不成功
            session('SMScode', NULL);
            $info['status'] = 0;
            $info['msg'] = '短信验证码发送失败，请联系客服';
            $this->apiError('发送失败',NULL,$info);           
        }           
    }
	
    public function logout($uid = "") {
	  if(IS_POST){
			
		if(empty($uid)){
			$info = array();
			$info['msg'] = '参数丢失';
			$info['status'] = 101;
			$this->apiError(false,null,$info);
		}
        $this->requireLogin();
        //调用用户中心
        $model = D('Home/Member');
        $model->logout();
        session_destroy();
        //返回成功信息
		##更改设备管理员为该用户的在离线状态
			$da['root_isonline'] = -1;
			$change_line = M('Equipment')->where(array('root_id'=>$uid))->save($da);
        $this->apiSuccess("登出成功");
	  }else{
		  $info = array();
          $info['status'] = 0;
          $info['msg'] = '非法请求'; 
          $this->apiError(FALSE,NULL,$info);
	  }	
    }

     public function register($name, $password,$email=null,$mobile,$Fcode=NULL) {
         ##如果有推广码$Fcode,则进行搜索比对，看该推广码是否正确
        if(!empty($Fcode)){
            $check = M('UcenterMember')->where(array('code'=>$Fcode))->find();
            if(empty($check)){
                $info = array();
                $info['status'] = 0;
                $info['msg'] = '没有该推荐码'; 
                $this->apiError(FALSE,NULL,$info);
            }
         }
        //调用用户中心
        $api = new UserApi();
        $nickname = $name;
        $username = $mobile;
        $uid = $api->register($username, $nickname, $password, $email,$mobile); // 邮箱为空
        if($uid <= 0) {
            $message = $this->getRegisterErrorMessage($uid);
            $code = $this->getRegisterErrorCode($uid);
            $this->apiError($code,$message);
        }
        if($uid > 0){
            //注册成功生成推广码
            $add['code'] = build_code();//生成自己的推广码
            $add['from_code'] = $Fcode;//推荐人推广码
            $score = get_table_field('REGISTER_SCORE','tag','value','ScoreRule');//获取积分规则的值
            $score_original = get_table_field($uid,'uid','score1','Member');//获取用户原来的积分值
            $data['score1'] = $score + $score_original;
            $add_code = M('UcenterMember')->where(array('id'=>$uid))->save($add);//存储自己和推广人的推广码
            $add_score = M('Member')->where(array('uid'=>$uid))->save($data);//为当前注册用户增加积分
            ##给推广人增加积分
            $take_from_score = get_table_field('INVITE_REGISTER_SCORE','tag','value','ScoreRule');//获取积分规则的值(被推荐人积分规则)
            $from_score = get_table_field($Fcode,'code','score1','Member');
            $from['score1'] = $from_score + $take_from_score;
            $add_from_score = M('Member')->where(array('code'=>$Fcode))->save($from);//为此邀请码用户增加积分
            //返回成功信息
            $extra = array();
            $extra['uid'] = $uid;
            $this->apiSuccess("注册成功", null, $extra);
           
        }
    }
    public function sendSms($mobile=null) {
        //如果没有填写手机号码，则默认使用已经绑定的手机号码
        if($mobile==='')
        {
            $this->apiError(802, "请输入手机号码。");
        }
        $uid = $this->getUid();
        $user = $this->getCombinedUser($uid);
        if($mobile === null) {
            $this->requireLogin();
            $mobile = $user['mobile'];
        }
        if(!$mobile) {
            $this->apiError(801, "用户未绑定手机号");
        }
        //调用短信插件发送短信
        $tianyi = new TianyiAddon;
        $result = $tianyi->sendVerify($mobile);
        if($result < 0) {
            $this->apiError(802, "短信发送失败：".$tianyi->getError());
        }
        //将手机号保存在session中
        saveMobileInSession($mobile);
        //显示成功消息
        $result = array('session_id'=>session_id());
        $this->apiSuccess("短信发送成功", null, $result);
    }
//修改密码
    public function resetPassword($new_password) {
        //检验校验码是否正确
        $mobile = getMobileFromSession();
        if(!$mobile) {
            $this->apiError(903, "未发送短信验证码");
        }
        //$tianyi = new TianyiAddon;
        //if(!$tianyi->checkVerify($mobile, $verify)) {
            //$this->apiError(803, "校验码错误");
       // }
        //根据手机号查询UID
        $uid = $this->api->getUidByMobile($mobile);
        if(!$uid) {
            $this->apiError(902, "该手机尚未绑定任何帐号");
        }
        //设置新密码
        $result = $this->updateUser($uid, array('password'=>$new_password));
        if(!$result) {
            $this->apiError(901, "更新用户信息失败：".$this->api->getError());
        }
        // TODO: 清除已登录的SESSION，强制重新登录
        //返回成功信息
        $this->apiSuccess("密码修改成功");
    }
	
	//获取手机验证码
    public function takeCode($newMobile){
        if(empty($newMobile)){
            $this->apiError(400, '号码不能为空');
        }else{
            
                 //初始化必填
                    $options['accountsid'] = C('ACCOUNTSID'); //填写自己的
                    $options['token'] = C('TOKEN'); //填写自己的
                    //初始化 $options必填
                    $ucpass = new \ORG\Ucpaas($options);

                            //随机生成6位验证码
                    srand((double)microtime()*1000000);//create a random number feed.
                    $ychar="0,1,2,3,4,5,6,7,8,9";
                    $list=explode(",",$ychar);
                    for($i=0;$i<6;$i++){
                    $randnum=rand(0,8); // 10+26;
                    $authnum.=$list[$randnum];
                    }
                    //短信验证码（模板短信）,默认以65个汉字（同65个英文）为一条（可容纳字数受您应用名称占用字符影响），超过长度短信平台将会自动分割为多条发送。分割后的多条短信将按照具体占用条数计费。
                    $appId = C('APPID');  //填写自己的
                    $to = $newMobile;
                    $templateId = C('TEMPLATEID');
                    $param=$authnum;
                    $arr=$ucpass->templateSMS($appId,$to,$templateId,$param);
                    if (substr($arr,21,6) == 000000) {
                        //如果成功就，这里只是测试样式，可根据自己的需求进行调节
                        //将手机号保存在session中
                        saveMobileInSession($to);
                        session('SMScode', NULL);
                        $info['status'] = 1;
                        $info['SMScode'] = $param;
                        $info['session_id'] = session_id();
                        $info['msg'] = '短信验证码已发送成功，请注意查收短信';
                        return $info;       
                    }else{
                        //如果不成功
                        session('SMScode', NULL);
                        $info['status'] = 0;
                        $info['msg'] = '短信验证码发送失败，请联系客服';
                        return $info;           
                    }           
        }
    }
	
	/*
     * 找回密码
     * $mobile 用户绑定手机号码
	 * $new_password  新密码
     */
    public function findPwd($mobile="",$new_password=""){
        if(!IS_POST){
            $info = array();
            $info['status'] = 302;
            $info['msg'] = '非法请求';
            $this->apiError(FALSE,NULL,$info);
        }
            
        if(empty($mobile) || empty($new_password)){
            $info = array();
            $info['status'] = 0;
            $info['msg'] = '参数不能为空';
            $this->apiError(FALSE,NULL,$info);
        }
            
        $check = M('UcenterMember')->where(array('mobile'=>$mobile))->find();
        if(empty($check)){
            $info = array();
            $info['status'] = 302;
            $info['msg'] = '该手机没有注册或进行帐户帮定';
            $this->error(FALSE,NULL,$info);die;
        }
        //设置新密码
        $data = array();
        $data['password'] = (think_ucenter_md5($new_password, UC_AUTH_KEY));        
        $result = M('UcenterMember')->where(array('mobile'=>$mobile))->save($data);
        if($result){
            $info = array();
            $info['status'] = 1;
            $info['msg'] = '密码修改成功';
            $this->apiSuccess(TRUE,NULL,$info);
        }else{
            $info = array();
            $info['status'] = 202;
            $info['msg'] = '修改失败：该密码为之前的密码';
            $this->apiError(FALSE,NULL,$info);
        }
        
    }
     //修改手机号码--获取验证码
    public function ResetMobile($uid,$oldMobile,$newMobile){
        ##检查就手机号码是否正确
        $check = M('UcenterMember')->where(array('id'=>$uid))->getField('mobile');
		$checkNew = M('UcenterMember')->where(array('mobile'=>$newMobile))->find();
        if($oldMobile != $check){
            $info = array();
            $info['msg'] = '原手机号码错误';
            $info['status'] = 0;
            $this->apiError(FALSE,NULL,$info);die;
        }
		if($checkNew){
			$info = array();
            $info['msg'] = '新手机号码已被使用请更换';
            $info['status'] = 0;
            $this->apiError(FALSE,NULL,$info);die;
		}

            $res = $this->takeCode($newMobile);
            $this->apiSuccess(TRUE, NULL, $res);

       
    }
     //修改手机号码
    public function updateMobile($uid,$newMobile){
        if(empty($uid) || empty($newMobile)){
            $info = array();
            $info['msg'] = '参数不能为空';
            $info['status'] = 0;
            $this->apiError(FALSE,NULL,$info);
        }else{
        
            //根据手机号查询UID
            $data['mobile'] = $newMobile;
            $Result = M('UcenterMember')->where(array('id'=>$uid))->save($data);
            if(!$Result) {
				$info = array();
				$info['msg'] = '手机号码修改失败';
				$info['status'] = 0;
                $this->apiError(FALSE,NULL,$info);
            }else{
				// TODO: 清除已登录的SESSION，强制重新登录
            //返回成功信息
            $info = array();
            $info['msg'] = '手机号码修改成功';
            $info['status'] = 1;
            $this->apiSuccess(TRUE,NULL,$info);
			}
                   
            }
    }
    // 修改密码（和原始密码进行比对）
    public function ResetPwd($uid,$oldPwd,$newPwd){
         if(empty($uid)){
                $info = array();
                $info['msg'] = '非法操作';
                $info['status'] = 0;
                $this->apiError(FALSE,NULL,$info);
         }else{
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
            $check = M('UcenterMember')->where(array('id'=>$uid))->getField('password');
            if(think_ucenter_md5($oldPwd, UC_AUTH_KEY) == $check){
                 $data['password'] = think_ucenter_md5($newPwd, UC_AUTH_KEY);
                $res = M('UcenterMember')->where(array('id'=>$uid))->save($data);
                if($res){
                    $info = array();
                    $info['msg'] = '密码修改成功';
                    $info['status'] = 1;
                    $this->apiSuccess(TRUE,NULL,$info);
                }else{
                    $info = array();
                    $info['msg'] = '密码修改失败，请重试';
                    $info['status'] = 0;
                    $this->apiError(FALSE,NULL,$info);
                }
            }else{
                $info = array();
                $info['msg'] = '原始密码错误';
                $info['status'] = 0;
                $this->apiError(FALSE,NULL,$info);
            }
         }
        
    }

    public function resetPasswordByEmail($email) {
        //调用找回密码组件
        $addon = new ResetByEmailAddon();
        $result = $addon->sendEmail($email);
        if(!$result) {
            $this->apiError(0,$addon->getError());
        }
        //返回结果
        $this->apiSuccess('邮件发送成功，请登录自己的邮箱找回密码');
    }
    
    /*文件上传接口**/
    public function uploandFile()
        {
          //print_r($_FILES);
                $upload = new \Think\Upload();// 实例化上传类
                $upload->maxSize   =     3145728 ;// 设置附件上传大小   
                $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型   
                $upload->savePath  =      'Picture/'; // 设置附件上传目录
                $info   =   $upload->upload();   
                //print_r($info);
                if(!$info) 
                {// 上传错误提示错误信息       
                 
                    $list=array();
                    $list['msg']=0;
                    $list['magess']=$upload->getError();
                    $this->apiError($list,'json');
                }
                else
                {// 上传成功       
                    
                    $list=array();
                    
                    foreach ($info as $k=>$v){
                        $md5  = $v['md5'];
                        $sha1 = $v['sha1'];
                        $list['mag']=1;
                        $list['pic_path'][]='/Uploads/'.str_replace('./', '', $v['savepath']).$v['savename'];
                        $list_path['pic_path']='/Uploads/'.str_replace('./', '', $v['savepath']).$v['savename'];
                        $data['path']=$list_path['pic_path'];
                        $data['md5'] = $md5;
                        $data['sha1'] = $sha1;
                        $data['status']=1;
                        $data['create_time'] = time();
                        $check = M('Picture')->where(array('md5'=>$md5,'sha1'=>$sha1))->find();
						if(!empty($check)){                    
                                $list['pic_id'][]=$check['id'];
                                $list['pic_path'][]=$check['path'];
                        }else{  
                            
                            $m=M('Picture')->add($data);
                            $path = M('Picture')->where(array('id'=>$m))->getField('path');
                            if($m){
                                $list['pic_id'][]=$m;
                                $list['pic_path'][]=$path;
                            } 
                        }
                    }                                                                                                              
                 }
                 $this->ajaxReturn($list,'json');          
        }
        
}