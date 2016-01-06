<?php

/**
 * memcache管理类
 *
 * @access public
 * @author chenwenbin
 * @copyright Copyright(C) Tenone Inc.
 * @version 0.01
 * @since 2014/11/13
 */
class MemcacheManager 
{

    const FILE_NAME           = "memcached_config.ini";
    const CONFIG_KEY_HOST     = 'host';
    //const CONFIG_KEY_ADD_HOST = 'add_host';
    const CONFIG_KEY_PORT     = 'port';
    const CONFIG_KEY_EXPIRE   = 'expire';

    private $memcached = null;
    private $configs = array();
    private static $_instance;

    public function __construct() 
    {
        $this->memcached = new Memcache();

        $this->memcached->connect(
            IniFileManager::getByFilesKey(self::FILE_NAME, self::CONFIG_KEY_HOST),
            IniFileManager::getByFilesKey(self::FILE_NAME, self::CONFIG_KEY_PORT)
        );
        /*$add_host = IniFileManager::getByFilesKey( self::FILE_NAME, self::CONFIG_KEY_ADD_HOST );
        if( $add_host )
        {
            $this->memcached->addServer( $add_host, IniFileManager::getByFilesKey(self::FILE_NAME, self::CONFIG_KEY_PORT));
        }*/
    }

    /**
     * memcache对象获取方法
     *
     * @return object MemcacheManager
     */
    public static function instance() 
    {
        if( !(MemcacheManager::$_instance instanceof MemcacheManager) )
        {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * 获取指定key => value
     * 
     * @param mixed $key 
     * @return mixed
     */
    public function get( $key ) 
    {
        if ( is_null( $this->memcached ) ) 
        {
            return false;
        }

        return $this->memcached->get( $key );
    }

    /**
     * 设置memcache值
     *
     * @param string $key 要设置值的key
     * @param mixed $var 要存储的值，字符串和数值直接存储，其他类型序列化后存储
     * @param int $flag 使用MEMCACHE_COMPRESSED指定对值进行压缩(使用zlib)
     * @param int $expire 当前写入缓存的数据的失效时间 / 0为永不过期
     * @return boolean
     */
    public function set( $key, $var , $flag = null, $expire = null ) 
    {
        if ( is_null( $this->memcached ) ) 
        {
            return false;
        }

        if( !isset($expire) )
        {
            $expire = IniFileManager::getByFilesKey(self::FILE_NAME, self::CONFIG_KEY_EXPIRE );
        }
        return $this->memcached->set( $key, $var, $flag, $expire );
    }

    /**
     * 删除memcache值
     * 
     * @param string $key
     * @return boolean
     */
    public function delete( $key ) 
    {
        if ( is_null( $this->memcached ) ) 
        {
            return false;
        }

        return $this->memcached->delete( $key );
    }

    /**
     * 销毁所有memcache值
     *
     * @return boolean
     */
    public function flush() 
    {
        if ( is_null( $this->memcached ) ) 
        {
            return false;
        }

        return $this->memcached->flush();
    }

    /**
     * 新增一条memcache记录
     *
     * @access public
     * @param string $key 要设置值的key
     * @param mixed $var 要存储的值，字符串和数值直接存储，其他类型序列化后存储
     * @param int $flag 使用MEMCACHE_COMPRESSED指定对值进行压缩(使用zlib)
     * @param int $expire 当前写入缓存的数据的失效时间 / 0为永不过期
     * @return boolean
     */
    public function add( $key, $var, $flag = null, $expire = null )
    {
        if ( is_null( $this->memcached ) ) 
        {
            return false;
        }

        if( !isset($expire) )
        {
            $expire = IniFileManager::getByFilesKey(self::FILE_NAME, self::CONFIG_KEY_EXPIRE );
        }
        return $this->memcached->add( $key, $var, $flag, $expire );
    }


}