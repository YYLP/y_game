<?php
/**
 * 请求处理基类
 *
 * @access abstract
 * @author chenwenbin
 * @copyright Copyright(C) Tenone Inc.
 * @version 0.01
 * @since 2014/11/13
 */

abstract class RequestStarter 
{

    const ACTION_CLASS_NAME_KEY_NUM  = 0;
    const ACTION_METHOD_NAME_KEY_NUM = 1;

    private $action_name = null;
    private $method_name = null;
    private $action      = null;
    private $path_info   = null;
    private $request_method  = null;
    private $request_params  = array();
    private $request_headers = array();

    public function __construct()
    {

    }

    /**
     * 根据PATH_INFO获得类名和方法名。
     *
     * @access public
     * @param 无
     * @return void
     */
    protected function initial()
    {
        // 获取PATH_INFO
        $path_info = $this->getPathInfo();
        if( empty($path_info) )
        {
            throw new NotFoundException();
        }

        // 对获得的类和方法进行检查
        $to_array          = explode( "/", $path_info );
        $this->action_name = ( isset($to_array[self::ACTION_CLASS_NAME_KEY_NUM]) ) ? Util::convertCamelCase( $to_array[self::ACTION_CLASS_NAME_KEY_NUM] ) . "Action" : null;
        $this->method_name = ( isset($to_array[self::ACTION_METHOD_NAME_KEY_NUM]) ) ? "exe" . Util::convertCamelCase( $to_array[self::ACTION_METHOD_NAME_KEY_NUM] ) : null;
        
        if( !class_exists($this->action_name) )
        {
            throw new NotFoundException("The $this->action_name Class is not exist.");
        }

        $this->action = new $this->action_name();
        if( !method_exists($this->action, $this->method_name) )
        {
            throw new NotFoundException("The $this->action_name Class $this->method_name Method is not exist.");
        }

    }

    /**
     * 获取请求的方式
     *
     * @access public
     * @param 无
     * @return string 请求方式
     */
    public function getRequestMethod()
    {
        if( is_null($this->request_method) )
        {
            $method = ( isset($_SERVER['REQUEST_METHOD']) ) ? $_SERVER['REQUEST_METHOD'] : null;
            // 统一用大写
            $this->request_method = strtoupper( $method );
        }

        return $this->request_method;
    }

    /**
     * 获取请求的参数
     *
     * @access public
     * @param string $key 参数名
     * @return string 请求value
     */
    public function getRequestParameter( $key )
    {
        $request_params = $this->getAllRequestParameters();
        if( isset($request_params[$key]) )
        {
            return $request_params[$key];
        }

        return null;
    }

    /**
     * 获取所有请求的参数
     *
     * @access public
     * @param 无
     * @return array 所有请求数据
     */
    public function getAllRequestParameters()
    {
        if( !$this->request_params )
        {
            // 包括保留的参数
            $this->request_params  = array_merge( $_POST, $_GET, $_FILES );
        }

        return $this->request_params;
    }

    /**
     * 获取请求头信息
     *
     * @access public
     * @param string $key 参数名
     * @return string 请求value
     */
    public function getRequestHeaderValue( $key )
    {
        $request_headers = $this->getAllRequestHeaderValues();
        if( isset($request_headers[$key]) )
        {
            return $request_headers[$key];
        }

        return null;
    }

    /**
     * 获取所有请求头信息
     *
     * @access public
     * @param 无
     * @return array 头信息数组
     */
    public function getAllRequestHeaderValues()
    {
        if( !$this->request_headers )
        {
            // 保存头信息
            $this->request_headers = apache_request_headers();
        }

        return $this->request_headers;
    }

    /**
     * 获取PATH_INFO
     *
     * @access public
     * @param 无
     * @return string PATH_INFO
     */
    public function getPathInfo()
    {
        if( is_null($this->path_info) )
        {
            $this->path_info = ( isset($_SERVER['PATH_INFO']) ) ? trim( $_SERVER['PATH_INFO'], "/" ) : "";
        }

        return $this->path_info;
    }

    /**
     * 获取action的实例
     *
     * @access public
     * @param 无
     * @return object action实例
     */
    protected function getActionInstance()
    {
        if( !isset($this->action) )
        {
            return null;
        }

        return $this->action;
    }

    /**
     * 获取action类名
     *
     * @access public
     * @param 无
     * @return string action类名
     */
    protected function getActionName()
    {
        if( !isset($this->action_name) )
        {
            return null;
        }

        return $this->action_name;
    }

    /**
     * 获取对应action类中的方法名
     *
     * @access public
     * @param 无
     * @return string 方法名
     */
    protected function getMethodName()
    {
        if( !isset($this->method_name) )
        {
            return null;
        }

        return $this->method_name;
    }

    /**
     * 开始执行对应的API处理
     *
     * @access public
     * @param 无
     * @return mixed 处理之后的结果
     */
    protected function startRequest()
    {

        $method = $this->getMethodName();

        $action = $this->getActionInstance();
        // 设置请求方式
        $action->setRequestMethod( $this->getRequestMethod() );
        // 设置请求参数
        $action->setParameters( $this->getAllRequestParameters() );
        // 设置HTTP请求header信息
        $action->setHeaders( $this->getAllRequestHeaderValues() );
        
        return $action->$method();
    }

}