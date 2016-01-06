<?php
/**
 * session管理类
 *
 * 实现对session的快捷操作
 * eg：使用session变量作为该类的属性
 *
 * @access public
 * @author chenwenbin
 * @copyright Copyright(C) Tenone Inc.
 * @version 0.01
 * @since 2014/11/13
 */
final class Session 
{
    /**
     * 变量的session状态:session开始
     */
    const SESSION_STARTED = TRUE;
    
    /**
     * 变量的session状态:session停止
     */
    const SESSION_NOT_STARTED = FALSE;

    /**
     *
     * @access private
     */
    private function __construct() 
    {

    }
    
    /**
     * session状态
     */
    private $_state = self::SESSION_NOT_STARTED;
    
    /**
     * 单例对象
     */
    private static $_instance;
    
    /**
     *
     * session对象 单例获取方法
     *
     * @access public
     * @return object
     */
    public static function instance() 
    {
        if ( !isset( self::$_instance ) ) 
        {
            self::$_instance = new self;
        }
        self::$_instance->start();
        return self::$_instance;
    }
    
    /**
     *
     * session开始
     *
     * @access public
     * @return boolean
     */
    public function start() 
    {
        if ( $this->_state == self::SESSION_NOT_STARTED ) 
        {
            $this->_state = session_start();
        }
        return $this->_state;
    }
    
    /**
     *
     * 销毁session
     *
     * @access public
     * @return boolean
     */
    public function destroy() 
    {
        if ( $this->_state == self::SESSION_STARTED ) 
        {
            $this->_state = !session_destroy();
            unset( $_SESSION );
            return !$this->_state;
        }
        return false;
    }
    
    // ----- magic properties start. -----
    
    public function set( $name, $value ) 
    {
        $_SESSION[ $name ] = $value;
    }
    
    /**
     * @return mixed
     */
    public function get( $name ) 
    {
        if ( isset( $_SESSION[ $name ] ) ) 
        {
            return $_SESSION[ $name ];
        }
    }
    
    public function __unset( $name ) 
    {
        unset( $_SESSION[ $name ] );
    }
    
    /**
     * @return boolean
     */
    public function __isset( $name ) 
    {
        return isset( $_SESSION[ $name ] );
    }
    
    // ----- magic properties end. -----
}