<?php
/**
 * 异常处理<br />
 *
 *
 * @access public
 * @author chenwenbin
 * @copyright Copyright(C) Tenone Inc.
 * @version 0.01
 * @since 2014/11/12
 */
class HandlerManager 
{

    public static $DEFINE_ERROR_CODE = array(
        E_ERROR           => 'Fatal',
        E_PARSE           => 'Fatal',
        E_CORE_ERROR      => 'Fatal',
        E_COMPILE_ERROR   => 'Fatal',
        E_CORE_WARNING    => 'Warning',
        E_COMPILE_WARNING => 'Warning'
    );

    /**
     * 注册处理回调
     *
     * @access public
     * @param void
     * @return void
     */
    static public function register()
    {
        // 注册退出处理回调
        register_shutdown_function( array(new self(), 'shutdownHandler') );
        // 注册异常处理回调
        set_exception_handler( array(new self(), 'exceptionReport') );
    }

    /**
     * 检查最后一个错误，如果匹配定义则发送邮件。
     *
     * @access public
     * @param void
     * @return void
     */
    public static function shutdownHandler()
    {
        // 获取最后一个错误信息
        $last_error = error_get_last();
        if( !isset($last_error['type']) )
        {
            return null;
        }

        $error_type     = $last_error['type'];
        $defined_errors = self::$DEFINE_ERROR_CODE;
        // 如果错误码的定义不匹配，则不会发送邮件
        if( !isset($defined_errors[$error_type]) )
        {
            return null;
        }

        // 处理异常
        self::exceptionReport( 
            new PhpErrorException( $defined_errors[$error_type] . ': ' . $last_error['message'] . ' file->' . $last_error['file'] . ' line->' . $last_error['line'] )
        );
    }

    /**
     * 处理异常，通过电子邮件通知。
     *
     * @access public
     * @param Exception $exception 异常
     * @return void
     */
    public static function exceptionReport( $exception )
    {
        //$ret = Util::sendExceptionMail( 'Report critical error.', $exception );
        // E-mail通知
        if( $ret !== true )
        {
            Logger::debug( "Error mail is not sent." );
        }
    }

}