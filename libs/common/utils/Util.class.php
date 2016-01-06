<?php
/**
 * 辅助工具类
 *
 *
 * @access public
 * @author chenwenbin
 * @copyright Copyright(C) Tenone Inc.
 * @version 0.01
 * @since 2014/11/13
 */

class Util 
{

    /**
     * 字符串转驼峰式命名
     *
     * @access public
     * @param string $str 已英文下划线_连接的字符串
     * @return boolean 驼峰式字符串
     */
    public static function convertCamelCase( $str )
    {
        return preg_replace( "/\s/", '', ucwords(strtolower(preg_replace( "/\_/", " ", $str ))) );
    }

    /**
     * 根据表名，获取的对应model类的实例
     *
     * @access public
     * @param string $table_name 表名
     * @return object Model类实例
     */
    public static function convertModelInstance( $table_name )
    {
        $model_name = self::convertCamelCase( $table_name ) . "Model";
        if( !class_exists($model_name) )
        {
            return null;
        }

        return new $model_name();
    }

    /**
     * 根据设定的总和，随即抽取(权重的概念)
     * ※随即选择的概率=设定值/设定总和
     * 使用场景:道具随即抽取，配置好比例权重 
     *
     * @access public
     * @param $randData array(「key」 => 「value number」)
     * @return mixed 抽取结果
     */
    public static function extractRandomAnswer( $randData ) 
    {
        $randNum = rand(1, array_sum( $randData ) );
        $subTotal = 0;
        foreach( $randData as $key => $num ) 
        {
            $subTotal += $num;
            if ( $subTotal >= $randNum ) 
            {
                return $key;
            }
        }

        return null;
    }

    /**
     * 获取时间数组
     *
     * @access public
     * @param  string $time_stamp YYYY-MM-DD HH:mm:ss
     * @return array $date_time 
     */
    public static function timeConverter($time_stamp)
    {
        $time_array = array();
        $time       = array();
        $time_array = explode(" ", $time_stamp);
        $datetime[]     = $time_array[0];
        if(isset($time_array[1]))
        {
            $datetime[]     = explode(":", $time_array[1]);
        }
        return $datetime;
    }

    /**
     *
     * 验证两个时间之间的范围是否合理
     *
     * @access public
     * @param string $start_date 开始时间 YYYY-MM-DD HH:mm:ss
     * @param string $end_date 结束时间 YYYY-MM-DD HH:mm:ss
     * @return boolean 结果
     */
    public function validateDiffDate($start_date, $end_date)
    {
       /*$start_utime = strtotime($start_date);
       $end_utime   = strtotime($end_date);*/

       /*
        mktime(hour,minute,second,month,day,year,is_dst)
        is_dst 可选。如果时间在日光节约时间(DST)期间，
        则设置为1，否则设置为0，若未知，则设置为-1。
        自 5.1.0 起，is_dst 参数被废弃。因此应该使用新的时区处理特性。
       */
       $start_tdate=Util::timeConverter($start_date);
       $start_date = explode("-", $start_tdate[0]);
       $start_udate = mktime($start_tdate[1][0],$start_tdate[1][1],$start_tdate[1][2],$start_date[1],$start_date[2],$start_date[0]);

       $end_tdate=Util::timeConverter($end_date);
       $end_date = explode("-", $end_tdate[0]);
       $end_udate  = mktime($end_tdate[1][0],$end_tdate[1][1],$end_tdate[1][2],$end_date[1],$end_date[2],$end_date[0]);
       if($start_udate > $end_udate)
       {
           return false;
       }
       return true;
    }

    /**
     * 获取错误消息
     *
     * @param int $error_id 错误id
     * @param array $param 变量数组
     * @return string $error_msg 错误消息
     */
    public static function getErrorMsg($error_id,$param = array())
    {
        //MessageConfig::$errorMsg文件暂时不定义
        /*$msg = MessageConfig::$errorMsg;
        $start_int = 0;
        $column = "";

        //如果param没有设置，直接输出
        if(isset($param))
        {
              $error_msg=vsprintf($msg[$error_id],$param);
        }
        else
        {
              $error_msg = $msg[$error_id];
        }
        return $error_msg;*/
        return "错误的操作";
    }

    /**
     * 解析接收到的HTTP header中的User-Agent，返回对应的关联数组
     * ※格式：[应用程序名称/sns版本/程序版本（操作系统名称;终端名称/发行版）
     * 暂未使用。。。
     *
     * @access public
     * @param  string $user_agent User-Agent
     * @return array $match 解析結果
     */
    public static function parseTenoneUserAgent( $user_agent )
    {
        // 采用正则表达式，以获得每项值
        if( !preg_match('/(?P<app_name>.*)\/(?P<sns_version>.*)\/(?P<version>.*)\((?P<os_name>.*)\;\s(?P<device_name>.*)\sBuild\/(?P<build>.*)\)/', $user_agent, $match) )
        {
            // 解析失败
            return null;
        }

        if( !is_array($match) )
        {
            return null;
        }

        $names = array(
            'app_name',
            'sns_version',
            'version',
            'os_name',
            'device_name',
            'build'
        );
        // 过滤数组
        return Util::trimArray( $match, $names, true );
    }

    /**
     *
     * 对数组的每一项进行trim操作
     * trim() 函数从字符串的两端删除空白字符和其他预定义字符
     * ※非递归
     *
     * @access public
     * @param array $array 待过滤的数组
     * @param array $trim_list 由待需要整理的key，构建而成的数组
     * @param boolean $trim_only 指定为TRUE，则得到的是以$trim_list中设置的value为key的数组，否则是过滤之后的$array
     * @return array 处理之后的数组
     */
    public static function trimArray( array $array, $trim_list = array(), $trim_only = false )
    {
        $return = array();
        // 没有该标志为TRUE，则返回值的格式以$array为基准的数组
        if( $trim_only !== true )
        {
            $return = $array;
        }
        // 如果待过滤的key数组为空，则待过滤的key数组等于$array中的$key
        if( empty($trim_list) )
        {
            $trim_list = array_keys( $array );
        }

        foreach( $trim_list as $name )
        {
            $value = null;
            // 只有当存在且不为数组时，才进行过滤
            if( isset($array[$name]) && !is_array($array[$name]) )
            {
                $value = trim( $array[$name] );
            }
            $return[$name] = $value;
        }

        return $return;
    }

    /**
     *
     * 邮件通知处理函数
     *
     * @access public
     * @param string $sub 邮件标题
     * @param Exception $exception 捕捉到的异常
     * @return boolean 处理结果：TRUE 发送成功 / FALSE 未发送成功
     */
    public static function sendExceptionMail( $sub, $exception )
    {
        // 获取系统Email接收者
        $reciever = IniFileManager::getByFilesKey( "environment_config.ini", "system_mail_reciever" );
        if( !$reciever )
        {
            return false;
        }

        $host = ( isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : php_uname('n') );
        // 发送
        $sub  = "[ylxx game] $sub / Host: $host";
        $msg  = "Date: " . date( 'Y-m-d H:i:s' ) .
            "\nName: " . get_class( $exception ) .
            "\nFile: " . $exception->getFile() .
            "\nLine: " . $exception->getLine() .
            "\nCode: " . $exception->getCode() .
            "\nMessage: " . $exception->getMessage()
        ;
        $ret  = mail( $reciever, $sub, $msg );
        if( $ret !== true )
        {
            return false;
        }

        Logger::debug( __METHOD__ . " was success. \nsubject->" . $sub . "\nmessage->\n" . $msg );
        return true;
    }

    /**
     *
     * 生成SessionKey
     *
     * @access public
     * @param integer $user_id 用户id
     * @return string
     */
    public static function generateSessionKey( $user_id )
    {
        return md5($user_id . '_' . uniqid());
    }

    /**
     * API：读取CSv
     *
     * @access public
     * @param $file 路径 $str 读取条件OR
     * @return array()
     */
    public function readCsv($file,$str=null)
    {
        //先读缓存，foreach需要的数据，没有找到，在用condition读取CSV
        $fileName=basename($file,".csv");

        /*$memcached = new Memcache();
        $memcached->connect("localhost", 11211);
        $CharacterDataArr=$memcached->get( $fileName );*/
        $cache = MemcacheManager::instance();
        $CharacterDataArr = $cache->get( $fileName );

        if (!$CharacterDataArr) 
        {
            $csv = new Parsecsv();
            $csv->conditions=$str;
            $csv->auto( $file);
            $CharacterDataArr = $csv->data;
            return $CharacterDataArr;
        }
        else
        {
            if (is_null($str)) 
            {

                 return $CharacterDataArr;
            }
            $conditionArr=explode(" OR ", $str);
            foreach ($conditionArr as $key => $value) 
            {
                $condition=explode(" = ", $value);
                $message[$key][$condition[0]]=$condition[1];
            }
           
            foreach ($message as $key => $value) 
            {
                foreach ($value as $key2 => $value2)
                {
                   foreach ($CharacterDataArr as $key3 => $value3) {
                        if ($value2==$CharacterDataArr[$key3][$key2]) {
                            $arr[]=$CharacterDataArr[$key3];
                        }
                    } 
                }
            }
            return $arr;
        }
        
    }
    
    
}