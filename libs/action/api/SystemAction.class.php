<?php
/**
 * Bulletin类
 *
 *
 * @access public
 * @author lijunhua
 * @copyright Copyright(C) Tenone Inc.
 * @version 0.01
 * @since 2015/01/06
 */

class SystemAction extends BaseAction {

    const BULLETIN_LIST = "bulletin";

    /**
     * API：获取公告信息
     *
     * @access public
     * @param 无
     * @return JsonView 响应json
     */
    public function getBulletin()
    {
        $requestParam = $this->getAllParameters();
        Logger::debug('requestParam:'. print_r($requestParam, true));

        $requestJsonParam = $this->getDecodedJsonRequest();
        Logger::debug('requestJsonParam:'. print_r($requestJsonParam, true));

        // 取缓存数据
        $cache = MemcacheManager::instance();
        $memcacheArr = $cache->get( self::BULLETIN_LIST );

        if( $memcacheArr )
        {
            $messageArr = $memcacheArr;
        }
        else
        {
            // 读取csv类
            $csv = new Parsecsv();
            $dir = IniFileManager::getRootDir() . "files/csv/" . self::BULLETIN_LIST . ".csv";
            $csv->auto( $dir );
            $messageArr = $csv->data;
        }
        return $messageArr;
    }

    /**
     * API：获取大事件信息
     *
     * @access public
     * @param 无
     * @return JsonView 响应json
     */
    public function getBigMessage()
    {
        $requestParam = $this->getAllParameters();
        Logger::debug('requestParam:'. print_r($requestParam, true));

        $requestJsonParam = $this->getDecodedJsonRequest();
        Logger::debug('requestJsonParam:'. print_r($requestJsonParam, true));

        // 取缓存数据
        $cache = MemcacheManager::instance();
        $memcacheArr = $cache->get( self::BULLETIN_LIST );

        if( $memcacheArr )
        {
            $messageArr = $memcacheArr;
        }
        else
        {
            // 读取csv类
            $csv = new Parsecsv();
            $dir = IniFileManager::getRootDir() . "files/csv/" . self::BULLETIN_LIST . ".csv";
            $csv->auto( $dir );
            $messageArr = $csv->data;
        }

        return $messageArr;
    }
}