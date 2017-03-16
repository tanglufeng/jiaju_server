<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Home\Controller;

use Think\Controller;


/**
 * 前台首页控制器
 * 主要获取首页聚合数据
 */
class IndexController extends Controller
{

    //系统首页
    public function index()
    {
        if(is_login()){
        }
        hook('homeIndex');
        $default_url = C('DEFUALT_HOME_URL');//获得配置，如果为空则显示聚合，否则跳转
        if ($default_url != ''&&strtolower($default_url)!='home/index/index') {
            redirect(get_nav_url($default_url));
        }

        $show_blocks = get_kanban_config('BLOCK', 'enable', array(), 'Home');

        $this->assign('showBlocks', $show_blocks);


        $enter = modC('ENTER_URL', '', 'Home');
        $this->assign('enter', get_nav_url($enter));




            $sub_menu['left']= array(array('tab' => 'home', 'title' => L('_SQUARE_'), 'href' =>  U('index'))//,array('tab'=>'rank','title'=>'排行','href'=>U('rank'))

            );


        $this->assign('sub_menu', $sub_menu);
        $this->assign('current', 'home');



        $this->display();
    }

    public function app_build(){
        $res=M("AppBuild")->order("create_time desc")->find();
        if($res){

            $fileres=M('File')->where(array('id'=>$res['filepath']))->find();//get_file($res['filepath'],'path');
            $res['filepath']=$fileres['savepath'].$fileres['savename'];
            $filename=$res['filepath'];
            echo "正在下载，请稍后。";
            header("Location:".$filename);
        }else{
            echo "正在努力生成！请稍后重试！";
        }
        
        exit();

        // header ( "Cache-Control: max-age=0" );
        // header ( "Content-Description: File Transfer");
        // header ( 'Content-disposition: attachment; filename=' . basename ( $filename) ); // 文件名
        // dump($res);exit;
        // header ( "Content-Type: application/apk" ); // zip格式的
        // header ( "Content-Transfer-Encoding: binary" ); // 告诉浏览器，这是二进制文件
        // header ( 'Content-Length: ' . filesize ( $filename) ); // 告诉浏览器，文件大小

        // @readfile ( $filename );//输出文件;
       
    }

    protected function _initialize()
    {

        /*读取站点配置*/
        $config = api('Config/lists');
        C($config); //添加配置

        if (!C('WEB_SITE_CLOSE')) {
            $this->error(L('_ERROR_WEBSITE_CLOSED_'));
        }
    }


}