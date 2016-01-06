<?php
/**
 *  表mail Model类
 *
 *
 * @access public
 * @author chenwenbin
 * @copyright Copyright(C) Tenone Inc.
 * @version 0.01
 * @since 2014/11/14
 */

class MailModel extends BaseModel {

    const TABLE_NAME = 'mail_system';
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
     * 获取用户信息
     *
     * @access public
     * @param string $user_id 
     * @return integer 结果
     */
      public static function getUserInfo( $user_id,$pdo = null )
    {
        if(is_null($pdo))
        {
            $pdo = Database::getPdo();
        }

        $stmt = $pdo->prepare("SELECT s_name,n_coin,n_diamond,n_soul,n_thew,n_refresh_time FROM user_info where n_id=:user_id");
        
        $stmt->bindValue(":user_id" , $user_id);
        $stmt->execute();
        $user_info = self::fetchToArray( $stmt );
        return $user_info[0]?$user_info[0]:$user_info;

    }
    /**
     * 好友邮箱信息
     *
     * @access public
     * @param string $user_id 用户ID
     * @return integer 结果
     */
    public static function getFriendMail( $user_id, $pdo = null )
    {
        if(is_null($pdo))
        {
            $pdo = Database::getPdo();
        }

        $checkTime = date( "Y-m-d H:i:s", strtotime( "-7 day" ) );
        $stmt = $pdo->prepare("SELECT mail_friend.n_id mail_id,n_send_id,n_head,s_name,n_type,mail_friend.t_create_time FROM mail_friend  LEFT JOIN user_info ON n_send_id=user_info.n_id where mail_friend.t_create_time>:checkTime and n_receive_id=:user_id and n_type>0 order by t_create_time asc limit 0,30");
        $stmt->bindValue(':checkTime',$checkTime);
        $stmt->bindValue(":user_id" , $user_id);  
        $stmt->execute();
        $user_info = self::fetchToArray( $stmt );
        $nowTime=time();  
        foreach ($user_info as $key => $value) 
        {
            $user_info[$key]['t_create_time']=$nowTime-strtotime($value['t_create_time']);
        }
        return $user_info;  
    }

    /**
     * 系统邮箱信息
     *
     * @access public
     * @param string $user_id 用户ID
     * @return integer 结果
     */
    public static function getSystemMail( $user_id, $pdo = null )
    {
        if(is_null($pdo))
        {
            $pdo = Database::getPdo();
        }
        $checkTime = date( "Y-m-d H:i:s", strtotime( "-7 day" ) );
        $stmt = $pdo->prepare("SELECT n_id,n_send_id,s_message,n_item_type,n_item_num,t_create_time FROM mail_system where  n_receive_id=:user_id and n_type=1 and t_create_time>:checkTime order by t_create_time asc limit 0,30");
        $stmt->bindValue(':checkTime',$checkTime);
        $stmt->bindValue(":user_id" , $user_id);  
        $stmt->execute();
        $user_info = self::fetchToArray( $stmt );  
        $nowTime=time(); 
        foreach ($user_info as $key => $value) 
        {
            $user_info[$key]['t_create_time']=$nowTime-strtotime($value['t_create_time']);
        }
        return $user_info;  
    }

    /**
     * 获取一个邮件信息
     *
     * @access public
     * @param string $user_id 用户ID
     * @return integer 结果
     */
    public static function getOneMail($mail_id,$pdo = null )
    {
        if(is_null($pdo))
        {
            $pdo = Database::getPdo();
        }
        $stmt = $pdo->prepare("SELECT n_receive_id,n_item_type,n_item_num FROM mail_system where  n_id=:mail_id and n_type=1");
        
        $stmt->bindValue(":mail_id" , $mail_id);  
        $stmt->execute();

        $mail_info = self::fetchToArray( $stmt );  

        return $mail_info[0]?$mail_info[0]:$mail_info;  
    }
}