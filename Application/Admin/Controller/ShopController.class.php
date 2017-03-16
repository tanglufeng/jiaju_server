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
class ShopController extends AdminController
{
    
    /*
     * 商家列表
     */
    
    public function shopList($page = 1, $r = 15){
        $list = M('StoreList')->order("createtime desc")->page($page, $r)->select();
        $totalCount = M('StoreList')->count();
        foreach($list as $k=>$v){
            $list[$k]['status'] = ($v['status'] ==1) ? '启用':'禁用';
			##地址转换
			$ad = explode(',',$v['address']);
			    $province = get_table_field($ad[0],'id','name','District');
                $city = get_table_field($ad[1],'id','name','District');
                $district = get_table_field($ad[2],'id','name','District');
			$list[$k]['address'] = $province.$city.$district;
        }
        $builder = new AdminListBuilder();
        $builder->title(L('商家列表'));
        $builder->meta_title = L('商家列表');
        $builder->buttonNew(U('Shop/editStore'))->buttonDelete(U('Shop/delStore'));
        $builder->keyId()->keyText('name','商家名称')->keyText('address','商家店铺地址')->keyImage('ico','商家封面')->keyText('introduce','商家简介')->keyImage('QR_code_id','商家二维码')->keyTime('servicetime','服务到期时间')->keyTime('createtime','商家入驻时间')->keyText('status','状态');
        $builder->keyDoAction('Shop/editStore?id=###', L('编辑'))->keyDoAction('Shop/delStore?id=###', L('删除'));
        $builder->data($list);
        $builder->pagination($totalCount, $r);
        $builder->display();
    }
    
        
     /*
     * 添加、编辑商家信息
     */
    public function editStore($id = 0){
         
        if($_POST){
            if(!empty($_POST['name']) && !empty($_POST['province']) && !empty($_POST['city']) && !empty($_POST['district']) && !empty($_POST['address_detail']) && !empty($_POST['ico']) && !empty($_POST['introduce']) && !empty($_POST['servicetime']) && !empty($_POST['status'])){
                //$province = get_table_field($_POST['province'],'id','name','District');
                //$city = get_table_field($_POST['city'],'id','name','District');
                //$district = get_table_field($_POST['district'],'id','name','District');
                $da['name'] = $_POST['name']; 
                $da['address'] = $_POST['province'].','.$_POST['city'].','.$_POST['district']; 
                $da['ico'] = $_POST['ico']; 
                $da['introduce'] = $_POST['introduce'];        
                $da['address_detail'] = $_POST['address_detail'];

               ##通过一个地址来获取经纬度
                $enAddress = urldecode($_POST['address_detail']);
                $output = C('BAIDU_API_KEY');
                $citys = $city;
                $url = 'http://api.map.baidu.com/geocoder?address='.$enAddress.'&output=json&key='.$output.'&city='.$city;
                $return = file_get_contents($url);
                $coord = json_decode($return,TRUE);
                $da['coord'] = $coord['result']['location']['lng'].','.$coord['result']['location']['lat']; //lng--经度，lat-纬度
                $da['QR_code_id'] = 1; 
                $da['QR_code_url'] = 111;
                $da['tel'] = $_POST['tel'];
                $da['discount'] = $_POST['discount'];
				$da['open_time'] = $_POST['open_time'];
				$da['store_show_id'] = $_POST['store_show_id'];
                $da['servicetime'] = $_POST['servicetime']; 
                $da['createtime'] = time(); 
                $da['status'] = $_POST['status'];
                if($id == 0){
                    $add = M('StoreList')->add($da);
                    if($add){
                        $this->success('添加成功',U('Shop/shopList'));
                    }
                }else{
                     $update = M('StoreList')->where(array('id'=>$id))->save($da);
                    if($update){
                        $this->success('修改成功',U('Shop/shopList'));
                    }
                }
               
            }else{
                $this->error('参数不能为空，请检查');
            }
            
        }
        
        $data = M('StoreList')->where(array('id'=>$id))->find();
        $data['address']=str2arr($data['address']);
        $builder = new AdminConfigBuilder();
        if($id == 0 ){
            $title = '新增用户设备';
        }else{
            $title = '编辑用户设备';
        }
        
        $builder->title($title);
        $builder->meta_title = L($title);
        $builder->keyId()->keyText('name', '商家名称')->keyCity('address')->keyText('address_detail','商家详细地址(请确认详细地址的准确性，否则将导致APP出错)')->keySingleImage('ico', '商品封面')->keyMultiImage('store_show_id', '商家图片展示')->keyText('tel','电话号码')->keyText('open_time','营业时间')->keyText('discount','商家折扣')->keyTextArea('introduce', '商家介绍')->keyTime('servicetime','服务到期时间')->keyStatus();
        $builder->data($data);
        $builder->buttonSubmit(U('editStore'), $id == 0 ? L('新增') : L('编辑'))->buttonBack();
        $builder->display();
    }

    
     /*
     * 删除商家
     */
    public function delStore($id){
         $id = array_unique((array)I('id',0));

        if ( empty($id) ) {
            $this->error(L('_ERROR_DATA_SELECT_').L('_EXCLAMATION_'));
        }

        $map = array('id' => array('in', $id) );
        $del = M('StoreList')->where($map)->delete();
        if($del){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }
   
}
