<?php
/**
 * 异常:访问目标错误
 *
 * @access public
 * @author chenwenbin
 * @copyright Copyright(C) Tenone Inc.
 * @version 0.01
 * @since 2014/11/12
 */

class NotFoundException extends Exception 
{

    public function __construct($message = "", $code = 0, Exception $previous = null) 
    {
    	Logger::error($message);
        // 执行处理
        parent::__construct($message, $code, $previous);
    }

}