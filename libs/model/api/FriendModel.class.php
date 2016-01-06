<?php
/**
 *  表Friend Model类
 *
 *
 * @access public
 * @author chenwenbin
 * @copyright Copyright(C) Tenone Inc.
 * @version 0.01
 * @since 2014/11/14
 */

class FriendModel extends BaseModel {

    const TABLE_NAME = 'friend_list';
    const PRIMARY_KEY_NAME = 'n_id';

    public static function getTableName()
    {
        return self::TABLE_NAME;
    }

    public static function getPrimaryKeyName()
    {
        return self::PRIMARY_KEY_NAME;
    }

    /**
     * 查找用户
     *
     * @access public
     * @param string $user_name 查找的昵称
     * @return integer 结果
     */
      public static function searchByName( $user_name,$pdo = null )
    {
        if(is_null($pdo))
        {
            $pdo = Database::getPdo();
        }

        $stmt = $pdo->prepare("SELECT n_id,s_name,n_sex,n_head,n_max_checkpoint,n_battle FROM user_info where s_name like :user_name LIMIT 0,10");
        
        $stmt->bindValue(":user_name" , "%".$user_name."%");
        $stmt->execute();
        $user_info = self::fetchToArray( $stmt );
        return $user_info?$user_info:0;

    }
    /**
     * 获取用户信息
     *
     * @access public
     * @param string $user_name 查找的昵称
     * @return integer 结果
     */
      public static function getUserInfo( $user_id,$pdo = null )
    {
        if(is_null($pdo))
        {
            $pdo = Database::getPdo();
        }

        $stmt = $pdo->prepare("SELECT n_coin,n_diamond,n_battle FROM user_info where n_id=:user_id");
        
        $stmt->bindValue(":user_id" , $user_id);
        $stmt->execute();
        $user_info = self::fetchToArray( $stmt );
        return $user_info[0]?$user_info[0]:$user_info;

    }

    /**
     * 判断是否好友关系
     *
     * @access public
     * @param integer $user_id 用户ID $friend 好友id
     * @return bool 结果
     */
      public static function isFriend( $user_id,$friend_id,$pdo = null )
    {
        if(is_null($pdo))
        {
            $pdo = Database::getPdo();
        }

        $stmt = $pdo->prepare("SELECT n_id FROM friend_list where (n_user_id=:user_id and n_friend_id=:friend_id) OR (n_user_id=:friend_id and n_friend_id=:user_id)");
        
        $stmt->bindValue(":user_id" , $user_id);
        $stmt->bindValue(":friend_id" , $friend_id);       
        $stmt->execute();
        $user_info = self::fetchToArray( $stmt );
        return $user_info[0]?1:0;

    }
   

    /**
     * 获取好友数量
     *
     * @access public
     * @param integer $user_id 用户ID 
     * @return bool 结果
     */
      public static function getFriendNum( $user_id,$pdo = null )
    {
        if(is_null($pdo))
        {
            $pdo = Database::getPdo();
        }
        $stmt = $pdo->prepare("SELECT count(n_id)num FROM friend_list where n_user_id=:user_id OR n_friend_id=:user_id");
        
        $stmt->bindValue(":user_id" , $user_id);  
        $stmt->execute();
        $user_info = self::fetchToArray( $stmt );
        return $user_info[0]['num'];
    }

    /**
     * 是否已发送添加请求
     *
     * @access public
     * @param integer $user_id 用户ID $friend_id 
     * @return bool 结果
     */
      public static function isAddFriend( $user_id,$friend_id,$pdo = null )
    { 
        if(is_null($pdo))
        {
            $pdo = Database::getPdo();
        }
        $stmt = $pdo->prepare("SELECT n_id FROM mail_friend where n_send_id=:user_id and n_receive_id=:friend_id and n_type=1");
        
        $stmt->bindValue(":user_id" , $user_id);  
        $stmt->bindValue(":friend_id" , $friend_id);
        $stmt->execute();
        $user_info = self::fetchToArray( $stmt );  
        return $user_info[0]?1:0;     
    }

    /**
     * 好友添加邮件
     *
     * @access public
     * @param array $record 插入记录 
     * @return pdo 结果
     */
      public static function insertMail( array $record,$pdo = null )
    { 
        if( !isset($pdo) )
        {
            $pdo = Database::getPdo();
        }

        $stmt = $pdo->prepare(
            "INSERT INTO mail_friend (" . implode( ",", array_keys($record) ) . ") " .
            "VALUES(". implode( ",", array_fill(0, count($record), '?') ) . ")"
        );

        if( $stmt === false )
        {
            return null;
        }

        $i = 1;
        foreach( $record as $column=>$value )
        {
            $stmt->bindValue( $i, $value );
            $i++;
        }
        if( !$stmt->execute() )
        {
            return null;
        }

        return $pdo;  
    }

    /**
     * 同意添加修改邮件状态
     *
     * @access public
     * @param integer $user_id 用户ID $friend_id 
     * @return bool 结果
     */
      public static function updateFriendMail( array $record,array $wheres,$pdo = null )
    { 
        if( empty($record) || empty($wheres) )
        {
            return null;
        }
        $columns       = array_keys( $record );
        $where_columns = array_keys( $wheres );

        if( !isset($pdo) )
        {
            $pdo = Database::getPdo();
        }
        $stmt = $pdo->prepare( 
            "UPDATE mail_friend SET " .
            vsprintf( implode(" = ?,",$columns) . " = ?", $columns) .
            " WHERE " .
            vsprintf( implode(" = ? AND ",$where_columns) . " = ?", $where_columns)
        );
        if( $stmt === false )
        {
            return null;
        }

        $i = 1;
        foreach( $record as $column=>$value )
        {
            $stmt->bindValue( $i, $value );
            $i++;
        }
        foreach( $wheres as $column=>$value )
        {
            $stmt->bindValue( $i, $value );
            $i++;
        }
        if( !$stmt->execute() )
        {
            return null;
        }

        return $pdo;
    }

    /**
     * 删除好友
     *
     * @access public
     * @param integer $user_id 用户ID $friend 好友id
     * @return bool 结果
     */
      public static function delFriend( $user_id,$friend_id,$pdo = null )
    {
        if(is_null($pdo))
        {
            $pdo = Database::getPdo();
        }

        $stmt = $pdo->prepare("DELETE FROM friend_list where (n_user_id=:user_id and n_friend_id=:friend_id) OR (n_user_id=:friend_id and n_friend_id=:user_id)");
        
        $stmt->bindValue(":user_id" , $user_id);
        $stmt->bindValue(":friend_id" , $friend_id);       
        
        if( !$stmt->execute() )
        {
            return null;
        }

        return $pdo;

    }

    /**
     * 好友合体信息
     *
     * @access public
     * @param integer $user_id 用户ID 
     * @return array 结果
     */
      public static function fitFriend( $user_id,$pdo = null )
    {
        if(is_null($pdo))
        {
            $pdo = Database::getPdo();
        }

        $stmt = $pdo->prepare("SELECT  if(n_user_id=:user_id,n_friend_id,n_user_id)n_id,
                                       if(n_user_id=:user_id,s_friend_time,s_user_time)time,
                                       if(n_user_id=:user_id,n_friend_num,n_user_num)num
                               FROM friend_list 
                               WHERE (n_user_id=:user_id ) OR (n_friend_id=:user_id)");
        $stmt->bindValue(":user_id" , $user_id);    
        $stmt->execute();
        $user_info = self::fetchToArray( $stmt );
        return $user_info;

    }

        /**
     * 好友合体信息
     *
     * @access public
     * @param integer $condition 查找的ID字符串
     * @return array 结果
     */
      public static function getFriendInfo( $condition,$pdo = null )
    {
        if(is_null($pdo))
        {
            $pdo = Database::getPdo();
        }
        $stmt = $pdo->prepare("SELECT n_id,s_name,n_head,n_battle FROM user_info WHERE n_id in (".$condition.") order by n_battle DESC LIMIT 0,10");
        $stmt->execute();
        $friend_Info = self::fetchToArray( $stmt ); 
        return $friend_Info;
    }

    /**
     * 更新合体时间
     *
     * @access public
     * @param integer $user_id 用户ID 
     * @return bool 结果
     */
      public static function fitTime( $user_id,$friend_id,$pdo = null )
    {
        if(is_null($pdo))
        {
            $pdo = Database::getPdo();
        }
        $stmt = $pdo->prepare("SELECT  if(n_user_id=:friend_id,'s_user_time','s_friend_time')string
                               FROM friend_list 
                               WHERE (n_user_id=:user_id and n_friend_id=:friend_id) OR (n_user_id=:friend_id and n_friend_id=:user_id) ");
        $stmt->bindValue(":user_id" , $user_id);  
        $stmt->bindValue(":friend_id" , $friend_id);
        $stmt->execute();
        $user_info = self::fetchToArray( $stmt ); 
        $string=$user_info[0]['string'];

        $time=time()+24*60*60;
        $stmt = $pdo->prepare("UPDATE friend_list SET ".$string."=:time
                               where (n_user_id=:user_id and n_friend_id=:friend_id) OR (n_user_id=:friend_id and n_friend_id=:user_id) ");
        
        $stmt->bindValue(":user_id" , $user_id);    
        $stmt->bindValue(":friend_id" , $friend_id); 
        $stmt->bindValue(":time" , $time);
        $stmt->bindValue(":string" , $string);   

        if( !$stmt->execute() )
        {
            return null;
        }
        return $pdo;

    }


    /**
     * 更新合体次数
     *
     * @access public
     * @param integer $user_id 用户ID 
     * @return bool 结果
     */
      public static function updateFitNum( $user_id,$friend_id,$pdo = null )
    {
        if(is_null($pdo))
        {
            $pdo = Database::getPdo();
        }
        $stmt = $pdo->prepare("SELECT  if(n_user_id=:friend_id,'n_user_num','n_friend_num')string,n_user_id,n_friend_id
                               FROM friend_list 
                               WHERE (n_user_id=:user_id and n_friend_id=:friend_id) OR (n_user_id=:friend_id and n_friend_id=:user_id) ");
        $stmt->bindValue(":user_id" , $user_id);  
        $stmt->bindValue(":friend_id" , $friend_id);
        $stmt->execute();
        $user_info = self::fetchToArray( $stmt ); 
        $string=$user_info[0]['string'];
        $sql="UPDATE friend_list SET ".$string."=".$string."+1
                               where n_user_id=:user_id and n_friend_id=:friend_id 
                                ";
        $stmt = $pdo->prepare($sql);

        $stmt->bindValue(":user_id" , $user_info[0]['n_user_id']);    
        $stmt->bindValue(":friend_id" , $user_info[0]['n_friend_id']); 
 
        if( !$stmt->execute() )
        {
            return null;
        }
        return $pdo;

    }


    /**
     * 删除好友
     *
     * @access public
     * @param integer $user_id 用户ID $friend 好友id
     * @return bool 结果
     */
      public static function clearFitNum( $user_id,$pdo = null )
    {
        if(is_null($pdo))
        {
            $pdo = Database::getPdo();
        }

        $stmt = $pdo->prepare("UPDATE friend_list SET n_friend_num=0 
                               where  n_user_id=:user_id  ");
        $stmt->bindValue(":user_id" , $user_id);    
        $stmt->execute();

        $stmt = $pdo->prepare("UPDATE friend_list SET n_user_num=0 
                               where  n_friend_id=:user_id  ");
        $stmt->bindValue(":user_id" , $user_id); 
        if( !$stmt->execute() )
        {
            return null;
        }
        return $pdo;
    }
    /**
     * 好友列表
     *
     * @access public
     * @param integer $user_id 用户ID 
     * @return array 结果
     */
      public static function getFriendList( $user_id,$pdo = null )
    {
        if(is_null($pdo))
        {
            $pdo = Database::getPdo();
        }

        $stmt = $pdo->prepare("SELECT  if(n_user_id=:user_id,n_friend_id,n_user_id)n_id
                               FROM friend_list 
                               WHERE (n_user_id=:user_id ) OR (n_friend_id=:user_id)");
        $stmt->bindValue(":user_id" , $user_id);    
        $stmt->execute();
        $user_info = self::fetchToArray( $stmt );
        return $user_info;

    }

    /**
     * 随机十个用户信息
     *
     * @access public
     * @param integer $condition 查找的ID字符串
     * @return array 结果
     */
      public static function getTenInfo( $user_id,$pdo = null )
    {
        if(is_null($pdo))
        {
            $pdo = Database::getPdo();
        }
        $stmt = $pdo->prepare("SELECT  n_id FROM user_info where n_id not in (".$user_id.")");
        $stmt->execute();
        $friend_Info = self::fetchToArray( $stmt );

        shuffle($friend_Info);
        $friend_Info= array_slice($friend_Info,0,10);
        foreach ($friend_Info as $key => $value) 
        {
            $condition.=$value['n_id'].',';
        }
        $condition=substr($condition, 0,-1);
        $stmt = $pdo->prepare("SELECT  n_id,s_name,n_head,n_sex,n_battle,n_max_checkpoint FROM user_info WHERE n_id in (".$condition.")");
        $stmt->execute();
        $friend_Info = self::fetchToArray( $stmt ); 
        return $friend_Info;
    }
}