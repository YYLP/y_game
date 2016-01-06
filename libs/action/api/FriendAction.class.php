<?php
/**
 * Friend 类
 *
 *
 * @access public
 * @author chenwenbin
 * @copyright Copyright(C) Tenone Inc.
 * @version 0.01
 * @since 2014/11/13
 */

class FriendAction extends BaseAction {

    const CHECKPOINT_LIST = "checkpoint_basic_list";
    /**
     * API：好友界面
     *
     * @access public
     * @param 无
     * @return JsonView 响应json
     */
    public function exeGetFriendMenu($user_id)
    {
        // $requestParam = $this->getAllParameters();
        // Logger::debug('requestParam:'. print_r($requestParam, true));

        // $requestJsonParam = $this->getDecodedJsonRequest();
        // Logger::debug('requestJsonParam:'. print_r($requestJsonParam, true));

        // $user_id=$requestParam['user_id'];
        // $session_key=$requestParam['session_key'];

        $friendInfo=FriendModel::getTenInfo($user_id);

        foreach ($friendInfo as $key => $value) 
        {
            $res=FriendModel::isFriend($user_id,$value['n_id']);
            $friendInfo[$key]['n_friend']=$res;
            $friendInfo[$key]['n_battle']=$value['n_battle'];
        }
        return $friendInfo;
        // $messageArr['friendInfo']=$friendInfo;
        // $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
        // $view = new JsonView();
        // return $this->getViewByJson( $view, $messageArr, 1,"friend/get_friend_menu" );

    }

    /**
     * API：查找好友
     *
     * @access public
     * @param 无
     * @return JsonView 响应json
     */
	public function exeSearchFriend()
	{
		$requestParam = $this->getAllParameters();
        Logger::debug('requestParam:'. print_r($requestParam, true));

        $requestJsonParam = $this->getDecodedJsonRequest();
        Logger::debug('requestJsonParam:'. print_r($requestJsonParam, true));

        $user_id=$requestParam['user_id'];
        $user_name=$requestJsonParam['user_name'];
        $session_key=$requestParam['session_key'];

        $friendInfo=FriendModel::searchByName($user_name);
        if (!$friendInfo)
        {
            $messageArr['error']="搜索的玩家不存在！";
            $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
            $view = new JsonView();
            return $this->getViewByJson( $view, $messageArr, 0,"friend/search_friend" );	
        }
        foreach ($friendInfo as $key => $value) 
        {
        	$res=FriendModel::isFriend($user_id,$value['n_id']);
        	$friendInfo[$key]['n_friend']=$res;
            $friendInfo[$key]['n_battle']=$value['n_battle'];
        }
        $messageArr['friendInfo']=$friendInfo;
        $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
        $view = new JsonView();
        return $this->getViewByJson( $view, $messageArr, 1,"friend/search_friend" );
	}


    /**
     * API：添加好友
     *
     * @access public
     * @param 无
     * @return JsonView 响应json
     */
	public function exeAddFriend()
	{
		$requestParam = $this->getAllParameters();
        Logger::debug('requestParam:'. print_r($requestParam, true));

        $requestJsonParam = $this->getDecodedJsonRequest();
        Logger::debug('requestJsonParam:'. print_r($requestJsonParam, true));

        $user_id=$requestParam['user_id'];
        $session_key=$requestParam['session_key'];
        $friend_id=$requestJsonParam['friend_id'];

        if ($user_id==$friend_id) 
        {
            $messageArr['error']="不能添加自己为好友！";
            $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
            $view = new JsonView();
            return $this->getViewByJson( $view, $messageArr, 0,"friend/add_friend" );
        }
        //最大好友数判断
        $friendNum=FriendModel::getFriendNum($user_id);
        if ($friendNum>=Constants::MAX_FRIEND_NUM) 
        {
            $messageArr['error']="好友数已达上限！";
            $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
            $view = new JsonView();
            return $this->getViewByJson( $view, $messageArr, 0,"friend/add_friend" );
        }

        //是否已申请添加，
        $isAdd=FriendModel::isAddFriend($user_id,$friend_id);
        if ($isAdd) 
        {
            $messageArr['error']="已在申请列表中！";
            $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
            $view = new JsonView();
            return $this->getViewByJson( $view, $messageArr, 0,"friend/add_friend" );
        }
        
        $res=FriendModel::insertMail(array('n_send_id'=>$user_id,'n_receive_id'=>$friend_id,'n_type'=>1,'t_update_time'=>date("Y-m-d H:i:s"),'t_create_time'=>date("Y-m-d H:i:s")));
        if (!$res) 
        {
        	throw new Exception("insert false");  	
        }
        $messageArr['friendInfo']=FriendAction::exeGetFriendMenu($user_id);
        $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
        $view = new JsonView();
        return $this->getViewByJson( $view, $messageArr, 1,"friend/add_friend" );
	}


	/**
     * API：同意添加好友
     *
     * @access public
     * @param 无
     * @return JsonView 响应json
     */
	public function exeAgreeFriend()
	{
		$requestParam = $this->getAllParameters();
        Logger::debug('requestParam:'. print_r($requestParam, true));

        $requestJsonParam = $this->getDecodedJsonRequest();
        Logger::debug('requestJsonParam:'. print_r($requestJsonParam, true));

        $user_id=$requestParam['user_id'];
        $friend_id=$requestJsonParam['friend_id'];
        $session_key=$requestParam['session_key']; 
        
        //判断是否已为好友关系
        $ret=FriendModel::isFriend($user_id,$friend_id);
        if ($ret) 
        {
            $messageArr['error']="已在好友列表中！";
            $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
            $view = new JsonView();
            return $this->getViewByJson( $view, $messageArr, 0,"friend/agree_friend" );
        }

        //好友人数上限判断
        $friendNum=FriendModel::getFriendNum($user_id);
        if ($friendNum>=Constants::MAX_FRIEND_NUM) 
        {
            $messageArr['error']="好友数已达上限！";
            $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
            $view = new JsonView();
            return $this->getViewByJson( $view, $messageArr, 0,"friend/agree_friend" );	
        }      

        $res=FriendModel::insert(array('n_user_id'=>$user_id,'n_friend_id'=>$friend_id,'t_create_time'=>date("Y-m-d H:i:s")));
        if (!$res) 
        {
        	throw new Exception("insert false");
        }

        $res=FriendModel::updateFriendMail(array('n_type'=>0,'t_update_time'=>date("Y-m-d H:i:s")),array('n_send_id'=>$friend_id,'n_receive_id'=>$user_id,'n_type'=>1));
        $res=FriendModel::insertMail(array('n_send_id'=>$user_id,'n_receive_id'=>$friend_id,'n_type'=>3,'t_update_time'=>date("Y-m-d H:i:s"),'t_create_time'=>date("Y-m-d H:i:s")));
        if (!$res) 
        {
        	throw new Exception("update false");
        }

        $str=$user_id.'_friend';
        $friendInfo=FriendModel::getFriendList($user_id);
        foreach ($friendInfo as $key => $value) 
        {
            $friendList[]=$value['n_id'];
        }
        UserCache::setByKey($str,'friend_list',$friendList);
        $messageArr['friend_mail']=MailModel::getFriendMail($user_id);
        $messageArr['system_mail']=MailModel::getSystemMail($user_id);
        //任务成就界面
        $messageArr['achieveInfo']=TaskAndAchieveAction::getAchieveInfo($user_id);
        $messageArr['taskInfo']=TaskAndAchieveAction::getTaskInfo($user_id);

        $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
        $view = new JsonView();
        return $this->getViewByJson( $view, $messageArr, 1,"friend/agree_friend" );
	}

	/**
     * API：拒绝添加好友
     *
     * @access public
     * @param 无
     * @return JsonView 响应json
     */
	public function exeRefuseAdd()
	{
		$requestParam = $this->getAllParameters();
        Logger::debug('requestParam:'. print_r($requestParam, true));

        $requestJsonParam = $this->getDecodedJsonRequest();
        Logger::debug('requestJsonParam:'. print_r($requestJsonParam, true));

        $user_id=$requestParam['user_id'];
        $friend_id=$requestJsonParam['friend_id'];
        $session_key=$requestParam['session_key'];

        $res=FriendModel::updateFriendMail(array('n_type'=>0,'t_update_time'=>date("Y-m-d H:i:s")),array('n_send_id'=>$friend_id,'n_receive_id'=>$user_id,'n_type'=>1));
        $res=FriendModel::insertMail(array('n_send_id'=>$user_id,'n_receive_id'=>$friend_id,'n_type'=>2,'t_update_time'=>date("Y-m-d H:i:s"),'t_create_time'=>date("Y-m-d H:i:s")));
        
        $messageArr['friend_mail']=MailModel::getFriendMail($user_id);
        $messageArr['system_mail']=MailModel::getSystemMail($user_id); 

        //任务成就界面
        $messageArr['achieveInfo']=TaskAndAchieveAction::getAchieveInfo($user_id);
        $messageArr['taskInfo']=TaskAndAchieveAction::getTaskInfo($user_id);

        $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
        $view = new JsonView();
        return $this->getViewByJson( $view, $messageArr, 1,"friend/refuse_add" );

    }

    /**
     * API：确定按钮
     *
     * @access public
     * @param 无
     * @return JsonView 响应json
     */
	public function exeConfigButton()
	{
		$requestParam = $this->getAllParameters();
        Logger::debug('requestParam:'. print_r($requestParam, true));

        $requestJsonParam = $this->getDecodedJsonRequest();
        Logger::debug('requestJsonParam:'. print_r($requestJsonParam, true));

        $user_id=$requestParam['user_id'];
        $mail_id=$requestJsonParam['mail_id'];
        $session_key=$requestParam['session_key'];

        $res=FriendModel::updateFriendMail(array('n_type'=>0,'t_update_time'=>date("Y-m-d H:i:s")),array('n_id'=>$mail_id));

        $messageArr['friend_mail']=MailModel::getFriendMail($user_id);
        $messageArr['system_mail']=MailModel::getSystemMail($user_id);

        //任务成就界面
        $messageArr['achieveInfo']=TaskAndAchieveAction::getAchieveInfo($user_id);
        $messageArr['taskInfo']=TaskAndAchieveAction::getTaskInfo($user_id);
        
        $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
        $view = new JsonView();
        return $this->getViewByJson( $view, $messageArr, 1,"friend/config_button" );

    }
    /**
     * API：删除好友
     *
     * @access public
     * @param 无
     * @return JsonView 响应json
     */
	public function exeDeleteFriend()
	{
		$requestParam = $this->getAllParameters();
        Logger::debug('requestParam:'. print_r($requestParam, true));

        $requestJsonParam = $this->getDecodedJsonRequest();
        Logger::debug('requestJsonParam:'. print_r($requestJsonParam, true));

        $user_id=$requestParam['user_id'];
        $friend_id=$requestJsonParam['friend_id'];
        $session_key=$requestParam['session_key'];

        $res=FriendModel::delFriend($user_id,$friend_id);

        if (!$res) 
        {
            throw new Exception("delete false");
        }

        $str=$user_id.'_friend';
        $friendInfo=FriendModel::getFriendList($user_id);
        foreach ($friendInfo as $key => $value) 
        {
            $friendList[]=$value['n_id'];
        }
        UserCache::setByKey($str,'friend_list',$friendList);
        $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
        $view = new JsonView();
        return $this->getViewByJson( $view, $messageArr, 1,"friend/delete_friend" );
	}

    /**
     * API：好友合体信息
     *
     * @access public
     * @param 无
     * @return JsonView 响应json
     */
	public function exeGetFitInfo()
	{
		$requestParam = $this->getAllParameters();
        Logger::debug('requestParam:'. print_r($requestParam, true));

        $requestJsonParam = $this->getDecodedJsonRequest();
        Logger::debug('requestJsonParam:'. print_r($requestJsonParam, true));

        $user_id=$requestParam['user_id'];
        $session_key=$requestParam['session_key'];

        $idArr=FriendModel::fitFriend($user_id);
        $nowTime=time();

        foreach ($idArr as $key => $value) 
        {
            $condition.=$value['n_id'].",";
            $waitTime=$value['time']-$nowTime;
            $idArr[$key]['time']=($waitTime>0)?($waitTime):0;   
        }
        
        $condition=substr($condition, 0,-1);
        $friendInfo=array();
        if ($condition) 
        {
            $friendInfo=FriendModel::getFriendInfo($condition);
        }
        
        foreach ($friendInfo as $key1 => $value1) 
        {
        	foreach ($idArr as $key2 => $value2) 
        	{
        		if ($value1['n_id']==$value2['n_id']) 
        		{
        			$friendInfo[$key1]=array_merge($value1,$value2);
                    $num=$value2['num']+1;
                    $costInfo=FriendAction::getFitCostInfo($value1['n_id'],$num);
                    $friendInfo[$key1]['price_type']=$costInfo['price_type'];
                    $friendInfo[$key1]['price']=$costInfo['price'];
                    $battleInfo=CharacterAction::getFitBattleInfo($user_id,$value1['n_battle']);
                    $friendInfo[$key1]['add_battle']=$battleInfo['add_battle']; 
                    $friendInfo[$key1]['attack']=$battleInfo['attack'];  
                    $friendInfo[$key1]['crit']=$battleInfo['crit']; 
                    $friendInfo[$key1]['hp']=$battleInfo['hp']; 
        		}
        	}
        }
        //游戏中主角、武将信息获取
        // $messageArr1=CharacterAction::GetAllCharacterInfo($user_id);
        // $messageArr2=GeneralAction::GetAllGeneralInfo($user_id);
        // $messageArr=array_merge($messageArr1,$messageArr2);

        // 关卡基本信息
        $dir = IniFileManager::getRootDir() . "files/csv/" . self::CHECKPOINT_LIST . ".csv";
        $str = "checkpoint_id = " . $requestJsonParam['checkpoint_id'];
        $checkpointArr = Util::readCsv( $dir, $str );

        if( empty( $checkpointArr ) )
        {
            $view = new JsonView();
            return $this->getViewByJson( $view, $messageArr, 0,"game/start_game" );
        }

        $pointsNum = $checkpointArr[0]['reward_points'];
        $proArr = array( 'a' => $checkpointArr[0]['reward_pro_1'], 
                         'b' => $checkpointArr[0]['reward_pro_2'],
                         'c' => $checkpointArr[0]['reward_pro_3'] );

        $sequence = "";
        for ( $i=0; $i < $pointsNum; $i++ ) { 
            $sequence = $sequence . Util::extractRandomAnswer( $proArr );
        }

        $messageArr['sequence'] = $sequence;  
        $messageArr['score']=$checkpointArr[0]['grade_score'];
        $messageArr['friendInfo']=$friendInfo;
        $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
        $view = new JsonView();
        return $this->getViewByJson( $view, $messageArr, 1,"friend/get_fit_info" );

	}


    /**
     * API：更新合体时间
     *
     * @access public
     * @param 无
     * @return JsonView 响应json
     */
	public function exeUpdateFitTime()
	{
		$requestParam = $this->getAllParameters();
        Logger::debug('requestParam:'. print_r($requestParam, true));

        $requestJsonParam = $this->getDecodedJsonRequest();
        Logger::debug('requestJsonParam:'. print_r($requestJsonParam, true));

        $user_id=$requestParam['user_id'];
        $friend_id=$requestJsonParam['friend_id'];
        $session_key=$requestParam['session_key'];

        //判断是否已为好友关系
        $ret=FriendModel::isFriend($user_id,$friend_id);
        if (!$ret) 
        {
            $messageArr['error']="非好友关系！";
            $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
            $view = new JsonView();
            return $this->getViewByJson( $view, $messageArr, 0,"friend/update_fit_time" );
        }
        
        $fitTime=FriendModel::fitFriend($user_id);

        foreach ($fitTime as $key => $value) 
        {
            if ($value['n_id']==$friend_id) 
            {
                $time=$value['time'];
                $fit_num=$value['num']+1;
            }
        }
        $nowTime=time();
        //冷却时间未到，扣除金钱
        if ($time>$nowTime) 
        {
            $price_type=array(1=>'n_coin',2=>'n_diamond');
            $costInfo=FriendAction::getFitCostInfo($friend_id,$fit_num);
            $type=$price_type[$costInfo['price_type']];
            $price=$costInfo['price'];

            $money=UserCache::getByKey($user_id,$type);
            if (!$money) 
            {
                $userInfo=FriendModel::getUserInfo($user_id);
                $money=$userInfo[$type];
            }
        	$money=$money-$price;
        	if ($money<0) 
        	{
                $messageArr['error']="人生果/钻石不足！";
                $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
                $view = new JsonView();
                return $this->getViewByJson( $view, $messageArr, 0,"friend/update_fit_time" );
        	}
        	$ret=CharacterModel::update(array($type=>$money),array('n_id'=>$user_id));
            FriendModel::updateFitNum($user_id,$friend_id);
            UserCache::setByKey($user_id,$type,$money);
        }
       else
       {
            $ret=FriendModel::fitTime($user_id,$friend_id);
       }
        //任务成就统计
        TaskAndAchieveAction::taskStatistic($user_id,array('friend_help'=>1));
        TaskAndAchieveAction::achieveStatistic($user_id,array('friend_help'=>1));
        $friendInfo=FriendModel::getUserInfo($friend_id);
        $messageArr=CharacterAction::getFitBattleInfo($user_id,$friendInfo['n_battle']);
        $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
        $view = new JsonView();
        return $this->getViewByJson( $view, $messageArr, 1,"friend/update_fit_time" );

	}

    /**
     * 好友列表
     *
     * @access public
     * @param 无
     * @return arrary
     */
	public function getFriend($user_id)
	{
        $str=$user_id.'_friend';
        $friendList=UserCache::getByKey($str,'friend_list');
        if (!$friendList) 
        {
            $friendInfo=FriendModel::getFriendList($user_id);
            foreach ($friendInfo as $key => $value) 
            {
                $friendList[]=$value['n_id'];
            }
            UserCache::setByKey($str,'friend_list',$friendList);
        }
        return $friendList;	
	}


    /**
     * 获取合体消耗信息
     *
     * @access public
     * @param 无
     * @return arrary
     */
    public function getFitCostInfo($user_id,$fit_num)
    {
        
        $userInfo=FriendModel::getUserInfo($user_id);
        $costNum=round($userInfo['n_battle']/200)+100;
        if ($fit_num==1) 
        {
            $costInfo['price_type']=1;
            $costInfo['price']=$costNum;
        }
        elseif ($fit_num>=2) 
        {
            $costInfo['price_type']=2;
            $costInfo['price']=round($costNum*0.05);
        }
        return $costInfo;
    }
}