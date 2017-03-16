<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Admin\Controller;

use Admin\Builder\AdminConfigBuilder;
use Admin\Builder\AdminListBuilder;
use Admin\Builder\AdminSortBuilder;
use Common\Model\MemberModel;
use User\Api\UserApi;

/**
 * 后台用户控制器
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
class EquipmentController extends AdminController
{
    
    /*
     * 积分商城列表
     */
    
    public function scoreShopList($page = 1, $r = 15){
        $list = M('ScoreShop')->order("createtime desc")->page($page, $r)->select();
        $totalCount = M('ScoreShop')->count();
        foreach($list as $k=>$v){
            $list[$k]['type'] = M('DeviceType')->where(array('id'=>$v['type']))->getField('name');
            $list[$k]['status'] = ($v['status'] ==1) ? '启用':'禁用';
        }
        $builder = new AdminListBuilder();
        $builder->title(L('商城设备列表'));
        $builder->meta_title = L('商城设备列表');
        $builder->buttonNew(U('Equipment/editScoreShop'))->buttonDelete(U('Equipment/delScoreShop'));
        $builder->keyId()->keyText('name','商品名称')->keyText('type','设备类型')->keyImage('pic','商品图片')->keyText('score','积分价格')->keyText('produce','生产厂家')->keyTime('createtime','添加时间')->keyText('remark','标签')->keyLink('status','状态','Equipment/setStatus?id=###','_self');
        $builder->keyDoAction('Equipment/editScoreShop?id=###', L('编辑'))->keyDoAction('Equipment/delScoreShop?ids=###', L('删除'));
        $builder->data($list);
        $builder->pagination($totalCount, $r);
        $builder->display();
    }
    
        
     /*
     * 添加、编辑积分商城商品
     */
    public function editScoreShop($id = 0){
         
        if($_POST){
            if(!empty($_POST['name']) && !empty($_POST['type']) && !empty($_POST['pic']) && !empty($_POST['score']) && !empty($_POST['produce']) && !empty($_POST['remark']) && !empty($_POST['status'])){
                $da['name'] = $_POST['name']; 
                $da['type'] = $_POST['type']; 
                $da['pic'] = $_POST['pic']; 
                $da['score'] = $_POST['score']; 
                $da['produce'] = $_POST['produce']; 
                $da['createtime'] = time(); 
                $da['remark'] = $_POST['remark']; 
                $da['status'] = $_POST['status'];
                if($id == 0){
                    $add = M('ScoreShop')->add($da);
                    if($add){
                        $this->success('添加成功',U('equipment/scoreShopList'));
                    }
                }else{
                     $update = M('ScoreShop')->where(array('id'=>$id))->save($da);
                    if($update){
                        $this->success('修改成功',U('equipment/scoreShopList'));
                    }
                }
               
            }else{
                $this->error('参数不能为空，请检查');
            }
            
        }
        
        //设备类型
        $opt = array();
        $accountList = M('DeviceType')->where(array('status'=>1))->select();
        foreach ($accountList as $k=>$category) {
            $opt[$accountList[$k]['id']] = $category['name'];
        }
        $data = M('ScoreShop')->where(array('id'=>$id))->find();
        $builder = new AdminConfigBuilder();
        if($id == 0 ){
            $title = '新增用户设备';
        }else{
            $title = '编辑用户设备';
        }
        $builder->title($title);
        $builder->meta_title = L($title);
        $builder->keyId()->keyText('name', '商品名称')->keySelect('type','设备类型','',array('0'=>'请选择')+$opt)->keySingleImage('pic', '商品封面')->keyText('score', '商品积分价格')->keyText('produce','商品生产厂家')->keyText('remark','标签')->keyStatus();
        $builder->data($data);
        $builder->buttonSubmit(U('editScoreShop'), $id == 0 ? L('新增') : L('编辑'))->buttonBack();
        $builder->display();
    }
    
     /*
     * 删除积分商城设备
     */
    public function delScoreShop($ids =""){
         $id = array_unique((array)I('id',0));

        if ( empty($ids) ) {
            $this->error(L('_ERROR_DATA_SELECT_').L('_EXCLAMATION_'));
        }

        $map = array('id' => array('in', $ids) );
        $del = M('ScoreShop')->where($map)->delete();
        if($del){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }
    /*
     * 设备类型列表
     */
    public function deviceTypeList($page = 1, $r = 15){
        $list = M('DeviceType')->order("createtime desc")->page($page, $r)->select();
        $totalCount = M('DeviceType')->count();
        foreach($list as $k=>$v){

        }
        $builder = new AdminListBuilder();
        $builder->title('设备类型');
        $builder->meta_title = L('设备类型');
        $builder->buttonNew(U('Equipment/editDeviceType'))->buttonDelete(U('Equipment/delDeviceType'));
        $builder->keyId()->keyText('name','设备名称')->keyStatus('status','状态');
        $builder->keyDoAction('Equipment/editDeviceType?id=###', L('编辑'))->keyDoAction('Equipment/delDeviceType?id=###', L('删除'));
        $builder->data($list);
        $builder->pagination($totalCount, $r);
        $builder->display();
    }
    
    /*
     * 设备类型添加，编辑
     */
    public function editDeviceType($id = 0){
        if($_POST){
            if(!empty($_POST['name']) && !empty($_POST['status'])){
                $da['name'] = $_POST['name']; 
                $da['createtime'] = time(); 
                $da['status'] = $_POST['status'];
                if($id == 0){
                    $add = M('DeviceType')->add($da);
                    if($add){
                        $this->success('添加成功',U('equipment/deviceTypeList'));
                    }
                }else{
                     $update = M('DeviceType')->where(array('id'=>$id))->save($da);
                    if($update){
                        $this->success('修改成功',U('equipment/deviceTypeList'));
                    }
                }
               
            }else{
                $this->error('参数不能为空，请检查');
            }
            
        }
        $data = M('DeviceType')->where(array('id'=>$id))->find();
        $builder = new AdminConfigBuilder();
        if($id == 0 ){
            $title = '添加设备类型';
        }else{
            $title = '编辑设备类型';
        }
        $builder->title($title);
        $builder->meta_title = L($title);
        $builder->keyId()->keyText('name', '类型名称')->keyStatus();
        $builder->data($data);
        $builder->buttonSubmit(U('editDeviceType'), $id == 0 ? L('新增') : L('编辑'))->buttonBack();
        $builder->display();
    }
     
    
     /*
     * 删除设备类型
     */
    public function delDeviceType($id){
         $id = array_unique((array)I('id',0));

        if ( empty($id) ) {
            $this->error(L('_ERROR_DATA_SELECT_').L('_EXCLAMATION_'));
        }

        $map = array('id' => array('in', $id) );
        $del = M('DeviceType')->where($map)->delete();
        if($del){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }
   
}
