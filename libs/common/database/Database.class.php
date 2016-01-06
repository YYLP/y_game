<?php
/**
 * 通过PDO操作DB <br />
 *
 * @access public
 * @author chenwenbin
 * @copyright Copyright(C) Tenone Inc.
 * @version 0.01
 * @since 2014/11/12
 */

class Database 
{

    const FILE_NAME           = "database_pdo.ini";
    const CONFIG_KEY_DSN      = 'dsn';
    const CONFIG_KEY_USER     = 'user';
    const CONFIG_KEY_PASSWORD = 'password';

    public static $DEFAULT_DRIVER_OPTIONS = array(
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET CHARACTER SET `utf8`"
    );

    private static $singleton;

    private function __construct() 
    {

    }

    /**
     * 单例:PDO对象
     *
     * @access public
     * @param 无
     * @return PDO
     */
    public function getPdo()
    {
        if( Database::isSetInstance() )
        {
            return Database::$singleton;
        }

        // 创建pdo对象
        Database::$singleton = Database::initializeInstance();
        return Database::$singleton;
    }
    
    /**
     * 检查pdo单例对象是否设置
     *
     * @access private
     * @param 无
     * @return boolean 判断结果（true=正常、false=异常）
     */
    private static function isSetInstance()
    {
        if( (Database::$singleton instanceof PDO) )
        {
            // 如果前一个连接是主连接 OK
            if( Database::$singleton->getDsn() == IniFileManager::getByFilesKey(Database::FILE_NAME, "dsn") )
            {
                return true;
            }
        }

        return false;
    }

    /**
     * 初始化 PDO对象
     *
     * @access public
     * @param array $configs PDO#__construct 使用数组来指定第一~第三个参数（默认值:生产环境的数据库）
     * @param array $driver_options PDO#__construct 第四个参数（默认：「SET CHARACTER SET `utf8`」）
     * @return PDO 对象
     */
    public static function initializeInstance( $configs = array(), $driver_options = array() )
    {
            if( empty($configs) )
            {
                $configs = IniFileManager::getByFileName( self::FILE_NAME );
            }
            if( empty($configs) )
            {
                throw new Exception( 'Could not get configuration of "' . self::FILE_NAME . '".' );
            }

            if( empty($driver_options) )
            {
                $driver_options = Database::$DEFAULT_DRIVER_OPTIONS;
            }

            $pdo = new ExtPDO(
                $configs[self::CONFIG_KEY_DSN],
                $configs[self::CONFIG_KEY_USER],
                $configs[self::CONFIG_KEY_PASSWORD],
                // 指定特定的连接选项
                $driver_options
            );
            // 设置错误报告:抛出异常
            $pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
            //$pdo->setAttribute( PDO::ATTR_EMULATE_PREPARES, false );
            return $pdo;
    }
    
    /**
     * 通过设置NULL来销毁pdo对象。
     * ※这时，如果在调用自＃getPdo，将产生一个新的连接对象。
     *
     * @access public
     * @param 无
     * @return void
     */
    public function destroy()
    {
        if( Database::isSetInstance() )
        {
            self::$singleton = null;
        }
    }

}