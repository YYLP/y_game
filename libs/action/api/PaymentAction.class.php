<?php
/**
 * Auth类
 *
 *
 * @access public
 * @author chenwenbin
 * @copyright Copyright(C) Tenone Inc.
 * @version 0.01
 * @since 2014/11/13
 */

class PaymentAction extends BaseAction {

    const PRODUCT_LIST = "files/payment.csv";

    //const PARAM_1 = 'user_id';
    
    //const PARAM_2 = 'type';

    /**
     * API：获取用户信息
     *
     * @access public
     * @param 无
     * @return JsonView 响应json
     */
    public function exeGetProduct()
    {
        $requestParam = $this->getAllParameters();
        Logger::debug('requestParam:'. print_r($requestParam, true));

        $requestJsonParam = $this->getDecodedJsonRequest();
        Logger::debug('requestJsonParam:'. print_r($requestJsonParam, true));

        // 读取csv类
        $csv = new Parsecsv();
        $csv->auto( self::PRODUCT_LIST );

        $messageArr = $csv->data;

        $view = new JsonView();
        return $this->getViewByJson( $view, $messageArr, 1,"payment/get_product" );
    }

}