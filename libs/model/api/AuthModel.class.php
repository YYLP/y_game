<?php
/**
 *  表auth_user Model类
 *
 *
 * @access public
 * @author chenwenbin
 * @copyright Copyright(C) Tenone Inc.
 * @version 0.01
 * @since 2014/11/14
 */

class AuthModel extends BaseModel {

    const TABLE_NAME = 'regist_list';
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
     * 检测用户账号是否重复
     *
     * @access public
     * @param string $user_account 用户名
     * @return integer 结果
     */
    public static function checkUserAccount( $user_account, $pdo = null )
    {
        if(is_null($pdo))
        {
            $pdo = Database::getPdo();
        }

        $stmt = $pdo->prepare("SELECT n_id FROM regist_list where s_account = :user_account");
        
        $stmt->bindValue(":user_account" , $user_account);
        $stmt->execute();
        $user_info = self::fetchToArray( $stmt );
        return isset($user_info[0])?0:1;
    }


    /**
     * 检测用户昵称是否重复
     *
     * @access public
     * @param string $user_name 用户名
     * @return integer 结果
     */
    public static function checkUserName( $user_name, $pdo = null )
    {
        if(is_null($pdo))
        {
            $pdo = Database::getPdo();
        }

        $stmt = $pdo->prepare("SELECT n_id FROM user_info where s_name = :user_name");
        
        $stmt->bindValue(":user_name" , $user_name);
        $stmt->execute();
        $user_info = self::fetchToArray( $stmt );
        return isset($user_info[0])?0:1;
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

        $stmt = $pdo->prepare("SELECT n_id FROM regist_list where s_account = :user_account and s_password = :user_pwd");
        
        $stmt->bindValue(":user_account" , $user_account);
        $stmt->bindValue(":user_pwd" , $user_pwd);
        $stmt->execute();
        $user_info = self::fetchToArray( $stmt );
        return isset($user_info[0])?$user_info[0]['n_id']:$user_info;
    }
}