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

class BuyPropModel extends BaseModel {

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

        $stmt = $pdo->prepare("SELECT n_coin,n_diamond,n_thew,n_soul FROM user_info where n_id=:user_id");
        
        $stmt->bindValue(":user_id" , $user_id);
        $stmt->execute();
        $user_info = self::fetchToArray( $stmt );
        return $user_info[0]?$user_info[0]:$user_info;

    }

    /**
     * 获取用户道具信息
     *
     * @access public
     * @param string $user_name 查找的昵称
     * @return integer 结果
     */
    public static function getItemInfo( $user_id,$pdo = null )
    {
        if(is_null($pdo))
        {
            $pdo = Database::getPdo();
        }

        $stmt = $pdo->prepare("SELECT s_item_info FROM user_info where n_id=:user_id");
        
        $stmt->bindValue(":user_id" , $user_id);
        $stmt->execute();
        $user_info = self::fetchToArray( $stmt );
        $user_info=isset($user_info[0])?$user_info[0]:$user_info;
        $arr = unserialize($user_info['s_item_info']);
        return $arr;
    }
}