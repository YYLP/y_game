<?php
/**
 * mail类
 *
 *
 * @access public
 * @author chenwenbin
 * @copyright Copyright(C) Tenone Inc.
 * @version 0.01
 * @since 2014/11/13
 */

class MailAction extends BaseAction {

    
    public $price_type= array(
            1 => 'n_coin',
            2 => 'n_diamond',
            3 => 'n_soul',
            4 => 'n_thew'
        );
    /**
     * API：获取好友邮箱
     *
     * @access public
     * @param 无
     * @return JsonView 响应json
     */
    public function exeGetMailInfo()
    {
    	$requestParam = $this->getAllParameters();
        Logger::debug('requestParam:'. print_r($requestParam, true));

        $requestJsonParam = $this->getDecodedJsonRequest();
        Logger::debug('requestJsonParam:'. print_r($requestJsonParam, true));

        $user_id=$requestParam['user_id'];
        $session_key=$requestParam['session_key'];
        
        //任务成就界面
        $messageArr['achieveInfo']=TaskAndAchieveAction::getAchieveInfo($user_id);
        $messageArr['taskInfo']=TaskAndAchieveAction::getTaskInfo($user_id);

        $messageArr['friend_mail']=MailModel::getFriendMail($user_id);
        $messageArr['system_mail']=MailModel::getSystemMail($user_id);

        $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
        $view = new JsonView();
        return $this->getViewByJson( $view, $messageArr, 1,"character/get_mail_info" );
    }


    /**
     * API：接受一个邮件
     *
     * @access public
     * @param 无
     * @return JsonView 响应json
     */
    public function exeUpdateOneMail()
    {
    	$requestParam = $this->getAllParameters();
        Logger::debug('requestParam:'. print_r($requestParam, true));

        $requestJsonParam = $this->getDecodedJsonRequest();
        Logger::debug('requestJsonParam:'. print_r($requestJsonParam, true));

        $user_id=$requestParam['user_id'];
        $session_key=$requestParam['session_key'];
        $mail_id=$requestJsonParam['mail_id'];
        
        $mailInfo=MailModel::getOneMail($mail_id);
        if (!$mailInfo) 
        {
            $messageArr['error']="该邮件不存在";
            $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
            $view = new JsonView();
            return $this->getViewByJson( $view, $messageArr, 0,"mail/update_one_mail" );
        }
        $price_type=$this->price_type;
        $type=$price_type[$mailInfo['n_item_type']];
        $money=UserCache::getBykey($user_id,$type);
        if (!$money) 
        {
            $userInfo=MailModel::getUserInfo($user_id);
            $money=$userInfo[$type];
        }
        $money=$money+$mailInfo['n_item_num'];

        $res=MailModel::update(array('n_type'=>0),array('n_id'=>$mail_id));
        
        //体力领取恢复时间更新
        if ($type==$price_type[4]) 
        {
            $thewArr['n_thew']=$money;
            $thewArr['n_refresh_time']=UserCache::getBykey($user_id,'n_refresh_time');
            if (!$thewArr['n_refresh_time']) 
            {
                $userInfo=MailModel::getUserInfo($user_id);
                $thewArr['n_refresh_time']=$userInfo['n_refresh_time'];
            }
            $getArr=UserAction::refreshThew($thewArr);
            if (!$getArr) 
            {
                $res=CharacterModel::update($thewArr,array('n_id'=>$user_id));
                UserCache::setByKey($user_id,'n_thew',$thewArr['n_thew']);
                UserCache::setByKey($user_id,'n_refresh_time',$thewArr['n_refresh_time']);
            }
            else
            {
                $res=CharacterModel::update($getArr,array('n_id'=>$user_id));
                UserCache::setByKey($user_id,'n_thew',$getArr['n_thew']);
                UserCache::setByKey($user_id,'n_refresh_time',$getArr['n_refresh_time']);
            }
        }
        else
        {
            $res=CharacterModel::update(array($type=>$money),array('n_id'=>$user_id));
            UserCache::setByKey($user_id,$type,$money);
        }
        
         //任务成就统计
        if ($type==$price_type[1]) 
        {           
            TaskAndAchieveAction::taskStatistic($user_id,array('reward'=>$mailInfo['n_item_num']));
        }        
        if ($type==$price_type[3]) 
        {           
            //TaskAndAchieveAction::taskStatistic($user_id,array('soul'=>$mailInfo['n_item_num']));
            TaskAndAchieveAction::achieveStatistic($user_id,array('soul'=>$mailInfo['n_item_num']));
        }

        foreach ($price_type as $key => $value) 
        {
            $moneyArr[$value]=UserCache::getByKey($user_id,$value);
            if (!$moneyArr[$value]) 
            {
                $userInfo=MailModel::getUserInfo($user_id);
                $moneyArr[$value]=$userInfo[$value];
            }
        }
        $messageArr['moneyInfo']=$moneyArr;

        //任务成就界面
        $messageArr['achieveInfo']=TaskAndAchieveAction::getAchieveInfo($user_id);
        $messageArr['taskInfo']=TaskAndAchieveAction::getTaskInfo($user_id);

        $messageArr['friend_mail']=MailModel::getFriendMail($user_id);
        $messageArr['system_mail']=MailModel::getSystemMail($user_id);
        $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
        $view = new JsonView();
        return $this->getViewByJson( $view, $messageArr, 1,"mail/update_one_mail" );
   

    }

    /**
     * API：接受所有邮件
     *
     * @access public
     * @param 无
     * @return JsonView 响应json
     */
    public function exeUpdateAllMail()
    {
    	$requestParam = $this->getAllParameters();
        Logger::debug('requestParam:'. print_r($requestParam, true));

        $requestJsonParam = $this->getDecodedJsonRequest();
        Logger::debug('requestJsonParam:'. print_r($requestJsonParam, true));

        $user_id=$requestParam['user_id'];
        $session_key=$requestParam['session_key'];

        $mailInfo=MailModel::getSystemMail($user_id);
        $userInfo = UserCache::getAllUserCache( $user_id);
        if( !$userInfo )
        {
            $userInfo = UserAction::iniUserInfo( $user_id );
        }
        if (!$mailInfo) 
        {
            $messageArr['error']="没有系统邮件";
            $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
            $view = new JsonView();
            return $this->getViewByJson( $view, $messageArr, 0,"mail/update_all_mail" );
        }
        $price_type=$this->price_type;
        foreach ($mailInfo as $key => $value) 
        {
        	foreach ($price_type as $key2 => $value2) 
        	{
        		if ($key2==$value['n_item_type']) 
        		{
                    $userInfo[$value2]=$userInfo[$value2]+$value['n_item_num'];
                    if ($key2==4) 
                    {
                        $n_thew=$userInfo[$value2];
                    }
                    else
                    {
                        $newInfo[$value2]=$userInfo[$value2];
                    }
                    
			        if ($key2==1) 
			        {           
			            $n_coin=$n_coin+$value['n_item_num'];
			        }        
			        if ($key2==3) 
			        {           
			            $n_soul=$n_soul+$value['n_item_num'];
			        }
        		}
        	}
        }

        if ($n_thew) 
        {
            $thewArr['n_thew']=$n_thew;
            $thewArr['n_refresh_time']=UserCache::getBykey($user_id,'n_refresh_time');
            if (!$thewArr['n_refresh_time']) 
            {
                $userInfo=MailModel::getUserInfo($user_id);
                $thewArr['n_refresh_time']=$userInfo['n_refresh_time'];
            }
            $getArr=UserAction::refreshThew($thewArr);
            if (!$getArr) 
            {
                $res=CharacterModel::update($thewArr,array('n_id'=>$user_id));
                UserCache::setByKey($user_id,'n_thew',$thewArr['n_thew']);
                UserCache::setByKey($user_id,'n_refresh_time',$thewArr['n_refresh_time']);
            }
            else
            {
                $res=CharacterModel::update($getArr,array('n_id'=>$user_id));
                UserCache::setByKey($user_id,'n_thew',$getArr['n_thew']);
                UserCache::setByKey($user_id,'n_refresh_time',$getArr['n_refresh_time']);
            }
        }
	    //任务成就统计
	    TaskAndAchieveAction::taskStatistic($user_id,array('reward'=>$n_coin));
        //TaskAndAchieveAction::taskStatistic($user_id,array('soul'=>$n_soul));
        TaskAndAchieveAction::achieveStatistic($user_id,array('soul'=>$n_soul));

        $res=MailModel::update(array('n_type'=>0),array('n_receive_id'=>$user_id));
        $res=CharacterModel::update($newInfo,array('n_id'=>$user_id));

        foreach ($newInfo as $key => $value) 
        {
            UserCache::setByKey($user_id,$key,$value);
        }

        foreach ($price_type as $key => $value) 
        {
            $moneyArr[$value]=UserCache::getByKey($user_id,$value);
            if (!$moneyArr[$value]) 
            {
                $userInfo=MailModel::getUserInfo($user_id);
                $moneyArr[$value]=$userInfo[$value];
            }
        }
        $messageArr['moneyInfo']=$moneyArr;

        //任务成就界面
        $messageArr['achieveInfo']=TaskAndAchieveAction::getAchieveInfo($user_id);
        $messageArr['taskInfo']=TaskAndAchieveAction::getTaskInfo($user_id);
        
        $messageArr['friend_mail']=MailModel::getFriendMail($user_id);
        $messageArr['system_mail']=MailModel::getSystemMail($user_id);
        $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
        $view = new JsonView();
        return $this->getViewByJson( $view, $messageArr, 1,"mail/update_all_mail" );
   
    }


    /**
     * 所有邮件数量
     *
     * @access public
     * @param 无
     * @return JsonView 响应json
     */
    public function getMailNum($user_id)
    {
        $friend_mail=MailModel::getFriendMail($user_id);
        $system_mail=MailModel::getSystemMail($user_id);
        $mail_num=count($friend_mail)+count($system_mail);
        return $mail_num;
    }


    /**
     * 发送系统邮件奖励
     *
     * @access public
     * @param 无
     * @return JsonView 响应json
     */
    public function sendSystemMail($user_id)
    {
        $str1="欢迎来到大划西游，首次登陆赠送50钻石！";
        $str2="欢迎来到大划西游，首次登陆赠送3000人参果！";
        // $str3="欢迎来到一路向嘻，首次登陆赠送99人生果！";
        // $str4="欢迎来到一路向嘻，首次登陆赠送50体力！";
        
        $res=MailModel::insert(array('n_send_id'=>0,'n_receive_id'=>$user_id,'s_message'=>$str1,'n_item_type'=>2,'n_item_num'=>50,'n_type'=>1,'t_update_time'=>date("Y-m-d H:i:s"),'t_create_time'=>date("Y-m-d H:i:s")));
        $res=MailModel::insert(array('n_send_id'=>0,'n_receive_id'=>$user_id,'s_message'=>$str2,'n_item_type'=>1,'n_item_num'=>3000,'n_type'=>1,'t_update_time'=>date("Y-m-d H:i:s"),'t_create_time'=>date("Y-m-d H:i:s")));
        // $res=MailModel::insert(array('n_send_id'=>0,'n_receive_id'=>$user_id,'s_message'=>$str1,'n_item_type'=>1,'n_item_num'=>99,'n_type'=>1,'t_update_time'=>date("Y-m-d H:i:s"),'t_create_time'=>date("Y-m-d H:i:s")));
        // $res=MailModel::insert(array('n_send_id'=>0,'n_receive_id'=>$user_id,'s_message'=>$str2,'n_item_type'=>4,'n_item_num'=>50,'n_type'=>1,'t_update_time'=>date("Y-m-d H:i:s"),'t_create_time'=>date("Y-m-d H:i:s")));
    }


    /**
     * 结算发送合体奖励邮件
     *
     * @access public
     * @param 无
     * @return JsonView 响应json
     */
    public function sendFitMail($user_id,$friend_id)
    {
        $user_name=UserCache::getByKey($user_id,"s_name");
        if (!$friend_id) 
        {
            $userInfo=MailModel::getUserInfo($user_id);
            $user_name=$userInfo['s_name'];
        }
        $type=Constants::FIT_REWARD_TYPE;
        $coin=Constants::FIT_REWARD_NUM;
        $str="你的好友".$user_name."邀你助战，你获得人生果".$coin;
        $res=MailModel::insert(array('n_send_id'=>$user_id,'n_receive_id'=>$friend_id,'s_message'=>$str,'n_item_type'=>$type,'n_item_num'=>$coin,'n_type'=>1,'t_update_time'=>date("Y-m-d H:i:s"),'t_create_time'=>date("Y-m-d H:i:s")));
    }
}