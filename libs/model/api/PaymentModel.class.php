<?php
/**
 *  商品相关 Model类
 *
 *
 * @access public
 * @author chenwenbin
 * @copyright Copyright(C) Tenone Inc.
 * @version 0.01
 * @since 2014/11/14
 */

class AuthModel extends BaseModel {

    const FILE_NAME = 'item_config.ini';
    const PRIMARY_KEY_NAME = 'id';

    public static function getFileName()
    {
        return self::FILE_NAME;
    }

    public static function getPrimaryKeyName()
    {
        return self::PRIMARY_KEY_NAME;
    }

    /**
     * 获取用户ID
     *
     * @access public
     * @param string $user_account 用户名
     * @param string $user_pwd 用户密码
     * @return integer 结果
     */
    public static function getUserID( $user_account, $user_pwd, $pdo = null )
    {
        if(is_null($pdo))
        {
            $pdo = Database::getPdo();
        }

        $stmt = $pdo->prepare("SELECT n_id FROM user where s_account = :user_account and s_password = :user_pwd");
        
        $stmt->bindValue(":user_account" , $user_account);
        $stmt->bindValue(":user_pwd" , $user_pwd);
        $stmt->execute();
        $user_info = self::fetchToArray( $stmt );
        return isset($user_info[0])?$user_info[0]['n_id']:$user_info;
    }

    /**
     * 获取用户表信息
     *
     * @access public
     * @param string $user_account 用户id
     * @return integer 结果
     */
    public static function getUserInfo( $id, $pdo = null )
    {
        if(is_null($pdo))
        {
            $pdo = Database::getPdo();
        }

        $stmt = $pdo->prepare("SELECT * FROM user where n_id = :id");
        
        $stmt->bindValue(":id" , $id);
        $stmt->execute();
        $user_info = self::fetchToArray( $stmt );
        return isset($user_info[0])?$user_info[0]:$user_info;
    }
}