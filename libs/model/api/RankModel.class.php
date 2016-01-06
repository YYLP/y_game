<?php
/**
 *  表user_info Model类
 *
 *
 * @access public
 * @author chenwenbin
 * @copyright Copyright(C) Tenone Inc.
 * @version 0.01
 * @since 2014/11/14
 */

class RankModel extends BaseModel {

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
     * 获取用户表信息
     *
     * @access public
     * @param integer $uid 用户id
     * @return integer 结果
     */
    public static function getRank( $max_num, $pdo = null )
    {
        if(is_null($pdo))
        {
            $pdo = Database::getPdo();
        }

        $stmt = $pdo->prepare("SELECT n_id, n_battle, n_max_checkpoint, s_name, n_sex, n_level FROM user_info ORDER BY n_max_checkpoint DESC, n_battle DESC,t_create_time DESC LIMIT 0, $max_num");
        // $stmt->bindParam(1, trim( $max_num ), PDO::PARAM_INT );
        //$stmt->bindValue(':max_num', (int)trim($max_num), PDO::PARAM_INT );
        //var_dump($max_num);
        //$stmt->execute(array(0,$max_num));
        $stmt->execute();
        $user_info = self::fetchToArray( $stmt );
        return isset($user_info)?$user_info:$user_info;
    }


    /**
     * 获取用户表信息
     *
     * @access public
     * @param integer $uid 用户id
     * @return integer 结果
     */
    public static function getUserRank( $user_id, $pdo = null )
    {
        if(is_null($pdo))
        {
            $pdo = Database::getPdo();
        }

        $stmt = $pdo->prepare("SELECT (SELECT count(*)+1 from user_info where k.n_max_checkpoint<n_max_checkpoint or (k.n_max_checkpoint=n_max_checkpoint and k.n_battle<n_battle ) or (k.n_max_checkpoint=n_max_checkpoint and k.n_battle=n_battle and k.t_create_time<t_create_time )) as rank from user_info k where n_id=$user_id");

        $stmt->execute();
        $user_info = self::fetchToArray( $stmt );
        return isset($user_info[0])?$user_info[0]:$user_info;
    }
}