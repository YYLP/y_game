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

class RankAction extends BaseAction {


    /**
     * API：获取解锁关卡基本信息
     *
     * @access public
     * @param 无
     * @return JsonView 响应json
     */
    public function exeGetRank()
    {
        $requestParam = $this->getAllParameters();
        Logger::debug('requestParam:'. print_r($requestParam, true));

        $requestJsonParam = $this->getDecodedJsonRequest();
        Logger::debug('requestJsonParam:'. print_r($requestJsonParam, true));

        // 获取用户表信息
        $userInfo = UserCache::getAllUserCache( $requestParam['user_id'] );

        if( !$userInfo )
        {
            $userInfo = UserAction::iniUserInfo( $requestParam['user_id'] );
        }

        if( $requestJsonParam['type'] == 'world' )
        {
            $messageArr = self::getWorldRank( $requestParam['user_id'] );
            $messageArr['userInfo']=RankModel::getUserRank( $requestParam['user_id'] );
            $messageArr['userInfo']['id']=$userInfo['n_id'];
            $messageArr['userInfo']['head']=$userInfo['n_head'];
            $messageArr['userInfo']['name']=$userInfo['s_name'];
            $messageArr['userInfo']['sex']=$userInfo['n_sex'];
            $messageArr['userInfo']['level']=$userInfo['n_level'];
            $messageArr['userInfo']['checkpoint']=$userInfo['n_max_checkpoint'];
            $messageArr['userInfo']['battle']=$userInfo['n_battle'];
        }
        else if( $requestJsonParam['type'] == 'friend' )
        {
            $messageArr = self::getFriendRank( $requestParam['user_id'] );
        }
        else
        {
            $view = new JsonView();
            return $this->getViewByJson( $view, '', 0, "rank/get_rank" );
        }

        // 生成缓存
        $newSessionKey = Util::generateSessionKey($requestParam['user_id']);
        $oldSessionKey = $requestParam['session_key'];
        Logger::debug('SessionKey1:'. $oldSessionKey);
        Logger::debug('SessionKey2:'. $newSessionKey);

        UserCache::setByKey($requestParam['user_id'], Constants::PREVIOUS_SESSION_KEY, $oldSessionKey);
        UserCache::setByKey($requestParam['user_id'], Constants::CURRENT_SESSION_KEY, $newSessionKey);
        //UserCache::setByKey($user_id, 'userInfo', $userInfo);
        $messageArr['session_key'] = $newSessionKey;

        $view = new JsonView();
        return $this->getViewByJson( $view, $messageArr, 1,"system/get_bulletin" );
    }

    /**
     * API：获取世界排行
     *
     * @access public
     * @param 无
     * @return JsonView 响应json
     */
    public function getWorldRank( $user_id )
    {
        UserCache::deleteAllUserCache( Constants::WORLD_RANK );
        //$rankArr = UserCache::getAllUserCache( Constants::WORLD_RANK );

        $friendList = FriendAction::getFriend( $user_id );

        if( !$rankArr )
        {
            //$worldRank = rankModel::selectlimit( $wheres = "",$limit = array( "page" => 1, "limit" => Constants::WORLD_RANKY_MAX_NUM ), $columns = array( "n_id", 'n_battle', 'n_max_checkpoint' ) );
            $worldRank = RankModel::getRank( Constants::WORLD_RANKY_MAX_NUM );
            foreach ( $worldRank as $key => $value ) {
                $result['max_checkpoint'] = $value['n_max_checkpoint'];
                $result['battle'] = $value['n_battle'];
                $result['n_id'] = $value['n_id'];
                $rankArr[$value['n_id']] = $result;
            }
            //echo "<pre>";var_dump($worldRank);exit;
            UserCache::setAllUserCache( Constants::WORLD_RANK, $rankArr );
        }

        // uasort($rankArr, function($a, $b) {
        //     $al = $a['max_checkpoint'];
        //     $bl = $b['max_checkpoint'];
        //     if ($al == $bl)
        //     {
        //         $al2 = $a['battle'];
        //         $bl2 = $b['battle'];
        //         return ($al2 > $bl2) ? -1 : 1;
        //     }
        //     return ($al > $bl) ? -1 : 1;
        // });

        $rankArr = array_slice( $rankArr, 0, Constants::WORLD_RANKY_MAX_NUM );
        //echo "<pre>";

        foreach ( $rankArr as $key => $value ) {
            $now_id = $value['n_id'];

            $userInfo = UserCache::getAllUserCache( $now_id );
            if( !$userInfo['n_id'] || !$userInfo['n_head'] || !$userInfo['s_name'] || !$userInfo['n_sex'] || !$userInfo['n_level'] || !$userInfo['n_max_checkpoint'] )
            {
                $userInfo = UserAction::iniUserInfo( $now_id );
            }
            //var_dump($userInfo);echo "<br />";

            $rankInfo['id'] = $userInfo['n_id'];
            $rankInfo['head'] = $userInfo['n_head'];
            $rankInfo['name'] = $userInfo['s_name'];
            $rankInfo['sex'] = $userInfo['n_sex'];
            $rankInfo['level'] = $userInfo['n_level'];
            $rankInfo['checkpoint'] = $userInfo['n_max_checkpoint'];
            $rankInfo['battle'] = $value['battle'];

            // 好友列表加入缓存后此处需要修改
            //$rankInfo['friend_type'] = FriendModel::isFriend( $user_id, $now_id );
            if( $friendList&&in_array( $now_id, $friendList ) )
            {
                $rankInfo['friend_type'] = 1;
            }
            else
            {
                $rankInfo['friend_type'] = 0;
            }

            $worldRankInfo[$key+1] = $rankInfo;
        }

        return $worldRankInfo;
    }
    /**
     * API：获取好友排行
     *
     * @access public
     * @param 无
     * @return JsonView 响应json
     */
    public function getFriendRank( $user_id )
    {
        // 好友列表加入缓存后此处需要修改
        $friendList = FriendAction::getFriend( $user_id );

        if( $friendList && count( $friendList ) )
        {
            foreach ($friendList as $key => $value) {
                $userInfo = UserCache::getAllUserCache( $value );
                if( !$userInfo['n_id'] || !$userInfo['n_head'] || !$userInfo['s_name'] || !$userInfo['n_sex'] || !$userInfo['n_level'] || !$userInfo['n_max_checkpoint'])
                {
                    $userInfo = UserAction::iniUserInfo( $value );
                }

                $rankInfo['id'] = $userInfo['n_id'];
                $rankInfo['head'] = $userInfo['n_head'];
                $rankInfo['name'] = $userInfo['s_name'];
                $rankInfo['sex'] = $userInfo['n_sex'];
                $rankInfo['level'] = $userInfo['n_level'];
                $rankInfo['checkpoint'] = $userInfo['n_max_checkpoint'];
                $rankInfo['battle'] = $userInfo['n_battle'];

                $FriendRankInfo[] = $rankInfo;
            }

        }
        $userInfo = UserCache::getAllUserCache( $user_id );
        if( !$userInfo )
        {
            $userInfo = UserAction::iniUserInfo( $user_id );
        }

        $rankInfo['id'] = $userInfo['n_id'];
        $rankInfo['head'] = $userInfo['n_head'];
        $rankInfo['name'] = $userInfo['s_name'];
        $rankInfo['sex'] = $userInfo['n_sex'];
        $rankInfo['level'] = $userInfo['n_level'];
        $rankInfo['checkpoint'] = $userInfo['n_max_checkpoint'];
        $rankInfo['battle'] =$userInfo['n_battle'];

        $FriendRankInfo[] = $rankInfo;

        return $FriendRankInfo;
    }

}