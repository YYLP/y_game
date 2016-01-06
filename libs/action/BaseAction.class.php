<?php
/**
 * Action基类
 * 所有的API Action类都继承此类, 并以exe开头来对各个方法进行命名
 *
 *
 * @access abstract
 * @author chenwenbin
 * @copyright Copyright(C) Tenone Inc.
 * @version 0.01
 * @since 2014/11/13
 */

abstract class BaseAction 
{

    private $parameters     = array();
    private $header_values  = array();
    private $json_request   = null;
    private $request_method = null;

    public function __construct() 
    {

    }

    public function setParameters( $parameters )
    {
        $this->parameters = $parameters;
    }

    public function setHeaders( $header_values )
    {
        $this->header_values = $header_values;
    }

    public function setRequestMethod( $request_method )
    {
        $this->request_method = strtoupper( $request_method );
    }

    /**
     * 检查请求的方式
     *
     * @access protected
     * @param string $method 请求方式
     * @return boolean 成功 / 失败
     */
    protected function checkRequestMethod( $method )
    {
        if( $this->request_method == strtoupper((string)$method) )
        {
            return true;
        }

        return false;
    }

    /**
     * 获取请求的参数
     *
     * @access protected
     * @param string $key 参数名
     * @return string 请求value
     */
    protected function getParameter( $key )
    {
        return isset( $this->parameters[$key] ) ? $this->parameters[$key] : null;
    }

    /**
     * 获取所有请求的参数
     *
     * @access protected
     * @param 无
     * @return array 所有请求数据
     */
    protected function getAllParameters()
    {
        return $this->parameters;
    }

    /**
     * 获取请求头信息
     *
     * @access protected
     * @param string $key 参数名
     * @return string 请求value
     */
    protected function getHeaderValue( $key )
    {
        if( isset($this->header_values[$key]) )
        {
            return $this->header_values[$key];
        }

        return null;
    }

    /**
     * 获取所有请求头信息
     *
     * @access protected
     * @param 无
     * @return array 头信息数组
     */
    protected function getAllHeaderValues()
    {
        return $this->header_values;
    }

    /**
     * 获取解码之后的JSON请求信息
     *
     * @access public
     * @param 无
     * @return array JSON请求信息所转换的数组
     */
    public function getDecodedJsonRequest()
    {
        if( isset($this->json_request) )
        {
            return $this->json_request;
        }
        $json_request = $this->getParameter( Constants::PARAM_JSON_KEY );

        if( !isset($json_request) )
        {
            return null;
        }

	    $utf8_string = mb_convert_encoding((string)$json_request, 'UTF-8');
        self::DBG($utf8_string);
        $decoded = json_decode( $utf8_string, true );
        //DBG($json_request);
        //$decoded = json_decode( (string)$json_request, true );
        if( !self::checkJsonLastError() )
        {
            Logger::debug( "Invalid Json format requested." );
            return null;
        }

        $this->json_request = self::getDecodedJson( $json_request );
        return $this->json_request;
    }

    /**
     * 解码JSON
     *
     */
    public static function getDecodedJson( $str )
    {
        $decoded = json_decode( $str, true );
        if( !self::checkJsonLastError() )
        {
            Logger::debug( "Invalid Json format requested." . print_r($str, true) );
            return null;
        }

        return $decoded;
    }
    
    
    
    /**
     * 检查函数json_last_error的返回值
     *
     * @access private
     * @param 无
     * @return boolean 成功 / 失败
     */
    private static function checkJsonLastError()
    {
        $last_error_code = json_last_error(); 
        if( $last_error_code == JSON_ERROR_NONE )
        {
            return true;
        }

        switch( $last_error_code )
        {
            case JSON_ERROR_DEPTH: 
                Logger::error( "json_last_error: The maximum stack depth has been exceeded." );
                break;
            case JSON_ERROR_STATE_MISMATCH:
                Logger::error( "json_last_error: Invalid or malformed JSON." );
                break;
            case JSON_ERROR_CTRL_CHAR:
                Logger::error( "json_last_error: Control character error, possibly incorrectly encoded." );
                break;
            case JSON_ERROR_SYNTAX:
                Logger::error( "json_last_error: Syntax error." );
                break;
            case JSON_ERROR_UTF8:
                Logger::error( "json_last_error: Malformed UTF-8 characters, possibly incorrectly encoded." );
                break;
            default:
                Logger::error( "json_last_error: The function returned invalid error code." );
                break;
       }

       return false;
    }

    /**
     * 基于API，返回规范的JSON错误响应信息
     *
     * @access protected
     * @param JsonView $view
     * @param string $msg 信息
     * @return string JSON
     */
    protected function getErrorViewByJson( $view, $msg = '', $api_type )
    {
        if( !( $view instanceof JsonView) )
        {
            throw new Exception( "Parameter must be instance of JsonView." );
        }

        $view->setValue( 'result', Constants::RESP_RESULT_ERROR );
        $view->setValue( 'tenone_api_type', $api_type );
        $view->setValue( 'message', $msg );
        return $view;
    }
    /**
     * 基于API，返回规范的JSON响应信息，包含错误类型
     *
     * @access protected
     * @param JsonView $view
     * @param string $msg 信息
     * @return string JSON
     */
    protected function getViewByJson( $view, $msg = '', $result_type ,$api_type )
    {
        if( !( $view instanceof JsonView) )
        {
            throw new Exception( "Parameter must be instance of JsonView." );
        }

        $view->setValue( 'result', $result_type );
        $view->setValue( 'tenone_api_type', $api_type );
        $view->setValue( 'message', $msg );
        return $view;
    }
    /**
     * 执行重定向
     *
     * @access public
     * @param string $path 跳转路径
     * @return void
     */
    public function redirect( $path )
    {
        header( "Location: $path" );
        exit;
    }
    
    /**
     * debug调试
     *
     * @access public
     * @param string $msg 
     * @return void
     */
    public function DBG( $msg )
    {
        $trace = debug_backtrace();
        Logger::debug(print_r($msg,true)." 【" . $trace[ 0 ][ 'file' ] . "(" . $trace[ 0 ][ 'line' ] . ")】");
    }     

}