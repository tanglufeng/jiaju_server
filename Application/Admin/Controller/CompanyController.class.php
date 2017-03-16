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
class CompanyController extends AdminController
{
    
    /*
     * 员工列表
     */
    
    public function memberList($page = 1, $r = 15){
        $list = M('InstallMember')->order("createtime desc")->page($page, $r)->select();
        $totalCount = M('InstallMember')->count();
        foreach($list as $k=>$v){
            $list[$k]['status'] = ($v['status'] == 0) ? '离职':'在职';
        }
        $builder = new AdminListBuilder();
        $builder->title(L('员工列表'));
        $builder->meta_title = L('员工列表');
        $builder->buttonNew(U('Company/editMember'))->buttonDelete(U('Company/delMember'));
        $builder->keyId()->keyText('name','员工姓名')->keyText('mobile','联系方式')->keyText('Department','部门')->keyText('Position','职位')->keyText('AgentID','工号')->keyTime('entrytime','入职时间')->keyTime('DepartureTime','离职时间')->keyText('address','员工住址')->keyText('status','员工状态');
        $builder->keyDoAction('Company/editMember?id=###', L('编辑'))->keyDoAction('Company/delMember?id=###', L('删除'));
        $builder->data($list);
        $builder->pagination($totalCount, $r);
        $builder->display();
    }
    
        
     /*
     * 添加、编辑员工信息
     */
    public function editMember($id = 0){
         
        if($_POST){
            if(!empty($_POST['name']) && !empty($_POST['mobile']) && !empty($_POST['Department']) && !empty($_POST['Position']) && !empty($_POST['AgentID']) && !empty($_POST['entrytime']) && !empty($_POST['address'])){
                $da['name'] = $_POST['name']; 
                $da['mobile'] = $_POST['mobile']; 
                $da['Department'] = $_POST['Department']; 
                $da['Position'] = $_POST['Position']; 
                $da['AgentID'] = $_POST['AgentID']; 
                $da['entrytime'] = $_POST['entrytime']; 
                $da['DepartureTime'] = $_POST['DepartureTime']; 
                $da['address'] = $_POST['address']; 
                $da['createtime'] = time(); 
                $da['status'] = $_POST['status'];
                if($id == 0){
                    $add = M('InstallMember')->add($da);
                    if($add){
                        $this->success('添加成功',U('Company/memberList'));
                    }
                }else{
                     $update = M('InstallMember')->where(array('id'=>$id))->save($da);
                    if($update){
                        $this->success('修改成功',U('Company/memberList'));
                    }
                }
               
            }else{
                $this->error('参数不能为空，请检查');
            }
            
        }       
        $data = M('InstallMember')->where(array('id'=>$id))->find();
        $builder = new AdminConfigBuilder();
        if($id == 0 ){
            $title = '新增员工';
        }else{
            $title = '编辑员工';
        }
        $builder->title($title);
        $builder->meta_title = L($title);
        $builder->keyId()->keyText('name','员工姓名')->keyText('mobile','联系方式')->keyText('Department','部门')->keyText('Position','职位')->keyText('AgentID','工号')->keyTime('entrytime','入职时间')->keyTime('DepartureTime','离职时间')->keyText('address','员工住址')->keyStatus("status",'状态');
        $builder->data($data);
        $builder->buttonSubmit(U('editMember'), $id == 0 ? L('新增') : L('编辑'))->buttonBack();
        $builder->display();
    }
    
     /*
     * 删除员工
     */
    public function delMember($id){
         $id = array_unique((array)I('id',0));

        if ( empty($id) ) {
            $this->error(L('_ERROR_DATA_SELECT_').L('_EXCLAMATION_'));
        }

        $map = array('id' => array('in', $id) );
        $del = M('InstallMember')->where($map)->delete();
        if($del){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    } 
    
    /*
     * 用户帮助列表
     */
    public function helpList($page = 1, $r = 15){
        $list = M('Help')->order("createtime desc")->page($page, $r)->select();
        $totalCount = M('Help')->count();
        foreach($list as $k=>$v){
            $list[$k]['status'] = ($v['status'] == 0) ? '禁用':'启用';
        }
        $builder = new AdminListBuilder();
        $builder->title(L('帮助问题列表'));
        $builder->meta_title = L('帮助问题列表');
        $builder->buttonNew(U('Company/editHelp'))->buttonDelete(U('Company/delHelp'));
        $builder->keyId()->keyText('question','问题')->keyText('answer','答案')->keyTime('createtime','添加时间');
        $builder->keyDoAction('Company/editHelp?id=###', L('编辑'))->keyDoAction('Company/delHelp?id=###', L('删除'));
        $builder->data($list);
        $builder->pagination($totalCount, $r);
        $builder->display();
    }
    
    /*
     * 添加、编辑帮助信息
     */
    public function editHelp($id = 0){
         
        if($_POST){
            if(!empty($_POST['question']) && !empty($_POST['answer']) && !empty($_POST['status'])){
                $da['question'] = $_POST['question']; 
                $da['answer'] = $_POST['answer']; 
                $da['createtime'] = time(); 
                $da['status'] = $_POST['status'];
                if($id == 0){
                    $add = M('Help')->add($da);
                    if($add){
                        $this->success('添加成功',U('Company/helpList'));
                    }
                }else{
                     $update = M('Help')->where(array('id'=>$id))->save($da);
                    if($update){
                        $this->success('修改成功',U('Company/helpList'));
                    }
                }
               
            }else{
                $this->error('参数不能为空，请检查');
            }
            
        }       
        $data = M('Help')->where(array('id'=>$id))->find();
        $builder = new AdminConfigBuilder();
        if($id == 0 ){
            $title = '新增帮助信息';
        }else{
            $title = '编辑帮助信息';
        }
        $builder->title($title);
        $builder->meta_title = L($title);
        $builder->keyId()->keyText('question','问题')->keyTextArea('answer','答案')->keyStatus();
        $builder->data($data);
        $builder->buttonSubmit(U('editHelp'), $id == 0 ? L('新增') : L('编辑'))->buttonBack();
        $builder->display();
    }
    
    
     /*
     * 删除帮助信息
     */
    public function delHelp($id){
         $id = array_unique((array)I('id',0));

        if ( empty($id) ) {
            $this->error(L('_ERROR_DATA_SELECT_').L('_EXCLAMATION_'));
        }

        $map = array('id' => array('in', $id) );
        $del = M('Help')->where($map)->delete();
        if($del){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    } 
    
    
    /*
     * 推广信息列表
     */
    public function generalizeList($page = 1, $r = 15){
        $list = M('Generalize')->order("createtime desc")->page($page, $r)->select();
        $totalCount = M('Generalize')->count();
        foreach($list as $k=>$v){
            $list[$k]['status'] = ($v['status'] == 0) ? '禁用':'启用';
        }
        $builder = new AdminListBuilder();
        $builder->title(L('推广列表'));
        $builder->meta_title = L('推广列表');
        $builder->buttonNew(U('Company/editGeneralize'))->buttonDelete(U('Company/delGeneralize'));
        $builder->keyId()->keyText('title','标题')->keyTime('createtime','添加时间')->keyText('status','状态');
        $builder->keyDoAction('Company/editGeneralize?id=###', L('编辑'))->keyDoAction('Company/delGeneralize?id=###', L('删除'));
        $builder->data($list);
        $builder->pagination($totalCount, $r);
        $builder->display();
    }
    
    /*
     * 添加、编辑推广信息
     */
    public function editGeneralize($id = 0){
         
        if($_POST){
            if(!empty($_POST['title']) && !empty($_POST['content']) && !empty($_POST['status'])){
                $da['title'] = $_POST['title']; 
                $da['content'] = $_POST['content']; 
                $da['createtime'] = time(); 
                $da['status'] = $_POST['status'];
                if($id == 0){
                    $add = M('Generalize')->add($da);
                    if($add){
                        $this->success('添加成功',U('Company/generalizeList'));
                    }
                }else{
                     $update = M('Generalize')->where(array('id'=>$id))->save($da);
                    if($update){
                        $this->success('修改成功',U('Company/generalizeList'));
                    }
                }
               
            }else{
                $this->error('参数不能为空，请检查');
            }
            
        }       
        $data = M('Generalize')->where(array('id'=>$id))->find();
        $builder = new AdminConfigBuilder();
        if($id == 0 ){
            $title = '新增帮助信息';
        }else{
            $title = '编辑帮助信息';
        }
        $builder->title($title);
        $builder->meta_title = L($title);
        $builder->keyId()->keyText('title','标题')->keyTextArea('content','推广内容')->keyStatus();
        $builder->data($data);
        $builder->buttonSubmit(U('editGeneralize'), $id == 0 ? L('新增') : L('编辑'))->buttonBack();
        $builder->display();
    }
    
    
     /*
     * 删除推广信息
     */
    public function delGeneralize($id){
         $id = array_unique((array)I('id',0));

        if ( empty($id) ) {
            $this->error(L('_ERROR_DATA_SELECT_').L('_EXCLAMATION_'));
        }

        $map = array('id' => array('in', $id) );
        $del = M('Generalize')->where($map)->delete();
        if($del){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    } 
    /*
     * app版本列表
     */
    public function appList($page = 1, $r = 15){
        $list = M('AppBuild')->order("create_time desc")->page($page, $r)->select();
        $totalCount = M('AppBuild')->count();
        foreach($list as $k=>$v){
            $path = M('File')->where(array('id'=>$v['filepath']))->find();
            $list[$k]['filepath'] = $path['savepath'].$path['savename'];
        }
        $builder = new AdminListBuilder();
        $builder->title(L('App版本列表'));
        $builder->meta_title = L('App版本列表');
        $builder->buttonNew(U('Company/editAppUpdate'))->buttonDelete(U('Company/delApp'));
        $builder->keyId()->keyText('name','版本描述')->keyText('filepath','下载路径')->keyTime('create_time','发布时间')->keyUid('uid','发布者');
        $builder->keyDoAction('Company/editAppUpdate?id=###', L('编辑'))->keyDoAction('Company/delApp?id=###', L('删除'));
        $builder->data($list);
        $builder->pagination($totalCount, $r);
        $builder->display();
    }
    
    /*
     * 添加、编辑app版本
     */
    public function editAppUpdate($id = 0){
         
        if($_POST){
            if(!empty($_POST['name']) && !empty($_POST['filepath'])){
                $da['name'] = $_POST['name']; 
                $da['filepath'] = $_POST['filepath']; 
                $da['create_time'] = time(); 
                $da['uid'] = UID;
                if($id == 0){
                    $add = M('AppBuild')->add($da);
                    if($add){
                        $this->success('添加成功',U('Company/appList'));
                    }
                }else{
                     $update = M('AppBuild')->where(array('id'=>$id))->save($da);
                    if($update){
                        $this->success('修改成功',U('Company/appList'));
                    }
                }
               
            }else{
                $this->error('参数不能为空，请检查');
            }
            
        }       
        $data = M('AppBuild')->where(array('id'=>$id))->find();
        $builder = new AdminConfigBuilder();
        if($id == 0 ){
            $title = '新增App版本';
        }else{
            $title = '编辑App版本';
        }
        $builder->title($title);
        $builder->meta_title = L($title);
        $builder->keyId()->keyTextArea('name','版本描述')->keySingleFile('filepath','apk上传','上传时请耐心等待，上传速度受网络影响');
        $builder->data($data);
        $builder->buttonSubmit(U('editAppUpdate'), $id == 0 ? L('新增') : L('编辑'))->buttonBack();
        $builder->display();
    }
    
     /*
     * 删除app版本
     */
    public function delApp($id){
         $id = array_unique((array)I('id',0));

        if ( empty($id) ) {
            $this->error(L('_ERROR_DATA_SELECT_').L('_EXCLAMATION_'));
        }

        $map = array('id' => array('in', $id) );
        $del = M('AppBuild')->where($map)->delete();
        if($del){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    } 
}
