<h1>y_game Server API接口测试工具</h1>
<form action="" method="POST" >
<p>API 接口: <input type="text" name="api_name" style="width: 300px" />  eg:auth/user_register</p>
<p>用户 ID: <input type="text" name="user_id" style="width: 300px" /></p>
<p>Session Key: <input type="text" name="session_key" style="width: 300px" /></p>
<p>json 数据: <input type="text" name="json_request" style="width: 300px" /></p>
<p><input type="submit" value="API接口测试" /></p>
</form>

<?php
require_once '../libs/common/curl/CurlRequest.class.php';
require_once '../libs/common/curl/CurlResponse.class.php';

$api_name = @$_POST['api_name'];
$user_id = @$_POST['user_id'];
$session_key = @$_POST['session_key'];
$json_request = @$_POST['json_request'];
//echo $api_name;
if (!empty($api_name))
{
	$request  = new CurlRequest( 'http://192.168.18.31/y_game/api.php/' . trim($api_name) );

	$postDate = 'user_id=' . $user_id . 
	'&session_key=' . $session_key .
	'&json_request=' . $json_request . '';

	$response = $request->httpPost( $postDate );
	       
	$error = "";

	echo "<hr />";
	echo "请求API接口:" . $api_name;
	echo "<br />";
	echo "请求参数:" . $postDate;
	echo "<br />";
	echo "<br />";
	echo "<br />";
	echo "API 处理结果:";
	
	if( $response->getStatus() != 200 )
	{
	    $error = "状态code： " . $response->getStatus();
	    echo  "error:" . $error;
	}

	echo "<br />";
	echo $response->toString();
}

?>