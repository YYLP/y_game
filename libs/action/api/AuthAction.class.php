<?php
/**
 * Auth类
 *
 *
 * @access public
 * @author chenwenbin
 * @copyright Copyright(C) Tenone Inc.
 * @version 0.01
 * @since 2014/11/13
 */

class AuthAction extends BaseAction {

    const API_NAME_USER_REGISTER = "auth/user_register";

    private static $userBasicIni = array( 'n_head'           => Constants::USER_HEAD_INI,
                                          'n_coin'           => Constants::USER_COIN_INI,
                                          'n_diamond'        => Constants::USER_DIAMOND_INI,
                                          'n_thew'           => Constants::USER_THEW_INI,
                                          'n_refresh_time'   => null,
                                          'n_soul'           => Constants::USER_SOUL_INI,
                                          'f_experience'     => Constants::USER_EXPERIENCE, 
                                          'n_level'          => Constants::USER_LEVEL,
                                          'n_battle'         => Constants::USER_BATTLE, 
                                          'n_max_checkpoint' => Constants::USER_MAX_CHECKPOINT
                                        );


    //const PARAM_1 = 'user_id';
    
    //const PARAM_2 = 'type';

    /**
     * API：用户注册
     *
     * @access public
     * @param 无
     * @return JsonView 响应json
     */
    public function exeUserRegister()
    {        
        $requestParam = $this->getAllParameters();
        Logger::debug('requestParam:'. print_r($requestParam, true));

        $requestJsonParam = $this->getDecodedJsonRequest();
        Logger::debug('requestJsonParam:'. print_r($requestJsonParam, true));

        // 检测账号是否重复
        $ret = AuthModel::checkUserAccount( $requestJsonParam['account'] );
        if( !$ret )
        {
            $view = new JsonView();
            $messageArr['error']="该账号已存在";
            return $this->getViewByJson( $view, $messageArr, 0,"auth/user_register" );
        }

        // 检测账号是否重复
        $ret = AuthModel::checkUserName( $requestJsonParam['user_name'] );
        if( !$ret )
        {
            $view = new JsonView();
            $messageArr['error']="该昵称已存在";
            return $this->getViewByJson( $view, $messageArr, 0,"auth/user_register" );
        }

        // 插入新用户
        $ret = AuthModel::insert(array('s_account'=>$requestJsonParam['account'],'s_password'=>$requestJsonParam['password'],'t_create_time'=>date("Y-m-d H:i:s")), $pdo);
        if(is_null($ret))
        {
            throw new ModelException( 'insert false pa_user_master' );
        }


        //---------------------------- 初始用户表 ----------------------------
        $user_id = AuthModel::getUserID( $requestJsonParam['account'], $requestJsonParam['password'], $pdo );

        $messageArr['n_id'] = $user_id;

        self::$userBasicIni['s_name'] = $requestJsonParam['user_name'];
        self::$userBasicIni['n_sex'] = $requestJsonParam['sex'];
        self::$userBasicIni['t_create_time'] = date( "Y-m-d H:i:s" );

        // 初始化关卡信息

        $checkPointIni = array( 1 => array( 'score'      => 0, 
                                            'scr_length' => 0, 
                                            'reward'     => 0, 
                                            'kill_num'   => 0, 
                                            'star_num'   => 0 ) );
        //self::$userBasicIni['s_checkpoint_info'] = serialize( $checkPointIni );

        // 初始化角色信息
        self::$userBasicIni['s_role_info '] = CharacterAction::registCharacter( $user_id, Constants::INI_CHARACTER_ID );

        // 初始化武将信息
        self::$userBasicIni['s_general_info'] = GeneralAction::registGeneral( $user_id, Constants::INI_GENERAL_ID );

        // 初始化任务信息
        self::$userBasicIni['s_task_info'] = serialize( TaskAndAchieveAction::randTask( $user_id ) );

        // 初始化成就信息
        self::$userBasicIni['s_achievement_info'] = TaskAndAchieveAction::registAchieveCsv( $user_id );
        
        // 初始化签到信息
        $loginInfo=UserAction::getUserLoginInfo();
        self::$userBasicIni['s_login_info'] = serialize( $loginInfo );

        $userInfo = array_merge( $messageArr, self::$userBasicIni );

        $ret = UserModel::insert( $userInfo, $pdo );
        FriendModel::insert(array('n_user_id'=>$user_id,'n_friend_id'=>Constants::ROBERT,'t_create_time'=>date("Y-m-d H:i:s")));
        $battle=UserAction::getUserbattle($user_id);
        UserModel::update( array('n_battle'=>$battle),array('n_id'=>$user_id) );
        MailAction::sendSystemMail($user_id);

        if(is_null($ret))
        {
            throw new ModelException( 'insert false pa_user_master' );
        }

        // // 成就
        // $statisticArr['login_day'] = 1;
        // TaskAndAchieveAction::achieveStatistic( $user_id, $statisticArr );

        $userInfo = array_merge( $messageArr, self::$userBasicIni );
        $session_key=CharacterModel::setSessionKey($user_id,$session_key);
        $messageArr['total_day']=$loginInfo['total_day'];
        $messageArr['session_key'] = $session_key;
        $view = new JsonView();
        return $this->getViewByJson( $view, $messageArr, 1,"auth/user_register" );
    }

    /**
     * API：用户登陆
     *
     * @access public
     * @param 无
     * @return JsonView 响应json
     */
    public function exeUserLogin()
    {
        $requestParam = $this->getAllParameters();
        Logger::debug('requestParam:'. print_r($requestParam, true));

        $requestJsonParam = $this->getDecodedJsonRequest();
        Logger::debug('requestJsonParam:'. print_r($requestJsonParam, true));

        // 获取用户id
        $user_id = AuthModel::getUserID( $requestJsonParam['account'], $requestJsonParam['password'], $pdo );
        if( !$user_id )
        {
            $view = new JsonView();
            $messageArr['error']="密码不正确，请重新输入";
            return $this->getViewByJson( $view, $messageArr, 0, "auth/user_login" );
        }

        // 初始化缓存
        $userInfo = UserAction::iniUserInfo( $user_id );
        $loginInfo = UserAction::getUserLoginInfo( $userInfo['s_login_info'] );

        if( $loginInfo != false )
        {
            $taskInfo = TaskAndAchieveAction::randTask( $user_id );
            $updateArr['s_login_info'] = serialize( $loginInfo );
            $updateArr['s_task_info'] = serialize( $taskInfo );

            UserCache::setByKey( $user_id, 's_login_info', $loginInfo );
            UserCache::setByKey( $user_id, 's_task_info', $taskInfo );
            $userInfo['s_login_info'] = $loginInfo;
            $userInfo['s_task_info'] = $taskInfo;

            UserModel::update( $updateArr, $user = array( 'n_id' => $user_id ), $pdo );

            //清零合体次数
            FriendModel::clearFitNum($user_id);
            // // 成就
            // $statisticArr['login_day'] = 1;
            // TaskAndAchieveAction::achieveStatistic( $user_id, $statisticArr );

        }

        //$user_id = $requestParam['user_id'];
        //$userInfo = AuthModel::getUserInfo( $user_id, $pdo );

        // 生成缓存
        $newSessionKey = Util::generateSessionKey($user_id);
        $oldSessionKey = $requestParam['session_key'];
        Logger::debug('SessionKey1:'. $oldSessionKey);
        Logger::debug('SessionKey2:'. $newSessionKey);

        UserCache::setByKey($user_id, Constants::PREVIOUS_SESSION_KEY, $oldSessionKey);
        UserCache::setByKey($user_id, Constants::CURRENT_SESSION_KEY, $newSessionKey);
        //UserCache::setByKey($user_id, 'userInfo', $userInfo);

        //$messageArr['user'] = $userInfo;
        $messageArr['n_id'] = $user_id;
        $messageArr['total_day']=$userInfo['s_login_info']['total_day'];
        $messageArr['session_key'] = $newSessionKey;

        $view = new JsonView();
        return $this->getViewByJson( $view, $messageArr, 1,"auth/user_login" );
    }


    /**
     * API：用户登陆
     *
     * @access public
     * @param 无
     * @return JsonView 响应json
     */
    public function exeQuickRegister()
    {
        // 检测账号是否重复
        /*$ret = AuthModel::checkUserAccount( $requestJsonParam['account'] );
        if( !$ret )
        {
            $view = new JsonView();
            return $this->getViewByJson( $view, '', 0,"auth/user_register" );
        }

        // 插入新用户
        $ret = AuthModel::insert(array('s_account'=>$requestJsonParam['account'],'s_password'=>$requestJsonParam['password'],'t_create_time'=>date("Y-m-d H:i:s")), $pdo);
        if(is_null($ret))
        {
            throw new ModelException( 'insert false pa_user_master' );
        }


        //---------------------------- 初始用户表 ----------------------------
        $user_id = AuthModel::getUserID( $requestJsonParam['account'], $requestJsonParam['password'], $pdo );

        $messageArr['n_id'] = $user_id;

        self::$userBasicIni['s_name'] = $requestJsonParam['user_name'];
        self::$userBasicIni['n_sex'] = $requestJsonParam['sex'];
        self::$userBasicIni['t_create_time'] = date( "Y-m-d H:i:s" );

        // 初始化关卡信息

        $checkPointIni = array( 1 => array( 'score'      => 0, 
                                            'scr_length' => 0, 
                                            'reward'     => 0, 
                                            'kill_num'   => 0, 
                                            'star_num'   => 0 ) );
        self::$userBasicIni['s_checkpoint_info'] = serialize( $checkPointIni );

        $userInfo = array_merge( $messageArr, self::$userBasicIni );

        $ret = UserModel::insert( $userInfo, $pdo );
        if(is_null($ret))
        {
            throw new ModelException( 'insert false pa_user_master' );
        }

        // 初始化角色信息

        // 初始化武将信息

        // 初始化道具信息

        // 初始化任务信息

        // 初始化成就信息

        $userInfo = array_merge( $messageArr, self::$userBasicIni );

        $ret = UserModel::insert( $userInfo, $pdo );
        if(is_null($ret))
        {
            throw new ModelException( 'insert false pa_user_master' );
        }

        $view = new JsonView();
        return $this->getViewByJson( $view, $messageArr, 1,"auth/user_register" );*/
        self::exeUserRegister();
        //$result = self::exeUserLogin();
        
        //$view = new JsonView();
        return $result;
    }
}