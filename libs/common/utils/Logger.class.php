<?php
/**
 * 日志操作类
 *
 *
 * @access public
 * @author chenwenbin
 * @copyright Copyright(C) Tenone Inc.
 * @version 0.01
 * @since 2014/11/13
 */

class Logger 
{

    const FILE_NAME      = "environment_config.ini";
    const LOG_DIR_NAME_ERROR = "dir_error_log";    
    const LOG_DIR_NAME_DEBUG = "dir_debug_log";
    const LOG_DIR_NAME_SQL   = "dir_sql_log";
    const LOG_DIR_NAME_INFO  = "dir_info_log";
    
    const WRITE_FLAG_ERROR = "error_log_flag";
    const WRITE_FLAG_DEBUG = "debug_log_flag";
    const WRITE_FLAG_SQL = "sql_log_flag";
    const WRITE_FLAG_INFO = "info_log_flag";

    /**
     * 错误日志操作函数
     *
     * @access public
     * @param string $msg 信息
     * @return boolean 成功 / 失败
     */
    public static function error( $msg )
    {
        if( !IniFileManager::getByFilesKey(self::FILE_NAME, self::WRITE_FLAG_ERROR) )
        {
            return false;
        }

        return self::write( self::LOG_DIR_NAME_ERROR, $msg, debug_backtrace() );
    }

    /**
     * 调试日志操作函数
     *
     * @access public
     * @param string $msg 信息
     * @param mixed $param 附加值（可以指定要显示的信息）
     * @return boolean 成功 / 失败
     */
    public static function debug( $msg, $param = null )
    {
        if( !IniFileManager::getByFilesKey(self::FILE_NAME, self::WRITE_FLAG_DEBUG) )
        {
            return false;
        }

        $param_str = "";
        if( $param )
        {
            $param_str = " Logging param-> " . print_r( $param, true );
        }

        return self::write( self::LOG_DIR_NAME_DEBUG, $msg . $param_str, debug_backtrace() );
    }

    /**
     * SQL日志操作函数
     *
     * @access public
     * @param string $msg 信息
     * @return boolean 成功 / 失败
     */
    public static function sql( $sql )
    {
        if( !IniFileManager::getByFilesKey(self::FILE_NAME, self::WRITE_FLAG_SQL) )
        {
            return false;
        }

        return self::write( self::LOG_DIR_NAME_SQL, $sql,debug_backtrace() );
    }

    /**
     * INFO日志操作函数
     *
     * @access public
     * @param string $msg 信息
     * @return boolean 成功 / 失败
     */
    public static function info( $msg )
    {
        if( !IniFileManager::getByFilesKey(self::FILE_NAME, self::WRITE_FLAG_INFO) )
        {
            return false;
        }

        return self::write( self::LOG_DIR_NAME_INFO, $msg, debug_backtrace() );
    }

    /**
     * 写入操作
     *
     * @access private
     * @param string $msg 信息
     * @return boolean 成功 / 失败
     */
    private static function write( $name, $msg, $backtrace = null )
    {
        $file_path = IniFileManager::getByFilesKey( self::FILE_NAME, $name );
        if( !$file_path )
        {
            return false;
        }

        $f = fopen( $file_path . '_' .date("Y-m-d"), 'a' );
        if( !$f )
        {
            return false;
        }

        if( !flock($f, LOCK_EX) )
        {
            return false;
        }

        $writer  = ( isset($backtrace[0]['file']) ) ? $backtrace[0]['file'] . '-> ' : "";
        $referer = ( isset($_SERVER['HTTP_REFERER']) ) ? ', referer: ' . $_SERVER['HTTP_REFERER'] : "";
        $client  = ( isset($_SERVER['REMOTE_ADDR']) ) ? $_SERVER['REMOTE_ADDR'] : "";
        $logs = '[' . date( 'D M d H:i:s Y' ) . '] [' . str_replace( 'dir_', '', $name ) . '] [client ' . $client . '] ' . $writer . $msg . $referer . "\n";
        if( !fwrite($f, $logs) )
        {
            return false;
        }

        return fclose( $f );
    }

}