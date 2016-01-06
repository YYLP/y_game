<?php
/**
 * buy_prop类
 *
 *
 * @access public
 * @author chenwenbin
 * @copyright Copyright(C) Tenone Inc.
 * @version 0.01
 * @since 2014/11/13
 */

class BuyPropAction extends BaseAction {

    private static  $price_type= array(
            1 => 'n_coin',
            2 => 'n_diamond',
            3 => 'n_thew'
        );

    /**
     * API：购买道具
     *
     * @access public
     * @param 无
     * @return JsonView 响应json
     */
    public function buyProp($user_id,$item_id)
    {
        // $requestParam = $this->getAllParameters();
        // Logger::debug('requestParam:'. print_r($requestParam, true));

        // $requestJsonParam = $this->getDecodedJsonRequest();
        // Logger::debug('requestJsonParam:'. print_r($requestJsonParam, true));

        // $user_id=$requestParam['user_id'];
        // $item_id=$requestJsonParam['item_id'];
        // $session_key=$requestParam['session_key'];

        $price_type=self::$price_type;

        $str="item_id = ".$item_id;
        $file = IniFileManager::getRootDir()."/files/csv/item.csv";
        $itemInfo=CharacterAction::readCsv($file,$str);
        
        $type=$price_type[$itemInfo[0]['buy_type']];

        $money=UserCache::getByKey( $user_id, $type);
        if (!$money) 
        {
            $userInfo=BuyPropModel::getUserInfo($user_id);
            $money=$userInfo[$type];
        }
        $money=$money-$itemInfo[0]['buy_price'];

        if ($money<0) 
        {
            return false;
        }

        $res=BuyPropModel::update(array($type=>$money),array('n_id'=>$user_id));

        //任务成就统计
        TaskAndAchieveAction::taskStatistic($user_id,array('pro_num'=>1));
        TaskAndAchieveAction::achieveStatistic($user_id,array('pro_num'=>1));
        if ($type==$price_type[1]) 
        {
            TaskAndAchieveAction::achieveStatistic($user_id,array('cost'=>$itemInfo[0]['buy_price']));
        } 

        UserCache::setByKey($user_id, $type, $money);

        return true;
        // $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
        // $view = new JsonView();
        // return $this->getViewByJson( $view, $messageArr, 1,"buy_prop/buy_prop" );
    }

    /**
     * API：购买永久道具
     *
     * @access public
     * @param 无
     * @return JsonView 响应json
     */
    public function exeForeverBuy()
    {
        // $requestParam = $this->getAllParameters();
        // Logger::debug('requestParam:'. print_r($requestParam, true));

        // $requestJsonParam = $this->getDecodedJsonRequest();
        // Logger::debug('requestJsonParam:'. print_r($requestJsonParam, true));

        // $user_id=$requestParam['user_id'];
        // $item_id=$requestJsonParam['item_id'];
        // $session_key=$requestParam['session_key'];

        // $userInfo=BuyPropModel::getUserInfo($user_id);
        // $userItem=BuyPropModel::getItemInfo($user_id);

        // //该永久道具已购买
        // if (is_array($userItem[0])&&in_array($item_id,$userItem[0])) 
        // {
        //     throw new Exception("this item you have got it");
        // }
        // $price_type=self::$price_type;

        // $str="item_id = ".$item_id;
        // $file = IniFileManager::getRootDir()."/files/csv/item.csv";
        // $itemInfo=CharacterAction::readCsv($file,$str);

        // $money=$userInfo[$price_type[$itemInfo[0]['forever_type']]]-$itemInfo[0]['forever_price'];
        // if ($money<0) 
        // {
        //     throw new Exception("not enough money");
        // }
        // $userItem[0][]=$item_id;
        // $s_item_info=serialize($userItem);
        // $res=BuyPropModel::update(array('s_item_info'=>$s_item_info,$price_type[$itemInfo[0]['forever_type']]=>$money),array('n_id'=>$user_id));


        // $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
        // $view = new JsonView();
        // return $this->getViewByJson( $view, $messageArr, 1,"buy_prop/forever_buy" );
    }


    /**
     * API：商城信息
     *
     * @access public
     * @param 无
     * @return JsonView 响应json
     */
    public function exeGetMallInfo($user_id)
    {
        // $requestParam = $this->getAllParameters();
        // Logger::debug('requestParam:'. print_r($requestParam, true));

        // $requestJsonParam = $this->getDecodedJsonRequest();
        // Logger::debug('requestJsonParam:'. print_r($requestJsonParam, true));

        // $user_id=$requestParam['user_id'];
        // $session_key=$requestParam['session_key'];

        $file = IniFileManager::getRootDir()."/files/csv/mall.csv";
        $mallInfo=CharacterAction::readCsv($file);
        return $mallInfo;
        // $messageArr['mallInfo']=$mallInfo;
        // $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
        // $view = new JsonView();
        // return $this->getViewByJson( $view, $messageArr, 1,"buy_prop/get_mall_info" );
    }

    /**
     * API：道具信息
     *
     * @access public
     * @param 无
     * @return JsonView 响应json
     */
    public function exeGetPropInfo($user_id)
    {
        // $requestParam = $this->getAllParameters();
        // Logger::debug('requestParam:'. print_r($requestParam, true));

        // $requestJsonParam = $this->getDecodedJsonRequest();
        // Logger::debug('requestJsonParam:'. print_r($requestJsonParam, true));

        // $user_id=$requestParam['user_id'];
        // $session_key=$requestParam['session_key'];

        $userItem=UserCache::getByKey($user_id,'s_item_info');
        if (!$userItem) 
        {
            $userItem=BuyPropModel::getItemInfo($user_id);
            UserCache::setByKey($user_id,'s_item_info',$userItem);
        }
        
        $file = IniFileManager::getRootDir()."/files/csv/item.csv";
        $itemInfo=CharacterAction::readCsv($file);

        foreach ($itemInfo as $key => $value) 
        {
            if (is_array($userItem[0])&&in_array($value['item_id'],$userItem[0])) 
            {
                $itemInfo[$key]['n_get']=1;
            }
            else
            { 
                $itemInfo[$key]['n_get']=0;
            }
        }
        return $itemInfo;
        // $messageArr['itemInfo']=$itemInfo;
        // $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
        // $view = new JsonView();
        // return $this->getViewByJson( $view, $messageArr, 1,"buy_prop/get_prop_info" );
    }


    /**
     * API：购买商城物品
     *
     * @access public
     * @param 无
     * @return JsonView 响应json
     */
    public function exeBuyMall()
    {
        $requestParam = $this->getAllParameters();
        Logger::debug('requestParam:'. print_r($requestParam, true));

        $requestJsonParam = $this->getDecodedJsonRequest();
        Logger::debug('requestJsonParam:'. print_r($requestJsonParam, true));

        $user_id=$requestParam['user_id'];
        $mall_id=$requestJsonParam['mall_id'];
        $session_key=$requestParam['session_key'];

        $str="mall_id = ".$mall_id;
        $file = IniFileManager::getRootDir()."/files/csv/mall.csv";
        $itemInfo=CharacterAction::readCsv($file,$str);

        $price_type=self::$price_type;
        //余额判断
        $type1=$price_type[$itemInfo[0]['price_type']];
        $type2=$price_type[$itemInfo[0]['buy_type']];

        $money1=UserCache::getByKey( $user_id, $type1);
        $money2=UserCache::getByKey( $user_id, $type2);
        if (!$money1) 
        {
            $userInfo=BuyPropModel::getUserInfo($user_id);
            $money1=$userInfo[$type1];
        }
        if (!$money2)
        {
            $userInfo=BuyPropModel::getUserInfo($user_id);
            $money2=$userInfo[$type2];
        }
        $money1=$money1-$itemInfo[0]['price_num'];
        $money2=$money2+$itemInfo[0]['buy_num'];
        if ($money1<0)
        {
            $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
            $messageArr['error']="人生果/钻石不足！";
            $view = new JsonView();
            return $this->getViewByJson( $view, $messageArr, 0,"buy_prop/buy_mall" );          
        }
        //任务成就统计
        if ($type2==$price_type[1]) 
        {
            TaskAndAchieveAction::taskStatistic($user_id,array('reward'=>$itemInfo[0]['buy_num']));
        }
        if ($type1==$price_type[1]) 
        {
            TaskAndAchieveAction::achieveStatistic($user_id,array('cost'=>$itemInfo[0]['price_num']));
        }
        //购买体力是更新体力时间
        if ($type2==$price_type[3]) 
        {
            $thewArr['n_thew']=$money2;
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
            $res=BuyPropModel::update(array($type1=>$money1),array('n_id'=>$user_id));
            UserCache::setByKey($user_id,$type1,$money1);
        }
        else
        {
            $res=BuyPropModel::update(array($type1=>$money1,$type2=>$money2),array('n_id'=>$user_id));
            UserCache::setByKey($user_id,$type1,$money1);
            UserCache::setByKey($user_id,$type2,$money2);
        }

        $messageArr['moneyInfo']=BuyPropModel::getUserInfo($user_id);
        $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
        //任务成就界面
        $messageArr['achieveInfo']=TaskAndAchieveAction::getAchieveInfo($user_id);
        $messageArr['taskInfo']=TaskAndAchieveAction::getTaskInfo($user_id);
        $view = new JsonView();
        return $this->getViewByJson( $view, $messageArr, 1,"buy_prop/buy_mall" );
    }
}