<?php
/**
 * 过滤器基类
 *
 *
 * @access abstract
 * @author chenwenbin
 * @copyright Copyright(C) Tenone Inc.
 * @version 0.01
 * @since 2014/11/13
 */

abstract class BaseFilter 
{

    protected function __construct()
    {

    }

    abstract public function execute( $request );
}