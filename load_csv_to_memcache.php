<?php
/**
 *The method use:
 *csv data to memcache
 *
 *by cwb
 */
require_once 'libs/common/utils/Parsecsv.class.php';

//to modify the production environment
$libsDir = "/opt/app/y_game/libs/";
$csvDir = $libsDir . "files/csv";
$memIP = "59.63.169.65";
$memPort = 11211;


csvDataToMemcache(tree($csvDir), $csvDir, $memIP, $memPort);


function tree($directory) 
{ 
	$fileArr = array();
	$mydir = dir($directory); 
	while($file = $mydir->read()) { 
		if (($file!=".") AND ($file!="..") AND ($file!=".svn")) {
			$fileArr[] = $file;
		}
		
	}
	$mydir->close();

	return $fileArr;
}

function csvDataToMemcache($csvList, $csvDir, $memIP, $memPort)
{
	$memcached = new Memcache();
	$memcached->connect($memIP, $memPort);

	foreach ($csvList as $key => $value) {
		$csvObj = new parseCSV();
    	$csvObj->auto($csvDir . '/' . $value);
    	list($fileName, $fileType) = explode('.', $value);
    	$memcached->set( $fileName, $csvObj->data, MEMCACHE_COMPRESSED, 0 );
    	//debug info
    	//echo "<br/>--------------------------------------------$fileName.csv to memcache-------------------------------------------<br/>";
    	print_r($memcached->get( $fileName ));
	}

	$memcached->close();echo "success";
}