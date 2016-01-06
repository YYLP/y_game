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

class GameAction extends BaseAction {

    const CHECKPOINT_LIST = "checkpoint_basic_list";

    private static $price_type= array(
            1 => 'n_coin',
            2 => 'n_diamond',
            3 => 'n_soul',
            4 => 'n_thew'
        );

    /**
     * API：获取解锁关卡基本信息
     *
     * @access public
     * @param 无
     * @return JsonView 响应json
     */
    public function exeGetCheckpoint()
    {
        $requestParam = $this->getAllParameters();
        Logger::debug('requestParam:'. print_r($requestParam, true));

        $requestJsonParam = $this->getDecodedJsonRequest();
        Logger::debug('requestJsonParam:'. print_r($requestJsonParam, true));

        // 用户关卡信息
        $userMaxCheckPoint = UserCache::getByKey( $requestParam['user_id'], 'n_max_checkpoint');
        $userCheckPointInfo = UserCache::getByKey( $requestParam['user_id'], 's_checkpoint_info');

        if( !$userCheckPointInfo )
        {
            $userInfo = UserModel::getUserInfo( $requestParam['user_id'] );
            UserCache::setByKey( $requestParam['user_id'], 's_checkpoint_info',$userInfo['s_checkpoint_info']);
            $userCheckPointInfo = unserialize( $userInfo['s_checkpoint_info'] );
        }

        // 生成缓存
        $newSessionKey = Util::generateSessionKey($requestParam['user_id']);
        $oldSessionKey = $requestParam['session_key'];
        Logger::debug('SessionKey1:'. $oldSessionKey);
        Logger::debug('SessionKey2:'. $newSessionKey);

        UserCache::setByKey($requestParam['user_id'], Constants::PREVIOUS_SESSION_KEY, $oldSessionKey);
        UserCache::setByKey($requestParam['user_id'], Constants::CURRENT_SESSION_KEY, $newSessionKey);

        $messageArr = $userCheckPointInfo;
        $messageArr['session_key'] = $newSessionKey;

        $view = new JsonView();
        return $this->getViewByJson( $view, $messageArr, 1,"game/check_point" );
    }

    /**
     * API：开始游戏
     *
     * @access public
     * @param 无
     * @return JsonView 响应json
     */
    public function exeStartGame()
    {
        $requestParam = $this->getAllParameters();
        Logger::debug('requestParam:'. print_r($requestParam, true));

        $requestJsonParam = $this->getDecodedJsonRequest();
        Logger::debug('requestJsonParam:'. print_r($requestJsonParam, true));

        // 获取体力
        $userInfo = UserCache::getAllUserCache( $requestParam['user_id'] );
        if( !$userInfo )
        {
            $userInfo = UserAction::iniUserInfo( $requestParam['user_id'] );
        }

        $userThew = $userInfo['n_thew'];

        // 更新用户体力
        $nowThew = $userThew - 1;
        if( $nowThew < 0 )
        {
            $view = new JsonView();
            $messageArr['error']="体力不足";
            return $this->getViewByJson( $view, $messageArr, 0,"game/start_game" );
        }
        else if( $userThew == Constants::USER_MAX_THEW )
        {
            $recordArr['n_refresh_time'] = $userInfo['n_refresh_time'] = time();           
        }

        // 购买一次性道具
        if( $requestJsonParam['propArr'] && count( $requestJsonParam['propArr'] ) )
        {
            foreach ($requestJsonParam['propArr'] as $key => $item_id) {
                $result = BuyPropAction::buyProp( $requestParam['user_id'], $item_id );
                if( $result == false )
                {
                    $view = new JsonView();
                    $messageArr['error']="人生果不足";
                    return $this->getViewByJson( $view, $messageArr, 0,"game/start_game" );
                }
            }
        }

        $recordArr['n_thew'] = $nowThew;
        $wheresArr['n_id'] = $requestParam['user_id'];
        UserCache::setByKey( $requestParam['user_id'], 'n_thew', $nowThew );
        UserCache::setByKey( $requestParam['user_id'], 'n_refresh_time', $userInfo['n_refresh_time'] );
        UserModel::update( $recordArr, $wheresArr );

        // 关卡基本信息
        /*$dir = IniFileManager::getRootDir() . "files/csv/" . self::CHECKPOINT_LIST . ".csv";
        $str = "checkpoint_id = " . $requestJsonParam['checkpoint_id'];
        $checkpointArr = Util::readCsv( $dir, $str );

        if( empty( $checkpointArr ) )
        {
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
        $messageArr['sequence'] = $sequence; */       

        // 生成缓存
        $newSessionKey = Util::generateSessionKey($requestParam['user_id']);
        $oldSessionKey = $requestParam['session_key'];
        Logger::debug('SessionKey1:'. $oldSessionKey);
        Logger::debug('SessionKey2:'. $newSessionKey);

        UserCache::setByKey($requestParam['user_id'], Constants::PREVIOUS_SESSION_KEY, $oldSessionKey);
        UserCache::setByKey($requestParam['user_id'], Constants::CURRENT_SESSION_KEY, $newSessionKey);

        $messageArr['session_key'] = $newSessionKey;

        $messageArr['n_thew']=$nowThew;
        $messageArr['n_refresh_time'] = $userInfo['n_refresh_time'];
        $messageArr['server_time'] = time();
        $messageArr['time_num'] = Constants::REFRESH_THEW_TIME;

        $view = new JsonView();
        return $this->getViewByJson( $view, $messageArr, 1,"game/start_game" );
    }

    /**
     * API：游戏结算
     *
     * @access public
     * @param 无
     * @return JsonView 响应json
     * {"scoreInfo":{"combo":[5,3,4],"award":10,"deduction":[3,2],"kill":{"monster":5,"boss":2}},"checkpoint_id":3,"scr_length":5,"star_num":5,"diamond":2,"pass":1}
     */
    public function exeEndGame()
    {
        $requestParam = $this->getAllParameters();
        Logger::debug('requestParam:'. print_r($requestParam, true));

        $requestJsonParam = $this->getDecodedJsonRequest();
        Logger::debug('requestJsonParam:'. print_r($requestJsonParam, true));

//------------------------------------统计分数---------------------------------------        

        $checkPointId = $requestJsonParam['checkpoint_id'];
        // 评级总分
        $dir = IniFileManager::getRootDir() . "files/csv/" . self::CHECKPOINT_LIST . ".csv";
        $str = "checkpoint_id = " . $checkPointId;
        $checkpointArr = Util::readCsv( $dir, $str );
        if( empty( $checkpointArr ) )
        {
            $view = new JsonView();
            $messageArr['error']="关卡不存在";
            return $this->getViewByJson( $view, $messageArr, 0,"game/end_game" );
        }
        $gradeScore = $checkpointArr[0]['grade_score'];
        $scoreInfo = $requestJsonParam['scoreInfo'];

        // 连击奖励分总和
        if( count( $scoreInfo['combo'] ) > 0 )
        {
            foreach ( $scoreInfo['combo'] as $key => $value ) 
            {
                $comboNum = $value;
            }
        }
        $comboTotal = $comboNum * 3;

        // 得人参果分总和
        $rewardTotal = $scoreInfo['award'];

        // 杀怪总分
        $killTotal = $scoreInfo['kill']['monster'] + $scoreInfo['kill']['boss'] * 3;

        // 被击扣分总和
        $deduction = $scoreInfo['deduction'][0] * 5 + $scoreInfo['deduction'][1] * 5;

        // 通关分 
        $passScore = $gradeScore * 0.1;

        // 技巧评分
        $skillScore = ( $comboTotal + $rewardTotal + $killTotal + $passScore - $deduction ) / $gradeScore * 100;

//------------------------------------最优关卡信息---------------------------------------        
        // 获取原有最优信息
        $userInfo = UserCache::getAllUserCache( $requestParam['user_id'] );

        if( !$userInfo )
        {
            $userInfo = UserAction::iniUserInfo( $requestParam['user_id'] );
        }
        $checkPointInfo = $userInfo['s_checkpoint_info'];

        // 此次游戏需对比信息
        $newInfo['score'] = $skillScore;
        $newInfo['scr_length'] = $requestJsonParam['scr_length'];
        $newInfo['reward'] = $scoreInfo['award'];
        $newInfo['kill_num'] = $scoreInfo['kill']['monster'] + $scoreInfo['kill']['boss'];
        $newInfo['star_num'] = $requestJsonParam['star_num'];
        $addStarNum = 0;

        if( $checkPointInfo[$checkPointId] )
        {
            // 分数判断
            if( $newInfo['score'] > $checkPointInfo[$checkPointId]['score'] )
            {
                $checkPointInfo[$checkPointId]['score'] = $newInfo['score'];
                $updateType = 1;
            }
            // 最短划痕
            if( $newInfo['scr_length'] < $checkPointInfo[$checkPointId]['scr_length'] )
            {
                $checkPointInfo[$checkPointId]['scr_length'] = $newInfo['scr_length'];
                $updateType = 1;
            }
            // 单局最多人生果
            if( $newInfo['reward'] > $checkPointInfo[$checkPointId]['reward'] )
            {
                $checkPointInfo[$checkPointId]['reward'] = $newInfo['reward'];
                $updateType = 1;
            }
            // 单局杀死最多怪物数
            if( $newInfo['kill_num'] > $checkPointInfo[$checkPointId]['kill_num'] )
            {
                $checkPointInfo[$checkPointId]['kill_num'] = $newInfo['kill_num'];
                $updateType = 1;
            }
            // 该关卡获得星星数
            if( $newInfo['star_num'] > $checkPointInfo[$checkPointId]['star_num'] )
            {
                $checkPointInfo[$checkPointId]['star_num'] = $newInfo['star_num'];
                $updateType = 1;

                $addStarNum = $newInfo['star_num'] - $checkPointInfo[$checkPointId]['star_num'];
            }
        }
        else
        {
            $updateType = 1;
            $checkPointInfo[$checkPointId] = $newInfo;

            // 预留激活下一关
        }

        // 增加钻石
        if( $requestJsonParam['diamond'] )
        {
            $newUserInfo['n_diamond'] = $userInfo['n_diamond'] + $requestJsonParam['diamond'];
            $updateType = 1;
        }
        // 增加魂石数
        if( $requestJsonParam['soul'] )
        {
            $newUserInfo['n_soul'] = $userInfo['n_soul'] + $requestJsonParam['soul'];
            $updateType = 1;
        }
        // 增加人生果数
        if( $newInfo['reward'] )
        {
            $newUserInfo['n_coin'] = $userInfo['n_coin'] + $newInfo['reward'];
            $updateType = 1;
        }

        if( $requestJsonParam['pass'] == 1 && $checkPointId >= $userInfo['n_max_checkpoint'] )
        {
            $updateType = 1;

            // 更新排行榜
            $newRank['id'] = $user_id;
            $newRank['max_checkpoint'] = $checkPointId;
            $newRank['battle'] = $userInfo['n_battle'];
            $cache = UserCache::setByKey( Constants::WORLD_RANK, $user_id, $newRank );

            $newUserInfo['n_max_checkpoint'] = $checkPointId;
            UserCache::setByKey( $requestParam['user_id'], 'n_max_checkpoint', $checkPointId );

        }

        if( $updateType == 1 )
        {
            //成功才存关卡信息
            if ( $requestJsonParam['lose_type']==0) 
            {
               $newUserInfo['s_checkpoint_info'] = serialize( $checkPointInfo );
               UserCache::setByKey( $requestParam['user_id'], 's_checkpoint_info', $checkPointInfo );
            }
            if ($newUserInfo) 
            {
                UserModel::update( $newUserInfo, $user = array( 'n_id' => $requestParam['user_id'] ), $pdo );
            }
            if ($newUserInfo['n_diamond']) 
            {
                UserCache::setByKey( $requestParam['user_id'], 'n_diamond', $newUserInfo['n_diamond'] );
            }
            if ($newUserInfo['n_soul']) 
            {
                 UserCache::setByKey( $requestParam['user_id'], 'n_soul', $newUserInfo['n_soul'] );
            }
            if ($newUserInfo['n_coin']) 
            {
                 UserCache::setByKey( $requestParam['user_id'], 'n_coin', $newUserInfo['n_coin'] );
            }  
            
        }
            
        // 任务成就信息
        $statisticArr['check_point_id'] = $checkPointId;
        $statisticArr['pass'] = $requestJsonParam['pass'];
        $statisticArr['lose_type'] = $requestJsonParam['lose_type'];
        $statisticArr['reward'] = $scoreInfo['award'];
        $statisticArr['monster'] = $scoreInfo['kill']['monster'];
        $statisticArr['boss'] = $scoreInfo['kill']['boss'];
        $statisticArr['all_star'] = $requestJsonParam['star_num'] == 3 ? 1 : 0;
        $statisticArr['soul'] = $requestJsonParam['soul'];
        $statisticArr['away'] = $scoreInfo['deduction'][0];
        $statisticArr['attack'] = $scoreInfo['deduction'][1];
        $statisticArr['star_num'] = $newInfo['star_num'];
        $statisticArr['skill_num'] = $requestJsonParam['skill_num'];

        $beforGame=TaskAndAchieveAction::endNotice($requestParam['user_id']);
        TaskAndAchieveAction::taskStatistic( $requestParam['user_id'], $statisticArr );
        $finishInfo=TaskAndAchieveAction::achieveStatistic( $requestParam['user_id'], $statisticArr );
/*------------------------------扣除用户体力、购买一次性道具道具----------------------*/

        // 获取体力
        $userThew = $userInfo['n_thew'];

        // 更新用户体力
        $nowThew = $userThew - 1;
        if( $nowThew < 0 )
        {
            $view = new JsonView();
            $messageArr['error']="体力不足";
            return $this->getViewByJson( $view, $messageArr, 0,"game/end_game" );
        }
        else if( $userThew == Constants::USER_MAX_THEW )
        {
            $recordArr['n_refresh_time'] = $userInfo['n_refresh_time'] = time();           
        }

        // 购买一次性道具
        if( $requestJsonParam['propArr'] && count( $requestJsonParam['propArr'] ) )
        {
            foreach ($requestJsonParam['propArr'] as $key => $item_id) {
                $result = BuyPropAction::buyProp( $requestParam['user_id'], $item_id );
                if( $result == false )
                {
                    $view = new JsonView();
                    $messageArr['error']="人生果不足";
                    return $this->getViewByJson( $view, $messageArr, 0,"game/end_game" );
                }
            }
        }

        $recordArr['n_thew'] = $nowThew;
        $wheresArr['n_id'] = $requestParam['user_id'];
        UserCache::setByKey( $requestParam['user_id'], 'n_thew', $nowThew );
        UserCache::setByKey( $requestParam['user_id'], 'n_refresh_time', $userInfo['n_refresh_time'] );
        UserModel::update( $recordArr, $wheresArr ); 

        // 生成缓存
        $newSessionKey = Util::generateSessionKey($requestParam['user_id']);
        $oldSessionKey = $requestParam['session_key'];
        Logger::debug('SessionKey1:'. $oldSessionKey);
        Logger::debug('SessionKey2:'. $newSessionKey);

        UserCache::setByKey($requestParam['user_id'], Constants::PREVIOUS_SESSION_KEY, $oldSessionKey);
        UserCache::setByKey($requestParam['user_id'], Constants::CURRENT_SESSION_KEY, $newSessionKey);

        $messageArr = RankAction::getFriendRank( $requestParam['user_id'] );

        $afterGame=TaskAndAchieveAction::endNotice($requestParam['user_id']);

        $messageArr['unlockInfo']=GeneralAction::isUnlock($requestParam['user_id']);
        //获取解锁武将
        $general=GeneralAction::GetAllGeneralInfo($requestParam['user_id']);
        $messageArr['generalInfo']=$general['generalInfo'];

        $messageArr['achieveInfo']=array_values(array_diff($afterGame['achieveInfo'],$beforGame['achieveInfo']));
        $messageArr['finish_num']=$afterGame['finish_num']>$beforGame['finish_num']?$afterGame['finish_num']:0;
        $messageArr['n_thew']=$nowThew;
        $messageArr['n_refresh_time'] = $userInfo['n_refresh_time'];
        $messageArr['server_time'] = time();
        $messageArr['time_num'] = Constants::REFRESH_THEW_TIME;
        $messageArr['session_key'] = $newSessionKey;

        $view = new JsonView();
        return $this->getViewByJson( $view, $messageArr, 1,"game/end_game" );


    }

    /**
     * API：复活
     *
     * @access public
     * @param 无
     * @return JsonView 响应json
     * 
     */
    public function exeResurrect()
    {
        $requestParam = $this->getAllParameters();
        Logger::debug('requestParam:'. print_r($requestParam, true));

        $requestJsonParam = $this->getDecodedJsonRequest();
        Logger::debug('requestJsonParam:'. print_r($requestJsonParam, true));

        $user_id=$requestParam['user_id'];

        $type=self::$price_type[Constants::RESURE_TYPE];
        $money=UserCache::getByKey($user_id,$type);
        if (!$money) 
        {
            $userInfo=FriendModel::getUserInfo($user_id);
            $money=$userInfo[$type];
        }
        $money=$money-Constants::RESURE_COST;
        if ($money<0) 
        {
            $messageArr['error']="人生果/钻石不足！";
            $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
            $view = new JsonView();
            return $this->getViewByJson( $view, $messageArr, 0,"game/resurrect" );
        }
        $ret=CharacterModel::update(array($type=>$money),array('n_id'=>$user_id));
        UserCache::setByKey($user_id,$type,$money);
        $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
        $view = new JsonView();
        return $this->getViewByJson( $view, $messageArr, 1,"game/resurrect" );
    }

    /**
     * API：获取星星数
     *
     * @access public
     * @param 无
     * @return JsonView 响应json
     */
    public function getUserStar( $user_id )
    {
        $userCheckPoint = UserCache::getByKey( $user_id, 's_checkpoint_info' );
        if( !$userCheckPoint )
        {
            $userInfo = UserModel::getUserInfo( $user_id);
            $userCheckPoint = unserialize( $userInfo['s_checkpoint_info'] );
        }

        $star_num = 0;
        if( $userCheckPoint && count( $userCheckPoint ) )
        {
            foreach ( $userCheckPoint as $key => $value ) {
                $star_num = $value['star_num'] + $star_num;
            }
        }

        return $star_num;
    }
}