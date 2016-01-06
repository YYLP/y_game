<?php
/**
 * 过滤器信息定义类
 *
 * @access public
 * @author chenwenbin
 * @copyright Copyright(C) Tenone Inc.
 * @version 0.01
 * @since 2014/11/12
 */

class FiltersConfig 
{

    /**
    * <p>PATH_INFO（api.php随后的字符串）每个过滤定义<p>
    * 
    */
    public static $API_FILTER_CONFIG = array(
        
        /*
         * 用户注册
         */        
        'auth/user_register' => array(
            'DefaultFilter'
        ),

        /*
         * 用户登录
         */   
        'auth/user_login' => array(
            'DefaultFilter'
        ),

        /*
         * 获取商品信息
         */           
        'payment/get_product' => array(
            'DefaultFilter'
        ),


        /*
         * 获取公告
         */   
        'bulletion/get_bulletin' => array(
        ),

         /*
         * 获取主角信息
         */           
        'character/get_character_info' => array(
            'DefaultFilter'
        ),

         /*
         * 升级主角属性
         */ 
        'character/update_character_attribute'=> array(
            'DefaultFilter'
        ),

        /*
         * 购买主角
         */ 
        'character/buy_character'=> array(
            'DefaultFilter'
        ),

        /*
         * 进化主角
         */           
        'character/update_character' => array(
            'DefaultFilter'
        ),

        /*
         * 主角进化完成
         */           
        'character/finish_update_character' => array(
            'DefaultFilter'
        ),

        /*
         * 花钱跳过等待时间
         */           
        'character/cost_update_character' => array(
            'DefaultFilter'
        ),

        /*
         * 更改上阵主角
         */           
        'character/change_fight_character' => array(
            'DefaultFilter'
        ),

        /*
         * 购买武将
         */           
        'general/buy_general' => array(
            'DefaultFilter'
        ),

        /*
         * 获取武将信息
         */           
        'general/get_general_info' => array(
            'DefaultFilter'
        ),

        /*
         * 升级武将
         */           
        'general/update_general' => array(
            'DefaultFilter'
        ),

        /*
         * 获取任务成就信息
         */           
        'task_and_achieve/get_all_info' => array(
            'DefaultFilter'
        ),
        
        /*
         * 领取成就奖励
         */           
        'task_and_achieve/get_achieve_reward' => array(
            'DefaultFilter'
        ),

        /*
         * 领取任务奖励
         */           
        'task_and_achieve/get_task_reward' => array(
            'DefaultFilter'
        ),

        /*
         * 好友界面
         */           
        'friend/get_friend_menu' => array(
            'DefaultFilter'
        ),
        /*
         * 查找好友
         */           
        'friend/search_friend' => array(
            'DefaultFilter'
        ),

        /*
         * 添加好友
         */           
        'friend/add_friend' => array(
            'DefaultFilter'
        ),

        /*
         * 同意添加
         */           
        'friend/agree_friend' => array(
            'DefaultFilter'
        ),

        /*
         * 拒绝添加
         */           
        'friend/refuse_add' => array(
            'DefaultFilter'
        ),

        /*
         * 删除好友
         */           
        'friend/delete_friend' => array(
            'DefaultFilter'
        ),

        /*
         * 拒绝确定按钮
         */           
        'friend/config_button' => array(
            'DefaultFilter'
        ),

        /*
         * 获取合体时间
         */           
        'friend/get_fit_info' => array(
            'DefaultFilter'
        ),

        /*
         * 更新合体时间
         */           
        'friend/update_fit_time' => array(
            'DefaultFilter'
        ),

        /*
         * 获取邮箱信息
         */           
        'mail/get_mail_info' => array(
            'DefaultFilter'
        ),

        /*
         * 接受一个邮件
         */           
        'mail/update_one_mail' => array(
            'DefaultFilter'
        ),

        /*
         * 接受所有邮件
         */           
        'mail/update_all_mail' => array(
            'DefaultFilter'
        ),

        /*
         * 购买永久道具
         */           
        'buy_prop/buy_mall' => array(
            'DefaultFilter'
        ),

        /*
         * 获取商城信息
         */           
        'buy_prop/get_mall_info' => array(
            'DefaultFilter'
        ),

        /*
         * 获取商城信息
         */           
        'buy_prop/get_prop_info' => array(
            'DefaultFilter'
        ),
    );

}