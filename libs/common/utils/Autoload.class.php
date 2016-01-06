<?php
/**
 * 自动加载处理文件。<br />
 *
 *
 * @access public
 * @author chenwenbin
 * @copyright Copyright(C) Tenone Inc.
 * @version 0.01
 * @since 2014/11/12
 */

require_once 'common/utils/Logger.class.php';
require_once 'common/utils/IniFileManager.class.php';

class Autoload 
{

    private static $config = array();

    /**
     * 自动加载注册方法。
     *
     * @access public
     * @param 自动加载对象的目录
     * @return void
     */
    static public function register( $config )
    {
        self::$config = $config;
        try
        {
            // 注册自动加载的回调函数
            if( !spl_autoload_register(array(new self(), '__autoload')) )
            {
                throw new Exception( 'Register autoloader function is failed.' );
            }
        }
        catch( Exception $e )
        {
            Logger::error( $e->getMessage() );
        }
    }

    /**
     * 自动加载回调函数。
     *
     * @access public
     * @param string $className 类名
     * @return boolean 成功 / 失败
     */
    public function __autoload( $className )
    {
        // 如果类或者接口已经定义, 直接返回
        if( class_exists($className, false) || interface_exists($className, false) )
        {
            return true;
        }

        $root_dir = IniFileManager::getByFilesKey( "environment_config.ini", "root_lib_dir" );

        foreach( self::$config as $dir )
        {
            $path = $root_dir ."/". $dir . '/' . $className . '.class.php';
            // 如果文件存在, 加载并返回
            if( file_exists($path) )
            {
                require $path;
                return true;
            }
        }

        // 文件不存在
        return false;
    }

}