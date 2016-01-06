<?php
/**
 * View层基类
 *
 * @access abstract
 * @author chenwenbin
 * @copyright Copyright(C) Tenone Inc.
 * @version 0.01
 * @since 2014/11/13
 */

abstract class BaseView{

    private $values = array();
    
    abstract public function display();

    /**
     *
     * 设置一个参数到view
     *
     * @access public
     * @param string $key 
     * @param mixed $value 
     * @return void
     */
    public function setValue( $key, $value )
    {
        $this->values[$key] = $value;
    }

    /**
     *
     * 获取预先分配好的参数
     *
     * @access public
     * @param string $key 
     * @return mixed $value 
     */
    public function getValue( $key )
    {
        if( !isset($this->values[$key]) )
        {
            return null;
        }

        return $this->values[$key];
    }

    /**
     *
     * 设置一个数组到view
     *
     * @access public
     * @param array $array 
     * @return void
     */
    public function setArray( $array )
    {
        if( is_array($array) )
        {
            $this->values = array_merge( $this->values, $array );
        }
    }

    /**
     *
     * 获取预先设置好的array参数
     *
     * @access public
     * @param string $array_name 数组名 
     * @param string $key 
     * @return mixed $value 
     */
    public function getArraysValue( $array_name, $key )
    {
        $array = $this->getValue( $array_name );
        if( !isset($array[$key]) )
        {
            return null;
        }

        return $array[$key];
    }
    
    
    /**
     *
     * 获取所有设置好的参数
     *
     * @access protected
     * @param 无
     * @return array 所有的参数
     */
    protected function getDisplay()
    {
        return $this->values;
    }

}