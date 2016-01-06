<?php
/**
 * 用户缓存管理类
 *
 *
 * @access public
 * @author chenwenbin
 * @copyright Copyright(C) Tenone Inc.
 * @version 0.01
 * @since 2014/11/13
 */
class UserCache 
{

    private static $cache_instance = null;
    private static $cache_data     = null;

    private function __construct() 
    {

    }

    /**
     * 判断数据是否可以缓存
     *
     * @access public
     * @param mixed 需要缓存的数据
     * @return boolean 成功 / 失败
     */
    public static function isCacheableData( $data )
    {
        if( is_resource($data) )
        {
            return false;
        }

        if( $data instanceof PDO )
        {
            return false;
        }

        return true;
    }

    /**
     * 用户缓存管理单例
     *
     * @access public
     * @param void
     * @return Memcache 单例对象
     */
    private static function getCacheInstance()
    {
        $get_instance = self::$cache_instance;
        if( is_null(self::$cache_instance) )
        {
            self::$cache_instance = MemcacheManager::instance();
        }
        return self::$cache_instance;
    }

    /**
     * 指定用户ID和key，设置缓存数据。
     *
     * @access public
     * @param integer $user_id 用户id
     * @param string $key 需要设置的用户缓存信息中键值
     * @param mixed $data 需要重新设置的缓存数据
     * @param string $user_cache_key 用户在缓存中设置的键值
     * @return boolean 
     */
    public static function setByKey( $user_id, $key, $data, $user_cache_key = null )
    {
        if( !self::isCacheableData($data) )
        {
            return false;
        }

        if( !$user_cache_key )
        {
            $user_cache_key = md5( $user_id );
        }
        $cache       = self::getAllUserCache( $user_id, $user_cache_key );
        $cache[$key] = $data;
        $instance    = self::getCacheInstance();
        return $instance->set( $user_cache_key, $cache );
    }

    /**
     * 指定用户ID和key，删除缓存数据。
     *
     * @access public
     * @param integer $user_id 用户id
     * @param string $key 需要释放的用户缓存信息中键值
     * @param string $user_cache_key 用户在缓存中设置的键值
     * @return boolean 
     */
    public static function unsetByKey( $user_id, $key, $user_cache_key = null )
    {
        if( !$user_cache_key )
        {
            $user_cache_key = md5( $user_id );
        }
        $instance = self::getCacheInstance();
        $cache    = self::getAllUserCache( $user_id, $user_cache_key );
        unset( $cache[$key] );
        return $instance->set( $user_cache_key, $cache );
    }

    /**
     * 指定用户ID和key，获取缓存数据。
     *
     * @access public
     * @param integer $user_id 用户id
     * @param string $key 需要获取的用户缓存信息中键值
     * @param string $user_cache_key 用户在缓存中设置的键值
     * @return mixed 缓存数据
     */
    public static function getByKey( $user_id, $key, $user_cache_key = null )
    {
        if( !$user_cache_key )
        {
            $user_cache_key = md5( $user_id );
        }
        $cache = self::getAllUserCache( $user_id, $user_cache_key );
        if( !isset($cache[$key]) )
        {
            return null;
        }

        return $cache[$key];
    }

    /**
     * 指定用户ID，获取该用户所有缓存数据。
     *
     * @access public
     * @param integer $user_id 用户id
     * @param string $user_cache_key 用户在缓存中设置的键值
     * @return array 缓存数据
     */
    public static function getAllUserCache( $user_id, $user_cache_key = null )
    {
        if( !$user_cache_key )
        {
            $user_cache_key = md5( $user_id );
        }
        $instance = self::getCacheInstance();
        $cache    = $instance->get( $user_cache_key );
        if( $cache === false )
        {
            $cache = array();
            $instance->set( $user_cache_key, $cache );
        }
        return $cache;
    }

    /**
     * 指定用户ID，获取该用户所有缓存数据。
     *
     * @access public
     * @param integer $user_id 用户id
     * @param string $user_cache_key 用户在缓存中设置的键值
     * @return array 缓存数据
     */
    public static function setAllUserCache( $user_id, $data, $user_cache_key = null )
    {
        if( !$user_cache_key )
        {
            $user_cache_key = md5( $user_id );
        }
        $instance = self::getCacheInstance();
        return $instance->set( $user_cache_key, $data );
    }

    /**
     * 指定用户ID，删除该用户所有缓存数据。
     *
     * @access public
     * @param integer $user_id 用户id
     * @param string $user_cache_key 用户在缓存中设置的键值
     * @return array 缓存数据
     */
    public static function deleteAllUserCache( $user_id, $user_cache_key = null )
    {
        if( !$user_cache_key )
        {
            $user_cache_key = md5( $user_id );
        }
        $instance = self::getCacheInstance();
        return $instance->set( $user_cache_key, array() );
    }

}