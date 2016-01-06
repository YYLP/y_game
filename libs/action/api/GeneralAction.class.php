<?php
/**
 * general类
 *
 *
 * @access public
 * @author chenwenbin
 * @copyright Copyright(C) Tenone Inc.
 * @version 0.01
 * @since 2014/11/13
 */

class GeneralAction extends BaseAction {
 

	private static $attribute=array(1=>'n_continue_level',
                                    2=>'n_cool_level');

    private static $price_type= array(
            1 => 'n_coin',
            2 => 'n_diamond',
            3 => 'n_soul',
            4 => 'n_thew'
        );

     /**
     * API：获取武将信息表
     *
     * @access public
     * @param 无
     * @return JsonView 响应json
     */
    public function exeGetGeneralInfo()
    {
        $requestParam = $this->getAllParameters();
        Logger::debug('requestParam:'. print_r($requestParam, true));

        $requestJsonParam = $this->getDecodedJsonRequest();
        Logger::debug('requestJsonParam:'. print_r($requestJsonParam, true));
        
        $user_id=$requestParam['user_id'];
        $session_key=$requestParam['session_key'];
        
        $messageArr=self::GetAllGeneralInfo($user_id);

        //session_key
        $messageArr['battle']=UserAction::getUserBattle( $user_id );
        $messageArr['mail_num'] = MailAction::getMailNum( $user_id);
        $messageArr['achieve_type']  = TaskAndAchieveAction::isFinish($user_id);
        $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
        
        $view = new JsonView();
        return $this->getViewByJson( $view, $messageArr, 1,"general/get_general_info" );

    }


     /**
     * API：购买武将
     *
     * @access public
     * @param 无
     * @return JsonView 响应json
     */
    public function exeBuyGeneral()
    {
        $requestParam = $this->getAllParameters();
        Logger::debug('requestParam:'. print_r($requestParam, true));

        $requestJsonParam = $this->getDecodedJsonRequest();
        Logger::debug('requestJsonParam:'. print_r($requestJsonParam, true));

        $user_id=$requestParam['user_id'];
        $general_id=$requestJsonParam['general_id'];
        $session_key=$requestParam['session_key'];

        $userGeneral=UserCache::getByKey($user_id,'s_general_info');
        if (!$userGeneral) 
        {
            $userGeneral=GeneralModel::getUserGeneralInfo($user_id);
            UserCache::setByKey($user_id,'s_general_info',$userGeneral);
        }

        //读取系统武将列表
        $str="";
        $str="general_id = ".$general_id;
        $file = IniFileManager::getRootDir()."/files/csv/general.csv";
        $generalArr=CharacterAction::readCsv($file,$str);

        //是否已拥有该武将
        if ($userGeneral[$general_id]) 
        {
            $messageArr['error']="已拥有该武将！";
            $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
            $view = new JsonView();
            return $this->getViewByJson( $view, $messageArr, 0,"general/buy_general" );
        }

        //判断余额
        $price_type=self::$price_type;
        $type=$price_type[$generalArr[0]['buy_type']];
        if (!$money) 
        {
            $userInfo=GeneralModel::getUserInfo($user_id);
            $money=$userInfo[$type];
        }
        $money=$money-$generalArr[0]['buy_price'];
        if ($money<0) 
        {
            $messageArr['error']="人生果/钻石不足！";
            $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
            $view = new JsonView();
            return $this->getViewByJson( $view, $messageArr, 0,"general/buy_general" );
        }

        //添加新武将到武将信息字段、更新用户金钱
        $userGeneral[$general_id]=array('n_continue_level'=>0,
                                        'n_cool_level'=>0);
        $s_general_info=serialize($userGeneral);

        $ret=GeneralModel::update(array('s_general_info'=>$s_general_info,$type=>$money),array('n_id'=>$user_id));
        if(is_null($ret))
        {
            throw new ModelException( 'update false pa_user_master' );
        } 

        //任务成就统计
        if ($type==$price_type[1]) 
        {
            TaskAndAchieveAction::achieveStatistic($user_id,array('cost'=>$generalArr[0]['buy_price']));
        }
        //session_key
        UserCache::setByKey($user_id,'s_general_info',$userGeneral);
        UserCache::setByKey($user_id,$type,$money);

        //更新战斗力
        $battle=UserAction::getUserBattle($user_id);
        GeneralModel::update(array('n_battle'=>$battle),array('n_id'=>$user_id));
        UserCache::setByKey($user_id, 'n_battle', $battle);
        
        $messageArr=self::GetAllGeneralInfo($user_id);
        $messageArr['battle']=$battle;

        //任务成就界面
        $messageArr['achieveInfo']=TaskAndAchieveAction::getAchieveInfo($user_id);
        $messageArr['taskInfo']=TaskAndAchieveAction::getTaskInfo($user_id);

        $messageArr['mail_num'] = MailAction::getMailNum( $user_id);
        $messageArr['achieve_type']  = TaskAndAchieveAction::isFinish($user_id);
        $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
        $view = new JsonView();
        return $this->getViewByJson( $view, $messageArr, 1,"general/buy_general" );

    }


     /**
     * API：升级武将属性
     *
     * @access public
     * @param 无
     * @return JsonView 响应json
     */
    public function exeUpdateGeneral()
    {
     	$requestParam = $this->getAllParameters();
        Logger::debug('requestParam:'. print_r($requestParam, true));

        $requestJsonParam = $this->getDecodedJsonRequest();
        Logger::debug('requestJsonParam:'. print_r($requestJsonParam, true));

        $user_id=$requestParam['user_id'];
        $general_id=$requestJsonParam['general_id'];
        $attribute_id=$requestJsonParam['attribute_id'];
        $session_key=$requestParam['session_key'];

        $userGeneral=UserCache::getByKey($user_id,'s_general_info');
        if (!$userGeneral) 
        {
            $userGeneral=GeneralModel::getUserGeneralInfo($user_id);
            UserCache::setByKey($user_id,'s_general_info',$userGeneral);
        }

        $attribute=self::$attribute;   
        $price_type=self::$price_type;   
        // 读取csv类  ,读取目标文件
        $file = IniFileManager::getRootDir()."/files/csv/general".$general_id."_update_value.csv";
        $str="update_id = ".$attribute_id.'_'.$userGeneral[$general_id][$attribute[$attribute_id]];
        $nowArr=CharacterAction::readCsv($file,$str);
        
        $type=$price_type[$nowArr[0]['price_type']];
        $money=UserCache::getByKey($user_id,$type);
        if (!$money) 
        {
            $userInfo=GeneralModel::getUserInfo($user_id);
            $money=$userInfo[$type];
        }
        $money=$money-$nowArr[0]['price'];
        if ($money<0)
        {
            $messageArr['error']="人生果/钻石不足！";
            $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
            $view = new JsonView();
            return $this->getViewByJson( $view, $messageArr, 0,"general/update_general" );
        }
        if ($userGeneral[$general_id][$attribute[$attribute_id]]>=Constants::MAX_LEVEL)
        {
            $messageArr['error']="等级已达最大！";
            $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
            $view = new JsonView();
            return $this->getViewByJson( $view, $messageArr, 0,"general/update_general" );
        }

        $userGeneral[$general_id][$attribute[$attribute_id]]=$userGeneral[$general_id][$attribute[$attribute_id]]+1;
        $s_general_info=serialize($userGeneral);
        $res=GeneralModel::update(array($type=>$money,'s_general_info'=>$s_general_info),array('n_id'=>$user_id));
        if (!$res) 
        {
        	throw new Exception(" update false pa_user_master ");
        }
        //任务成就统计
        $num=0;
        TaskAndAchieveAction::taskStatistic($user_id,array('generl_up'=>1));
        if ($type==$price_type[1]) 
        {
            TaskAndAchieveAction::achieveStatistic($user_id,array('cost'=>$nowArr[0]['price']));
        }
        foreach ($attribute as $key => $value) 
        {
            if ($userGeneral[$general_id][$value]>=Constants::MAX_LEVEL) 
            {
                $num++;
            }
        }
        if ($num==2) 
        {
            TaskAndAchieveAction::achieveStatistic($user_id,array('generl_full'=>1));
        }
        //session_key
        UserCache::setByKey($user_id,'s_general_info',$userGeneral);
        UserCache::setByKey($user_id,$type,$money);

        //更新战斗力
        $battle=UserAction::getUserBattle($user_id);
        GeneralModel::update(array('n_battle'=>$battle),array('n_id'=>$user_id));
        UserCache::setByKey($user_id, 'n_battle', $battle);

        $messageArr=self::GetAllGeneralInfo($user_id);
        $messageArr['battle']=$battle;

        //任务成就界面
        $messageArr['achieveInfo']=TaskAndAchieveAction::getAchieveInfo($user_id);
        $messageArr['taskInfo']=TaskAndAchieveAction::getTaskInfo($user_id);

        $messageArr['mail_num'] = MailAction::getMailNum( $user_id);
        $messageArr['achieve_type']  = TaskAndAchieveAction::isFinish($user_id);
        $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
        $view = new JsonView();
        return $this->getViewByJson( $view, $messageArr, 1,"general/update_general" );       

    } 


     /**
     * API：获取武将属性
     *
     * @access public
     * @param $integer $user_id 用户ID
     * @return array 
     */
    public function GetAllGeneralInfo($user_id)
    {
        $userGeneral=UserCache::getByKey($user_id,'s_general_info');
        if (!$userGeneral) 
        {
            $userGeneral=GeneralModel::getUserGeneralInfo($user_id);
            UserCache::setByKey($user_id,'s_general_info',$userGeneral);
        }
        
        //读取系统武将，属性列表
        $file = IniFileManager::getRootDir()."/files/csv/general.csv";
        $generalArr=CharacterAction::readCsv($file);
        foreach ($generalArr as $key => $value) 
        {
            if ($userGeneral[$value['general_id']]) 
            {
                $generalArr[$key]['n_get']=1;
                //$generalArr[$key]['battle']=self::GetGeneralBattle($user_id,$value['general_id']);
                $generalArr[$key]['attributeInfo']=self::GetGeneralAttribute($user_id,$value['general_id']);
            }
            else
            { 
                $generalArr[$key]['n_get']=0;
               // $generalArr[$key]['battle']=self::GetGeneralBattle($user_id,$value['general_id']);
                $generalArr[$key]['attributeInfo']=self::GetGeneralAttribute($user_id,$value['general_id']);

            }
        }

        foreach (self::$price_type as $key => $value) 
        {
            $moneyArr[$value]=UserCache::getByKey($user_id,$value);
            if (!$moneyArr[$value]) 
            {
                $userInfo=CharacterModel::getUserInfo($user_id);
                $moneyArr[$value]=$userInfo[$value];
            }
        }
        $messageArr['moneyInfo']=$moneyArr;
        $messageArr['generalInfo']=$generalArr;

        return $messageArr;
    }

     /**
     * API：获取武将属性
     *
     * @access public
     * @param $integer $user_id $general_id 用户ID，武将ID
     * @return array 
     */
     public function GetGeneralAttribute($user_id,$general_id)
    {
        $userGeneral=UserCache::getByKey($user_id,'s_general_info');
        if (!$userGeneral)
        {
            $userGeneral=GeneralModel::getUserGeneralInfo($user_id);
            UserCache::setByKey($user_id,'s_general_info',$userGeneral);
        }     

        //读取当前属性等级条件 
        $str="";
        foreach (self::$attribute as $key => $value) 
        {
        	$level=$userGeneral[$general_id][$value]?$userGeneral[$general_id][$value]:0;
            $str.='update_id = '.$key.'_'.$level.' OR ';
        }
        $str=substr($str, 0,-4);
        
        // 读取csv类，读取目标文件
        $file = IniFileManager::getRootDir()."/files/csv/general".$general_id."_update_value.csv";
        $nowArr=CharacterAction::readCsv($file,$str);

        //读取下一等级
        foreach (self::$attribute as $key => $value) 
        {
        	$level=$userGeneral[$general_id][$value]?$userGeneral[$general_id][$value]:0;
            if ($level<Constants::MAX_LEVEL) 
            {
                $level=$level+1;
                $str2.='update_id = '.$key.'_'.$level.' OR ';
            }
            else 
            {
                $str2.='update_id = '.$key.'_'.$level.' OR ';
            }
        }
        $str2=substr($str2, 0,-4);
        $nextArr=CharacterAction::readCsv($file,$str2);

        //将属性值取出来重新赋 键
        foreach ($nextArr as $key1 => $value) 
        {
            foreach ($value as $key2 => $value2) 
            {
                if ($key2=='value') 
                {
                    $next_value[$key1]['next_value']=$value2;
                }
            }
        }

        //两组属性合并
        foreach($nowArr as $k=>$r)
        {
           $arr[] = array_merge($r,$next_value[$k]);
           $arr[$k]['max_level']=Constants::MAX_LEVEL;
        } 

        return $arr;
    }


     /**
     * API：获取武将战斗力
     *
     * @access public
     * @param $integer $user_id $general_id 用户ID，武将ID
     * @return array 
     */
     public function GetGeneralBattle($user_id,$general_id=null)
    {
        $userGeneral=UserCache::getByKey($user_id,'s_general_info');
        if (!$userGeneral) 
        {
            $userGeneral=GeneralModel::getUserGeneralInfo($user_id);
            UserCache::setByKey($user_id,'s_general_info',$userGeneral);
        }
        
        //读取系统武将，属性列表
        $file = IniFileManager::getRootDir()."/files/csv/general.csv";
        $generalArr=Util::readCsv($file);
        foreach ($generalArr as $key => $value) 
        {
            $attributeArr=GeneralAction::GetGeneralAttribute($user_id,$value['general_id']);
            if ($userGeneral[$value['general_id']]) 
            {
                $battle+=$attributeArr[0]['battle']+$attributeArr[1]['battle'];
            }
        }
        return $battle;
    }


     /**
     * API：获取当前武将信息
     *
     * @access public
     * @param $integer $user_id $general_id 用户ID，武将ID
     * @return array 
     */
     public function nowGeneralInfo($user_id)
    {
        $userGeneral=UserCache::getByKey($user_id,'s_general_info');
        if (!$userGeneral) 
        {
            $userGeneral=GeneralModel::getUserGeneralInfo($user_id);
            UserCache::setByKey($user_id,'s_general_info',$userGeneral);
        }
        foreach ($userGeneral as $key => $value) 
        {
            $generalInfo[$key]=self::GetGeneralAttribute($user_id,$key);
        }
        return $generalInfo;
    }


      /**
     * API：注册时武将初始化
     *
     * @access public
     * @param int $user_id 用户ID $general_id主角ID
     * @return array
     */
    public function registGeneral($user_id,$general_id)
    {
        $userGeneral[$general_id]=array('n_continue_level'=>0,
                                        'n_cool_level'=>0);
        $s_general_info=serialize($userGeneral);

        return $s_general_info;
    }


      /**
     * API：武将解锁直接拥有
     *
     * @access public
     * @param int $user_id 用户ID $general_id主角ID
     * @return array
     */
    public function isUnlock($user_id)
    {
        $starNum=GameAction::getUserStar($user_id);
        $userGeneral=UserCache::getByKey($user_id,'s_general_info');
        if (!$userGeneral) 
        {
            $userGeneral=GeneralModel::getUserGeneralInfo($user_id);
            UserCache::setByKey($user_id,'s_general_info',$userGeneral);
        }
    
        //读取系统武将，属性列表
        $file = IniFileManager::getRootDir()."/files/csv/general.csv";
        $generalArr=CharacterAction::readCsv($file);
        foreach ($generalArr as $key => $value) 
        {
            if (!$userGeneral[$value['general_id']]) 
            {
                if ($starNum>=$value['unlock_star']) 
                {
                    //添加新武将到武将信息字段、更新用户金钱
                    $userGeneral[$value['general_id']]=array('n_continue_level'=>0,
                                                             'n_cool_level'=>0);
                    $unlockInfo=$value['general_id'];
                }
            }
        }
        
        $s_general_info=serialize($userGeneral);
        $ret=GeneralModel::update(array('s_general_info'=>$s_general_info),array('n_id'=>$user_id));
        UserCache::setByKey($user_id,'s_general_info',$userGeneral);

        //更新战斗力
        $battle=UserAction::getUserBattle($user_id);
        GeneralModel::update(array('n_battle'=>$battle),array('n_id'=>$user_id));
        UserCache::setByKey($user_id, 'n_battle', $battle);

        return $unlockInfo?$unlockInfo:0;
    }
}