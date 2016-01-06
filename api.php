<?php
/**
 * 入口文件 <br />
 *
 * @access public
 * @author chenwenbin
 * @copyright Copyright(C) Tenone Inc.
 * @version 0.01
 * @since 2014/11/12
 */
$gameLibPath = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'libs';
set_include_path(get_include_path() . PATH_SEPARATOR . $gameLibPath);

require_once 'auto_config.php';
$starter = new ApiStarter();

//开始执行
$starter->execute();