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
class UserController extends AdminController
{

    /**
     * 用户管理首页
     * @author 麦当苗儿 <zuojiazi@vip.qq.com>
     */
    public function index()
    {
        $nickname = I('nickname', '', 'text');
        $map['status'] = array('egt', 0);
        if (is_numeric($nickname)) {
            $map['uid|nickname'] = array(intval($nickname), array('like', '%' . $nickname . '%'), '_multi' => true);
        } else {
            if ($nickname !== '') {
                $map['nickname'] = array('like', '%' . (string)$nickname . '%');
            }
        }
        $list = $this->lists('Member', $map);
        int_to_string($list);
        foreach($list as $key=>$v){
            $list[$key]['ext']=query_user(array('username','mobile','email'),$v['uid']);

        }

		foreach($list as $k=>$v){
            $e_idlist=M('equipment')->where(array('uid'=>$v['uid']))->field("e_id,uid")->select();
			if(is_array($e_idlist)){
				foreach($e_idlist as $ks=>$vs){
                    $list[$k]['e_type'][] = $vs['e_id'];
				}

            }
        }

        foreach($list as $k=>$v){
            $list[$k]['e_type']= arr2str($v['e_type'],"|");
        }

        unset($e_idlist);
        foreach($list as $k=>$v){

            $e_idlist=M('EquipmentAuthRule')->where(array('uid'=>$v['uid']))->field("e_id,uid")->select();
            $e_idlist=assoc_unique($e_idlist,'e_id');
            // dump($e_idlist);
            if(is_array($e_idlist)){
                foreach($e_idlist as $ks=>$vs){
                    // $e_idlistss[$ks]=M('equipment')->where(array('id'=>$vs['e_id']))->field("e_id,uid")->select();
                    $list[$k]['e_idlists'][] = M('equipment')->where(array('id'=>$vs['e_id']))->field("e_id,uid")->select();;
                }

            }
        }
        unset($e_idlistss);
        foreach($list as $k=>$v){
            foreach($v['e_idlists'] as $ks=>$vs){
                foreach($vs as $kss=>$vss){
                    $list[$k]['e_idlists_id'][]=$vss['e_id'];
                }
            }
        }

        foreach($list as $k=>$v){
            $list[$k]['e_type']= arr2str($v['e_idlists_id'],"|");
        }
        $this->assign('_list', $list);
        $this->meta_title = L('_USER_INFO_');
        $this->display();
    }

    /**
     * 重置用户密码
     * @author 郑钟良<zzl@ourstu.com>
     */
    public function initPass()
    {
        $uids = I('id');
        !is_array($uids) && $uids = explode(',', $uids);
        foreach ($uids as $key => $val) {
            if (!query_user('uid', $val)) {
                unset($uids[$key]);
            }
        }
        if (!count($uids)) {
            $this->error(L('_ERROR_USER_RESET_SELECT_').L('_EXCLAMATION_'));
        }
        $ucModel = UCenterMember();
        $data = $ucModel->create(array('password' => '123456'));
        $res = $ucModel->where(array('id' => array('in', $uids)))->save(array('password' => $data['password']));
        if ($res) {
            $this->success(L('_SUCCESS_PW_RESET_').L('_EXCLAMATION_'));
        } else {
            $this->error(L('_ERROR_PW_RESET_'));
        }
    }

    public function changeGroup()
    {

        if ($_POST['do'] == 1) {
            //清空group
            $aAll = I('post.all', 0, 'intval');
            $aUids = I('post.uid', array(), 'intval');
            $aGids = I('post.gid', array(), 'intval');

            if ($aAll) {//设置全部用户
                $prefix = C('DB_PREFIX');
                D('')->execute("TRUNCATE TABLE {$prefix}auth_group_access");
                $aUids = UCenterMember()->getField('id', true);

            } else {
                M('AuthGroupAccess')->where(array('uid' => array('in', implode(',', $aUids))))->delete();;
            }
            foreach ($aUids as $uid) {
                foreach ($aGids as $gid) {
                    M('AuthGroupAccess')->add(array('uid' => $uid, 'group_id' => $gid));
                }
            }


            $this->success(L('_SUCCESS_'));
        } else {
            $aId = I('post.id', array(), 'intval');

            foreach ($aId as $uid) {
                $user[] = query_user(array('space_link', 'uid'), $uid);
            }


            $groups = M('AuthGroup')->where(array('status' => 1))->select();
            $this->assign('groups', $groups);
            $this->assign('users', $user);
            $this->display();
        }

    }

    /**用户扩展资料信息页
     * @param null $uid
     * @author 郑钟良<zzl@ourstu.com>
     */
    public function expandinfo_select($page = 1, $r = 20)
    {
        $nickname = I('nickname');
        $map['status'] = array('egt', 0);
        if (is_numeric($nickname)) {
            $map['uid|nickname'] = array(intval($nickname), array('like', '%' . $nickname . '%'), '_multi' => true);
        } else {
            $map['nickname'] = array('like', '%' . (string)$nickname . '%');
        }
        $list = M('Member')->where($map)->order('last_login_time desc')->page($page, $r)->select();
        $totalCount = M('Member')->where($map)->count();
        int_to_string($list);
        //扩展信息查询
        $map_profile['status'] = 1;
        $field_group = D('field_group')->where($map_profile)->select();
        $field_group_ids = array_column($field_group, 'id');
        $map_profile['profile_group_id'] = array('in', $field_group_ids);
        $fields_list = D('field_setting')->where($map_profile)->getField('id,field_name,form_type');
        $fields_list = array_combine(array_column($fields_list, 'field_name'), $fields_list);
        $fields_list = array_slice($fields_list, 0, 8);//取出前8条，用户扩展资料默认显示8条
        foreach ($list as &$tkl) {
            $tkl['id'] = $tkl['uid'];
            $map_field['uid'] = $tkl['uid'];
            foreach ($fields_list as $key => $val) {
                $map_field['field_id'] = $val['id'];
                $field_data = D('field')->where($map_field)->getField('field_data');
                if ($field_data == null || $field_data == '') {
                    $tkl[$key] = '';
                } else {
                    $tkl[$key] = $field_data;
                }
            }
        }
        $builder = new AdminListBuilder();
        $builder->title(L('_USER_EXPAND_INFO_LIST_'));
        $builder->meta_title = L('_USER_EXPAND_INFO_LIST_');
        $builder->setSearchPostUrl(U('Admin/User/expandinfo_select'))->search(L('_SEARCH_'), 'nickname', 'text', L('_PLACEHOLDER_NICKNAME_ID_'));
        $builder->keyId()->keyLink('nickname', L('_NICKNAME_'), 'User/expandinfo_details?uid=###');
        foreach ($fields_list as $vt) {
            $builder->keyText($vt['field_name'], $vt['field_name']);
        }
        $builder->data($list);
        $builder->pagination($totalCount, $r);
        $builder->display();
    }


    /**用户扩展资料详情
     * @param string $uid
     * @author 郑钟良<zzl@ourstu.com>
     */
    public function expandinfo_details($uid = 0)
    {
        if (IS_POST) {
            /* 修改积分 xjw129xjt(肖骏涛)*/
            $data = I('post.');
            foreach ($data as $key => $val) {
                if (substr($key, 0, 5) == 'score') {
                    $data_score[$key] = $val;
                }
            }
            unset($key, $val);
            $res = D('Member')->where(array('uid' => $data['id']))->save($data_score);
            foreach ($data_score as $key => $val) {
                $value = query_user(array($key), $data['id']);
                if ($val == $value[$key]) {
                    continue;
                }
                D('Ucenter/Score')->addScoreLog($data['id'], cut_str('score', $key, 'l'), 'to', $val, '', 0, get_nickname(is_login()) . L('_BACKGROUND_ADJUSTMENT_'));
                D('Ucenter/Score')->cleanUserCache($data['id'], cut_str('score', $key, 'l'));
            }
            unset($key, $val);
            /* 修改积分 end*/
            /*身份设置 zzl(郑钟良)*/
            $data_role = array();
            foreach ($data as $key => $val) {
                if ($key == 'role') {
                    $data_role = explode(',', $val);
                } else if (substr($key, 0, 4) == 'role') {
                    $data_role[] = $val;
                }
            }
            unset($key, $val);
            $this->_resetUserRole($uid, $data_role);
            $this->success(L('_SUCCESS_OPERATE_').L('_EXCLAMATION_'));
            /*身份设置 end*/
        } else {
            $map['uid'] = $uid;
            $map['status'] = array('egt', 0);
            $member = M('Member')->where($map)->find();
            $member['id'] = $member['uid'];
            $member['username'] = query_user('username', $uid);
            //扩展信息查询
            $map_profile['status'] = 1;
            $field_group = D('field_group')->where($map_profile)->select();
            $field_group_ids = array_column($field_group, 'id');
            $map_profile['profile_group_id'] = array('in', $field_group_ids);
            $fields_list = D('field_setting')->where($map_profile)->getField('id,field_name,form_type');
            $fields_list = array_combine(array_column($fields_list, 'field_name'), $fields_list);
            $map_field['uid'] = $member['uid'];
            foreach ($fields_list as $key => $val) {
                $map_field['field_id'] = $val['id'];
                $field_data = D('field')->where($map_field)->getField('field_data');
                if ($field_data == null || $field_data == '') {
                    $member[$key] = '';
                } else {
                    $member[$key] = $field_data;
                }
                $member[$key] = $field_data;
            }
            $builder = new AdminConfigBuilder();
            $builder->title(L('_USER_EXPAND_INFO_DETAIL_'));
            $builder->meta_title = L('_USER_EXPAND_INFO_DETAIL_');
            $builder->keyId()->keyReadOnly('username', L('_USER_NAME_'))->keyReadOnly('nickname', L('_NICKNAME_'));
            $field_key = array('id', 'username', 'nickname');
            foreach ($fields_list as $vt) {
                $field_key[] = $vt['field_name'];
                $builder->keyReadOnly($vt['field_name'], $vt['field_name']);
            }

            /* 积分设置 xjw129xjt(肖骏涛)*/
            $field = D('Ucenter/Score')->getTypeList(array('status' => 1));
            $score_key = array();
            foreach ($field as $vf) {
                $score_key[] = 'score' . $vf['id'];
                $builder->keyText('score' . $vf['id'], $vf['title']);
            }
            $score_data = D('Member')->where(array('uid' => $uid))->field(implode(',', $score_key))->find();
            $member = array_merge($member, $score_data);
            /*积分设置end*/
            $builder->data($member);

            /*身份设置 zzl(郑钟良)*/
            $already_role = D('UserRole')->where(array('uid' => $uid, 'status' => 1))->field('role_id')->select();
            if (count($already_role)) {
                $already_role = array_column($already_role, 'role_id');
            }
            $roleModel = D('Role');
            $role_key = array();
            $no_group_role = $roleModel->where(array('group_id' => 0, 'status' => 1))->select();
            if (count($no_group_role)) {
                $role_key[] = 'role';
                $no_group_role_options = $already_no_group_role = array();
                foreach ($no_group_role as $val) {
                    if (in_array($val['id'], $already_role)) {
                        $already_no_group_role[] = $val['id'];
                    }
                    $no_group_role_options[$val['id']] = $val['title'];
                }
                $builder->keyCheckBox('role', L('_ROLE_GROUP_NONE_'), L('_MULTI_OPTIONS_'), $no_group_role_options)->keyDefault('role', implode(',', $already_no_group_role));
            }
            $role_group = D('RoleGroup')->select();
            foreach ($role_group as $group) {
                $group_role = $roleModel->where(array('group_id' => $group['id'], 'status' => 1))->select();
                if (count($group_role)) {
                    $role_key[] = 'role' . $group['id'];
                    $group_role_options = $already_group_role = array();
                    foreach ($group_role as $val) {
                        if (in_array($val['id'], $already_role)) {
                            $already_group_role = $val['id'];
                        }
                        $group_role_options[$val['id']] = $val['title'];
                    }
                    $myJs = "$('.group_list').last().children().last().append('<a class=\"btn btn-default\" id=\"checkFalse\">".L('_SELECTION_CANCEL_')."</a>');";
                    $myJs = $myJs."$('#checkFalse').click(";
                    $myJs = $myJs."function(){ $('input[type=\"radio\"]').attr(\"checked\",false)}";
                    $myJs = $myJs.");";

                    $builder->keyRadio('role' . $group['id'], L('_ROLE_GROUP_',array('title'=>$group['title'])), L('_ROLE_GROUP_VICE_'), $group_role_options)->keyDefault('role' . $group['id'], $already_group_role)->addCustomJs($myJs);
                }
            }
            /*身份设置 end*/

            $builder->group(L('_BASIC_SETTINGS_'), implode(',', $field_key));
            $builder->group(L('_SETTINGS_SCORE_'), implode(',', $score_key));
            $builder->group(L('_SETTINGS_ROLE_'), implode(',', $role_key));
            $builder->buttonSubmit('', L('_SAVE_'));
            $builder->buttonBack();
            $builder->display();
        }

    }

    /**
     * 重新设置某一用户拥有身份
     * @param int $uid
     * @param array $haveRole
     * @return bool
     * @author 郑钟良<zzl@ourstu.com>
     */
    private function _resetUserRole($uid = 0, $haveRole = array())
    {
        $userRoleModel = D('UserRole');
        $memberModel = D('Common/Member');
        $map['uid'] = $uid;
        foreach ($haveRole as $val) {
            $map['role_id'] = $val;
            $userRole = $userRoleModel->where($map)->find();
            if ($userRole) {
                if (!$userRole['init']) {
                    $memberModel->initUserRoleInfo($val, $uid);
                }
                if ($userRole['status'] != 1) {
                    $userRoleModel->where($map)->setField('status', 1);
                }
            } else {
                $data = $map;
                $data['status'] = 1;
                $data['step'] = 'start';
                $data['init'] = 1;
                $res = $userRoleModel->add($data);
                if ($res) {
                    $memberModel->initUserRoleInfo($val, $uid);
                }
            }
        }
        $map_remove['uid'] = $uid;
        $map_remove['role_id'] = array('not in', $haveRole);
        $userRoleModel->where($map_remove)->setField('status', -1);
        $user_info = $memberModel->where(array('uid' => $uid))->find();
        if (!in_array($user_info['show_role'], $haveRole)) {
            $user_data['show_role'] = $haveRole[count($haveRole) - 1];
        }
        if (!in_array($user_info['last_login_role'], $haveRole)) {
            $user_data['last_login_role'] = $haveRole[count($haveRole) - 1];
        }
        $memberModel->where(array('uid' => $uid))->save($user_data);
        return true;
    }

    /**扩展用户信息分组列表
     * @author 郑钟良<zzl@ourstu.com>
     */
    public function profile($page = 1, $r = 20)
    {
        $map['status'] = array('egt', 0);
        $profileList = D('field_group')->where($map)->order("sort asc")->page($page, $r)->select();
        $totalCount = D('field_group')->where($map)->count();
        $builder = new AdminListBuilder();
        $builder->title(L('_GROUP_EXPAND_INFO_LIST_'));
        $builder->meta_title = L('_GROUP_EXPAND_INFO_');
        $builder->buttonNew(U('editProfile', array('id' => '0')))->buttonDelete(U('changeProfileStatus', array('status' => '-1')))->setStatusUrl(U('changeProfileStatus'))->buttonSort(U('sortProfile'));
        $builder->keyId()->keyText('profile_name', L('_GROUP_NAME_'))->keyText('sort', L('_SORT_'))->keyTime("createTime", L('_CREATE_TIME_'))->keyBool('visiable', L('_PUBLIC_IF_'));
        $builder->keyStatus()->keyDoAction('User/field?id=###', L('_FIELD_MANAGER_'))->keyDoAction('User/editProfile?id=###', L('_EDIT_'));
        $builder->data($profileList);
        $builder->pagination($totalCount, $r);
        $builder->display();
    }

    /**扩展分组排序
     * @author 郑钟良<zzl@ourstu.com>
     */
    public function sortProfile($ids = null)
    {
        if (IS_POST) {
            $builder = new AdminSortBuilder();
            $builder->doSort('Field_group', $ids);
        } else {
            $map['status'] = array('egt', 0);
            $list = D('field_group')->where($map)->order("sort asc")->select();
            foreach ($list as $key => $val) {
                $list[$key]['title'] = $val['profile_name'];
            }
            $builder = new AdminSortBuilder();
            $builder->meta_title = L('_GROUPS_SORT_');
            $builder->data($list);
            $builder->buttonSubmit(U('sortProfile'))->buttonBack();
            $builder->display();
        }
    }

    /**扩展字段列表
     * @param $id
     * @author 郑钟良<zzl@ourstu.com>
     */
    public function field($id, $page = 1, $r = 20)
    {
        $profile = D('field_group')->where('id=' . $id)->find();
        $map['status'] = array('egt', 0);
        $map['profile_group_id'] = $id;
        $field_list = D('field_setting')->where($map)->order("sort asc")->page($page, $r)->select();
        $totalCount = D('field_setting')->where($map)->count();
        $type_default = array(
            'input' => L('_ONE-WAY_TEXT_BOX_'),
            'radio' => L('_RADIO_BUTTON_'),
            'checkbox' => L('_CHECKBOX_'),
            'select' => L('_DROP-DOWN_BOX_'),
            'time' => L('_DATE_'),
            'textarea' => L('_MULTI_LINE_TEXT_BOX_')
        );
        $child_type = array(
            'string' => L('_STRING_'),
            'phone' => L('_PHONE_NUMBER_'),
            'email' => L('_MAILBOX_'),
            'number' => L('_NUMBER_'),
            'join' => L('_RELATED_FIELD_')
        );
        foreach ($field_list as &$val) {
            $val['form_type'] = $type_default[$val['form_type']];
            $val['child_form_type'] = $child_type[$val['child_form_type']];
        }
        $builder = new AdminListBuilder();
        $builder->title('【' . $profile['profile_name'] . '】 字段管理');
        $builder->meta_title = $profile['profile_name'] . L('_FIELD_MANAGEMENT_');
        $builder->buttonNew(U('editFieldSetting', array('id' => '0', 'profile_group_id' => $id)))->buttonDelete(U('setFieldSettingStatus', array('status' => '-1')))->setStatusUrl(U('setFieldSettingStatus'))->buttonSort(U('sortField', array('id' => $id)))->button(L('_RETURN_'), array('href' => U('profile')));
        $builder->keyId()->keyText('field_name', L('_FIELD_NAME_'))->keyBool('visiable', L('_OPEN_YE_OR_NO_'))->keyBool('required', L('_WHETHER_THE_REQUIRED_'))->keyText('sort', L('_SORT_'))->keyText('form_type', L('_FORM_TYPE_'))->keyText('child_form_type', L('_TWO_FORM_TYPE_'))->keyText('form_default_value', L('_DEFAULT_'))->keyText('validation', L('_FORM_VERIFICATION_MODE_'))->keyText('input_tips', L('_USER_INPUT_PROMPT_'));
        $builder->keyTime("createTime", L('_CREATE_TIME_'))->keyStatus()->keyDoAction('User/editFieldSetting?profile_group_id=' . $id . '&id=###', L('_EDIT_'));
        $builder->data($field_list);
        $builder->pagination($totalCount, $r);
        $builder->display();
    }

    /**分组排序
     * @param $id
     * @author 郑钟良<zzl@ourstu.com>
     */
    public function sortField($id = '', $ids = null)
    {
        if (IS_POST) {
            $builder = new AdminSortBuilder();
            $builder->doSort('FieldSetting', $ids);
        } else {
            $profile = D('field_group')->where('id=' . $id)->find();
            $map['status'] = array('egt', 0);
            $map['profile_group_id'] = $id;
            $list = D('field_setting')->where($map)->order("sort asc")->select();
            foreach ($list as $key => $val) {
                $list[$key]['title'] = $val['field_name'];
            }
            $builder = new AdminSortBuilder();
            $builder->meta_title = $profile['profile_name'] . L('_FIELD_SORT_');
            $builder->data($list);
            $builder->buttonSubmit(U('sortField'))->buttonBack();
            $builder->display();
        }
    }

    /**添加、编辑字段信息
     * @param $id
     * @param $profile_group_id
     * @param $field_name
     * @param $child_form_type
     * @param $visiable
     * @param $required
     * @param $form_type
     * @param $form_default_value
     * @param $validation
     * @param $input_tips
     * @author 郑钟良<zzl@ourstu.com>
     */
    public function editFieldSetting($id = 0, $profile_group_id = 0, $field_name = '', $child_form_type = 0, $visiable = 0, $required = 0, $form_type = 0, $form_default_value = '', $validation = 0, $input_tips = '')
    {
        if (IS_POST) {
            $data['field_name'] = $field_name;
            if ($data['field_name'] == '') {
                $this->error(L('_FIELD_NAME_CANNOT_BE_EMPTY_'));
            }
            $data['profile_group_id'] = $profile_group_id;
            $data['visiable'] = $visiable;
            $data['required'] = $required;
            $data['form_type'] = $form_type;
            $data['form_default_value'] = $form_default_value;
            //当表单类型为以下三种是默认值不能为空判断@MingYang
            $form_types = array('radio', 'checkbox', 'select');
            if (in_array($data['form_type'], $form_types)) {
                if ($data['form_default_value'] == '') {
                    $this->error($data['form_type'] . L('_THE_DEFAULT_VALUE_OF_THE_FORM_TYPE_CAN_NOT_BE_EMPTY_'));
                }
            }
            $data['input_tips'] = $input_tips;
            //增加当二级字段类型为join时也提交$child_form_type @MingYang
            if ($form_type == 'input') {
                $data['child_form_type'] = $child_form_type;
            } else {
                $data['child_form_type'] = '';
            }
            $data['validation'] = $validation;
            if ($id != '') {
                $res = D('field_setting')->where('id=' . $id)->save($data);
            } else {
                $map['field_name'] = $field_name;
                $map['status'] = array('egt', 0);
                $map['profile_group_id'] = $profile_group_id;
                if (D('field_setting')->where($map)->count() > 0) {
                    $this->error(L('_THIS_GROUP_ALREADY_HAS_THE_SAME_NAME_FIELD_PLEASE_USE_ANOTHER_NAME_'));
                }
                $data['status'] = 1;
                $data['createTime'] = time();
                $data['sort'] = 0;
                $res = D('field_setting')->add($data);
            }
            $role_ids = I('post.role_ids', array());
            $this->_setFieldRole($role_ids, $res, $id);
            $this->success($id == '' ? L('_ADD_FIELD_SUCCESS_') : L('_EDIT_FIELD_SUCCESS_'), U('field', array('id' => $profile_group_id)));
        } else {
            $roleOptions = D('Role')->selectByMap(array('status' => array('gt', -1)), 'id asc', 'id,title');

            $builder = new AdminConfigBuilder();
            if ($id != 0) {
                $field_setting = D('field_setting')->where('id=' . $id)->find();

                //所属身份
                $roleConfigModel = D('RoleConfig');
                $map = getRoleConfigMap('expend_field', 0);
                unset($map['role_id']);
                $map['value'] = array('like', array('%,' . $id . ',%', $id . ',%', '%,' . $id, $id), 'or');
                $already_role_id = $roleConfigModel->where($map)->field('role_id')->select();
                $already_role_id = array_column($already_role_id, 'role_id');
                $field_setting['role_ids'] = $already_role_id;
                //所属身份 end

                $builder->title(L('_MODIFY_FIELD_INFORMATION_'));
                $builder->meta_title = L('_MODIFY_FIELD_INFORMATION_');
            } else {
                $builder->title(L('_ADD_FIELD_'));
                $builder->meta_title = L('_NEW_FIELD_');
                $field_setting['profile_group_id'] = $profile_group_id;
                $field_setting['visiable'] = 1;
                $field_setting['required'] = 1;
            }
            $type_default = array(
                'input' => L('_ONE-WAY_TEXT_BOX_'),
                'radio' => L('_RADIO_BUTTON_'),
                'checkbox' => L('_CHECKBOX_'),
                'select' => L('_DROP-DOWN_BOX_'),
                'time' => L('_DATE_'),
                'textarea' => L('_MULTI_LINE_TEXT_BOX_')
            );
            $child_type = array(
                'string' => L('_STRING_'),
                'phone' => L('_PHONE_NUMBER_'),
                'email' => L('_MAILBOX_'),
                //增加可选择关联字段类型 @MingYang
                'join' => L('_RELATED_FIELD_'),
                'number' => L('_NUMBER_')
            );
            $builder->keyReadOnly("id", L('_LOGO_'))->keyReadOnly('profile_group_id', L('_GROUP_ID_'))->keyText('field_name', L('_FIELD_NAME_'))->keyChosen('role_ids', L('_POSSESSION_OF_THE_FIELD_'), L('_DETAIL_COME_TO_'), $roleOptions)->keySelect('form_type', L('_FORM_TYPE_'), '', $type_default)->keySelect('child_form_type', L('_TWO_FORM_TYPE_'), '', $child_type)->keyTextArea('form_default_value', "多个值用'|'分割开,格式【字符串：男|女，数组：1:男|2:女，关联数据表：字段名|表名】开")
                ->keyText('validation', L('_FORM_VALIDATION_RULES_'), '例：min=5&max=10')->keyText('input_tips', L('_USER_INPUT_PROMPT_'), L('_PROMPTS_THE_USER_TO_ENTER_THE_FIELD_INFORMATION_'))->keyBool('visiable', L('_OPEN_YE_OR_NO_'))->keyBool('required', L('_WHETHER_THE_REQUIRED_'));
            $builder->data($field_setting);
            $builder->buttonSubmit(U('editFieldSetting'), $id == 0 ? L('_ADD_') : L('_MODIFY_'))->buttonBack();
            $builder->display();
        }

    }

    /**设置字段状态：删除=-1，禁用=0，启用=1
     * @param $ids
     * @param $status
     * @author 郑钟良<zzl@ourstu.com>
     */
    public function setFieldSettingStatus($ids, $status)
    {
        $builder = new AdminListBuilder();
        $builder->doSetStatus('field_setting', $ids, $status);
    }

    /**设置分组状态：删除=-1，禁用=0，启用=1
     * @param $status
     * @author 郑钟良<zzl@ourstu.com>
     */
    public function changeProfileStatus($status)
    {
        $id = array_unique((array)I('ids', 0));
        if ($id[0] == 0) {
            $this->error(L('_PLEASE_CHOOSE_TO_OPERATE_THE_DATA_'));
        }
        $id = is_array($id) ? $id : explode(',', $id);
        D('field_group')->where(array('id' => array('in', $id)))->setField('status', $status);
        if ($status == -1) {
            $this->success(L('_DELETE_SUCCESS_'));
        } else if ($status == 0) {
            $this->success(L('_DISABLE_SUCCESS_'));
        } else {
            $this->success(L('_ENABLE_SUCCESS_'));
        }

    }

    /**添加、编辑分组信息
     * @param $id
     * * @param $profile_name
     * @author 郑钟良<zzl@ourstu.com>
     */
    public function editProfile($id = 0, $profile_name = '', $visiable = 1)
    {
        if (IS_POST) {
            $data['profile_name'] = $profile_name;
            $data['visiable'] = $visiable;
            if ($data['profile_name'] == '') {
                $this->error(L('_GROUP_NAME_CANNOT_BE_EMPTY_'));
            }
            if ($id != '') {
                $res = D('field_group')->where('id=' . $id)->save($data);
            } else {
                $map['profile_name'] = $profile_name;
                $map['status'] = array('egt', 0);
                if (D('field_group')->where($map)->count() > 0) {
                    $this->error(L('_ALREADY_HAS_THE_SAME_NAME_GROUP_PLEASE_USE_THE_OTHER_GROUP_NAME_'));
                }
                $data['status'] = 1;
                $data['createTime'] = time();
                $res = D('field_group')->add($data);
            }
            if ($res) {
                $this->success($id == '' ? L('_ADD_GROUP_SUCCESS_') : L('_EDIT_GROUP_SUCCESS_'), U('profile'));
            } else {
                $this->error($id == '' ? L('_ADD_GROUP_FAILURE_') : L('_EDIT_GROUP_FAILED_'));
            }
        } else {
            $builder = new AdminConfigBuilder();
            if ($id != 0) {
                $profile = D('field_group')->where('id=' . $id)->find();
                $builder->title(L('_MODIFIED_GROUP_INFORMATION_'));
                $builder->meta_title = L('_MODIFIED_GROUP_INFORMATION_');
            } else {
                $builder->title(L('_ADD_EXTENDED_INFORMATION_PACKET_'));
                $builder->meta_title = L('_NEW_GROUP_');
            }
            $builder->keyReadOnly("id", L('_LOGO_'))->keyText('profile_name', L('_GROUP_NAME_'))->keyBool('visiable', L('_OPEN_YE_OR_NO_'));
            $builder->data($profile);
            $builder->buttonSubmit(U('editProfile'), $id == 0 ? L('_ADD_') : L('_MODIFY_'))->buttonBack();
            $builder->display();
        }

    }

    /**
     * 修改昵称初始化
     * @author huajie <banhuajie@163.com>
     */
    public function updateNickname()
    {
        $nickname = M('Member')->getFieldByUid(UID, 'nickname');
        $this->assign('nickname', $nickname);
        $this->meta_title = L('_MODIFY_NICKNAME_');
        $this->display();
    }

    /**
     * 修改昵称提交
     * @author huajie <banhuajie@163.com>
     */
    public function submitNickname()
    {
        //获取参数
        $nickname = I('post.nickname');
        $password = I('post.password');
        empty($nickname) && $this->error(L('_PLEASE_ENTER_A_NICKNAME_'));
        empty($password) && $this->error(L('_PLEASE_ENTER_THE_PASSWORD_'));

        //密码验证
        $User = new UserApi();
        $uid = $User->login(UID, $password, 4);
        ($uid == -2) && $this->error(L('_INCORRECT_PASSWORD_'));

        $Member = D('Member');
        $data = $Member->create(array('nickname' => $nickname));
        if (!$data) {
            $this->error($Member->getError());
        }

        $res = $Member->where(array('uid' => $uid))->save($data);

        if ($res) {
            $user = session('user_auth');
            $user['username'] = $data['nickname'];
            session('user_auth', $user);
            session('user_auth_sign', data_auth_sign($user));
            $this->success(L('_MODIFY_NICKNAME_SUCCESS_'));
        } else {
            $this->error(L('_MODIFY_NICKNAME_FAILURE_'));
        }
    }

    /**
     * 修改密码初始化
     * @author huajie <banhuajie@163.com>
     */
    public function updatePassword()
    {
        $this->meta_title = L('_CHANGE_PASSWORD_');
        $this->display();
    }

    /**
     * 修改密码提交
     * @author huajie <banhuajie@163.com>
     */
    public function submitPassword()
    {
        //获取参数
        $password = I('post.old');
        empty($password) && $this->error(L('_PLEASE_ENTER_THE_ORIGINAL_PASSWORD_'));
        $data['password'] = I('post.password');
        empty($data['password']) && $this->error(L('_PLEASE_ENTER_A_NEW_PASSWORD_'));
        $repassword = I('post.repassword');
        empty($repassword) && $this->error(L('_PLEASE_ENTER_THE_CONFIRMATION_PASSWORD_'));

        if ($data['password'] !== $repassword) {
            $this->error(L('_YOUR_NEW_PASSWORD_IS_NOT_CONSISTENT_WITH_THE_CONFIRMATION_PASSWORD_'));
        }

        $Api = new UserApi();
        $res = $Api->updateInfo(UID, $password, $data);
        if ($res['status']) {
            $this->success(L('_CHANGE_PASSWORD_SUCCESS_'));
        } else {
            $this->error(UCenterMember()->getErrorMessage($res['info']));
        }
    }

    /**
     * 用户行为列表
     * @author huajie <banhuajie@163.com>
     */
    public function action()
    {
        // $aModule = I('post.module', '-1', 'text');
        $aModule = $this->parseSearchKey('module');

        is_null($aModule) && $aModule = -1;
        if ($aModule != -1) {
            $map['module'] = $aModule;
        }
        unset($_REQUEST['module']);
        $this->assign('current_module', $aModule);
        $map['status'] = array('gt', -1);
        //获取列表数据
        $Action = M('Action')->where(array('status' => array('gt', -1)));

        $list = $this->lists($Action, $map);
        lists_plus($list);
        int_to_string($list);
        // 记录当前列表页的cookie
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->assign('_list', $list);
        $module = D('Common/Module')->getAll();
        foreach ($module as $key => $v) {
            if ($v['is_setup'] == false) {
                unset($module[$key]);
            }
        }
        $module = array_merge(array(array('name' => '', 'alias' => L('_SYSTEM_'))), $module);
        $this->assign('module', $module);

        $this->meta_title = L('_USER_BEHAVIOR_');
        $this->display();
    }

    protected function parseSearchKey($key = null)
    {
        $action = MODULE_NAME . '_' . CONTROLLER_NAME . '_' . ACTION_NAME;
        $post = I('post.');
        if (empty($post)) {
            $keywords = cookie($action);
        } else {
            $keywords = $post;
            cookie($action, $post);
            $_GET['page'] = 1;
        }

        if (!$_GET['page']) {
            cookie($action, null);
            $keywords = null;
        }
        return $key ? $keywords[$key] : $keywords;
    }

    /**
     * 新增行为
     * @author huajie <banhuajie@163.com>
     */
    public function addAction()
    {
        $this->meta_title = L('_NEW_BEHAVIOR_');


        $module = D('Module')->getAll();
        $this->assign('module', $module);
        $this->assign('data', null);
        $this->display('editaction');
    }

    /**
     * 编辑行为
     * @author huajie <banhuajie@163.com>
     */
    public function editAction()
    {
        $id = I('get.id');
        empty($id) && $this->error(L('_PARAMETERS_CANT_BE_EMPTY_'));
        $data = M('Action')->field(true)->find($id);

        $module = D('Module')->getAll();
        $this->assign('module', $module);
        $this->assign('data', $data);
        $this->meta_title = L('_EDITING_BEHAVIOR_');
        $this->display();
    }

    /**
     * 更新行为
     * @author huajie <banhuajie@163.com>
     */
    public function saveAction()
    {
        $res = D('Action')->update();
        if (!$res) {
            $this->error(D('Action')->getError());
        } else {
            $this->success($res['id'] ? L('_UPDATE_SUCCESS_') : L('_NEW_SUCCESS_'), Cookie('__forward__'));
        }
    }

    /**
     * 会员状态修改
     * @author 朱亚杰 <zhuyajie@topthink.net>
     */
    public function changeStatus($method = null)
    {
        $id = array_unique((array)I('id', 0));
        if (count(array_intersect(explode(',', C('USER_ADMINISTRATOR')), $id)) > 0) {
            $this->error(L('_DO_NOT_ALLOW_THE_SUPER_ADMINISTRATOR_TO_PERFORM_THE_OPERATION_'));
        }
        $id = is_array($id) ? implode(',', $id) : $id;
        if (empty($id)) {
            $this->error(L('_PLEASE_CHOOSE_TO_OPERATE_THE_DATA_'));
        }
        $map['uid'] = array('in', $id);
        switch (strtolower($method)) {
            case 'forbiduser':
                $this->forbid('Member', $map);
                break;
            case 'resumeuser':
                $this->resume('Member', $map);
                break;
            case 'deleteuser':
                $this->delete('Member', $map);
                break;
            default:
                $this->error(L('_ILLEGAL_'));

        }
    }


    /**
     * 获取用户注册错误信息
     * @param  integer $code 错误编码
     * @return string        错误信息
     */
    private function showRegError($code = 0)
    {
        switch ($code) {
            case -1:
                $error = L('_USER_NAME_MUST_BE_IN_LENGTH_') . modC('USERNAME_MIN_LENGTH', 2, 'USERCONFIG') . '-' . modC('USERNAME_MAX_LENGTH', 32, 'USERCONFIG') . L('_BETWEEN_CHARACTERS_');
                break;
            case -2:
                $error = L('_USER_NAME_IS_FORBIDDEN_TO_REGISTER_');
                break;
            case -3:
                $error = L('_USER_NAME_IS_OCCUPIED_');
                break;
            case -4:
                $error = L('_PASSWORD_LENGTH_MUST_BE_BETWEEN_6-30_CHARACTERS_');
                break;
            case -5:
                $error = L('_MAILBOX_FORMAT_IS_NOT_CORRECT_');
                break;
            case -6:
                $error = L('_MAILBOX_LENGTH_MUST_BE_BETWEEN_1-32_CHARACTERS_');
                break;
            case -7:
                $error = L('_MAILBOX_IS_PROHIBITED_TO_REGISTER_');
                break;
            case -8:
                $error = L('_MAILBOX_IS_OCCUPIED_');
                break;
            case -9:
                $error = L('_MOBILE_PHONE_FORMAT_IS_NOT_CORRECT_');
                break;
            case -10:
                $error = L('_MOBILE_PHONES_ARE_PROHIBITED_FROM_REGISTERING_');
                break;
            case -11:
                $error = L('_PHONE_NUMBER_IS_OCCUPIED_');
                break;
            case -12:
                $error = L('_USER_NAME_MY_RULE_').L('_EXCLAMATION_');
                break;
            default:
                $error = L('_UNKNOWN_ERROR_');
        }
        return $error;
    }


    public function scoreList()
    {
        //读取数据
        $map = array('status' => array('GT', -1));
        $model = D('Ucenter/Score');
        $list = $model->getTypeList($map);

        //显示页面
        $builder = new AdminListBuilder();
        $builder
            ->title(L('_INTEGRAL_TYPE_'))
            ->suggest(L('_CANNOT_DELETE_ID_4_'))
            ->buttonNew(U('editScoreType'))
            ->setStatusUrl(U('setTypeStatus'))->buttonEnable()->buttonDisable()->button(L('_DELETE_'), array('class' => 'btn ajax-post tox-confirm', 'data-confirm' => '您确实要删除积分分类吗？（删除后对应的积分将会清空，不可恢复，请谨慎删除！）', 'url' => U('delType'), 'target-form' => 'ids'))
            ->button(L('_RECHARGE_'), array('href' => U('recharge')))
            ->keyId()->keyText('title', L('_NAME_'))
            ->keyText('unit', L('_UNIT_'))->keyStatus()->keyDoActionEdit('editScoreType?id=###')
            ->data($list)
            ->display();
    }

    public function recharge()
    {
        $scoreTypes = D('Ucenter/Score')->getTypeList(array('status' => 1));
        if (IS_POST) {
            $aUids = I('post.uid');
            foreach ($scoreTypes as $v) {
                $aAction = I('post.action_score' . $v['id'], '', 'op_t');
                $aValue = I('post.value_score' . $v['id'], 0, 'intval');
                D('Ucenter/Score')->setUserScore($aUids, $aValue, $v['id'], $aAction, '', 0, L('_BACKGROUND_ADMINISTRATOR_RECHARGE_PAGE_RECHARGE_'));
                D('Ucenter/Score')->cleanUserCache($aUids, $aValue);

            }
            $this->success(L('_SET_UP_'), 'refresh');
        } else {

            $this->assign('scoreTypes', $scoreTypes);
            $this->display();
        }
    }

    public function getNickname()
    {
        $uid = I('get.uid', 0, 'intval');
        if ($uid) {
            $user = query_user(null, $uid);
            $this->ajaxReturn($user);
        } else {
            $this->ajaxReturn(null);
        }

    }

    public function setTypeStatus($ids, $status)
    {
        $builder = new AdminListBuilder();
        $builder->doSetStatus('ucenter_score_type', $ids, $status);

    }

    public function delType($ids)
    {
        $model = D('Ucenter/Score');
        $res = $model->delType($ids);
        if ($res) {
            $this->success(L('_DELETE_SUCCESS_'));
        } else {
            $this->error(L('_DELETE_FAILED_'));
        }
    }

    public function editScoreType()
    {
        $aId = I('id', 0, 'intval');
        $model = D('Ucenter/Score');
        if (IS_POST) {
            $data['title'] = I('post.title', '', 'op_t');
            $data['status'] = I('post.status', 1, 'intval');
            $data['unit'] = I('post.unit', '', 'op_t');

            if ($aId != 0) {
                $data['id'] = $aId;
                $res = $model->editType($data);
            } else {
                $res = $model->addType($data);
            }
            if ($res) {
                $this->success(($aId == 0 ? L('_ADD_') : L('_EDIT_')) . L('_SUCCESS_'));
            } else {
                $this->error(($aId == 0 ? L('_ADD_') : L('_EDIT_')) . L('_FAILURE_'));
            }
        } else {
            $builder = new AdminConfigBuilder();
            if ($aId != 0) {
                $type = $model->getType(array('id' => $aId));
            } else {
                $type = array('status' => 1, 'sort' => 0);
            }
            $builder->title(($aId == 0 ? L('_NEW_') : L('_EDIT_')) . L('_INTEGRAL_CLASSIFICATION_'))->keyId()->keyText('title', L('_NAME_'))
                ->keyText('unit', L('_UNIT_'))
                ->keySelect('status', L('_STATUS_'), null, array(-1 => L('_DELETE_'), 0 => L('_DISABLE_'), 1 => L('_ENABLE_')))
                ->data($type)
                ->buttonSubmit(U('editScoreType'))->buttonBack()->display();
        }
    }

    /**
     * 重新设置拥有字段的身份
     * @param $role_ids 身份ids
     * @param $add_id 新增字段时字段id
     * @param $edit_id 编辑字段时字段id
     * @return bool
     * @author 郑钟良<zzl@ourstu.com>
     */
    private function _setFieldRole($role_ids, $add_id, $edit_id)
    {
        $type = 'expend_field';
        $roleConfigModel = D('RoleConfig');
        $map = getRoleConfigMap($type, 0);
        if ($edit_id) {//编辑字段
            unset($map['role_id']);
            $map['value'] = array('like', array('%,' . $edit_id . ',%', $edit_id . ',%', '%,' . $edit_id, $edit_id), 'or');
            $already_role_id = $roleConfigModel->where($map)->select();
            $already_role_id = array_column($already_role_id, 'role_id');

            unset($map['value']);
            if (count($role_ids) && count($already_role_id)) {
                $need_add_role_ids = array_diff($role_ids, $already_role_id);
                $need_del_role_ids = array_diff($already_role_id, $role_ids);
            } else if (count($role_ids)) {
                $need_add_role_ids = $role_ids;
            } else {
                $need_del_role_ids = $already_role_id;
            }

            foreach ($need_add_role_ids as $val) {
                $map['role_id'] = $val;
                $oldConfig = $roleConfigModel->where($map)->find();
                if (count($oldConfig)) {
                    $oldConfig['value'] = implode(',', array_merge(explode(',', $oldConfig['value']), array($edit_id)));
                    $roleConfigModel->saveData($map, $oldConfig);
                } else {
                    $data = $map;
                    $data['value'] = $edit_id;
                    $roleConfigModel->addData($data);
                }
            }

            foreach ($need_del_role_ids as $val) {
                $map['role_id'] = $val;
                $oldConfig = $roleConfigModel->where($map)->find();
                $oldConfig['value'] = array_diff(explode(',', $oldConfig['value']), array($edit_id));
                if (count($oldConfig['value'])) {
                    $oldConfig['value'] = implode(',', $oldConfig['value']);
                    $roleConfigModel->saveData($map, $oldConfig);
                } else {
                    $roleConfigModel->deleteData($map);
                }
            }

        } else {//新增字段
            foreach ($role_ids as $val) {
                $map['role_id'] = $val;
                $oldConfig = $roleConfigModel->where($map)->find();
                if (count($oldConfig)) {
                    $oldConfig['value'] = implode(',', array_unique(array_merge(explode(',', $oldConfig['value']), array($add_id))));
                    $roleConfigModel->saveData($map, $oldConfig);
                } else {
                    $data = $map;
                    $data['value'] = $add_id;
                    $roleConfigModel->addData($data);
                }
            }
        }
        return true;
    }
    
    /*
     * 订单管理--兑换订单
     * type:2 
     * dh_status  -1-订单关闭；1:兑换成功；2：配送中；3：已完成
     */
    public function order($page = 1, $r = 10,$uid = ''){
		$where['type'] = 2;
        if(!empty($uid)){
            $u_uid = get_table_field($uid,'username','id','UcenterMember');
            $where['uid'] = $u_uid;
        }
        $list = M('OrderList')->where($where)->order("createtime desc")->page($page, $r)->select();
        $totalCount = M('OrderList')->where($where)->count();
        foreach($list as $k=>$v){
            $list[$k]['good_id'] = get_table_field($v['good_id'],'id','name','ScoreShop');
            $list[$k]['uid'] = get_table_field($v['uid'],'id','username','UcenterMember');
            $list[$k]['account_id'] = get_table_field($v['account_id'],'id','name','UserAccount');
			$pic = get_table_field($v['good_id'],'id','pic','ScoreShop');
			$list[$k]['pic_show'] = get_table_field($pic,'id','path','Picture');
			$list[$k]['sendAddress'] = $v['province'].$v['city'].$v['district'].$v['addDetail'];
            //if($v['type'] == 2){
              //  $list[$k]['type'] = '兑换订单';
           // }
            if($v['dh_status'] == -1){
                $list[$k]['dh_status'] = '订单关闭';
            }
             if($v['dh_status'] == 0){
                $list[$k]['dh_status'] = '未付款';
            }
            if($v['dh_status'] == 1){
                $list[$k]['dh_status'] = '兑换成功';
				$sendTitle = '标记为发货';
            }
            if($v['dh_status'] == 2){
                $list[$k]['dh_status'] = '已发货';
				$sendTitle = '标记为完成';
            }
            if($v['dh_status'] == 3){
                $list[$k]['dh_status'] = '已完成';
            }

            switch ($v['user_log']) {
                case 1:
                    $list[$k]['user_log'] = '用户确认收货';
                    break;
                case 2:
                    $list[$k]['user_log'] = '系统确认收货';
                    break;
                default:
                   $list[$k]['user_log'] = '--';
                   break;
            }
        }
        $builder = new AdminListBuilder();
        $builder->title('兑换订单');
        $builder->meta_title = L('兑换订单');
        $builder->search('下单用户名', 'uid')->buttonDelete(U('User/delOrder'));
        $builder->keyId()->keyText('good_id','商品名称')->keyText('price','单价')->keyText('num','数量')->keyText('uid','下单用户')->keyImage('pic_show','商品展示图')->keyText('sendAddress','送货地址')->keyText('name','收货人')
        ->keyText('mobile','收货人联系方式')->keyTime('createtime','下单时间')->keyTime('updata_time','发货时间')
        // ->keyTime('finsh_time','确认收货时间')
        ->keyText('dh_sendOrder','订单号')->keyText('dh_sendType','快递公司')
        ->keyText('dh_status','状态')
        ->keyTime('user_create_time','收货时间')
        ->keyText('user_log','收货日志');
        $builder->keyDoAction('User/setSend/?ids=###', '发货')->keyDoAction('User/finshSend/?ids=###', '确认收货')->keyDoAction('User/delOrder?ids=###', L('删除'));
        $builder->data($list);
        $builder->pagination($totalCount, $r);
        $builder->display();
    }
    /*
     * 检查是否符合发货资格
     * $ids 订单ID
     */
    public function setSend($ids = 0){
        $ids = $_GET['ids'];
        if (empty($ids) ) {
            $this->error(L('参数丢失'));
        }

        ##检查该订单是否已发货
	$map = array('id' => $ids );
        $check = M('OrderList')->where($map)->find();
        if($check['dh_status'] == 1 ){
            $this->setSendNum($ids);
        }
        if($check['dh_status'] == -1){
            $this->error('该订单已关闭');
        }
        if($check['dh_status'] == 0){
             $this->error('该订单未付款');
        }
        if($check['dh_status'] == 2){
             $this->error('该订单已发货');
        }
        if($check['dh_status'] == 3){
             $this->error('该订单已完成');
        }
    }
    public function setSendNum($ids = 0){
        if(IS_POST){
            if(empty($_POST['dh_sendOrder'])){
                $this->error('订单号不能为空');die;
            }
            if(empty($_POST['dh_sendOrder'])){
                $this->error('物流公司不能为空');die;
            }
            $da['dh_sendOrder'] = $_POST['dh_sendOrder'];
            $da['dh_sendType'] = $_POST['dh_sendType'];
            $da['dh_status'] = 2;
            $da['updata_time'] = time();
            $id = $_POST['id'];
            $send = M('OrderList')->where(array('id'=>$id))->save($da);
            if($send){
                $this->success($ids == 0 ? L('新增成功') : L('编辑成功'),U('Admin/User/order'));
            }else{
                $this->error('修改失败，请重试');
            }
        }
        $data = M('OrderList')->where(array('id'=>$ids))->find();
        $builder = new AdminConfigBuilder();
        $builder->title('发货信息');
        $builder->meta_title = L('发货信息');
        $builder->keyId()->keyText('dh_sendOrder', '订单号')->keyText('dh_sendType', '物流公司');
        $builder->data($data);
        $builder->buttonSubmit(U('setSendNum'), $ids == 0 ? L('新增') : L('编辑'))->buttonBack();
        $builder->display();
    }
	/*
     * 确认收货
     * $ids 订单ID
     */
    public function finshSend($ids = 0){
		$ids = array_unique((array)I('ids',0));

        if ( empty($ids) ) {
            $this->error(L('参数丢失'));
        }


        ##检查该订单是否已发货
		$map = array('id' => array('in', $ids) );
        $check = M('OrderList')->where($map)->find();
        if($check['dh_status'] == 1 ){
			$da['dh_status'] = 3;
			$da['updata_time'] = time();
            $da['user_log'] = 2;
            $da['user_create_time'] = NOW_TIME;
            $set = M('OrderList')->where($map)->save($da);
            if($set){
                $this->success('该订单已设置为发货');
            }else{
                $this->error('请重试,该订单未付款');
            }
        }
		if($check['dh_status'] == 2){
			$dad['dh_status'] = 3;
			$dad['finsh_time'] = time();
            $dad['user_create_time'] = NOW_TIME;
            $set = M('OrderList')->where($map)->save($dad);
            if($set){
                $this->success('该订单已设置为已完成');
            }else{
                $this->error('请重试,该订单已发货');
            }
        }
        if($check['dh_status'] == -1){
            $this->error('该订单已关闭');
        }
        if($check['dh_status'] == 0){
             $this->error('该订单未付款');
        }
        if($check['dh_status'] == 2){
             $this->error('该订单已发货');
        }
        if($check['dh_status'] == 3){
             $this->error('该订单已完成');
        }
    }


    /*
     * 订单管理--续费订单
     * type : 4
     */
    public function renewOrder($page = 1, $r = 10,$uid = ''){
		$where['type'] = 4;
        if(!empty($uid)){
            $u_uid = get_table_field($uid,'username','id','UcenterMember');
            $where['uid'] = $u_uid;
        }
        $list = M('OrderList')->where($where)->order("createtime desc")->page($page, $r)->select();
        $totalCount = M('OrderList')->where($where)->count();
        foreach($list as $k=>$v){
            $list[$k]['e_id'] = get_table_field($v['e_id'],'id','e_id','Equipment');
            $list[$k]['uid'] = get_table_field($v['uid'],'id','username','UcenterMember');
			$list[$k]['nickname'] = get_table_field($v['uid'],'uid','nickname','Member');
            $list[$k]['address'] = get_table_field($v['e_id'],'id','address','Equipment');
            //$list[$k]['account_id'] = get_table_field($v['account_id'],'id','name','UserAccount');
			$list[$k]['servicetime'] = get_table_field($v['e_id'],'id','servicetime','Equipment');
            //if($v['type'] == 4){
               // $list[$k]['type'] = '续费订单';
            //}
            if($v['xf_status'] == -1){
                $list[$k]['xf_status'] = '订单关闭';
            }
            if($v['xf_status'] == 0){
                $list[$k]['xf_status'] = '未付款';
            }
            if($v['xf_status'] == 1){
                $list[$k]['xf_status'] = '付款成功';
            }
			 if($v['xf_status'] == 2){
                $list[$k]['xf_status'] = '现金付款';
            }
        }
        $builder = new AdminListBuilder();
        $builder->title('续费订单');
        $builder->meta_title = L('续费订单');
        $builder->search('下单用户名', 'uid')->buttonDelete(U('User/delOrder'))
		->buttonSetStatus(U('changeOrderStatusend'), '1', '标记付款', null)
		->buttonSetStatus(U('changeOrderStatusend'), '2', '标记现金付款', null);
        $builder->keyId()->keyText('e_id','续费设备序列号')->keyText('address','续费地址')
		->keyText('xf_yeah','续费时长（年）')->keyText('uid','用户')->keyText('nickname','姓名')->keyTime('servicetime','设备下次到期时间')->keyTime('createtime','下单时间')->keyText('xf_status','状态');
        $builder->keyDoAction('User/delOrder?ids=###', L('删除'));
        $builder->data($list);
        $builder->pagination($totalCount, $r);
        $builder->display();
    }
    
    /*
     * 订单管理--开户订单
     * type : 3
     */
    public function openAccount($page = 1, $r = 10,$uid = ''){
		$where['type'] = 3;
        if(!empty($uid)){
            $u_uid = get_table_field($uid,'username','id','UcenterMember');
            $where['uid'] = $u_uid;
        }
        $list = M('OrderList')->where($where)->order("createtime desc")->page($page, $r)->select();
        // dump($list);exit;
        $totalCount = M('OrderList')->where($where)->count();
        foreach($list as $k=>$v){
            $list[$k]['account_id'] = get_table_field($v['account_id'],'id','name','UserAccount');
            $list[$k]['uid'] = get_table_field($v['uid'],'id','username','UcenterMember');
            $list[$k]['account_id'] = get_table_field($v['account_id'],'id','name','UserAccount');
             $list[$k]['address'] = get_table_field($v['uid'],'uid','address','Equipment');
			##取服务年限
			$service_time = get_table_field($v['account_id'],'id','service_time','UserAccount');
			##取开户时间
			$createtime = get_table_field($v['account_id'],'id','createtime','UserAccount');
			 $add_serviceTime =date('Y',$createtime) + $service_time . '-' . date('m-d H:i:s',$createtime);//续费后的日期
			$list[$k]['servicetime'] = strtotime($add_serviceTime);
            if($v['type'] == 3){
                $list[$k]['type'] = '开户订单';
            }
            
          
				switch ($v['kh_status'])
					{
					case -1:
					  $list[$k]['kh_status'] = '订单关闭';
					  $list[$k]['acurl']="";
					  $list[$k]['actext']="";
					  break;  
					case 0:
					  $list[$k]['kh_status'] = '未付款';
					  $list[$k]['acurl']="";
					  $list[$k]['actext']="";
					  break;
					case 1:
					  $list[$k]['kh_status'] = '付款成功';
					  $list[$k]['acurl']="User/changeOrderStatus?ids=###";
					  $list[$k]['actext']="派单";
					  break;
					case 2:
					  $list[$k]['kh_status'] = '派单中';
					  $list[$k]['acurl']="User/changeOrderStatusend?ids=###";
					  $list[$k]['actext']="完成";
					  break;
					case 3:
					  $list[$k]['kh_status'] = '已完成';
					  $list[$k]['acurl']="";
					  $list[$k]['actext']="";
					  break; 
                    case 4:
                      $list[$k]['kh_status'] = '现金付款';
                      $list[$k]['acurl']="";
                      $list[$k]['actext']="";
                      break;  
					}

            
        }
        $builder = new AdminListBuilder();
        $builder->title('开户订单');
        $builder->meta_title = L('开户订单');
        $builder->search('下单用户名', 'uid')->buttonDelete(U('User/delOrder'))
		->buttonSetStatus(U('changeOrderStatus'), '2', '派单', null)
		->buttonSetStatus(U('changeOrderStatus'), '3','完成',null)
        ->buttonSetStatus(U('changeOrderStatus'), '1', '标记已付款', null)
        ->buttonSetStatus(U('changeOrderStatus'), '4','标记现金付款',null);
        $builder->keyId()->keyText('account_id','户头名称')->keyText('uid','开户用户')->keyText('address','开户地址')
        ->keyText('type','订单类型')
        ->keyText('kh_yeah','开户年限（年）')
        ->keyText('kh_money','金额')
        ->keyTime('createtime','下单时间')
        ->keyTime('servicetime','到期时间')
        ->keyText('kh_status','状态');
       
        $builder->keyDoAction('User/delOrder?ids=###', L('删除'));
        $builder->data($list);
        $builder->pagination($totalCount, $r);
        $builder->display();
    }

    /*
    *  开户订单派单
    * $ids 订单ID
    */
    public function changeOrderStatus($ids='',$status=''){
        if(empty($ids)){
            $this->error("缺少参数 ");
            return false;
        }
		$map['id']=array('in',arr2str($ids));
        $res=M("OrderList")->where($map)->save(array('kh_status'=>$status));
		//dump(M("OrderList"));exit;
        if($res){
            $this->success("操作成功！");
        }else{
            $this->error("操作失败！");
        }

    }

	/*
    *  xufei订单
    * $ids 订单ID
    */
    public function changeOrderStatusend($ids='',$status=''){
        if(empty($ids)){
            $this->error("缺少参数 ");
            return false;
        }
		$map['id']=array('in',arr2str($ids));
        $res=M("OrderList")->where($map)->save(array('xf_status'=>$status));
		//dump(M("OrderList"));exit;
        if($res){
            $this->success("操作成功！");
        }else{
            $this->error("操作失败！");
        }

    }
 
    /*
     * 订单管理--维修订单
     *  type ; 1
     */
    public function repairOrder($page = 1, $r = 10,$uid = ''){
		$where['type'] = 1;
        if(!empty($uid)){
            $u_uid = get_table_field($uid,'username','id','UcenterMember');
            $where['uid'] = $u_uid;
        }
        $list = M('OrderList')->where($where)->order("createtime desc")->page($page, $r)->select();
        $totalCount = M('OrderList')->where($where)->count();
        foreach($list as $k=>$v){
            $list[$k]['e_id'] = get_table_field($v['e_id'],'id','e_id','Equipment');
            $list[$k]['uid'] = get_table_field($v['uid'],'id','username','UcenterMember');
			$user_id = get_table_field($v['order_id'],'order_id','user_id','OrderCommit');
            $list[$k]['repair_uid'] = get_table_field($user_id,'id','name','InstallMember');
			$list[$k]['score'] = get_table_field($v['order_id'],'order_id','score','OrderCommit');
			$list[$k]['finsh_time'] = get_table_field($v['order_id'],'order_id','update_time','OrderCommit');
            $list[$k]['address'] = get_table_field($v['uid'],'uid','address','Equipment');
			$list[$k]['nickname'] = get_table_field($v['uid'],'uid','nickname','Member');
            //if($v['type'] == 1){
              //  $list[$k]['type'] = '维修订单';
           // }
            if($v['wx_status'] == -1){
                $list[$k]['wx_status'] = '取消维修服务';
            }
            if($v['wx_status'] == 1){
                $list[$k]['wx_status'] = '审核中';
            }
            if($v['wx_status'] == 2){
                $list[$k]['wx_status'] = '维修中';
            }
            if($v['wx_status'] == 3){
                $list[$k]['wx_status'] = '已完成';
            }
        }
        $builder = new AdminListBuilder();
        $builder->title('维修订单');
        $builder->meta_title = L('维修订单');
        $builder->search('下单用户名', 'uid')->buttonDelete(U('User/delOrder'));
        $builder->keyId()->keyText('e_id','维修设备序列号')->keyText('wx_type','设备问题类型')
		->keyText('content','问题描述')->keyText('uid','用户')->keyText('nickname','姓名')->keyText('address','维修地址')
		->keyTime('createtime','下单时间')->keyText('score','客户评分(分)')->keyTime('finsh_time','订单完成时间')->keytext('repair_uid','维修工作人员')->keytext('wx_status','状态');
        $builder->keyDoAction('User/changeWxStatus?id=###', L('标记为维修中'))->keyDoAction('User/delOrder?ids=###', L('删除'));
        $builder->data($list);
        $builder->pagination($totalCount, $r);
        $builder->display();
    }
    /*
     * 标记维修订单为已处理
     */
    public function changeWxStatus($id = 0){
        if($id == 0){
            $this->error('参数丢失');
        }
        $change = M('OrderList')->where(array('id'=>$id))->find();
        if($change['wx_status'] ==1){
			$change = M('OrderList')->where(array('id'=>$id))->save(array('wx_status'=>2));
            $this->success('操作成功');
        }else{
            $this->error('已处理，不要重复操作');
        }
    }
    
    /*
     * 订单删除
     */
    public function delOrder($ids = 0){
        $ids = array_unique((array)I('ids',0));

        if ( empty($ids) ) {
            $this->error(L('_ERROR_DATA_SELECT_').L('_EXCLAMATION_'));
        }
        
        $map = array('id' => array('in', $ids) );
        $del = M('OrderList')->where($map)->delete();
        if($del){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    } 

    /**
     * 设备报警信息
     * @author 麦当苗儿 <zuojiazi@vip.qq.com>
     */
    
    public function alarm($page = 1, $r = 15,$type=0){

        if($type){
            $map['res']=$type;
        }
        
        $list = M('Alarm')->order("id desc")->where($map)->page($page, $r)->select();
        $totalCount = M('Alarm')->count();  
        foreach($list as $k=>$v){
                $e_id=strSplit($v['uid']);
                $res=M("equipment")->where(array('e_id'=>$e_id))->find();
            $list[$k]['uuid'] = M('UcenterMember')->where(array('id'=>$res['uid']))->getField('username');
            $list[$k]['e_type'] = M('DeviceType')->where(array('id'=>$res['e_type']))->getField('name');
            $list[$k]['installuid'] = M('UcenterMember')->where(array('id'=>$res['installuid']))->getField('username'); 
            $list[$k]['status'] = ($v['status'] ==1) ? '已查看':'未处理';
            $list[$k]['e_id'] = strSplit($v['uid']);
            $list[$k]['address'] = $res['address'];
            $list[$k]['res_text'] = $this->rescode($v['res']);
            $list[$k]['nickname'] =M('Member')->where(array('uid'=>$res['uid']))->getField('nickname');
        }
        $builder = new AdminListBuilder();
        $builder->title(L('设备报警'));
        $builder->buttonDelete(U('User/delalarm'));
        $builder->meta_title = L('设备报警');
        // $builder->buttonNew(U('User/editEquipment'));
        $builder->keyId()->keyText('uuid','报警用户')->keyText('nickname','姓名')->keyText('e_type','设备类型')->keyText('e_id','设备编号')->keyText('address','报警地址')->keyText('res_text','动作')->keyTime('createtime','报警时间')->keyText('status','状态');
        // $builder->keyDoAction('User/delEquipment?id=###', L('删除'));
        $builder->data($list);
        $builder->pagination($totalCount, $r);
        $builder->display();
    }

//报警删除
public function delalarm($ids = 0){
        $ids = array_unique((array)I('ids',0));

        if ( empty($ids) ) {
            $this->error(L('_ERROR_DATA_SELECT_').L('_EXCLAMATION_'));
        }
        
        $map = array('id' => array('in', $ids) );
        $del =  M('Alarm')->where($map)->delete();
        if($del){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    } 


    /**
     * 用户设备列表
     * @author 麦当苗儿 <zuojiazi@vip.qq.com>
     */

    final protected function rescode($code){
        switch ($code) {
            case 1: return "传感器告警"; break;
            case 2: return "传感器低电"; break;
            // case 3:return "传感器告警"; break;
            // case 4:return "传感器告警"; break;
            case 5: return "摄像机布防"; break;
            case 6: return "摄像机撤防"; break;
            case 7: return "传感器紧急报警"; break;
            case 8: return "摄像机对码中"; break;
            // case 9:return "传感器告警"; break;
            case 10: return "门磁开关"; break;
            case 11: return "摄像机退出对码"; break;
            case 12: return "摄像机移动侦测警报"; break;
            case 13: return "摄像机GPIO输入报警"; break;
            case 14: return "可视门铃报警"; break;
            case 15: return "B高温报警"; break;
            case 16: return "B低温报警"; break;
            case 17: return "B低电报警"; break;
            case 18: return "B哭声报警"; break;
           
        }
    }
    
    public function equipmentLists($page = 1, $r = 15){
        $aSearch2 = I('get.user_search2');
        $map = array();

            if (is_numeric($aSearch2)) {
                 $map['id|e_id'] = array(intval($aSearch2), array('like', '%' . $aSearch2 . '%'), '_multi' => true);
            }else{
                 if ($aSearch2 !== '') {
                     $map['e_id|address'] = array(array('like', '%' . (string)$aSearch2 . '%'),array('like', '%' . $aSearch2 . '%'), '_multi' => true);
                    }
            }
            

            
         
        
        $list = M('Equipment')->where($map)->order("id desc")->page($page, $r)->select();
        // dump(M('Equipment'));exit;
        $totalCount = D('Equipment')->where($map)->count();  
        foreach($list as $k=>$v){
            $list[$k]['uid'] = M('UcenterMember')->where(array('id'=>$v['uid']))->getField('username');
			$list[$k]['nickname'] =M('Member')->where(array('uid'=>$v['uid']))->getField('nickname');
            $list[$k]['e_type'] = M('DeviceType')->where(array('id'=>$v['e_type']))->getField('name');
            $list[$k]['installuid'] = M('UcenterMember')->where(array('id'=>$v['installuid']))->getField('username'); 
            $list[$k]['status'] = ($v['status'] ==1) ? '启用':'禁用';
        }
        $builder = new AdminListBuilder();
        $builder->title(L('设备列表'));
        $builder->meta_title = L('设备列表');
        $builder->buttonNew(U('User/editEquipment'))->buttonDelete(U('User/delEquipment'));
       $builder->setSelectPostUrl(U('User/equipmentLists'))
            ->setSearchPostUrl(U('User/equipmentLists'))
            // ->search('','user_search1','',L('_SEARCH_ACCORDING_TO_USERS_NICKNAME_'),'','','')
            ->search('','user_search2','',L('搜索ID,序列号,安装地址'),'','','');
        $builder->keyId()->keyText('uid','用户')->keyText('nickname','姓名')->keyText('e_type','设备')->keyText('e_id','设备编号')->keyText('address','安装地址')->keyTime('installtime','设备安装时间')->keyTime('servicetime','服务到期时间')->keyText('installuid','安装人员')->keyLink('status','状态','User/setUserStatus?id=###','_self');
        $builder->keyDoAction('User/thisMember?id=###', L('成员'))->keyDoAction('User/editEquipment?id=###', L('编辑'))->keyDoAction('User/delEquipment?id=###', L('删除'));
        $builder->data($list);
        $builder->pagination($totalCount, $r);
        $builder->display();
    }
    
    /*
     * 该设备下成员
     * $id 设备ID
     */
    public function thisMember($id = "",$page = 1, $r = 15){
        if(empty($id)){
            $this->error('参数丢失');
        }
        $list = M('EquipmentAuthRule')->where(array('e_id'=>$id))->order("createtime desc")->page($page, $r)->select();
        $totalCount = M('EquipmentAuthRule')->where(array('e_id'=>$id))->count();
        foreach($list as $k=>$v){
            $list[$k]['uid'] = M('UcenterMember')->where(array('id'=>$v['uid']))->getField('username');
            $list[$k]['e_id'] = get_table_field($v['e_id'],'id','e_name','Equipment');
            $list[$k]['nickname'] =M('Member')->where(array('uid'=>$v['uid']))->getField('nickname');
            $list[$k]['status'] = ($v['status'] ==1) ? '启用':'禁用';
            $list[$k]['is_setPwd'] = ($v['is_setPwd'] ==1) ? '是':'否';
        }
        $builder = new AdminListBuilder();
        $builder->title(L('设备成员列表'));
        $builder->meta_title = L('设备成员列表');
        $builder->buttonDelete(U('User/delEquipment'));
        $builder->keyId()->keyText('uid','用户名')->keyText('nickname','姓名')->keyText('e_id','名称')->keyText('is_setPwd','密码权限')->keyTime('online_time','最后在线时间')->keyTime('createtime','添加时间')->keyText('status','状态');
        $builder->keyDoAction('User/delMember?id=###', L('删除'));
        $builder->data($list);
        $builder->pagination($totalCount, $r);
        $builder->display();
    }
    /*
     * 改变用户设备状态
     */
    public function setUserStatus($id){
        $check = M('Equipment')->where(array('id'=>$id))->getField('status');
        if($check == 0){
            $data['status'] = 1;
        }
        if($check == 1){
            $data['status'] = 0;
        }
        $update = M('Equipment')->where(array('id'=>$id))->save($data);
        if($update){
            $this->success('修改成功', U('User/equipmentlists'));
        }else{
            $this->error('修改失败',U('User/equipmentlists'));
        }
    }
    /*
     * 删除用户设备
     */  
        public function delEquipment($id = 0){
         $id = array_unique((array)I('id',0));

        if ( empty($id) ) {
            $this->error(L('_ERROR_DATA_SELECT_').L('_EXCLAMATION_'));
        }

        $map = array('id' => array('in', $id) );
        $del = M('Equipment')->where($map)->delete();
        if($del){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }
    
     /*
     * 删除用户设备
     */  
        public function delMember($id = 0){
         $id = array_unique((array)I('id',0));

        if ( empty($id) ) {
            $this->error(L('_ERROR_DATA_SELECT_').L('_EXCLAMATION_'));
        }

        $map = array('id' => array('in', $id) );
        $del = M('EquipmentAuthRule')->where($map)->delete();
        if($del){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }
    /*
     * 删除成员
     */
    public function editEquipment($id = 0){
         
        if($_POST){
            if(!empty($_POST['uid']) && !empty($_POST['e_pwd']) && !empty($_POST['e_name']) && !empty($_POST['e_id']) && !empty($_POST['e_type']) && !empty($_POST['address']) && !empty($_POST['root_id']) && !empty($_POST['status'])){
                $uid = get_table_field($_POST['uid'],'username','id','UcenterMember');

                if($uid){
                    $da['uid'] = $uid ;
                }else{
                    $this->error('此用户不存在');
                }

                $da['e_type'] = $_POST['e_type']; 
                $da['e_name'] = $_POST['e_name']; 
                $da['e_id'] = $_POST['e_id']; 
                $da['e_pwd'] = $_POST['e_pwd']; 
                $da['address'] = $_POST['address']; 
                $da['installtime'] = $_POST['installtime']; 
                $da['servicetime'] = $_POST['servicetime'];
                $root_id = get_table_field($_POST['root_id'],'username','id','UcenterMember');

                if($root_id){
                    $da['root_id'] =  $root_id;
                }else{
                    $this->error('此用户不存在');
                }
                $installuid = get_table_field($_POST['installuid'],'username','id','UcenterMember');
                 if($installuid){
                    $da['installuid'] = $installuid;
                }else{
                    $this->error('此用户不存在');
                }        
                $da['status'] = $_POST['status'];

                // $update = M('Equipment')->where(array('id'=>$id))->save($da);

                // if($update){
                //     $this->success('修改成功',U('User/equipmentlists'));
                // }

                 if(empty($id)){
                    $add =  M('Equipment')->add($da);
                    if($add){
                        $this->success('添加成功',U('User/equipmentlists'));
                    }
                }else{

                    $update = M('Equipment')->where(array('id'=>$id))->save($da);
                    if($update){
                        $this->success('修改成功',U('User/equipmentlists'));
                    }else{
                         $this->success('修改成功',U('User/equipmentlists'));
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
        $data = M('Equipment')->where(array('id'=>$id))->find();
        $data['uid'] = get_table_field($data['uid'] ,'id','username','UcenterMember');
        $data['root_id'] = get_table_field($data['root_id'] ,'id','username','UcenterMember');
        $data['installuid'] = get_table_field($data['installuid'] ,'id','username','UcenterMember');
        //$data['e_type'] = get_table_field($data['e_type'] ,'id','name','Device');
        $data['account_id'] = get_table_field($data['account_id'] ,'id','name','UserAccount');
        $builder = new AdminConfigBuilder();
        if($id == 0 ){
            $title = '新增用户设备';
        }else{
            $title = '编辑用户设备';
        }
        $builder->title($title);
        $builder->meta_title = L($title);
        $builder->keyId()->keyText('uid', '安装用户','输入用户名')->keySelect('e_type', '设备类型','',$opt)->keyText('e_name', '设备名称')->keyText('e_id', '设备序列号')->keyReadOnly('account_id','设备所属户头')->keyText('e_pwd','设备链接密码')->keyText('address','安装地址','请填写详细的安装地址')->keyCreateTime('installtime', '安装时间')->keyCreateTime('servicetime', '服务到期时间')->keyText('root_id', '管理员','输入用户名')->keyText('installuid', '安装人员','输入用户名')->keyStatus();
        $builder->data($data);
        $builder->buttonSubmit(U('editEquipment'), $id == 0 ? L('新增') : L('编辑'))->buttonBack();
        $builder->display();
    }
    
    
    /*
     * 用户户头列表
     */
    public function userAccount($uid = "" ){
        if(empty($uid)){
            $this->error('参数错误','user/index');
        }
        
        $list = M('UserAccount')->where(array('uid'=>$uid))->order("createtime desc")->page($page, $r)->select();
        $totalCount = M('UserAccount')->where(array('uid'=>$uid))->count();
        foreach($list as $k=>$v){
            $list[$k]['uid'] = M('UcenterMember')->where(array('id'=>$v['uid']))->getField('username');
            $list[$k]['status'] = ($v['status'] ==1) ? '启用':'禁用';
            $address = explode(',', $v['address']);
            $province = get_table_field($address[0],'id','name','District');
            $city = get_table_field($address[1],'id','name','District');
            $district = get_table_field($address[2],'id','name','District');
            $list[$k]['address'] = $province.$city.$district;
        }
        $builder = new AdminListBuilder();
        $builder->title(L('户头列表'));
        $builder->meta_title = L('户头列表');
        $builder->buttonNew(U('User/editAccount?uid='.$uid))->buttonDelete(U('User/delAccount'));
        $builder->keyId()->keyText('name','用户姓名')->keyText('uid','用户账号')->keyText('mobile','手机号码')->keyText('address','安装地区')->keyText('address_detail','安装详细地址')->keyText('service_price','价格（元）')->keyText('service_time','服务时间（年）')->keyTime('createtime','开户时间')->keyText('status','状态');
        $builder->keyDoAction('User/editAccount?id=###', L('编辑'))->keyDoAction('User/delAccount?id=###', L('删除'));
        $builder->data($list);
        $builder->pagination($totalCount, $r);
        $builder->display();
        
    }
    
    /*
     * 户头编辑
     */
    public function editAccount($id = 0){
         if($_POST){
            if(!empty($_POST['name']) && !empty($_POST['uid']) && !empty($_POST['mobile']) && !empty($_POST['address_detail']) && !empty($_POST['service_price']) && !empty($_POST['service_time']) && !empty($_POST['status'])){
                $da['name'] = $_POST['name']; 
                $da['uid'] = $_POST['uid']; 
                $da['mobile'] = $_POST['mobile']; 
                $province = $_POST['province'];
                $city = $_POST['city'];
                $district = $_POST['district'];
                $da['address'] = $province.','.$city.','.$district; 
                $da['address_detail'] = $_POST['address_detail']; 
                $da['service_price'] = $_POST['service_price']; 
                $da['service_time'] = $_POST['service_time'];
                $da['createtime'] = time();       
                $da['status'] = $_POST['status'];
                 if($id == 0){
                    $add =  M('UserAccount')->add($da);
                    if($add){
                        $this->success('添加成功',U('User/userAccount',array('uid'=>$_POST['uid'])));
                    }
                }else{
                    $update = M('UserAccount')->where(array('id'=>$id))->save($da);
                    if($update){
                        $this->success('修改成功',U('User/userAccount',array('uid'=>$_POST['uid'])));
                    }
                }
            }else{
                $this->error('参数不能为空，请检查');
            }
            
        }
        
        $data = M('UserAccount')->where(array('id'=>$id))->find();
        if($id == 0){
            $data['uid'] = $_GET['uid'];
        }
        $builder = new AdminConfigBuilder();
        if($id == 0 ){
            $title = '新增户头';
        }else{
            $title = '编辑户头';
        }
        $builder->title($title);
        $builder->meta_title = L($title);
        $builder->keyId()->keyReadOnly('uid','用户UID')->keyText('name','用户姓名')->keyText('mobile', '联系电话')->keyCity('address', '地址（地区）')->keyText('address_detail', '详细地址')->keyText('service_price','服务价格（多少一年）')->keyText('service_time','服务时长（1，2，3）')->keyText('type','备注')->keyCreatetime('createtime','开户时间')->keyStatus();
        $builder->data($data);
        $builder->buttonSubmit(U('editAccount'), $id == 0 ? L('新增') : L('编辑'))->buttonBack();
        $builder->display();
    }
    
     /*
     * 删除户头 
     */
    public function delAccount($id){
         $id = array_unique((array)I('id',0));

        if ( empty($id) ) {
            $this->error(L('_ERROR_DATA_SELECT_').L('_EXCLAMATION_'));
        }

        $map = array('id' => array('in', $id) );
        $del = M('UserAccount')->where($map)->delete();
        if($del){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    } 

}
