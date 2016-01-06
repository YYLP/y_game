<?php
/**
 * 默认检查
 *
 *
 * @access public
 * @author chenwenbin
 * @copyright Copyright(C) Tenone Inc.
 * @version 0.01
 * @since 2014/11/12
 */

class DefaultFilter extends BaseFilter 
{

    public function __construct()
    {

    }

    /**
     *
     * 执行默认检查方法
     *
     * @access public
     * @param 无
     * @return void
     */
    public function execute( $request )
    {

        Logger::debug( '----- ' . __CLASS__ . ' is started -----' );

        // 验证 SessionKey
        $user_id = $request->getRequestParameter(Constants::PARAM_USER_ID);
        $clientSendKey = $request->getRequestParameter(Constants::PARAM_SESSION_KEY);
        
        //$userCache = UserCache::getCacheInstance();
        //$serverSaveKey = $userCache->getByKey( $user_id, Constants::CURRENT_SESSION_KEY);
        $serverSaveKey = UserCache::getByKey( $user_id, Constants::CURRENT_SESSION_KEY);

        if( $serverSaveKey !== $clientSendKey && $clientSendKey != 0 )
        {
            Logger::debug( 'Stopping ' . __CLASS__ . '. caused by: Invalid session key.' );
            Logger::debug( 'server save session key: ', $serverSaveKey );
            Logger::debug( 'client send session key: ', $clientSendKey );

            $view = new JsonView();
            $view->setValue( 'result', Constants::RESP_RESULT_ERROR );
            $view->setValue( 'message', "session key not match" );
            $view->display();
            throw new ForbiddenException("userid:$user_id send Session Key not match.");
        }

        Logger::debug( __CLASS__ . ' is success.' );
        Logger::debug( '----- ' . __CLASS__ . ' is finished -----' );
    }

}