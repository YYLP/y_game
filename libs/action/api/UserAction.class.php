<?php
/**
 * Game类
 *
 *
 * @access public
 * @author lijunhua
 * @copyright Copyright(C) Tenone Inc.
 * @version 0.01
 * @since 2015/01/15
 */

class UserAction extends BaseAction {

    const LOGIN_FILE = "login_value";

    private static  $generalCoffcion= array(
            1 => 2,
            2 => 2.5,
            3 => 3,
            4 => 3.5
        );

    /**
     * API：获取解锁关卡基本信息
     *
     * @access public
     * @param 无
     * @return JsonView 响应json
     */
    public function exeGetUserInfo()
    {
        $requestParam = $this->getAllParameters();
        Logger::debug('requestParam:'. print_r($requestParam, true));

        $requestJsonParam = $this->getDecodedJsonRequest();
        Logger::debug('requestJsonParam:'. print_r($requestJsonParam, true));

        // 获取用户表信息
        UserCache::deleteAllUserCache( $requestParam['user_id'] );
        $userInfo = UserCache::getAllUserCache( $requestParam['user_id'] );

        if( !$userInfo )
        {
            $userInfo = self::iniUserInfo( $requestParam['user_id'] );
        }

        // 检测体力
        $checkThewArr['n_thew'] = $userInfo['n_thew'];
        $checkThewArr['n_refresh_time'] = $userInfo['n_refresh_time'];

        $checkArr = self::refreshThew( $checkThewArr );
        if( $checkArr )
        {
            UserModel::update( $checkArr, $user = array( 'n_id' => $requestParam['user_id'] ), $pdo );
            UserCache::setByKey( $requestParam['user_id'], 'n_thew', $checkArr['n_thew'] );
            UserCache::setByKey( $requestParam['user_id'], 'n_refresh_time', $checkArr['n_refresh_time'] );
            $userInfo['n_thew'] = $checkArr['n_thew'];
            $userInfo['n_refresh_time'] = $checkArr['n_refresh_time'];
        }

        $nowTime = time();
        
        // 生成缓存
        $newSessionKey = Util::generateSessionKey($requestParam['user_id']);
        $oldSessionKey = $requestParam['session_key'];
        Logger::debug('SessionKey1:'. $oldSessionKey);
        Logger::debug('SessionKey2:'. $newSessionKey);

        UserCache::setByKey($requestParam['user_id'], Constants::PREVIOUS_SESSION_KEY, $oldSessionKey);
        UserCache::setByKey($requestParam['user_id'], Constants::CURRENT_SESSION_KEY, $newSessionKey);
        //UserCache::setByKey($user_id, 'userInfo', $userInfo);

        $messageArr=CharacterAction::getAllMessage($requestParam['user_id']);

        $messageArr['bullent'] = SystemAction::getBulletin();
        $messageArr['big_message'] = SystemAction::getBigMessage();
        $messageArr['s_login_info'] = $userInfo['s_login_info'];

        $messageArr['user_info']['s_name'] = $userInfo['s_name'];
        $messageArr['user_info']['n_level'] = $userInfo['n_level'];
        $messageArr['user_info']['f_experience'] = $userInfo['f_experience'];
        $messageArr['user_info']['n_coin'] = $userInfo['n_coin'];
        $messageArr['user_info']['n_diamond'] = $userInfo['n_diamond'];
        $messageArr['user_info']['n_soul'] = $userInfo['n_soul'];
        //$messageArr['user_info']['n_reward'] = $userInfo['n_reward'];
        $messageArr['user_info']['n_head'] = $userInfo['n_head'];
        $messageArr['user_info']['n_thew'] = $userInfo['n_thew'];
        $messageArr['user_info']['n_refresh_time'] = $userInfo['n_refresh_time'];
        $messageArr['user_info']['server_time'] = $nowTime;
        $messageArr['user_info']['time_num'] = Constants::REFRESH_THEW_TIME;
        $messageArr['user_info']['n_battle'] = $userInfo['n_battle'];

        //$messageArr['user_info']['s_role_info'] = $userInfo['s_role_info'];
        //$messageArr['user_info']['s_general_info'] = $userInfo['s_general_info'];

        $messageArr['role_id'] = $userInfo['s_role_info'][0];
        $messageArr['item'] = $userInfo['s_item_info']; 

        $messageArr['id'] = $requestParam['user_id'];
        //$messageArr = $userInfo;
        $messageArr['session_key'] = $newSessionKey;

        $view = new JsonView();
        return $this->getViewByJson( $view, $messageArr, 1,"system/get_bulletin" );
    }


    /**
     * API：用户签到
     *
     * @access public
     * @param 无
     * @return JsonView 响应json
     */
    public function exeLogin()
    {
        $requestParam = $this->getAllParameters();
        Logger::debug('requestParam:'. print_r($requestParam, true));

        $requestJsonParam = $this->getDecodedJsonRequest();
        Logger::debug('requestJsonParam:'. print_r($requestJsonParam, true));

        // 获取用户表信息
        $userInfo = UserCache::getAllUserCache( $requestParam['user_id'] );
        if( !$userInfo )
        {
            $userInfo = self::iniUserInfo( $requestParam['user_id'] );
        }

        if( $userInfo['s_login_info']['type'] == 0 )
        {
            $rewardTypeArr = array( 1 => 'n_coin', 
                                    2 => 'n_diamond',
                                    3 => 'n_thew',
                                    4 => 'n_soul');  

            /*$reward = $userInfo['s_login_info']['reward'];
            $updateArr[$rewardTypeArr[$reward['type']]] = $userInfo[$rewardTypeArr[$reward['type']]] = $userInfo[$rewardTypeArr[$reward['type']]] + $reward['num'];
            $userInfo['s_login_info']['type'] = 1;
            $updateArr['s_login_info'] = serialize( $userInfo['s_login_info'] );*/

            $userInfo['s_login_info']['con_day'] = $userInfo['s_login_info']['con_day'] + 1;
            $userInfo['s_login_info']['total_day'] = $userInfo['s_login_info']['total_day'] + 1;

            // 非连续签到
            if( strtotime( date( 'Y-m-d',time() ) ) - $userInfo['s_login_info']['time'] > 86400 )
            {
                $userInfo['s_login_info']['con_day'] = 1;
            }
            $userInfo['s_login_info']['time'] = time();

            $rewardNum = $userInfo['s_login_info']['rewardArr']['check'];
            $reward = $userInfo['s_login_info']['rewardArr']['box_info'][$rewardNum];

            $updateArr[$rewardTypeArr[$reward['type']]] = $userInfo[$rewardTypeArr[$reward['type']]] = $userInfo[$rewardTypeArr[$reward['type']]] + $reward['num'];
            $userInfo['s_login_info']['type'] = 1;
            $updateArr['s_login_info'] = serialize( $userInfo['s_login_info'] );

            // 成就
            if ($reward['type']==1) 
            {
                $statisticArr['reward'] = $reward['num'];
                TaskAndAchieveAction::taskStatistic( $requestParam['user_id'], $statisticArr );
            }
            elseif ($reward['type']==4) 
            {
                $statisticArr['soul'] = $reward['num'];
            }
            $statisticArr['login_day'] = 1;
            TaskAndAchieveAction::achieveStatistic( $requestParam['user_id'], $statisticArr );

            UserModel::update($updateArr,array('n_id'=>$requestParam['user_id']));
            UserCache::setByKey( $requestParam['user_id'], $rewardTypeArr[$reward['type']], $userInfo[$rewardTypeArr[$reward['type']]] );
            UserCache::setByKey( $requestParam['user_id'], 's_login_info', $userInfo['s_login_info'] );
        }
        else
        {
            $messageArr['error']="今天已签到";
            $view = new JsonView();
            return $this->getViewByJson( $view, $messageArr, 0,"user/login" );
        }

        // 生成缓存
        $newSessionKey = Util::generateSessionKey($requestParam['user_id']);
        $oldSessionKey = $requestParam['session_key'];
        Logger::debug('SessionKey1:'. $oldSessionKey);
        Logger::debug('SessionKey2:'. $newSessionKey);

        UserCache::setByKey($requestParam['user_id'], Constants::PREVIOUS_SESSION_KEY, $oldSessionKey);
        UserCache::setByKey($requestParam['user_id'], Constants::CURRENT_SESSION_KEY, $newSessionKey);
        //UserCache::setByKey($user_id, 'userInfo', $userInfo);
 
        $messageArr['n_id'] = $requestParam['user_id'];
        //$messageArr = $userInfo;
        $messageArr['n_coin'] = $userInfo['n_coin'];
        $messageArr['n_diamond'] = $userInfo['n_diamond'];
        $messageArr['n_soul'] = $userInfo['n_soul'];
        $messageArr['n_thew'] = $userInfo['n_thew'];
        //$messageArr['n_reward'] = $userInfo['n_reward'];

        //任务成就界面
        $messageArr['achieveInfo']=TaskAndAchieveAction::getAchieveInfo($requestParam['user_id']);
        $messageArr['taskInfo']=TaskAndAchieveAction::getTaskInfo($requestParam['user_id']);
        $messageArr['session_key'] = $newSessionKey;

        $view = new JsonView();
        return $this->getViewByJson( $view, $messageArr, 1,"user/login" );
    }

    /**
     * API：获取签到信息
     *
     * @access public
     * @param mixed $login_info 签到缓存信息
     * @return $loginArr 返回签到信息数组
     */
    public function getUserLoginInfo( $login_info="" )
    {
        // 无初始数据
        if( $login_info == "" )
        {
            $loginArr['con_day'] = 0;
            $loginArr['total_day'] = 0;
            // $loginArr['time'] = time();
            $loginArr['type'] = 0;
        }
        else
        {
            // 非当天首次登陆
            if( $login_info['time'] > strtotime( date( 'Y-m-d',time() ) ) )
            {
                return false;
            }

            $loginArr['con_day'] = $login_info['con_day'];
            $loginArr['total_day'] = $login_info['total_day'];
            //$loginArr['time'] = time();
            $loginArr['type'] = 0;

            // // 非连续签到
            // if( strtotime( date( 'Y-m-d',time() ) ) - $login_info['check_time'] > 86400 )
            // {
            //     $loginArr['con_day'] = 1;
            // }
        }

        // 获取签到奖励内容
        $cache = MemcacheManager::instance();
        $memcacheArr = $cache->get( self::LOGIN_FILE );

        if( $memcacheArr )
        {
            $messageArr = $memcacheArr;
        }
        else
        {
            // 读取csv类
            $dir = IniFileManager::getRootDir() . "files/csv/" . self::LOGIN_FILE . ".csv";
            /*$str = "chest_id = " . 1;
            $messageArr = Util::readCsv( $dir, $str );*/

            $csv = new Parsecsv();
            $csv->auto( $dir );
            $messageArr = $csv->data;
            /*echo "<pre>";var_dump($messageArr);exit;

            $loginArr['reward'] = $messageArr[0];*/
        }

        /********************* 更新奖励列表 *****************/
        // 统计箱子个数
        $num = 1;
        foreach ( $messageArr as $key => $value ) {
            if( $value['chest_id'] == $messageArr[ $key + 1 ]['chest_id'] )
            {
                $rewardNumArr[$value['chest_id']] = ++$num;
            }
            else
            {
                $num = 1;
            }
        }
        
        // 随机箱子内容
        $num = 0;
        foreach ( $rewardNumArr as $key => $value ) {
            $rewardArr[$key] = $messageArr[$num + rand( 0, $value - 1 )];
            $probabilityArr[$key] = $rewardArr[$key]['probability'];
            $num = $num + $value;
        }

        $checkNum = Util::extractRandomAnswer( $probabilityArr );
        $loginArr['rewardArr']['check'] = $checkNum;
        $loginArr['rewardArr']['box_info'] = $rewardArr;

        return $loginArr;
    }

    /**
     * API：初始化用户数据
     *
     * @access public
     * @param 无
     * @return JsonView 响应json
     */
    public function iniUserInfo( $user_id )
    {
        $userInfo = UserModel::getUserInfo( $user_id );
        $userInfo['s_checkpoint_info'] = unserialize( $userInfo['s_checkpoint_info'] );
        $userInfo['s_role_info'] = unserialize( $userInfo['s_role_info'] );
        $userInfo['s_general_info'] = unserialize( $userInfo['s_general_info'] );
        $userInfo['s_item_info'] = unserialize( $userInfo['s_item_info'] );
        $userInfo['s_task_info'] = unserialize( $userInfo['s_task_info'] );
        $userInfo['s_achievement_info'] = unserialize( $userInfo['s_achievement_info'] );
        $userInfo['s_login_info'] = unserialize( $userInfo['s_login_info'] );

        UserCache::setAllUserCache( $user_id, $userInfo );
        return $userInfo;
    }    


    /**
     * API：更新体力
     *
     * @access public
     * @param 无
     * @return JsonView 响应json
     */
    public function refreshThew( $userInfo )
    {
        $thew = $userInfo['n_thew'];
        $refresh_time = $userInfo['n_refresh_time'];

        if( $thew >= Constants::USER_MAX_THEW && $refresh_time != null )
        {
            $userInfo['n_refresh_time'] = null;
            return $userInfo;
        }

        $addThew = $refresh_time != null ? (int)( ( time() - $refresh_time ) / ( 60 * Constants::REFRESH_THEW_TIME ) ) : 0;
        $thew = $thew + $addThew;

        if( $addThew > 0 )
        {
            if( $thew >= Constants::USER_MAX_THEW )
            {
                $userInfo['n_thew'] = Constants::USER_MAX_THEW;
                $userInfo['n_refresh_time'] = null;
            }
            else
            {
                $userInfo['n_thew'] = $thew;
                $userInfo['n_refresh_time'] = $userInfo['n_refresh_time'] + $addThew * 60 * Constants::REFRESH_THEW_TIME;
            }
        }
        else
        {
            return false;
        }       
        
        return $userInfo;
    }    


    /**
     * API：获取用户战斗力
     *
     * @access public
     * @param mixed 
     * @return $loginArr 
     */
    public function getUserBattle( $user_id )
    {
        $generalBattle=GeneralAction::GetGeneralBattle($user_id);
        $CharacterBattle=CharacterAction::getCharacterBattle($user_id);
        $battle=$CharacterBattle+$generalBattle;
        return $battle;
    }
}