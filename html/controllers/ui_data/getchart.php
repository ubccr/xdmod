<?php 
@session_cache_limiter("private_no_expire"); 
if (!session_id()) { session_start(); }

$filename = null;
$stype = null;
if (isset($_GET))
{
	$image = $_SESSION[$_GET["img"]];
	
	if (isset($_GET["filename"]))
		$filename = $_GET["filename"];
	if (isset($_GET["stype"]))
		$stype = $_GET["stype"];
}
else
{
	$image = $HTTP_SESSION_VARS[$HTTP_GET_VARS["img"]];
	if (isset($HTTP_GET_VARS["filename"]))
		$filename = $HTTP_GET_VARS["filename"];
	if (isset($HTTP_GET_VARS["stype"]))
		$stype = $HTTP_GET_VARS["stype"];
}

$contentType = "text/html; charset=utf-8";
if (strlen($image) >= 3)
{
	$c0 = ord($image[0]);
	$c1 = ord($image[1]);
	$c2 = ord($image[2]);
	if (($c0 == 0x47) && ($c1 == 0x49))
		$contentType = "image/gif";
	else if (($c1 == 0x50) && ($c2 == 0x4e))
		$contentType = "image/png";
	else if (($c0 == 0x42) && ($c1 == 0x4d))
		$contentType = "image/bmp";
	else if (($c0 == 0xff) && ($c1 == 0xd8))
		$contentType = "image/jpeg";
	else if (($c0 == 0) && ($c1 == 0))
		$contentType = "image/vnd.wap.wbmp";
	else if ($stype == ".svg")
		$contentType = "image/svg+xml";
	if (($c0 == 0x1f) && ($c1 == 0x8b))
		header("Content-Encoding: gzip");
}


header("Content-type: $contentType");
if ($filename != null && (isset($_GET['export']) && $_GET['export'] == 'y'))
{
	header("Content-Disposition: attachment; filename=$filename");

}
print $image;
?>