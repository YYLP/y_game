<?php
/**
 * 响应Json View类
 *
 * @access public
 * @author chenwenbin
 * @copyright Copyright(C) Tenone Inc.
 * @version 0.01
 * @since 2014/11/13
 */

class JsonView extends BaseView{

    /**
     *
     * Json响应
     *
     * @access public
     * @param boolean $is_return 是否返回（可选：默认值= FALSE）
     * @return void
     */
    public function display( $is_return = false )
    {
        $encoded = json_encode( $this->getDisplay(), JSON_NUMERIC_CHECK  );
        //$encoded = json_encode( $this->getDisplay() );
        if( $is_return === true )
        {
            return $encoded;
        }

        Logger::debug( 'Response json-> ' . $encoded );
        echo $encoded;
        exit;
    }

}