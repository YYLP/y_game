<?php

require_once 'config/AutoloadConfig.class.php';
require_once 'common/utils/Autoload.class.php';

// 注册需要自动加载的类
Autoload::register( AutoloadConfig::$DIR_LIST );
// 注册异常处理
HandlerManager::register();