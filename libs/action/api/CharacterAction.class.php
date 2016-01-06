<?php
/**
 * character类
 *
 *
 * @access public
 * @author chenwenbin
 * @copyright Copyright(C) Tenone Inc.
 * @version 0.01
 * @since 2014/11/13
 */

class CharacterAction extends BaseAction {

    //const PARAM_1 = 'user_id';
    
    //const PARAM_2 = 'type';

    private static  $attribute = array(
            1 => 'n_attack_level',
            2 => 'n_crit_level',
            3 => 'n_hp_level'
        );
    private static $price_type= array(
            1 => 'n_coin',
            2 => 'n_diamond',
            3 => 'n_soul',
            4 => 'n_thew'
        );

    const CHARACTER_STRING='s_role_info';


    /**
     * API：获取主角信息表
     *
     * @access public
     * @param 无
     * @return JsonView 响应json
     */
    public function exeGetCharacterInfo()
    {
        $requestParam = $this->getAllParameters();
        Logger::debug('requestParam:'. print_r($requestParam, true));

        $requestJsonParam = $this->getDecodedJsonRequest();
        Logger::debug('requestJsonParam:'. print_r($requestJsonParam, true));
         
        $user_id=$requestParam['user_id'];

        $messageArr=self::GetAllCharacterInfo($user_id);
        $messageArr['battle']=UserAction::getUserBattle( $user_id );
        $messageArr['mail_num'] = MailAction::getMailNum( $user_id);
        $messageArr['achieve_type']  = TaskAndAchieveAction::isFinish($user_id);
        $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$requestParam['session_key']);
        $view = new JsonView();
        return $this->getViewByJson( $view, $messageArr, 1,"character/get_character_info" );
    }


    /**
     * API：升级主角信息
     *
     * @access public
     * @param 无
     * @return JsonView 响应json
     */
     public function exeUpdateCharacterAttribute()
     {
        $requestParam = $this->getAllParameters();
        Logger::debug('requestParam:'. print_r($requestParam, true));

        $requestJsonParam = $this->getDecodedJsonRequest();
        Logger::debug('requestJsonParam:'. print_r($requestJsonParam, true));

        $user_id=$requestParam['user_id'];
        $character_id=$requestJsonParam['character_id'];
        $attribute_id=$requestJsonParam['attribute_id'];
        $session_key=$requestParam['session_key'];

        $userCharacter=UserCache::getByKey( $user_id, self::CHARACTER_STRING);
        if (!$userCharacter) 
        {
            $userCharacter=CharacterModel::getUserCharacterInfo($user_id);
            UserCache::setByKey($user_id, self::CHARACTER_STRING, $userCharacter);
        }

        $attribute=self::$attribute;   
        $price_type=self::$price_type;   
        // 读取csv类  ,读取目标文件
        $file = IniFileManager::getRootDir()."/files/csv/character".$character_id."_update_value.csv";
        $str="update_id = ".$userCharacter[$character_id]['n_level'].$attribute_id.$userCharacter[$character_id][$attribute[$attribute_id]];
        $nowArr=self::readCsv($file,$str);
        
        //余额判断
        $type=$price_type[$nowArr[0]['price_type']];
        $money=UserCache::getByKey( $user_id, $type);
        if (!$money) 
        {
            $userInfo=CharacterModel::getUserInfo($user_id);
            $money=$userInfo[$type];
        }
        $money=$money-$nowArr[0]['price'];
        if ($money<0)
        {
            $messageArr['error']="人生果/钻石不足！";
            $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
            $view = new JsonView();
            return $this->getViewByJson( $view, $messageArr, 0,"character/update_character_attribute" );         
        }
        //超出最大等级
        if ($userCharacter[$character_id][$attribute[$attribute_id]]>=Constants::MAX_ATTRIBUTE) 
        {
            $messageArr['error']="该属性已达最大等级！";
            $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
            $view = new JsonView();
            return $this->getViewByJson( $view, $messageArr, 0,"character/update_character_attribute" );
        }
        //金钱、主角属性
        $userCharacter[$character_id][$attribute[$attribute_id]]=$userCharacter[$character_id][$attribute[$attribute_id]]+1;
        $s_role_info=serialize($userCharacter);
        $res=CharacterModel::update(array($type=>$money,'s_role_info'=>$s_role_info),array('n_id'=>$user_id));
        if(is_null($res))
        {
            throw new ModelException( 'update false pa_user_master' );
        }
 
        //任务成就统计
        TaskAndAchieveAction::taskStatistic($user_id,array('character_up'=>1));
        if ($type==$price_type[1]) 
        {
            TaskAndAchieveAction::achieveStatistic($user_id,array('cost'=>$nowArr[0]['price']));
        }

        UserCache::setByKey($user_id, self::CHARACTER_STRING, $userCharacter);
        UserCache::setByKey($user_id, $type, $money);

        //更新战斗力
        $battle=UserAction::getUserBattle($user_id);
        CharacterModel::update(array('n_battle'=>$battle),array('n_id'=>$user_id));
        UserCache::setByKey($user_id, 'n_battle', $battle);

        $messageArr=self::GetAllCharacterInfo($user_id);
        $messageArr['battle']=$battle;
        //任务成就界面
        $messageArr['achieveInfo']=TaskAndAchieveAction::getAchieveInfo($user_id);
        $messageArr['taskInfo']=TaskAndAchieveAction::getTaskInfo($user_id);

        $messageArr['mail_num'] = MailAction::getMailNum( $user_id);
        $messageArr['achieve_type']  = TaskAndAchieveAction::isFinish($user_id);
        $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
        $view = new JsonView();
        return $this->getViewByJson( $view, $messageArr, 1,"character/update_character_attribute" );

     }


    /**
     * API：购买主角
     *
     * @access public
     * @param 无
     * @return JsonView 响应json
     */
     public function exeBuyCharacter()
     {
        $requestParam = $this->getAllParameters();
        Logger::debug('requestParam:'. print_r($requestParam, true));

        $requestJsonParam = $this->getDecodedJsonRequest();
        Logger::debug('requestJsonParam:'. print_r($requestJsonParam, true));

        $user_id=$requestParam['user_id'];
        $character_id=$requestJsonParam['character_id'];
        $session_key=$requestParam['session_key'];

        $userCharacter=UserCache::getByKey( $user_id, self::CHARACTER_STRING);
        if (!$userCharacter)
        {
            $userCharacter=CharacterModel::getUserCharacterInfo($user_id);
            UserCache::setByKey($user_id, self::CHARACTER_STRING, $userCharacter);
        }

        //读取系统主角列表
        $str="";
        $str="character_id = ".$character_id;
        $file = IniFileManager::getRootDir()."/files/csv/character.csv";
        $characterArr=self::readCsv($file,$str);

        $price_type=self::$price_type;
        $type=$price_type[$characterArr[0]['price_type']];
        $money=UserCache::getByKey( $user_id, $type);
        if (!$money)
        {
            $userInfo=CharacterModel::getUserInfo($user_id);
            $money=$userInfo[$type];
        }
        $money=$money-$characterArr[0]['price'];
        if ($money<0)
        {
            $messageArr['error']="人生果/钻石不足！";
            $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
            $view = new JsonView();
            return $this->getViewByJson( $view, $messageArr, 0,"character/buy_character" );         
        }
        if ($userCharacter[$character_id]) 
        {
            $messageArr['error']="您已拥有该角色！无需购买！";
            $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
            $view = new JsonView();
            return $this->getViewByJson( $view, $messageArr, 0,"character/buy_character" );
        }

        //添加新角色到主角信息字段、更新用户金钱
        $userCharacter[0]=$character_id;
        $userCharacter[$character_id]=array('n_level'=>$characterArr[0]['level'],
                                            'n_attack_level'=>0,
                                            'n_crit_level'=>0,
                                            'n_hp_level'=>0,
                                            'wait_time'=>0);
        $s_role_info=serialize($userCharacter);
        $res=CharacterModel::update(array($type=>$money,'s_role_info'=>$s_role_info,'n_head'=>$character_id),array('n_id'=>$user_id));
        if(is_null($res))
        {
            throw new ModelException( 'update false pa_user_master' );
        }
        //任务成就统计
        if ($type==$price_type[1]) 
        {
            TaskAndAchieveAction::achieveStatistic($user_id,array('cost'=>$characterArr[0]['price']));
        } 

        UserCache::setByKey($user_id, $type, $money);
        UserCache::setByKey($user_id, self::CHARACTER_STRING, $userCharacter);
        UserCache::setByKey($user_id, 'n_head', $character_id);
        $messageArr=self::GetAllCharacterInfo($user_id);
        $messageArr['battle']=UserAction::getUserBattle( $user_id );

        //任务成就界面
        $messageArr['achieveInfo']=TaskAndAchieveAction::getAchieveInfo($user_id);
        $messageArr['taskInfo']=TaskAndAchieveAction::getTaskInfo($user_id);

        $messageArr['mail_num'] = MailAction::getMailNum( $user_id);
        $messageArr['achieve_type']  = TaskAndAchieveAction::isFinish($user_id);
        $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
        $view = new JsonView();
        return $this->getViewByJson( $view, $messageArr, 1,"character/buy_character" );

     }


    /**
     * API：进化主角
     *
     * @access public
     * @param 无
     * @return JsonView 响应json
     */
     public function exeUpdateCharacter()
     {
        $requestParam = $this->getAllParameters();
        Logger::debug('requestParam:'. print_r($requestParam, true));

        $requestJsonParam = $this->getDecodedJsonRequest();
        Logger::debug('requestJsonParam:'. print_r($requestJsonParam, true));
        
        $user_id=$requestParam['user_id'];
        $character_id=$requestJsonParam['character_id'];
        $session_key=$requestParam['session_key'];

        $userCharacter=UserCache::getByKey( $user_id, self::CHARACTER_STRING);
        if (!$userCharacter)
        {
            $userCharacter=CharacterModel::getUserCharacterInfo($user_id);
            UserCache::setByKey($user_id, self::CHARACTER_STRING, $userCharacter);
        }
        
        $str="";
        $str="character_id = ".$character_id.$userCharacter[$character_id]['n_level'];
        $file = IniFileManager::getRootDir()."/files/csv/character_update_grade.csv";
        $characterArr=self::readCsv($file,$str);
        //最大等阶判断
        $str2="";
        $str2="character_id = ".$character_id;
        $file2 = IniFileManager::getRootDir()."/files/csv/character.csv";
        $characterInfo=self::readCsv($file2,$str2);

        if ($characterInfo[0]['bigest_level']==$userCharacter[$character_id]['n_level']) 
        {
            $messageArr['error']="角色已达最大等阶！";
            $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
            $view = new JsonView();
            return $this->getViewByJson( $view, $messageArr, 0,"character/update_character" );
        }

        //金额判断
        $price_type=self::$price_type;
        $type=$price_type[$characterArr[0]['price_type']];
        $money=UserCache::getByKey( $user_id, $type);
        if (!$money) 
        {
            $userInfo=CharacterModel::getUserInfo($user_id);
            $money=$userInfo[$type];
        }
        $money=$money-$characterArr[0]['price'];
        if ($money<0) 
        {
            $messageArr['error']="魂石不足！";
            $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
            $view = new JsonView();
            return $this->getViewByJson( $view, $messageArr, 0,"character/update_character" );
        }

        //进化条件判断
        foreach (self::$attribute as $key => $value) 
        {
            $updateCondit.=$userCharacter[$character_id]['n_level'].$key.$userCharacter[$character_id][$value].',';
        }
        $updateCondit=substr($updateCondit, 0,-1);
        if ($updateCondit!=$characterArr[0]['condition']) 
        {
            $messageArr['error']="进阶条件不足！";
            $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
            $view = new JsonView();
            return $this->getViewByJson( $view, $messageArr, 0,"character/update_character" );
        }
        //更新 等待的时间
        $nowTime=time();
        $time=$nowTime+$characterArr[0]['wait_time']*3600;
        $userCharacter[$character_id]['wait_time']=$time;

        $s_role_info=serialize($userCharacter);
        $res=CharacterModel::update(array($type=>$money,'s_role_info'=>$s_role_info),array('n_id'=>$user_id));
        if(is_null($res))
        {
            throw new ModelException( 'update false pa_user_master' );
        }
        
        UserCache::setByKey($user_id, $type, $money);
        UserCache::setByKey($user_id, self::CHARACTER_STRING, $userCharacter);
        $messageArr=self::GetAllCharacterInfo($user_id);
        $messageArr['battle']=UserAction::getUserBattle( $user_id );
        //任务成就界面
        $messageArr['achieveInfo']=TaskAndAchieveAction::getAchieveInfo($user_id);
        $messageArr['taskInfo']=TaskAndAchieveAction::getTaskInfo($user_id);

        $messageArr['mail_num'] = MailAction::getMailNum( $user_id);
        $messageArr['achieve_type']  = TaskAndAchieveAction::isFinish($user_id);
        $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
        $view = new JsonView();
        return $this->getViewByJson( $view, $messageArr, 1,"character/update_character" );
     }


    /**
     * API：主角完成进化
     *
     * @access public
     * @param 无
     * @return JsonView 响应json
     */
     public function exeFinishUpdateCharacter()
     {
        $requestParam = $this->getAllParameters();
        Logger::debug('requestParam:'. print_r($requestParam, true));

        $requestJsonParam = $this->getDecodedJsonRequest();
        Logger::debug('requestJsonParam:'. print_r($requestJsonParam, true));
        
        $user_id=$requestParam['user_id'];
        $character_id=$requestJsonParam['character_id'];
        $session_key=$requestParam['session_key'];

        $userCharacter=UserCache::getByKey( $user_id, self::CHARACTER_STRING);
        if (!$userCharacter)
        {
            $userCharacter=CharacterModel::getUserCharacterInfo($user_id);
            UserCache::setByKey($user_id, self::CHARACTER_STRING, $userCharacter);
        }
        
        $str="";
        $str="character_id = ".$character_id.$userCharacter[$character_id]['n_level'];
        $file = IniFileManager::getRootDir()."/files/csv/character_update_grade.csv";
        $characterArr=self::readCsv($file,$str);

        //等待时间是否达到
        $nowTime=time();
        if ($nowTime-$userCharacter[$character_id]['wait_time']<0) 
        {
            $messageArr['error']="进阶等待时间未达到！";
            $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
            $view = new JsonView();
            return $this->getViewByJson( $view, $messageArr, 0,"character/update_character" );
        }

        //进化完成，更新主角信息
        $level=substr($characterArr[0]['next_grade'], 1,1);
        $userCharacter[$character_id]=array('n_level'=>$level,
                                            'n_attack_level'=>0,
                                            'n_crit_level'=>0,
                                            'n_hp_level'=>0,
                                            'wait_time'=>0);
        $s_role_info=serialize($userCharacter);
        $res=CharacterModel::update(array('s_role_info'=>$s_role_info),array('n_id'=>$user_id));
        if(is_null($res))
        {
            throw new ModelException( 'update false pa_user_master' );
        } 

        //任务成就统计
        TaskAndAchieveAction::achieveStatistic($user_id,array('update_times'=>1));

        UserCache::setByKey($user_id, self::CHARACTER_STRING, $userCharacter);

        //更新战斗力
        $battle=UserAction::getUserBattle($user_id);
        CharacterModel::update(array('n_battle'=>$battle),array('n_id'=>$user_id));
        UserCache::setByKey($user_id, 'n_battle', $battle);

        $messageArr=self::GetAllCharacterInfo($user_id);
        $messageArr['battle']=$battle;

        //任务成就界面
        $messageArr['achieveInfo']=TaskAndAchieveAction::getAchieveInfo($user_id);
        $messageArr['taskInfo']=TaskAndAchieveAction::getTaskInfo($user_id);

        $messageArr['mail_num'] = MailAction::getMailNum( $user_id);
        $messageArr['achieve_type']  = TaskAndAchieveAction::isFinish($user_id);
        $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
        $view = new JsonView();
        return $this->getViewByJson( $view, $messageArr, 1,"character/finish_update_character" );

     }


    /**
     * API：使用钻石跳过进阶等待时间
     *
     * @access public
     * @param 无
     * @return JsonView 响应json
     */
     public function exeCostUpdateCharacter()
     {
        $requestParam = $this->getAllParameters();
        Logger::debug('requestParam:'. print_r($requestParam, true));

        $requestJsonParam = $this->getDecodedJsonRequest();
        Logger::debug('requestJsonParam:'. print_r($requestJsonParam, true));
        
        $user_id=$requestParam['user_id'];
        $character_id=$requestJsonParam['character_id'];
        $session_key=$requestParam['session_key'];

        $userCharacter=UserCache::getByKey( $user_id, self::CHARACTER_STRING);
        if (!$userCharacter)
        {
            $userCharacter=CharacterModel::getUserCharacterInfo($user_id);
            UserCache::setByKey($user_id, self::CHARACTER_STRING, $userCharacter);
        }
        
        $str="";
        $str="character_id = ".$character_id.$userCharacter[$character_id]['n_level'];
        $file = IniFileManager::getRootDir()."/files/csv/character_update_grade.csv";
        $characterArr=self::readCsv($file,$str);

        //等待时间
        $wait_time=$userCharacter[$character_id]['wait_time']-time();
        $wait_time=$wait_time>0?$wait_time:0;
        $price_type=self::$price_type;
        $type=$price_type[Constants::MATA_TYPE];
        $money=UserCache::getByKey( $user_id, $type);
        if (!$money) 
        {
            $userInfo=CharacterModel::getUserInfo($user_id);
            $money=$userInfo[$type];
        }
        $money=$money- ceil($wait_time/3600)*Constants::UPDATE_BASE;
        if ($money<0) 
        {
            $messageArr['error']="钻石不足！";
            $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
            $view = new JsonView();
            return $this->getViewByJson( $view, $messageArr, 0,"character/update_character" );
        }

        //进化完成，更新主角信息
        $level=substr($characterArr[0]['next_grade'], 1,1);
        $userCharacter[$character_id]=array('n_level'=>$level,
                                            'n_attack_level'=>0,
                                            'n_crit_level'=>0,
                                            'n_hp_level'=>0,
                                            'wait_time'=>0);
        $s_role_info=serialize($userCharacter);
        $res=CharacterModel::update(array('s_role_info'=>$s_role_info,$type=>$money),array('n_id'=>$user_id));
        if(is_null($res))
        {
            throw new ModelException( 'update false pa_user_master' );
        } 

        //任务成就统计
        TaskAndAchieveAction::achieveStatistic($user_id,array('update_times'=>1));

        UserCache::setByKey($user_id, self::CHARACTER_STRING, $userCharacter);
        UserCache::setByKey($user_id, $type, $money);

        //更新战斗力
        $battle=UserAction::getUserBattle($user_id);
        CharacterModel::update(array('n_battle'=>$battle),array('n_id'=>$user_id));
        UserCache::setByKey($user_id, 'n_battle', $battle);

        $messageArr=self::GetAllCharacterInfo($user_id);
        $messageArr['battle']=$battle;

        //任务成就界面
        $messageArr['achieveInfo']=TaskAndAchieveAction::getAchieveInfo($user_id);
        $messageArr['taskInfo']=TaskAndAchieveAction::getTaskInfo($user_id);

        $messageArr['mail_num'] = MailAction::getMailNum( $user_id);
        $messageArr['achieve_type']  = TaskAndAchieveAction::isFinish($user_id);
        $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
        $view = new JsonView();
        return $this->getViewByJson( $view, $messageArr, 1,"character/cost_update_character" );

     }


    /**
     * API：更改上阵主角,及头像
     *
     * @access public
     * @param 无
     * @return JsonView 响应json
     */
     public function exeChangeFightCharacter()
     {
        $requestParam = $this->getAllParameters();
        Logger::debug('requestParam:'. print_r($requestParam, true));

        $requestJsonParam = $this->getDecodedJsonRequest();
        Logger::debug('requestJsonParam:'. print_r($requestJsonParam, true));
        
        $user_id=$requestParam['user_id'];
        $character_id=$requestJsonParam['character_id'];
        $session_key=$requestParam['session_key'];
        $userCharacter=UserCache::getByKey( $user_id, self::CHARACTER_STRING);
        if (!$userCharacter)
        {
            $userCharacter=CharacterModel::getUserCharacterInfo($user_id);
            UserCache::setByKey($user_id, self::CHARACTER_STRING, $userCharacter);
        }

        if (!$userCharacter[$character_id]) 
        {
            $messageArr['error']="尚未拥有该角色！";
            $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
            $view = new JsonView();
            return $this->getViewByJson( $view, $messageArr, 0,"character/change_fight_character" );
        }

        $userCharacter[0]=$character_id;
        $s_role_info=serialize($userCharacter);
        $res=CharacterModel::update(array('s_role_info'=>$s_role_info,'n_head'=>$character_id),array('n_id'=>$user_id));
        if (!$res) 
        {
            throw new Exception("update false");
        }

        UserCache::setByKey($user_id, self::CHARACTER_STRING, $userCharacter);
        UserCache::setByKey($user_id, 'n_head', $character_id);

        //更新战斗力
        $battle=UserAction::getUserBattle($user_id);
        CharacterModel::update(array('n_battle'=>$battle),array('n_id'=>$user_id));
        UserCache::setByKey($user_id, 'n_battle', $battle);

        $messageArr=self::GetAllCharacterInfo($user_id);
        $messageArr['battle']=$battle;

        //任务成就界面
        $messageArr['achieveInfo']=TaskAndAchieveAction::getAchieveInfo($user_id);
        $messageArr['taskInfo']=TaskAndAchieveAction::getTaskInfo($user_id);

        $messageArr['mail_num'] = MailAction::getMailNum( $user_id);
        $messageArr['achieve_type']  = TaskAndAchieveAction::isFinish($user_id);
        $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
        $view = new JsonView();
        return $this->getViewByJson( $view, $messageArr, 1,"character/change_fight_character" );

     }


      /**
     * API：获取主角所有信息
     *
     * @access public
     * @param int $user_id 用户ID 
     * @return array
     */
    public function GetAllCharacterInfo($user_id)
    { 
        $userCharacter=UserCache::getByKey( $user_id, self::CHARACTER_STRING);
        if (!$userCharacter) 
        {
            $userCharacter=CharacterModel::getUserCharacterInfo($user_id);
            UserCache::setByKey($user_id, self::CHARACTER_STRING, $userCharacter);
        }
        
        //读取系统主角，属性列表
        $file = IniFileManager::getRootDir()."/files/csv/character.csv";
        $characterArr=self::readCsv($file);
        foreach ($characterArr as $key => $value) {
            if ($userCharacter[$value['character_id']]) 
            {
                $characterArr[$key]['n_get']=1;
                $characterArr[$key]['battle']=self::getCharacterBattle($user_id,$value['character_id']);
                $characterArr[$key]['updateInfo']=self::getUpdateInfo($user_id,$value['character_id']);
                $characterArr[$key]['level']=$userCharacter[$value['character_id']]['n_level'];
                $characterArr[$key]['bigest_level']=$value['bigest_level'];
                $characterArr[$key]['attributeInfo']=self::GetCharacterAttribute($user_id,$value['character_id']);
            }
            else
            { 
                $characterArr[$key]['n_get']=0;
                $characterArr[$key]['battle']=self::getCharacterBattle($user_id,$value['character_id']);
                $characterArr[$key]['updateInfo']=self::getUpdateInfo($user_id,$value['character_id']);
                $characterArr[$key]['level']=$value['level'];
                $characterArr[$key]['bigest_level']=$value['bigest_level'];
                $characterArr[$key]['attributeInfo']=self::GetCharacterAttribute($user_id,$value['character_id']);
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
        $messageArr['characterInfo']=$characterArr;
        $messageArr['n_fight_id']=$userCharacter[0];
        return $messageArr;

    }

      /**
     * API：获取已拥有主角属性信息
     *
     * @access public
     * @param int $user_id 用户ID $character_id主角ID
     * @return array
     */
    public function GetCharacterAttribute($user_id,$character_id)
    {   
        $ret=UserCache::getByKey( $user_id, self::CHARACTER_STRING);
        if (!$ret)
        {
            $ret=CharacterModel::getUserCharacterInfo($user_id);
            UserCache::setByKey($user_id, self::CHARACTER_STRING, $ret);
        }   
        //初始等阶 
        $string="";
        $string="character_id = ".$character_id;
        $path = IniFileManager::getRootDir()."/files/csv/character.csv";
        $characterInfo=self::readCsv($path,$string);
        $minLevel=$characterInfo[0]['level'];
        $maxLevel=$characterInfo[0]['bigest_level'];

        //读取当前属性等级条件
        $str="";
        foreach (self::$attribute as $key => $value) 
        {   
            $level=$ret[$character_id]['n_level']?$ret[$character_id]['n_level']:$minLevel;
            $levelNum=$ret[$character_id][$value]?$ret[$character_id][$value]:0;
            $str.='update_id = '.$level.$key.$levelNum.' OR ';
        }
        $str=substr($str, 0,-4);
        
        // 读取csv类，读取目标文件
        $file = IniFileManager::getRootDir()."/files/csv/character".$character_id."_update_value.csv";
        $nowArr=self::readCsv($file,$str);

        //读取下一等级
        foreach (self::$attribute as $key => $value) 
        {
            $level=$ret[$character_id]['n_level']?$ret[$character_id]['n_level']:$minLevel;
            $levelNum=$ret[$character_id][$value]?$ret[$character_id][$value]:0;
            if ($ret[$character_id][$value]<Constants::MAX_ATTRIBUTE) 
            {
                $levelNum=$levelNum+1;
                $str2.='update_id = '.$level.$key.$levelNum.' OR ';
            }
            else 
            {
                if ($level==$maxLevel) 
                {
                    $str2.='update_id = '.$level.$key.$levelNum.' OR ';
                }
                else
                {
                    $levelArr=array(1=>'C',2=>'B',3=>'A',4=>'S');
                    $num=array_search($level, $levelArr);
                    $nextLevel=$levelArr[$num+1];
                    $str2.='update_id = '.$nextLevel.$key.'0'.' OR ';
                }
            }
        }
        $str2=substr($str2, 0,-4);
        $nextArr=self::readCsv($file,$str2);

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
           $arr[$k]['max_level']=Constants::MAX_ATTRIBUTE;
        }

        return $arr;
    }


    /**
     * API：进阶信息
     *
     * @access public
     * @param $file 路径 $str 读取条件OR
     * @return array()
     */
     public function getUpdateInfo($user_id,$character_id)
     {
        $ret=UserCache::getByKey( $user_id, self::CHARACTER_STRING);
        if (!$ret) 
        {
            $ret=CharacterModel::getUserCharacterInfo($user_id);
            UserCache::setByKey($user_id, self::CHARACTER_STRING, $ret);
        } 
        //初始等阶 
        $string="";
        $string="character_id = ".$character_id;
        $path = IniFileManager::getRootDir()."/files/csv/character.csv";
        $characterInfo=self::readCsv($path,$string);
        $minLevel=$characterInfo[0]['level'];

        $file1= IniFileManager::getRootDir()."/files/csv/character_update_grade.csv";
        $level=$ret[$character_id]['n_level']?$ret[$character_id]['n_level']:$minLevel;
        $str3="character_id = ".$character_id.$level;
        $updateInfo=self::readCsv($file1,$str3);
        
        $arr['cost_type']=$updateInfo[0]['price_type']?$updateInfo[0]['price_type']:0;
        $arr['cost_num']=$updateInfo[0]['price']?$updateInfo[0]['price']:0;
        $wait_time=$ret[$character_id]['wait_time']-time();
        //是否在进阶状态
        $arr['is_update']=0;
        if ($ret[$character_id]['wait_time']>0) 
        {
            $arr['is_update']=1;
        }

        $arr['wait_time']=$wait_time>0?$wait_time:0;
        $arr['mata_type']=Constants::MATA_TYPE;
        $arr['mata_num']=ceil($arr['wait_time']/3600)*Constants::UPDATE_BASE;
        return $arr;
     }


    /**
     * API：注册时主角战斗力
     *
     * @access public
     * @param int $user_id 用户ID $character_id主角ID
     * @return array
     */
    public function getCharacterBattle($user_id,$character_id=null)
    {
        $userCharacter=UserCache::getByKey( $user_id, self::CHARACTER_STRING);
        if (!$userCharacter) 
        {
            $userCharacter=CharacterModel::getUserCharacterInfo($user_id);
            UserCache::setByKey($user_id, self::CHARACTER_STRING, $userCharacter);
        } 

        if (is_null($character_id)) 
        {
            $character_id=$userCharacter[0];
        }

        $characterInfo=self::GetCharacterAttribute($user_id,$character_id);

        $battle=round($characterInfo[0]['value']*($characterInfo[1]['value']*3+1)*log($characterInfo[2]['value']/10+1)*100);
        return $battle;
    }


    /**
     * API：好友合体后信息
     *
     * @access public
     * @param int $user_id 用户ID $battle 好友战斗力
     * @return array
     */
    public function getFitBattleInfo($user_id,$battle)
    {
        $userCharacter=UserCache::getByKey( $user_id, self::CHARACTER_STRING);
        if (!$userCharacter) 
        {
            $userCharacter=CharacterModel::getUserCharacterInfo($user_id);
            UserCache::setByKey($user_id, self::CHARACTER_STRING, $userCharacter);
        } 
        
        $character_id=$userCharacter[0];
        $characterInfo=self::GetCharacterAttribute($user_id,$character_id);

        $battleInfo['attack']=round($characterInfo[0]['value']*(1.1+$battle/200000),1)-$characterInfo[0]['value'];
        $battleInfo['crit']=round($characterInfo[1]['value']+$battle/600000,3)-$characterInfo[1]['value'];
        $battleInfo['hp']=round($characterInfo[2]['value']*(1.1+$battle/200000),1)-$characterInfo[2]['value'];
        $userBattle=round($characterInfo[0]['value']*($characterInfo[1]['value']*3+1)*log($characterInfo[2]['value']/10+1)*100);
        $battleInfo['add_battle']=round($battleInfo['attack']*($battleInfo['crit']*3+1)*log($battleInfo['hp']/10+1)*100)-$userBattle;
        return $battleInfo;
    }

     /**
     * API：获取当前上阵主角信息
     *
     * @access public
     * @param $integer $user_id $general_id 用户ID，武将ID
     * @return array 
     */
     public function nowCharacterInfo($user_id)
    {
        $userCharacter=UserCache::getByKey( $user_id, self::CHARACTER_STRING);
        if (!$userCharacter) 
        {
            $userCharacter=CharacterModel::getUserCharacterInfo($user_id);
            UserCache::setByKey($user_id, self::CHARACTER_STRING, $userCharacter);
        }
        return self::GetCharacterAttribute($user_id,$userCharacter[0]);
    }

    
    /**
     * API：读取CSv
     *
     * @access public
     * @param $file 路径 $str 读取条件OR
     * @return array()
     */
     public function readCsv($file,$str=null)
     {
     //先读缓存，foreach需要的数据，没有找到，在用condition读取CSV
        $fileName=basename($file,".csv");
        $cache= MemcacheManager::instance();
        $CharacterDataArr=$cache->get($fileName);

        if (!$CharacterDataArr) 
        {
            $csv = new Parsecsv();
            $csv->conditions=$str;
            $csv->auto( $file);
            $CharacterDataArr = $csv->data;
            return $CharacterDataArr;
        }
        else
        {
            if (is_null($str)) 
            {
                 return $CharacterDataArr;
            }
            $conditionArr=explode(" OR ", $str);
            foreach ($conditionArr as $key => $value) 
            {
                $condition=explode(" = ", $value);
                $message[$key][$condition[0]]=$condition[1];
            }
           
            foreach ($message as $key => $value) 
            {
                foreach ($value as $key2 => $value2)
                {
                   foreach ($CharacterDataArr as $key3 => $value3) {
                        if ($value2==$CharacterDataArr[$key3][$key2]) {
                            $arr[]=$CharacterDataArr[$key3];
                        }
                    } 
                }
            }
            return $arr;
        }
     }


      /**
     * API：注册时主角初始化
     *
     * @access public
     * @param int $user_id 用户ID $character_id主角ID
     * @return array
     */
    public function registCharacter($user_id,$character_id)
    {   
        $str="character_id = ".$character_id;
        $file = IniFileManager::getRootDir()."/files/csv/character.csv";
        $characterArr=self::readCsv($file,$str);
        $userCharacter[$character_id]=array('n_level'=>$characterArr[0]['level'],
                                            'n_attack_level'=>0,
                                            'n_crit_level'=>0,
                                            'n_hp_level'=>0,
                                            'wait_time'=>0);
        $userCharacter[0]=$character_id;
        $s_role_info=serialize($userCharacter);
        return $s_role_info;
    }

      /**
     * API：注册登陆时获取所有界面信息
     *
     * @access public
     * @param int $user_id 用户ID $character_id主角ID
     * @return array
     */
    public function getAllMessage($user_id)
    {   
        //主角界面
        $characterInfo=CharacterAction::GetAllCharacterInfo($user_id);

        //武将界面
        $generalInfo=GeneralAction::GetAllGeneralInfo($user_id);
        $messageArr=array_merge($characterInfo,$generalInfo);

        //任务成就界面
        $messageArr['achieveInfo']=TaskAndAchieveAction::getAchieveInfo($user_id);
        $messageArr['taskInfo']=TaskAndAchieveAction::getTaskInfo($user_id);

        //商城数据
        $messageArr['mallInfo']=BuyPropAction::exeGetMallInfo($user_id);

        //道具数据
        $messageArr['itemInfo']=BuyPropAction::exeGetPropInfo($user_id);

        //好友界面
        $messageArr['friendInfo']=FriendAction::exeGetFriendMenu($user_id);

        $messageArr['mail_num'] = MailAction::getMailNum( $user_id);
        $messageArr['achieve_type']  = TaskAndAchieveAction::isFinish($user_id);

        //复活信息
        $messageArr['resurrextInfo']['price_type']=Constants::RESURE_TYPE;
        $messageArr['resurrextInfo']['price']=Constants::RESURE_COST;
        $messageArr['resurrextInfo']['hp']=Constants::RESURE_HP;
        $messageArr['resurrextInfo']['time']=Constants::RESURE_TIME;

        //BOSS关卡评分
        $messageArr['bossPoint']['time']=Constants::BOSS_TIME;
        $messageArr['bossPoint']['less_time']=Constants::LESS_TIME;
        $messageArr['bossPoint']['more_time']=Constants::MORE_TIME;  

        return $messageArr;

    }

}