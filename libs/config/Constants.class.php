<?php
/**
 * 宏定义变量类
 *
 * @access public
 * @author chenwenbin
 * @copyright Copyright(C) Tenone Inc.
 * @version 0.01
 * @since 2014/11/12
 */

class Constants 
{
    
    
    //-----------------------------初始设置---------------------------------

   /*
    * POST请求json字段
    * 
    */
    const PARAM_JSON_KEY = 'json_request';

   /*
    * POST请求SessionKey字段
    * 
    */
    const PARAM_SESSION_KEY = 'session_key';

   /*
    * POST请求user_id字段
    * 
    */
    const PARAM_USER_ID = 'user_id';

   /*
    * API处理结果：成功
    * 
    */
    const RESP_RESULT_SUCCESS = '0';

   /*
    * API处理结果：失敗
    * 
    */
    const RESP_RESULT_ERROR   = '1';

    /*
    * 服务器保存的上一次SessionKey字段
    * 
    */
    const PREVIOUS_SESSION_KEY = 'previous_session_key';

    /*
    * POST请求SessionKey字段
    * 
    */
    const CURRENT_SESSION_KEY = 'current_session_key';

    /*
    * 世界排行榜
    * 
    */
    const WORLD_RANK = 'check_point_world_rank';

    /*
    * 世界排行榜上线
    * 
    */
    const WORLD_RANKY_MAX_NUM = 100;

    /*
    * 用户最大体力
    * 
    */
    const USER_MAX_THEW = 12;

    /*
    * 用户回复时间(分钟，整数)
    * 
    */
    const REFRESH_THEW_TIME = 5;
    
    //-----------------------------CVS设置----------------------------------
    
    //-----------------------------服务器设置-------------------------------

    //-----------------------------第三方平台设置---------------------------
    
    //-----------------------------API设置----------------------------------
    
    //-----------------------------购买设置---------------------------------
    
    //-----------------------------数据变更设置-----------------------------
    
     /*
      * 变更类型:1 加
      *
      */
     const UP_MONEY = 1;

     /*
      * 变更类型:2 减
      *
      */
     const DOWN_MONEY = 2;
     
     
     
    //-----------------------------系统相关设置-----------------------------
    /*
     * 发布时间
     *
     */
    const RELEASE_START_DAYS = "2014-11-12";

     
    //----------------------------用户初始化设置----------------------------
    /*
     * 注册初始化
     *
     */
    // 头像
    const USER_HEAD_INI = 1;

    // 金币
    const USER_COIN_INI = 1000;

    // 钻石
    const USER_DIAMOND_INI = 10;

    // 体力值
    const USER_THEW_INI = 30;

    // 魂石
    const USER_SOUL_INI = 0;

    // 经验值
    const USER_EXPERIENCE = 0;

    // 等级
    const USER_LEVEL = 0;

    // 战斗力
    const USER_BATTLE = 0;

    // 最大关卡
    const USER_MAX_CHECKPOINT = 0;   

    // 初始角色id
    const INI_CHARACTER_ID = 1;   

    // 初始武将id
    const INI_GENERAL_ID = 1;  


/*--------------------------各个常量设置------------------------*/
    //主角最大属性等级
    const MAX_ATTRIBUTE=5;

    //主角最高品阶
    const CHARACTER_MAX_LEVEL='S';

    //跳过主角进阶等待时间花费类型
    const MATA_TYPE=2;

    //主角立即进阶消耗没小时基数
    const UPDATE_BASE=10;

    //好友最大数量
    const MAX_FRIEND_NUM=99;

    //好友合体冷却时间未到花费
    const FIT_COST=2;

    const FIT_TYPE=2;

    //好友合体结算奖励
    const FIT_REWARD_TYPE=1;

    const FIT_REWARD_NUM=100;

    //复活花费
    const RESURE_TYPE=2;

    const RESURE_COST=10;

    const RESURE_HP=0.5;

    const RESURE_TIME=10;

    //boss关卡评分
    const BOSS_TIME=90;

    const LESS_TIME=0.6;

    const MORE_TIME=0.2;

    //武将最高属性等级
    const MAX_LEVEL=10;

    //每日任务数
    const TASK_NUM=5;

    //已开章节
    const CHAPTER_NUM=4;

    //机器人好友
    const ROBERT=514;


}
