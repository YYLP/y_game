<?php
/**
 * 开始处理API请求 <br />
 * 执行FiltersConfig过滤器 <br />
 * 过滤器执行完之后,跳转到相应的函数进行处理 <br />
 *
 * @access public
 * @author chenwenbin
 * @copyright Copyright(C) Tenone Inc.
 * @version 0.01
 * @since 2014/11/13
 */

class ApiStarter extends RequestStarter 
{

    /**
     * API请求处理开始
     *
     * @access public
     * @param 无
     * @return void
     */
    public function execute()
    {
        Logger::debug( '********************START***********************' );
        Logger::debug( 'Headers: ', $this->getAllRequestHeaderValues() );
        try
        {
            // 初始化请求处理
            $this->initial();
            $path_info = $this->getPathInfo();
            // API白名单检查
            if( !in_array($path_info, array_keys(ApiConfig::getApiList())) )
            {
                throw new NotFoundException( 'The "PATH_INFO" is not defined.' );
            }

            // 执行过滤器
            $config = FiltersConfig::$API_FILTER_CONFIG;
            if( isset( $config[$path_info] ) )
            {
                $list = $config[$path_info];
         
                Logger::debug( ' ' . count($list) . ' filters are defined.' );
                foreach( $list as $filter_name )
                {
                    // 过滤器初始化
                    $filter = new $filter_name( $this );
    
                    Logger::debug( 'Executing filter "' . $filter_name . '".' );

                    // 执行过滤器
                    $filter->execute( $this );
                }
            }
            Logger::debug( 'Executing action "' . $this->getActionName() . '#' . $this->getMethodName() . '".' );
            // 连接后关闭
            Database::destroy();
            // 过滤结束，执行对应的方法
            $view = $this->startRequest();
            if( !($view instanceof BaseView) )
            {
                throw new Exception( '"' . $this->getActionName() . '#' . $this->getMethodName() . '" returned invalid value.' );
            }

            Logger::debug( '********************FINISH***********************' );
            
            // 输出结果
            $view->display();
        }
        catch( NotFoundException $ne )
        {
            // 404回应
            header( "HTTP/1.1 404 Not Found" );
        }
        catch( ForbiddenException $fe )
        {
            // 403回应
            header( "HTTP/1.1 403 Forbidden" );
        }
        catch( ModelException $me )
        {
            //在Model层异常（DB相关的处理）
            Logger::error( $me->getMessage() . "\n" . $me->getTraceAsString() );
            // E-mail通知
            HandlerManager::exceptionReport( $me );
            // 服务器错误
            header('HTTP/1.1 500 Internal Server Error');
        }
        catch( PDOException $pe )
        {
            Logger::error( $pe->getMessage() . "\n" . $pe->getTraceAsString() );
            // E-mail通知
            HandlerManager::exceptionReport( $pe );
            // 服务器错误
            header('HTTP/1.1 500 Internal Server Error');
        }
        catch( Exception $e )
        {
            Logger::error( $e->getMessage() . "\n" . $e->getTraceAsString() );
            // E-mail通知
            HandlerManager::exceptionReport( $e );
            // 服务器错误
            header('HTTP/1.1 500 Internal Server Error');
        }
    }

    public static function isProd()
    {
        return self::checkEnviroment( 'prod' );
    }

    public static function isDev()
    {
        return self::checkEnviroment( 'dev' );
    }

    public static function isLocal()
    {
        return self::checkEnviroment( 'local' );
    }

    public static function checkEnviroment( $env )
    {
        return ( IniFileManager::getByFilesKey("environment_config.ini", "env_mode") == strtolower($env) );
    }
}