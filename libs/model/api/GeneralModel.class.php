<?php
/**
 *  表character Model类
 *
 *
 * @access public
 * @author chenwenbin
 * @copyright Copyright(C) Tenone Inc.
 * @version 0.01
 * @since 2014/11/14
 */

class GeneralModel extends BaseModel {

    const TABLE_NAME = 'user_info';
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
     * 获取用户主角表信息
     *
     * @access public
     * @param integer $uid $character_id 用户id
     * @return integer 结果
     */
    public static function getUserGeneralInfo( $uid,$pdo = null )
    {
        if(is_null($pdo))
        {
            $pdo = Database::getPdo();
        }

        $stmt = $pdo->prepare("SELECT s_general_info FROM user_info where n_id = :uid ");
        
        $stmt->bindValue(":uid" , $uid);
        $stmt->execute();
        $user_info = self::fetchToArray( $stmt );
        $user_info=isset($user_info[0])?$user_info[0]:$user_info;
        $arr = unserialize($user_info['s_general_info']);
        return $arr;
    }
      public static function getUserInfo( $uid,$pdo = null )
    {
        if(is_null($pdo))
        {
            $pdo = Database::getPdo();
        }

        $stmt = $pdo->prepare("SELECT * FROM user_info where n_id = :uid ");
        
        $stmt->bindValue(":uid" , $uid);
        $stmt->execute();
        $user_info = self::fetchToArray( $stmt );
        return ($user_info[0])?$user_info[0]:$user_info;

    }
}