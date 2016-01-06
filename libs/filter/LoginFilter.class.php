<?php
/**
 * 对任意访问的url做登录检查
 *
 *
 * @access public
 * @author chenwenbin
 * @copyright Copyright(C) Tenone Inc.
 * @version 0.01
 * @since 2014/11/12
 */

class LoginFilter extends BaseFilter 
{

    public function __construct()
    {

    }

    /**
     *
     * 执行登录检查，如果错误，将返回状态代码403
     *
     * @access public
     * @param 无
     * @return void
     */
    public function execute( $request )
    {
        Logger::debug( '----- ' . __CLASS__ . ' is started -----' );

        // 验证 SessionKey
        $user_id = $request->getRequestParameter(Constants::PARAM_SESSION_KEY);
        $clientSendKey = $request->getRequestParameter(Constants::PARAM_USER_ID);

        $userCache = UserCache::getCacheInstance();
        $serverSaveKey = $userCache->getByKey( $user_id, Constants::CURRENT_SESSION_KEY);
        

        if( $serverSaveKey !== $clientSendKey )
        {
            Logger::debug( 'Stopping ' . __CLASS__ . '. caused by: Invalid session key.' );
            Logger::debug( 'server save session key: ', $serverSaveKey );
            Logger::debug( 'client send session key: ', $clientSendKey );

            $view = new JsonView();
            $view->setValue( 'result', Constants::RESP_RESULT_ERROR );
            $view->setValue( 'message', "session key not match" );
            $view->display();
            throw new ForbiddenException("Session Key not match.");
        }

        // 登录检查
        /*if( !$authorizer->loginCheck($auth_param) )
        {
            Logger::debug( 'Stopping ' . __CLASS__ . '. caused by: The request not authenticated.' );
            throw new ForbiddenException();
        }*/

        Logger::debug( __CLASS__ . ' is success.' );
        Logger::debug( '----- ' . __CLASS__ . ' is finished -----' );
    }
}