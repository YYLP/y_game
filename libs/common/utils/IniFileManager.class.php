<?php
/**
 * ini文件管理。<br />
 *
 *
 * @access public
 * @author chenwenbin
 * @copyright Copyright(C) Tenone Inc.
 * @version 0.01
 * @since 2014/11/12
 */

class IniFileManager 
{

    /**
     * ini文件所在的目录
     */
    const DIR_NAME = "files";
    
    const ENV_CONFIG_PATH = "files/environment_config.ini";

    const ENV_CONFIG_FILE = "files/environment_config.ini";
    
    const ROOT_DIR_KEY = "root_lib_dir";

    /**
     * 定义私有的ini文件内容存储变量。
     */
    private static $ini_contents = array();

    private function __construct() 
    {

    }

    /**
     * 获取全部ini文件信息
     *
     * @access public
     * @param 无
     * @return array 全部ini文件信息
     */
    public static function getAll()
    {
        if( empty(IniFileManager::$ini_contents) )
        {
            IniFileManager::$ini_contents = self::getAllParsed();
        }

        return IniFileManager::$ini_contents;
    }

    /**
     * 获取ini文件信息
     *
     * @access public
     * @param string 文件名
     * @return array 全部文件信息
     */
    public static function getByFileName( $name )
    {
        $contents = IniFileManager::$ini_contents;
        if( !isset($contents[$name]) )
        {
            $parse = self::getParsedByFileName( $name );
            if( !$parse )
            {
                return null;
            }

            $contents[$name] = $parse;
            IniFileManager::$ini_contents = $contents;
        }

        return $contents[$name];
    }

    /**
     * 获取ini文件的字段信息。
     *
     * @access public
     * @param string 文件名
     * @param string 字段名
     * @return mixed ini文件中设定的值
     */
    public static function getByFilesKey( $file_name, $key )
    {
        $file_array = self::getByFileName( $file_name );
        if( !isset($file_array[$key]) )
        {
            return null;
        }

        return $file_array[$key];
    }

    /**
     * 解析指定的ini文件
     *
     * @access private
     * @param string 文件名
     * @return array 文件内容
     */
    private static function getParsedByFileName( $file )
    {
        // 判断是否为ini文件
        if( !preg_match("/\.ini/", $file) )
        {
            return null;
        }

        // 检查根目录是否已经设置
        $root_dir = self::getRootDir();
        if( !$root_dir )
        {
            return null;
        }

        $file_path = $root_dir ."/". self::DIR_NAME . "/" . $file;
        // 判断ini文件是否存在
        if( !is_file($file_path) )
        {
            return null;
        }

        return parse_ini_file( $file_path );
    }

    /**
     * 获取根目录的路径
     *
     * @access public
     * @param 无
     * @return string 根目录绝对路径
     */
    public static function getRootDir()
    {
        $contents = IniFileManager::$ini_contents;
        if( empty($contents[self::ENV_CONFIG_FILE]) )
        {
            $contents[self::ENV_CONFIG_FILE] = parse_ini_file( self::ENV_CONFIG_PATH );
            IniFileManager::$ini_contents = $contents;
        }

        if( !isset($contents[self::ENV_CONFIG_FILE][self::ROOT_DIR_KEY]) )
        {
            return null;
        }

        return $contents[self::ENV_CONFIG_FILE][self::ROOT_DIR_KEY];
    }

    /**
     * 解析所有ini文件
     * ※格式：array(「文件名」 => 「文件内容」)
     *
     * @access private
     * @param 无
     * @return array 所有ini文件内容
     */
    private static function getAllParsed()
    {
        //  检查根目录是否已经设置
        $root_dir = self::getRootDir();
        if( !$root_dir )
        {
            return array();
        }

        exec( "ls $root_dir" . self::DIR_NAME . "/", $files, $status );
        // 如果命令返回错误，则返回空
        if( (string)$status !== "0" )
        {
            return array();
        }

        $array = array();
        foreach( $files as $file )
        {
            $parse = self::getParsedByFileName( $file );
            if( $parse )
            {
                $array[$file] = $parse;
            }
        }

        return $array;
    }

}