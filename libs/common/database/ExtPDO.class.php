<?php
/**
 * PDO扩展类<br />
 * 包含一些基本过程和写log的操作
 *
 *
 * @access public
 * @author chenwenbin
 * @copyright Copyright(C) Tenone Inc.
 * @version 0.01
 * @since 2014/11/12
 */

class ExtPDO extends PDO 
{

    private $dsn = null;
    private $username = null;
    private $password = null;
    private $driver_options = array();

    public function __construct( $dsn, $username, $password, $driver_options = array() ) 
    {
        $this->dsn            = $dsn;
        $this->username       = $username;
        $this->password       = $password;
        $this->driver_options = $driver_options;
        parent::__construct( $dsn, $username, $password, $driver_options );
    }

    public function getDsn()
    {
        return $this->dsn;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getDriverOptions()
    {
        return $this->driver_options;
    }

    /**
     * PDO#prepare方法的重载, 扩展写日志的操作
     *
     * @access public
     * @param 
     * @return boolean 成功 / 失败
     */
    public function prepare( $statement, $driver_options = array() )
    {
        // SQL日志
        Logger::sql( $statement );
        return parent::prepare( $statement, $driver_options );
    }

    /**
     * PDO#query方法的重载, 扩展写日志的操作
     *
     * @access public
     * @param 
     * @return boolean 成功 / 失败
     */
    public function query( $statement )
    {
        // SQL日志
        Logger::sql( $statement );
        return parent::query( $statement );
    }

    /**
     * PDO#beginTransaction方法的重载, 扩展写日志的操作
     *
     * @access public
     * @param 无
     * @return boolean 成功 / 失败
     */
    public function beginTransaction()
    {
        $result = parent::beginTransaction();
        if( $result )
        {
            $msg = "The transaction started.";
            Logger::debug( $msg );
            Logger::info( $msg );
        }
        return $result;
    }

    /**
     * PDO#commit方法的重载, 扩展写日志的操作
     *
     * @access public
     * @param 无
     * @return boolean 成功 / 失败
     */
    public function commit()
    {
        $result = parent::commit();
        if( $result )
        {
            $msg = "The transaction commited.";
            Logger::debug( $msg );
            Logger::info( $msg );
        }
        return $result;
    }

    /**
     * PDO#rollBack方法的重载, 扩展写日志的操作
     *
     * @access public
     * @param 无
     * @return boolean 成功 / 失败
     */
    public function rollBack()
    {
        $result = parent::rollBack();
        if( $result )
        {
            $msg = "The transaction rollbacked.";
            Logger::debug( $msg );
            Logger::info( $msg );
            Logger::error( $msg );
        }
        return $result;
    }
}