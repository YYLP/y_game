<?php
/**
 * 自动加载配置文件 <br />
 *
 * @access public
 * @author chenwenbin
 * @copyright Copyright(C) Tenone Inc.
 * @version 0.01
 * @since 2014/11/12
 */

class AutoloadConfig 
{

    /**
     * <p>自动加载的对象目录。<p>
     */
    public static $DIR_LIST = array(
        'config',
        'action',
        'action/api',
        'common/view',
        'common/utils',
        'common/database',
        'common/curl',
        'exception',
        'filter',
        'model',
        'model/api',
        'request'
    );
}