<?php
/**
 * API信息定义类。
 *
 * @access public
 * @author chenwenbin
 * @copyright Copyright(C) Tenone Inc.
 * @version 0.01
 * @since 2014/11/12
 */

class ApiConfig 
{
    /**
     * <p>API列表:获取类<p>
     * array([URL传递参数] => [参数描述])
     * 
     */
    public static $API_LIST_GET = array(
        'user/get_user_info'     => '获取用户信息',
        'payment/get_diamond'    => '获取所拥有的钻石',
        'payment/get_product_id' => '获取商品信息',
        'payment/get_product'    => '获取商品信息',
        'system/get_bulletin'    => '获取公告',
        //'character/get_character_info'    => '获取主角信息',
        //'general/get_general_info'    => '获取武将信息',
        //'task_and_achieve/get_all_info'    => '获取任务成就信息',
        //'friend/get_friend_menu'    => '好友界面',
        'friend/search_friend'    => '查找好友',
        'friend/get_fit_info'    => '获取合体信息',
        'mail/get_mail_info'    => '获取邮箱信息',
        'game/get_checkpoint'   => '获取已开启的关卡信息',
        'rank/get_rank'   => '获取排行榜',
        //'buy_prop/get_mall_info'    => '获取商城信息',
        //'buy_prop/get_prop_info'    => '获取道具信息',
    );

    /**
     * <p>API列表:更新类<p>
     * array([URL传递参数] => [参数描述])
     * 
     */
    public static $API_LIST_UPDATE = array(
        'user/name_setting'      => '用户名设置',
        'payment/ios'            => '付费购买(IOS)',
        'payment/android'        => '付费购买(Android)',
        'payment/update_diamond' => '更新用户钻石',
        'character/update_character_attribute' => '升级主角属性',
        'character/buy_character' => '购买主角',
        'character/update_character' => '进化主角',
        'character/finish_update_character' => '主角完成进化',
        'character/cost_update_character' => '花钱跳过等待时间',
        'character/change_fight_character' => '更换上阵主角',
        'general/buy_general' => '购买武将',
        'general/update_general' => '升级武将',
        'task_and_achieve/get_achieve_reward'    => '领取成就奖励',
        'task_and_achieve/get_task_reward'    => '领取任务奖励',
        'friend/add_friend'    => '添加好友',
        'friend/agree_friend'    => '同意添加好友',
        'friend/refuse_add'    => '拒绝添加好友',
        'friend/delete_friend'    => '删除好友',
        'friend/config_button'    => '拒绝确定按钮',
        'friend/update_fit_time'    => '更新合体时间',
        'mail/update_one_mail'    => '接收一个邮件',
        'mail/update_all_mail'    => '接收所有邮件',
        'buy_prop/buy_mall'    => '购买商城物品',
        'game/start_game'        => '开始游戏',
        'game/end_game'          => '游戏结束',
        'game/resurrect'          => '复活',
        'user/login'          => '游戏结束',


    );

    /**
     * <p>API列表:其他类<p>
     * array([URL传递参数] => [参数描述])
     * 
     */
    public static $API_LIST_OTHER = array(
        'auth/user_register'   => '用户注册',
        'auth/user_login'      => '用户登录',
        'auth/quick_register'  => '快速注册',
    );

    /**
     * <p>API列表:所有<p>
     * array([URL传递参数] => [参数描述])
     * 
     */
    public static function getApiList()
    {
        return array_merge( self::$API_LIST_GET, self::$API_LIST_UPDATE, self::$API_LIST_OTHER );
    }

}
