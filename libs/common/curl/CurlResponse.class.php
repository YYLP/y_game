<?php
/**
 * curlによるレスポンスを扱います。<br />
 *
 *
 * @access public
 * @author Kawaguchi Kazuhiro
 * @copyright Copyright(C) Premium Agency Inc.
 * @version 0.01
 * @since 2011/03/28
 */

class CurlResponse 
{

    const CURL_INFO_HTTP_CODE_KEY = 'http_code';

    private $response = null;
    private $string_response = null;
    private $curl_info = null;

    public function __construct( $curl ) 
    {
        $this->string_response = curl_exec( $curl );
        $this->curl_info       = curl_getinfo( $curl );
        curl_close( $curl );
        $this->response = $this->parse( $this->string_response );
    }

    /**
     * ステータスコードを取得します。
     *
     * @access public
     * @param なし
     * @return integer ステータスコード
     */
    public function getStatus()
    {
        return $this->getInfoByName( self::CURL_INFO_HTTP_CODE_KEY );
    }

    /**
     * 伝送に関する情報を取得します。
     *
     * @access public
     * @param なし
     * @return array 伝送に関する情報（curl_getinfoの戻り値です）
     */
    public function getAllInfo()
    {
        return $this->curl_info;
    }

    /**
     * 伝送に関する情報を名前を指定して取得します。
     *
     * @access public
     * @param string $name 項目名
     * @return array 伝送に関する情報
     */
    public function getInfoByName( $name )
    {
        if( isset($this->curl_info[$name]) )
        {
            return $this->curl_info[$name];
        }

        return null;
    }

    /**
     * レスポンスを文字列で取得します。
     *
     * @access public
     * @param なし
     * @return string レスポンス
     */
    public function toString()
    {
        if( is_null($this->string_response) )
        {
            return "";
        }

        return $this->string_response;
    }

    /**
     * レスポンスを配列で取得します。
     *
     * @access public
     * @param なし
     * @return array レスポンス
     */
    public function toArray()
    {
        if( is_null($this->response) )
        {
            return array();
        }

        return $this->response;
    }

    /**
     * レスポンスを名前を指定して取得します。
     *
     * @access public
     * @param string $key パラメータ名
     * @return array レスポンス
     */
    public function getByName( $key )
    {
        if( isset($this->response[$key]) )
        {
            return $this->response[$key];
        }

        return null;
    }

    /**
     * レスポンスがJSONの場合、配列に変換して返します。
     * ※変換に失敗した場合、空の配列を返します。
     *
     * @access public
     * @param string $root_name rootエントリ名
     * @param string $key エントリ内でさらに条件を指定する場合は、キー名を1つまで指定できます
     * @return array レスポンス
     */
    public function decodeJson( $root_name = null )
    {
        $string     = $this->toString();
        $array_json = json_decode( $string, true );
        if( !$array_json )
        {
            return array();
        }

        if( isset($array_json[$root_name]) )
        {
            return $array_json[$root_name];
        }

        // 名前が存在しなければ、そのまま返す
        return $array_json;
    }

    /**
     * レスポンスのbody部分を配列にパースします。
     *
     * @access private
     * @param string $field レスポンスのbody部分
     * @return array レスポンス
     */
    private function parse( $field )
    {
        //"/([\w,.:;&=+*%$#!?@()~\'\/-]*)/"
        if( !preg_match("/(.*)=(.*)/", $field) )
        {
            return array( $field );
        }

        $response = array();
        parse_str( $field, $response );
        return $response;
    }
}