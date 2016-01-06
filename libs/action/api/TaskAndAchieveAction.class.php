<?php
/**
 * task_and_achieve类
 *
 *
 * @access public
 * @author chenwenbin
 * @copyright Copyright(C) Tenone Inc.
 * @version 0.01
 * @since 2014/11/13
 */

class TaskAndAchieveAction extends BaseAction {

    const ACHIEVEMENT_STRING='s_achievement_info';

    const TASK_STRING='s_task_info';

    public $price_type= array(
            1 => 'n_coin',
            2 => 'n_diamond',
            3 => 'n_soul',
            4 => 'n_thew'
        );

     /**
     * API：获取任务成就信息
     *
     * @access public
     * @param 无
     * @return JsonView 响应json
     */
	public function exeGetAllInfo()
	{
        $requestParam = $this->getAllParameters();
        Logger::debug('requestParam:'. print_r($requestParam, true));

        $requestJsonParam = $this->getDecodedJsonRequest();
        Logger::debug('requestJsonParam:'. print_r($requestJsonParam, true));

        $user_id=$requestParam['user_id'];
        $session_key=$requestParam['session_key'];        
        $messageArr['achieveInfo']=self::getAchieveInfo($user_id);
        $messageArr['taskInfo']=self::getTaskInfo($user_id);

        //session_key
        $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);

        $view = new JsonView();
        return $this->getViewByJson( $view, $messageArr, 1,"task_and_achieve/get_all_info" );
	}

     /**
     * API：领取成就奖励
     *
     * @access public
     * @param 无
     * @return JsonView 响应json
     */
    public function exeGetAchieveReward()
    {
     	$requestParam = $this->getAllParameters();
        Logger::debug('requestParam:'. print_r($requestParam, true));

        $requestJsonParam = $this->getDecodedJsonRequest();
        Logger::debug('requestJsonParam:'. print_r($requestJsonParam, true));

        $user_id=$requestParam['user_id'];
        $achievement_id=$requestJsonParam['achievement_id'];
        $session_key=$requestParam['session_key'];

        $userAchieve=UserCache::getByKey($user_id,self::ACHIEVEMENT_STRING);
        if (!$userAchieve) 
        {
            $userAchieve=TaskAchieveModel::getUserInfoByCondition($user_id,self::ACHIEVEMENT_STRING);
            UserCache::setByKey($user_id,self::ACHIEVEMENT_STRING,$userAchieve);
        }

        $str="achievement_id = ".$achievement_id."_".($userAchieve[$achievement_id]['n_level']+1);
        $file = IniFileManager::getRootDir()."/files/csv/achievement.csv";
        $achieveInfo=CharacterAction::readCsv($file,$str);
        if (!$achieveInfo) 
        {
            $messageArr['error']="奖励已领取！";
            $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
            $view = new JsonView();
            return $this->getViewByJson( $view, $messageArr, 0,"task_and_achieve/get_achieve_reward" );
        }
        //领取条件判断
        if ($userAchieve[$achievement_id]['n_num']<$achieveInfo[0]['condition'])
        {
            $messageArr['error']="领取条件不足！";
            $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
            $view = new JsonView();
            return $this->getViewByJson( $view, $messageArr, 0,"task_and_achieve/get_achieve_reward" );
        }

        //更新成就阶段，及金钱
        $price_type=$this->price_type;
        $type=$price_type[$achieveInfo[0]['reward_type']];
        $money=UserCache::getByKey($user_id,$type);
        if (!$money)
        {
            $userInfo=TaskAchieveModel::getUserInfo($user_id);
            $money=$userInfo[$type];
        }
        $money=$money+$achieveInfo[0]['reward_num'];

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

        $userAchieve[$achievement_id]['n_level']=$userAchieve[$achievement_id]['n_level']+1;
        $s_achievement_info=serialize($userAchieve);
        $res=TaskAchieveModel::update(array('s_achievement_info'=>$s_achievement_info),array('n_id'=>$user_id));
        if (!$res) 
        {
            throw new Exception("update false");
        }
        UserCache::setByKey($user_id,self::ACHIEVEMENT_STRING,$userAchieve);
         //任务成就统计
        if ($type==$price_type[1]) 
        {           
            TaskAndAchieveAction::taskStatistic($user_id,array('reward'=>$achieveInfo[0]['reward_num']));
        }
        if ($type==$price_type[3]) 
        {           
            //TaskAndAchieveAction::taskStatistic($user_id,array('soul'=>$achieveInfo[0]['reward_num']));
            TaskAndAchieveAction::achieveStatistic($user_id,array('soul'=>$achieveInfo[0]['reward_num']));
        }
        // sessdion_key
        $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
        $messageArr['achieveInfo']=self::getAchieveInfo($user_id);
        $messageArr['taskInfo']=self::getTaskInfo($user_id);
        $view = new JsonView();
        return $this->getViewByJson( $view, $messageArr, 1,"task_and_achieve/get_achieve_reward" );
	
    }


     /**
     * API：领取任务奖励
     *
     * @access public
     * @param 无
     * @return JsonView 响应json
     */
    public function exeGetTaskReward()
    {
    	$requestParam = $this->getAllParameters();
        Logger::debug('requestParam:'. print_r($requestParam, true));

        $requestJsonParam = $this->getDecodedJsonRequest();
        Logger::debug('requestJsonParam:'. print_r($requestJsonParam, true));

        $user_id=$requestParam['user_id'];
        $task_id=$requestJsonParam['task_id'];
        $session_key=$requestParam['session_key'];

         $userTask=UserCache::getByKey($user_id,self::TASK_STRING);
        if (!$userTask) 
        {
            $userTask=TaskAchieveModel::getUserInfoByCondition($user_id,self::TASK_STRING);
            UserCache::setByKey($user_id,self::TASK_STRING,$userTask);
        }

        $str="task_id = ".$task_id."_".($userTask[0][$task_id]);
        $file = IniFileManager::getRootDir()."/files/csv/task.csv";
        $taskInfo=CharacterAction::readCsv($file,$str);
        
        //完成条件的判断
        if ($userTask[$task_id]['n_num']<$taskInfo[0]['condition']) 
        {
            $messageArr['error']="领取条件不足！";
            $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
            $view = new JsonView();
            return $this->getViewByJson( $view, $messageArr, 0,"task_and_achieve/get_task_reward" );

        }

        //是否领取的判断
        if ($userTask[$task_id]['n_reward']!=0) 
        {
            $messageArr['error']="已领取该奖励！";
            $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
            $view = new JsonView();
            return $this->getViewByJson( $view, $messageArr, 0,"task_and_achieve/get_task_reward" );
        }
        //更新任务状态，及金钱
        $price_type=$this->price_type;
        $type=$price_type[$taskInfo[0]['reward_type']];
        $money=UserCache::getByKey($user_id,$type);
        if (!$money) 
        {
            $userInfo=TaskAchieveModel::getUserInfo($user_id);
            $money=$userInfo[$type];
        }
        $money=$money+$taskInfo[0]['reward_num'];
        
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

        $userTask[$task_id]['n_reward']=1;
        $s_task_info=serialize($userTask);
        $res=TaskAchieveModel::update(array('s_task_info'=>$s_task_info),array('n_id'=>$user_id));
        if (!$res) 
        {
            throw new Exception("update false");
        }
        UserCache::setByKey($user_id,self::TASK_STRING,$userTask);
         //任务成就统计
        if ($type==$price_type[1]) 
        {           
            TaskAndAchieveAction::taskStatistic($user_id,array('reward'=>$taskInfo[0]['reward_num']));
        }
        if ($type==$price_type[3]) 
        {           
            //TaskAndAchieveAction::taskStatistic($user_id,array('soul'=>$taskInfo[0]['reward_num']));
            TaskAndAchieveAction::achieveStatistic($user_id,array('soul'=>$taskInfo[0]['reward_num']));
        }
        //sessdion_key
        $messageArr['session_key']=CharacterModel::setSessionKey($user_id,$session_key);
        $messageArr['achieveInfo']=self::getAchieveInfo($user_id);
        $messageArr['taskInfo']=self::getTaskInfo($user_id);
        $view = new JsonView();
        return $this->getViewByJson( $view, $messageArr, 1,"task_and_achieve/get_task_reward" );
	
    }

     /**
     * API：获取任务信息
     *
     * @access public
     * @param int $user_id 用户ID
     * @return array
     */
	public function getTaskInfo($user_id)
	{
        $userTask=UserCache::getByKey($user_id,self::TASK_STRING);
        if (!$userTask)
        {
            $userTask=TaskAchieveModel::getUserInfoByCondition($user_id,self::TASK_STRING);
            UserCache::setByKey($user_id,self::TASK_STRING,$userTask);
        }

        foreach ($userTask[0] as $key => $value) 
        {
        	$str.="task_id = ".$key."_".$value." OR ";
        }

        $str=substr($str, 0,-4);
        $file = IniFileManager::getRootDir()."/files/csv/task.csv";
        $taskInfo=CharacterAction::readCsv($file,$str);    
        foreach ($taskInfo as $key => $value) 
        {
        	$id=explode("_", $value['task_id']);
            unset($value['descript']);
        	$arr[]=array_merge($value,$userTask[$id[0]]);
        }

        return $arr;
	}


     /**
     * API：获取成就信息
     *
     * @access public
     * @param int $user_id 用户ID
     * @return array
     */
	public function getAchieveInfo($user_id)
	{
        $userAchieve=UserCache::getByKey($user_id,self::ACHIEVEMENT_STRING);
        if (!$userAchieve)
        {
            $userAchieve=TaskAchieveModel::getUserInfoByCondition($user_id,self::ACHIEVEMENT_STRING);
            UserCache::setByKey($user_id,self::ACHIEVEMENT_STRING,$userAchieve);
        }
        
        foreach ($userAchieve as $key => $value) 
        {
        	$level=$value['n_level'];
            if ($key==10) 
            {
                $userAchieve[$key]['max_level']=$value['max_level']=Constants::CHAPTER_NUM;
            }
        	if ($value['n_level']<$value['max_level']) 
        	{
        		$level=$value['n_level']+1;
        	}
        	
        	$str.="achievement_id = ".$key.'_'.$level." OR ";
        }
        $str=substr($str, 0,-4);
        $file = IniFileManager::getRootDir()."/files/csv/achievement.csv";
        $achieveInfo=CharacterAction::readCsv($file,$str);

        foreach ($achieveInfo as $key => $value) 
        {
			$achievement=explode('_',$value['achievement_id']);
            unset($value['descript']);
            unset($value['name']);
			//$value['achievement_id']=$achievement[0];
			$achieveInfo[$key]=array_merge($value,$userAchieve[$achievement[0]]);
            if ($achieveInfo[$key]['n_num']>$achieveInfo[$key]['condition']) 
            {
                $achieveInfo[$key]['n_num']=$achieveInfo[$key]['condition'];
            }
        }
        return($achieveInfo);
	}


     /**
     * API：注册时初始化成就信息
     *
     * @access public
     * @param int $user_id 用户ID
     * @return array
     */
	public function registAchieveCsv($user_id)
	{
		$file = IniFileManager::getRootDir()."/files/csv/achievement.csv";
		$achieveInfo=CharacterAction::readCsv($file);
		foreach ($achieveInfo as $key => $value) 
		{
			$achievement=explode('_',$value['achievement_id']);
			$userAchieve[$achievement[0]]=array('n_level'=>0,
				                                'n_num' =>0,
				                                'max_level'=>$achievement[1]
				                                );
		}
		$s_achievement_info=serialize($userAchieve);
        return $s_achievement_info;
		//$res=TaskAchieveModel::update(array('s_achievement_info'=>$s_achievement_info),array('n_id'=>$user_id));
		//$userAchieve2=TaskAchieveModel::getUserInfoByCondition($user_id,self::ACHIEVEMENT_STRING);

	}


     /**
     * API：Csv变更时，成就信息更新
     *
     * @access public
     * @param int $user_id 用户ID
     * @return array
     */
	public function updateAchieveCsv($user_id)
	{
		$file = IniFileManager::getRootDir()."/files/csv/achievement.csv";
		$achieveInfo=CharacterAction::readCsv($file);
        $userAchieve=UserCache::getByKey($user_id,self::ACHIEVEMENT_STRING);
        if (!$userAchieve) 
        {
            $userAchieve=TaskAchieveModel::getUserInfoByCondition($user_id,self::ACHIEVEMENT_STRING);
            UserCache::setByKey($user_id,self::ACHIEVEMENT_STRING,$userAchieve);
        }
		foreach ($achieveInfo as $key => $value) 
		{
			$achievement=explode('_',$value['achievement_id']);
			if ($userAchieve[$achievement[0]]) 
			{
				$userAchieve[$achievement[0]]['max_level']=$achievement[1];
			}
			else
			{
				$userAchieve[$achievement[0]]=array('n_level'=>0,
				                                    'n_num' =>0,
				                                    'max_level'=>$achievement[1]
				                                    );
            }
		}
		$s_achievement_info=serialize($userAchieve);
		$res=TaskAchieveModel::update(array('s_achievement_info'=>$s_achievement_info),array('n_id'=>$user_id));
	    //加入缓存
    }

     /**
     * API：每日任务随机
     *
     * @access public
     * @param int $user_id 用户ID
     * @return array
     */
	public function randTask($user_id)
	{
		$file = IniFileManager::getRootDir()."/files/csv/task.csv";
		$taskInfo=CharacterAction::readCsv($file);	
		foreach ($taskInfo as $key => $value) 
		{
			$task=explode('_',$value['task_id']);
			$taskArr[$task[0]][]=$task[1];
			$s_task_info[$task[0]]=array('n_num'=>0,
				                         'n_reward'=>0);
		}
        $keyArr=array_keys($taskArr);
		//武将，主角相关任务判断是否达到最高级，是否出这个任务(主角，武将在表格1、2位)
        $CharacterNum=self::isRoleMax($user_id);
        $generalNum=self::isGenerlMax($user_id);
		if ($CharacterNum<3) 
		{
			unset($keyArr[0]);
		}
        if ($generalNum<3) 
        {
            unset($keyArr[1]);
        }

        $isSoulExit=0;
		$taskIdArr=self::randShuffle($keyArr,Constants::TASK_NUM);
		foreach ($taskIdArr as $key => $value) 
		{
            if ($value==3) 
            {
                $userInfo=TaskAchieveModel::getUserInfo($user_id);
                $arr=range(1, $userInfo['n_max_checkpoint']);

                $levelNum=self::randShuffle($arr,1);
                $s_task_info[$value]['condition2']=(string)($levelNum[0]?$levelNum[0]:1);
            }
            if (in_array(4, $taskArr[$value])&&!$isSoulExit) 
            {
                $isSoulExit=1;
                $num=4;
                $s_task_info[0][$value]=$num;
                continue;
            }
            // elseif (in_array(5, $taskArr[$value])&&!$isThewExit) 
            // {
            //     $isThewExit=1;
            //     $num=5;
            //     $s_task_info[0][$value]=$num;
            //     continue;
            // }
            else
            {   $num=4;
                while ($num==4) 
                {
                    $level=self::randShuffle($taskArr[$value],1);
                    $num=$level[0];
                }
                $s_task_info[0][$value]=$num;       
            }
			
		}
		//$s_task_info=serialize($s_task_info);
		/*$res=TaskAchieveModel::update(array('s_task_info'=>$s_task_info),array('n_id'=>$user_id));
        if (!$res) 
        {
        	throw new Exception("update false");
        }*/
        return $s_task_info;

	}


     /**
     * API：随机
     *
     * @access public
     * @param int $user_id 用户ID
     * @return array
     */
	public function randShuffle($rand_array,$limit)
	{
    	shuffle($rand_array);
    	return array_slice($rand_array,0,$limit);
	}

     /**
     * 判断是否主角全满级
     *
     * @access public
     * @param int $user_id 用户ID
     * @return array
     */
    public function isRoleMax($user_id)
    {
        $file = IniFileManager::getRootDir()."/files/csv/character.csv";
        $characterArr=CharacterAction::readCsv($file);

        $userCharacter=UserCache::getByKey( $user_id, CharacterAction::CHARACTER_STRING);
        if (!$userCharacter) 
        {
            $userCharacter=CharacterModel::getUserCharacterInfo($user_id);
            UserCache::setByKey($user_id, CharacterAction::CHARACTER_STRING, $userCharacter);
        }
        $attribute = array(
            1 => 'n_attack_level',
            2 => 'n_crit_level',
            3 => 'n_hp_level');
        $CharacterNum=0;
        foreach ($characterArr as $key => $value) 
        {
            if ($userCharacter[$value['character_id']]&&$userCharacter[$value['character_id']]['n_level']==$value['bigest_level']) 
            {
                foreach ($attribute as $key2 => $value2) 
                {
                    $CharacterNum+=Constants::MAX_ATTRIBUTE-$userCharacter[$value['character_id']][$value2];
                }
            }
            else
            {
                $CharacterNum+=15;
            }
        }
        // if (count($userCharacter)>count($characterArr)) 
        // {
        //     foreach ($userCharacter as $key => $value) 
        //     {
        //         if ($value['n_level']==Constants::CHARACTER_MAX_LEVEL) 
        //         {
        //             foreach ($attribute as $key2 => $value2) 
        //             {
        //                 $CharacterNum+=Constants::MAX_ATTRIBUTE-$value[$value2];
        //             }
        //         }
        //         else
        //         {
        //             $generalNum=5;
        //         }
        //     }
        // }
        // else {
        //     $generalNum=10;
        // }
        return $CharacterNum;
    }

     /**
     * 判断是否主角全满级
     *
     * @access public
     * @param int $user_id 用户ID
     * @return array
     */
    public function isGenerlMax($user_id)
    {
        $file = IniFileManager::getRootDir()."/files/csv/general.csv";
        $generlArr=CharacterAction::readCsv($file);
        $userGeneral=UserCache::getByKey($user_id,'s_general_info');
        if (!$userGeneral) 
        {
            $userGeneral=GeneralModel::getUserGeneralInfo($user_id);
            UserCache::setByKey($user_id,'s_general_info',$userGeneral);
        }

        $attribute = array( 1=>'n_continue_level',
                            2=>'n_cool_level');
        $generalNum=0;
        if (count($userGeneral)>=count($generlArr)) 
        {
            foreach ($userGeneral as $key => $value) 
            {
                foreach ($attribute as $key2 => $value2) 
                {
                    $generalNum+=Constants::MAX_LEVEL-$value[$value2];
                }
            }
        }
        else
        {
            $generalNum=10;
        }
        return $generalNum;
    }

     /**
     * API：是否有任务成就完成
     *
     * @access public
     * @param int $user_id 用户ID
     * @return array
     */
    public function isFinish($user_id)
    {
        $achieveInfo=self::getAchieveInfo($user_id);
        $taskInfo=self::getTaskInfo($user_id);
        $num=0;

        foreach ($achieveInfo as $key => $value) 
        {
            if ($value['n_num']>=$value['condition']&&$value['n_level']<$value['max_level']) 
            {
               $num++;
            }
        }

        foreach ($taskInfo as $key => $value) 
        {
            if ($value['n_num']>=$value['condition']&&$value['n_reward']==0) 
            {
               $num++;
            }
        }

        if ($num>0) 
        {
            return  1;
        }
        else
        {
            return 0;
        }
    }


     /**
     * API：结算时任务成就完成提示
     *
     * @access public
     * @param int $user_id 用户ID
     * @return array
     */
    public function endNotice($user_id)
    {
        $achieveInfo=self::getAchieveInfo($user_id);
        $taskInfo=self::getTaskInfo($user_id);
        $arr['achieveInfo']=array();
        $num=0;

        foreach ($achieveInfo as $key => $value) 
        {
            if ($value['n_num']>=$value['condition']&&$value['n_level']<$value['max_level']) 
            {
               $arr['achieveInfo'][]=$value['achievement_id'];
            }
        }

        foreach ($taskInfo as $key => $value) 
        {
            if ($value['n_num']>=$value['condition']) 
            {
               $num++;
            }
            // if ($value['n_num']>=$value['condition']&&$value['n_reward']==0) 
            // {
            //    $num2++;
            // }
        }

        $arr['finish_num']=$num;
        return $arr;
    }



     /**
     * API：任务统计
     *
     * @access public
     * @param int $user_id 用户ID
     * @return array
     */
    public function taskStatistic($user_id,$dataArr)
    {
        $userTask=UserCache::getByKey($user_id,self::TASK_STRING);
        if (!$userTask) 
        {
            $userTask=TaskAchieveModel::getUserInfoByCondition($user_id,self::TASK_STRING);
            UserCache::setByKey($user_id,self::TASK_STRING,$userTask);
        }
        foreach ($userTask as $key => $value) 
        {
            $str="task_id = ".$key."_".($userTask[0][$key]);
            $file = IniFileManager::getRootDir()."/files/csv/task.csv";
            $taskInfo=CharacterAction::readCsv($file,$str);
            if ($userTask[0][$key]&&$userTask[$key]['n_num']<$taskInfo[0]['condition']) 
            {
                //1
                if($dataArr['character_up']&&$key==1)
                {       
                    $totalNum=$userTask[$key]['n_num']+1;
                    if ($totalNum>=$taskInfo[0]['condition']) 
                    {
                        $totalNum=$taskInfo[0]['condition'];
                    }
                    $userTask[$key]['n_num']=$totalNum;
                }

                //2
                if($dataArr['generl_up']&&$key==2)
                {       
                    $totalNum=$userTask[$key]['n_num']+1;
                    if ($totalNum>=$taskInfo[0]['condition']) 
                    {
                        $totalNum=$taskInfo[0]['condition'];
                    }
                    $userTask[$key]['n_num']=$totalNum;
                }
                //3
                if($dataArr['pass']==1&&$key==3&&$dataArr['check_point_id']==$userTask[$key]['condition2'])
                {      
                    $totalNum=$userTask[$key]['n_num']+$dataArr['check_point_id'];
                    if ($totalNum>=$taskInfo[0]['condition']) 
                    {
                        $totalNum=$taskInfo[0]['condition'];
                    }
                    $userTask[$key]['n_num']=$totalNum;
                }
                //4
                if($dataArr['monster']&&$key==4)
                {      
                    $totalNum=$userTask[$key]['n_num']+$dataArr['monster'];
                    if ($totalNum>=$taskInfo[0]['condition']) 
                    {
                        $totalNum=$taskInfo[0]['condition'];
                    }
                    $userTask[$key]['n_num']=$totalNum;
                }
                //5
                if($dataArr['pro_num']&&$key==5)
                {       
                    $totalNum=$userTask[$key]['n_num']+$dataArr['pro_num'];
                    if ($totalNum>=$taskInfo[0]['condition']) 
                    {
                        $totalNum=$taskInfo[0]['condition'];
                    }
                    $userTask[$key]['n_num']=$totalNum;
                }
                //6
                if($dataArr['pass']===0&&$dataArr['lose_type']==1&&$key==6)
                {       
                    $totalNum=$userTask[$key]['n_num']+1;
                    if ($totalNum>=$taskInfo[0]['condition']) 
                    {
                        $totalNum=$taskInfo[0]['condition'];
                    }
                    $userTask[$key]['n_num']=$totalNum;
                }
                //7
                if($dataArr['pass']==1&&$key==7)
                {       
                    $totalNum=$userTask[$key]['n_num']+1;
                    if ($totalNum>=$taskInfo[0]['condition']) 
                    {
                        $totalNum=$taskInfo[0]['condition'];
                    }
                    $userTask[$key]['n_num']=$totalNum;
                }
                //8
                if(($dataArr['pass']===0||$dataArr['pass']==1)&&$key==8)
                {      
                    $totalNum=$userTask[$key]['n_num']+1;
                    if ($totalNum>=$taskInfo[0]['condition']) 
                    {
                        $totalNum=$taskInfo[0]['condition'];
                    }
                    $userTask[$key]['n_num']=$totalNum;
                }
                //9
                if($dataArr['reward']&&$key==9)
                {       
                    $totalNum=$userTask[$key]['n_num']+$dataArr['reward'];
                    if ($totalNum>=$taskInfo[0]['condition']) 
                    {
                        $totalNum=$taskInfo[0]['condition'];
                    }
                    $userTask[$key]['n_num']=$totalNum;
                }
                //10
                if($dataArr['boss']>0&&$dataArr['pass']==1&&$key==10)
                {       
                    $totalNum=$userTask[$key]['n_num']+1;
                    if ($totalNum>=$taskInfo[0]['condition']) 
                    {
                        $totalNum=$taskInfo[0]['condition'];
                    }
                    $userTask[$key]['n_num']=$totalNum;
                }
                //11
                if($dataArr['friend_help']&&$key==11)
                {       
                    $totalNum=$userTask[$key]['n_num']+1;
                    if ($totalNum>=$taskInfo[0]['condition']) 
                    {
                        $totalNum=$taskInfo[0]['condition'];
                    }
                    $userTask[$key]['n_num']=$totalNum;
                }
                //12
                if($dataArr['all_star']==1&&$key==12)
                {       
                    $totalNum=$userTask[$key]['n_num']+1;
                    if ($totalNum>=$taskInfo[0]['condition']) 
                    {
                        $totalNum=$taskInfo[0]['condition'];
                    }
                    $userTask[$key]['n_num']=$totalNum;
                }
                //13
                // if($dataArr['soul']&&$key==13)
                // {       
                //     $totalNum=$userTask[$key]['n_num']+$dataArr['soul'];
                //     if ($totalNum>=$taskInfo[0]['condition']) 
                //     {
                //         $totalNum=$taskInfo[0]['condition'];
                //     }
                //     $userTask[$key]['n_num']=$totalNum;
                // }
            }
        }

        $s_task_info=serialize($userTask);
        $res=TaskAchieveModel::update(array('s_task_info'=>$s_task_info),array('n_id'=>$user_id));
        UserCache::setByKey($user_id,self::TASK_STRING,$userTask);        

    }

     /**
     * API：成就统计
     *
     * @access public
     * @param int $user_id 用户ID
     * @return array
     */
    public function achieveStatistic($user_id,$dataArr)
    {
        $pointArr=array(6=>1,12=>2,22=>3,30=>4); 
        $userAchieve=UserCache::getByKey($user_id,self::ACHIEVEMENT_STRING);
        if (!$userAchieve) 
        {
            $userAchieve=TaskAchieveModel::getUserInfoByCondition($user_id,self::ACHIEVEMENT_STRING);
            UserCache::setByKey($user_id,self::ACHIEVEMENT_STRING,$userAchieve);
        }

        foreach ($userAchieve as $key => $value) 
        {
            $str="achievement_id = ".$key."_".($userAchieve[$key]['n_level']+1);
            $file = IniFileManager::getRootDir()."/files/csv/achievement.csv";
            $achieveInfo=CharacterAction::readCsv($file,$str);
            if ($userAchieve[$key]['n_level']<$userAchieve[$key]['max_level']&&$userAchieve[$key]['n_num']<$achieveInfo[0]['condition']) 
            {
                //1
                if($dataArr['friend_help']==1&&$key==1)
                {       
                    $totalNum=$userAchieve[$key]['n_num']+$dataArr['friend_help'];
                    if ($totalNum>=$achieveInfo[0]['condition']) 
                    {
                        $totalNum=$achieveInfo[0]['condition'];
                    }
                    $userAchieve[$key]['n_num']=$totalNum;
                }

                //2
                if($dataArr['login_day']==1&&$key==2)
                {       
                    $totalNum=$userAchieve[$key]['n_num']+$dataArr['login_day'];
                    if ($totalNum>=$achieveInfo[0]['condition']) 
                    {
                        $totalNum=$achieveInfo[0]['condition'];
                    }
                    $userAchieve[$key]['n_num']=$totalNum;
                }
                //3
                if($dataArr['monster']&&$key==3)
                {       
                    $totalNum=$userAchieve[$key]['n_num']+$dataArr['monster'];
                    if ($totalNum>=$achieveInfo[0]['condition']) 
                    {
                        $totalNum=$achieveInfo[0]['condition'];
                    }
                    $userAchieve[$key]['n_num']=$totalNum;
                }
                //4
                if($dataArr['star_num']&&$key==4)
                {       
                    $totalNum=$userAchieve[$key]['n_num']+$dataArr['star_num'];
                    if ($totalNum>=$achieveInfo[0]['condition']) 
                    {
                        $totalNum=$achieveInfo[0]['condition'];
                    }
                    $userAchieve[$key]['n_num']=$totalNum;
                }
                //5
                if($dataArr['pro_num']&&$key==5)
                {       
                    $totalNum=$userAchieve[$key]['n_num']+$dataArr['pro_num'];
                    if ($totalNum>=$achieveInfo[0]['condition']) 
                    {
                        $totalNum=$achieveInfo[0]['condition'];
                    }
                    $userAchieve[$key]['n_num']=$totalNum;
                }
                //6
                if(($dataArr['pass']===0||$dataArr['pass']===1)&&$key==6)
                {       
                    $totalNum=$userAchieve[$key]['n_num']+1;
                    if ($totalNum>=$achieveInfo[0]['condition']) 
                    {
                        $totalNum=$achieveInfo[0]['condition'];
                    }
                    $userAchieve[$key]['n_num']=$totalNum;
                }
                //7
                if($dataArr['away']===0&&$dataArr['attack']===0&&$key==7)
                {  
                    $totalNum=$userAchieve[$key]['n_num']+1;
                    if ($totalNum>=$achieveInfo[0]['condition']) 
                    {
                        $totalNum=$achieveInfo[0]['condition'];
                    }
                    $userAchieve[$key]['n_num']=$totalNum;
                }
                //8
                if($dataArr['skill_num']&&$key==8)
                {       
                    $totalNum=$userAchieve[$key]['n_num']+$dataArr['skill_num'];
                    if ($totalNum>=$achieveInfo[0]['condition']) 
                    {
                        $totalNum=$achieveInfo[0]['condition'];
                    }
                    $userAchieve[$key]['n_num']=$totalNum;
                }
                //9
                if($dataArr['cost']&&$key==9)
                {       
                    $totalNum=$userAchieve[$key]['n_num']+$dataArr['cost'];
                    if ($totalNum>=$achieveInfo[0]['condition']) 
                    {
                        $totalNum=$achieveInfo[0]['condition'];
                    }
                    $userAchieve[$key]['n_num']=$totalNum;
                }

                //11
                if($dataArr['pass']===0&&$key==11)
                { 
                    $totalNum=$userAchieve[$key]['n_num']+1;
                    if ($totalNum>=$achieveInfo[0]['condition']) 
                    {
                        $totalNum=$achieveInfo[0]['condition'];
                    }
                    $userAchieve[$key]['n_num']=$totalNum;
                }
                //12
                if($dataArr['soul']&&$key==12)
                {       
                    $totalNum=$userAchieve[$key]['n_num']+$dataArr['soul'];
                    if ($totalNum>=$achieveInfo[0]['condition']) 
                    {
                        $totalNum=$achieveInfo[0]['condition'];
                    }
                    $userAchieve[$key]['n_num']=$totalNum;
                }

                //15
                if($dataArr['check_point_id']==1&&$dataArr['pass']==1&&$key==15)
                {       
                    $totalNum=$userAchieve[$key]['n_num']+1;
                    if ($totalNum>=$achieveInfo[0]['condition']) 
                    {
                        $totalNum=$achieveInfo[0]['condition'];
                    }
                    $userAchieve[$key]['n_num']=$totalNum;
                }

            }
            if($userAchieve[$key]['n_level']<$userAchieve[$key]['max_level'])
            {
                //10
                if($dataArr['pass']==1&&$pointArr[$dataArr['check_point_id']]>$userAchieve[$key]['n_num']&&$key==10)
                {  
                    if ($value['n_level']<Constants::CHAPTER_NUM) 
                    {
                        $totalNum=$userAchieve[$key]['n_num']+1;
                        // if ($totalNum>=$achieveInfo[0]['condition']) 
                        // {
                        //     $totalNum=$achieveInfo[0]['condition'];
                        // }
                        $userAchieve[$key]['n_num']=$totalNum;
                    }

                }
                //13
                if($dataArr['generl_full']&&$key==13)
                {       
                    $totalNum=$userAchieve[$key]['n_num']+$dataArr['generl_full'];
                    // if ($totalNum>=$achieveInfo[0]['condition']) 
                    // {
                    //     $totalNum=$achieveInfo[0]['condition'];
                    // }
                    $userAchieve[$key]['n_num']=$totalNum;
                }
                //14
                if($dataArr['update_times']&&$key==14)
                {       
                    $totalNum=$userAchieve[$key]['n_num']+$dataArr['update_times'];
                    // if ($totalNum>=$achieveInfo[0]['condition']) 
                    // {
                    //     $totalNum=$achieveInfo[0]['condition'];
                    // }
                    $userAchieve[$key]['n_num']=$totalNum;
                }
            }

        }
        $s_achievement_info=serialize($userAchieve);
        $res=TaskAchieveModel::update(array('s_achievement_info'=>$s_achievement_info),array('n_id'=>$user_id));
        UserCache::setByKey($user_id,self::ACHIEVEMENT_STRING,$userAchieve);
    }
}