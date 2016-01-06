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

class CharacterModel extends BaseModel {

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
    public static function getUserCharacterInfo( $uid,$pdo = null )
    {
        if(is_null($pdo))
        {
            $pdo = Database::getPdo();
        }

        $stmt = $pdo->prepare("SELECT s_role_info FROM user_info where n_id = :uid ");
        
        $stmt->bindValue(":uid" , $uid);
        $stmt->execute();
        $user_info = self::fetchToArray( $stmt );
        $user_info=isset($user_info[0])?$user_info[0]:$user_info;
        $arr = unserialize($user_info['s_role_info']);
        return $arr;
    }
      public static function getUserInfo( $uid,$pdo = null )
    {
        if(is_null($pdo))
        {
            $pdo = Database::getPdo();
        }

        $stmt = $pdo->prepare("SELECT n_coin,n_diamond,n_soul FROM user_info where n_id = :uid ");
        
        $stmt->bindValue(":uid" , $uid);
        $stmt->execute();
        $user_info = self::fetchToArray( $stmt );
        return ($user_info[0])?$user_info[0]:$user_info;

    }

    public static function setSessionKey($user_id,$oldSessionKey)
    {
        // 生成缓存
        $newSessionKey = Util::generateSessionKey($user_id);

        Logger::debug('SessionKey1:'. $oldSessionKey);
        Logger::debug('SessionKey2:'. $newSessionKey);

        UserCache::setByKey($user_id, Constants::PREVIOUS_SESSION_KEY, $oldSessionKey);
        UserCache::setByKey($user_id, Constants::CURRENT_SESSION_KEY, $newSessionKey);

        return $newSessionKey;
    } 

}