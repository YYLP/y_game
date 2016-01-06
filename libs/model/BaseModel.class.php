<?php
/**
 * Model基类
 *
 *
 * @access abstract
 * @author chenwenbin
 * @copyright Copyright(C) Tenone Inc.
 * @version 0.01
 * @since 2014/11/14
 */

abstract class BaseModel 
{

    const DEFAULT_PRIMARY_KEY_NAME     = 'id';

    abstract public static function getTableName();
    abstract public static function getPrimaryKeyName();

    // 仅开发使用
    public function __construct() 
    {
        $class_name       = get_called_class();
        $valid_class_name = Util::convertCamelCase( $class_name::getTableName() ) . "Model";
        if( $class_name !== $valid_class_name )
        {
            throw new ModelException( __CLASS__ . " called by invalid named class '" . $class_name . "'." );
        }
    }
    
    /**
     * 执行INSERT
     *
     * @access public
     * @param array $record INSERT的数组（[字段名]=>[值]））
     * @param PDO $pdo PDO对象（可选、指定的情况：需要进行事务处理时）
     * @return PDO 执行处理之后的PDO对象
     */
    public static function insert( array $record, $pdo = null )
    {

        $class = get_called_class();
        //$class      = new $class_name();

        $table_name    = '`' . $class::getTableName() . '`';

        if( !isset($pdo) )
        {
            $pdo = Database::getPdo();
        }

        $stmt = $pdo->prepare(
            "INSERT INTO $table_name (" . implode( ",", array_keys($record) ) . ") " .
            "VALUES(". implode( ",", array_fill(0, count($record), '?') ) . ")"
        );

        if( $stmt === false )
        {
            return null;
        }

        $i = 1;
        foreach( $record as $column=>$value )
        {
            $stmt->bindValue( $i, $value );
            $i++;
        }
        if( !$stmt->execute() )
        {
            return null;
        }

        return $pdo;
    }

    /**
     * 执行REPLACE
     *
     * @access public
     * @param array $record REPLACE的数组（[字段名]=>[值]））
     * @param PDO $pdo PDO对象（可选、指定的情况：需要进行事务处理时）
     * @return PDO 执行处理之后的PDO对象
     */
    public static function replace( array $record, $pdo = null )
    {

        $class = get_called_class();
        //$class      = new $class_name();

        $table_name    = '`' . $class::getTableName() . '`';

        if( !isset($pdo) )
        {
            $pdo = Database::getPdo();
        }
        $stmt = $pdo->prepare(
            "REPLACE INTO $table_name (" . implode( ",", array_keys($record) ) . ") " .
            "VALUES(". implode( ",", array_fill(0, count($record), '?') ) . ")"
        );
        if( $stmt === false )
        {
            return null;
        }

        $i = 1;
        foreach( $record as $column=>$value )
        {
            $stmt->bindValue( $i, $value );
            $i++;
        }
        if( !$stmt->execute() )
        {
            return null;
        }

        return $pdo;
    }

    /**
     * 执行UPDATE
     * （WHERE条件为必须项）
     *
     * @access public
     * @param array $record 更新数组，包含需要变更的值（[字段名]=>[值]））
     * @param array $wheres 条件数组，包含需要搜索的值（[字段名]=>[值]））
     * @param PDO $pdo PDO对象（可选、指定的情况：需要进行事务处理时）
     * @return PDO 执行处理之后的PDO对象
     */
    public static function update( array $record, array $wheres, $pdo = null )
    {
        if( empty($record) || empty($wheres) )
        {
            return null;
        }

        $class_name    = get_called_class();
       // $class         = new $class_name();
        $table_name    = '`' . $class_name::getTableName() . '`';
        $columns       = array_keys( $record );
        $where_columns = array_keys( $wheres );

        if( !isset($pdo) )
        {
            $pdo = Database::getPdo();
        }
        $stmt = $pdo->prepare( 
            "UPDATE $table_name SET " .
            vsprintf( implode(" = ?,",$columns) . " = ?", $columns) .
            " WHERE " .
            vsprintf( implode(" = ? AND ",$where_columns) . " = ?", $where_columns)
        );
        if( $stmt === false )
        {
            return null;
        }

        $i = 1;
        foreach( $record as $column=>$value )
        {
            $stmt->bindValue( $i, $value );
            $i++;
        }
        foreach( $wheres as $column=>$value )
        {
            $stmt->bindValue( $i, $value );
            $i++;
        }
        if( !$stmt->execute() )
        {
            return null;
        }

        return $pdo;
    }

    /**
     * 执行SELECT
     * （如果省略了第一和第二个参数，将查询所有）
     *
     * @access public
     * @param array $wheres 条件数组，包含需要搜索的值（可选，[字段名]=>[值]））
     * @param array $columns 查询字段数组，包含字段名（可选：数组（[字段名1]，[字段名2]，[字段名3]））
     * @param PDO $pdo PDO对象（可选）
     * @return array SELECT记录的数组（数组（[字段名]=>[值]））
     */
    public static function select( $wheres = array(), $columns = array(), $pdo = null )
    {
        $class_name    = get_called_class();
        $table_name    = '`' . $class_name::getTableName() . '`';

        if( !isset($pdo) )
        {
            $pdo = Database::getPdo();
        }

        $str_columns = "*";
        if( !empty($columns) )
        {
            $str_columns = implode( ",", $columns );
        }

        $str_wheres = "";
        if( !empty($wheres) )
        {
            $where_columns = array_keys( $wheres );
            $str_wheres = "WHERE " . vsprintf( implode(" = ? AND ",$where_columns) . " = ?", $where_columns );
        }

        $stmt = $pdo->prepare( 
            "SELECT $str_columns FROM $table_name $str_wheres"
        );

        if( $stmt === false )
        {
            return array();
        }

        $i = 1;
        foreach( $wheres as $column=>$value )
        {
            $stmt->bindValue( $i, $value );
            $i++;
        }
        return self::fetchToArray( $stmt );
    }
    
    /**
     * 执行SELECT（限制limit）
     * （如果省略了第一和第二个参数，将查询所有）
     *
     * @access public
     * @param array $wheres 条件数组，包含需要搜索的值（可选，[字段名]=>[值]））
     * @param limit array $limit array("page"=>页码,"limit"=>每页几条记录)
     * @param array $columns 查询字段数组，包含字段名（可选：数组（[字段名1]，[字段名2]，[字段名3]））
     * @param PDO $pdo PDO对象（可选）
     * @return array SELECT记录的数组（数组（[字段名]=>[值]）） 
     */
    public static function selectlimit( $wheres = array(),$limit = array(), $columns = array(), $pdo = null )
    {
        $class_name    = get_called_class();
        $table_name    = '`' . $class_name::getTableName() . '`';

        if( !isset($pdo) )
        {
            $pdo = Database::getPdo();
        }

        $str_columns = "*";
        if( !empty($columns) )
        {
            $str_columns = implode( ",", $columns );
        }

        $str_wheres = "";
        if( !empty($wheres) )
        {
            $where_columns = array_keys( $wheres );
            $str_wheres = "WHERE " . vsprintf( implode(" = ? AND ",$where_columns) . " = ?", $where_columns );
        }

        $str_limit = "";
        if(!empty($limit))
        {
            $page = $limit["page"];
            $setlimit = $limit["limit"];
            $setpage = ($page - 1) * $setlimit;
            $str_limit = "LIMIT $setpage,$setlimit";
        }
        
        $stmt = $pdo->prepare( 
            "SELECT $str_columns FROM $table_name $str_wheres $str_limit"
        );
        if( $stmt === false )
        {
            return array();
        }

        $i = 1;
        foreach( $wheres as $column=>$value )
        {
            $stmt->bindValue( $i, $value );
            $i++;
        }
        return self::fetchToArray( $stmt );
    }
    
    /**
     * 执行SELECT，获取记录第一行
     * （如果省略了第一和第二个参数，将查询所有）
     *
     * @access public
     * @param array $wheres 条件数组，包含需要搜索的值（可选，[字段名]=>[值]））
     * @param array $columns 查询字段数组，包含字段名（可选：数组（[字段名1]，[字段名2]，[字段名3]））
     * @param PDO $pdo PDO对象（可选）
     * @return array SELECT记录的数组（数组（[字段名]=>[值]）） 
     */
    public static function selectOne( $wheres = array(), $columns = array(), $pdo = null )
    {
        $record = self::select( $wheres, $columns, $pdo );
        // 第一条记录是否存在
        if( !isset($record[0]) )
        {
            return array();
        }

        // 第一条记录是否为数组
        if( !is_array($record[0]) )
        {
            return array();
        }

        return $record[0];
    }

    /**
     * 结果集转数组
     *
     * @access public
     * @param PDOStatement $stmt SELEC的prepare方法处理完之后返回的PDOStatement对象
     * @return array SELECT记录的数组（数组（[字段名]=>[值]）） 
     */
    public static function fetchToArray( $stmt )
    {
        if( !$stmt )
        {
            return array();
        }

        if( !$stmt->execute() )
        {
            return array();
        }

        $result = $stmt->fetchAll( PDO::FETCH_ASSOC );
        if( !$result )
        {
            return array();
        }

        return $result;
    }

    /**
     * select求平均值
     *
     * @access public
     * @param  array $wheres 搜索条件
     * @param  string $field 需要求平均值的字段
     * @param  string $fraction 平均值保留到小数点后几位
     * @param  string $pdo
     * @return array  $average 结果
     */
    public static function selectAverage( $wheres=array(), $field, $fraction=0, $pdo=null )
    {
        //DB接続
        if( !isset($pdo) )
        {
            $pdo = Database::getPdo();
        }
        $class_name    = get_called_class();
        $class         = new $class_name();
        $table_name    = '`' . $class::getTableName() . '`';

        //条件
        $str_wheres = "";
        if( !empty($wheres) )
        {
            $where_columns = array_keys( $wheres );
            $str_wheres = "WHERE " . vsprintf( implode(" = ? AND ",$where_columns) . " = ?", $where_columns );
        }

        $stmt = $pdo->prepare( 
            "SELECT round(AVG($field),$fraction) FROM $table_name $str_wheres"
        );

        $i = 1;
        foreach( $wheres as $column=>$value )
        {
            $stmt->bindValue( $i, $value );
            $i++;
        }

        return self::fetchToArray( $stmt );
        
    }
     
    /**
     * 获取表定义
     *
     * @access public
     * @return array 表注释
     */
    public static function getTableDefine()
    {
        $class_name = get_called_class();
        $class      = new $class_name();
        $table_name    = '`' . $class::getTableName() . '`';

        if( !isset($pdo) )
        {
            $pdo = Database::getPdo();
        }

        $stmt = $pdo->prepare("SHOW FULL COLUMNS FROM ".$table_name);
        if( $stmt === false )
        {
            return null;
        }

        return self::fetchToArray($stmt);
    }
}
