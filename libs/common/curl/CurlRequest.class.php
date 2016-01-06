<?php
/**
 * curlにてリクエストを行います。<br />
 *
 *
 * @access public
 * @author Kawaguchi Kazuhiro
 * @copyright Copyright(C) Premium Agency Inc.
 * @version 0.01
 * @since 2011/03/28
 */

class CurlRequest 
{

    private $http_url       = null;
    private $request_method = null;
    private $headers   = array();

    private static $INIT_OPT = array(
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FAILONERROR    => false,
        CURLOPT_ENCODING       => "gzip"
    );

    public function __construct( $http_url ) 
    {
        if( !$http_url )
        {
            throw new Exception( "Request url for " . __CLASS__ . " is required." );
        }

        $this->http_url = $http_url;
    }

    /**
     * 既に設定されているURLを当該処理内で取得します。
     *
     * @access public
     * @param なし
     * @return string URL
     */
    protected function getUrl()
    {
        return $this->http_url;
    }

    /**
     * ヘッダーを配列によって設定します。
     * ※フォーマット array('[設定項目a]:[設定値a]','[設定項目b]:[設定値b]')
     * （curl_setoptの「CURLOPT_HTTPHEADER」に設定する配列です）
     *
     * @access public
     * @param array $headers ヘッダー
     * @return void
     */
    public function setHeaders( array $headers )
    {
        $this->headers = $headers;
    }

    /**
     * 既に設定しているヘッダーを配列で取得します。
     *
     * @access public
     * @param なし
     * @return array ヘッダー
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * 配列へヘッダーを追加します。
     * ※配列はself#setHeadersと同等のフォーマットです
     *
     * @access public
     * @param string $value ヘッダーの設定項目と設定値（[設定項目a]:[設定値a]）
     * @return void
     */
    public function addHeader( $value )
    {
        $this->headers[] = $value;
    }

    /**
     * リクエストを行います。
     *
     * @access public
     * @param array $query_param クエリパラメータ
     * @param array $options curl_setoptへ設定する内容（array([定数]=>[設定値])）
     * @return CurlResponse レスポンス
     */
    public function execHttp( $query_param = array(), $options = array() )
    {
        $http_url = $this->http_url;
        if( $query_param )
        {
            $http_url = $this->http_url . "?" . http_build_query( $query_param );
        }
        $curl = curl_init( $http_url );
        foreach( $options as $define=>$value )
        {
            curl_setopt( $curl, $define, $value );
        }
        $http_headers = $this->getHeaders();
        if( isset($http_headers) )
        {
            curl_setopt( $curl, CURLOPT_HTTPHEADER, $http_headers );
        }
        return new CurlResponse( $curl );
    }

    /**
     * 任意のリクエストメソッドによってリクエストを行います。
     *
     * @access public
     * @param string $method リクエストメソッド
     * @param mixed $post_fields POSTフィールド
     * @param array $query_param クエリパラメータ
     * @param string $encoding ヘッダ「Accept-Encoding: 」の設定値
     * @param boolean $ssl_verifypeer SSL証明書フラグ
     * @return CurlResponse レスポンス
     */
    public function httpCustomRequest( $method, $post_fields = null, $query_param = array(), $encoding = null, $ssl_verifypeer = false )
    {
        $options = self::$INIT_OPT;
        $options[CURLOPT_CUSTOMREQUEST] = strtoupper( $method );
        $options[CURLOPT_SSL_VERIFYPEER] = $ssl_verifypeer;
        if( isset($encoding) )
        {
            $options[CURLOPT_ENCODING] = $encoding;
        }
        if( isset($post_fields) )
        {
            $options[CURLOPT_POSTFIELDS] = $post_fields;
        }
        return $this->execHttp( $query_param, $options );
    }

    /**
     * POSTメソッドによってリクエストを行います。
     *
     * @access public
     * @param mixed $post_fields POSTフィールド
     * @param array $query_param クエリパラメータ
     * @param string $encoding ヘッダ「Accept-Encoding: 」の設定値
     * @param boolean $ssl_verifypeer SSL証明書フラグ
     * @return CurlResponse レスポンス
     */
    public function httpPost( $post_fields = null, $query_param = array(), $encoding = null, $ssl_verifypeer = false )
    {
        $options = self::$INIT_OPT;
        $options[CURLOPT_SSL_VERIFYPEER] = $ssl_verifypeer;
        if( $encoding )
        {
            $options[CURLOPT_ENCODING] = $encoding;
        }
        // POST
        $options[CURLOPT_POST] = true;
        if( isset($post_fields) )
        {
            $options[CURLOPT_POSTFIELDS] = $post_fields;
        }
        return $this->execHttp( $query_param, $options );
    }

    /**
     * GETメソッドによってリクエストを行います。
     *
     * @access public
     * @param array $query_param クエリパラメータ
     * @param string $encoding ヘッダ「Accept-Encoding: 」の設定値
     * @param boolean $ssl_verifypeer SSL証明書フラグ
     * @return CurlResponse レスポンス
     */
    public function httpGet( $query_param = array(), $encoding = null, $ssl_verifypeer = false )
    {
        $options = self::$INIT_OPT;
        $options[CURLOPT_SSL_VERIFYPEER] = $ssl_verifypeer;
        if( $encoding )
        {
            $options[CURLOPT_ENCODING] = $encoding;
        }
        return $this->execHttp( $query_param, $options );
    }

    /**
     * PUTメソッドによってリクエストを行います。
     *
     * @access public
     * @param mixed $post_fields POSTフィールド
     * @param array $query_param クエリパラメータ
     * @param string $encoding ヘッダ「Accept-Encoding: 」の設定値
     * @param boolean $ssl_verifypeer SSL証明書フラグ
     * @return CurlResponse レスポンス
     */
    public function httpPut( $post_fields = null, $query_param = array(), $encoding = null, $ssl_verifypeer = false )
    {
        return $this->httpCustomRequest( 'PUT', $post_fields, $query_param, $encoding, $ssl_verifypeer );
    }

    /**
     * DELETEメソッドによってリクエストを行います。
     *
     * @access public
     * @param mixed $post_fields POSTフィールド
     * @param array $query_param クエリパラメータ
     * @param string $encoding ヘッダ「Accept-Encoding: 」の設定値
     * @param boolean $ssl_verifypeer SSL証明書フラグ
     * @return CurlResponse レスポンス
     */
    public function httpDelete( $post_fields = null, $query_param = array(), $encoding = null, $ssl_verifypeer = false )
    {
        return $this->httpCustomRequest( 'DELETE', $post_fields, $query_param, $encoding, $ssl_verifypeer );
    }
}