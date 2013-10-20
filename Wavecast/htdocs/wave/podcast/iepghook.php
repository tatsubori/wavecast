<?php
#$scriptURL = 'http://' . $_SERVER['SERVER_ADDR'] . $_SERVER['PHP_SELF'];
$scriptURL = "http://${_SERVER['SERVER_NAME']}${_SERVER['PHP_SELF']}";
if (! isset($_REQUEST['target'])) {
	$targetURL = 'http://tv.so-net.ne.jp/chart/23.action';
} else {
	$targetURL = $_REQUEST['target'];
	if (!strncmp($targetURL, 'http://tv.so-net.ne.jp/', strlen('http://tv.so-net.ne.jp/'))) {
		#die;
	}
	foreach ($_REQUEST as $key => $value) {
		if ($key == 'target')  continue;
		$targetURL .= strpos($targetURL, '?') ? '&' : '?';
		$targetURL .= $key . '=' . $value;
	}
}
	
$parsed = parse_url($targetURL);
#$targetURLBase = preg_replace('|\\?.*|i', '', $targetURL);
$targetURLBase = $parsed['scheme'] . '://' . $parsed['host'] . $parsed['path'];
$targetHost = $parsed['host'];

$html = file_get_contents($targetURL);
# accesses to iepg files
$html = preg_replace_callback('|href="/iepg\\.tvpi\\?id=(\d+)"|i',
		function($matches) {
			return "href=\"http://${_SERVER['SERVER_NAME']}/wave/jp/vidrecsrc.php?proc=iepg&iepg=" .
				urlencode('http://tv.so-net.ne.jp/iepg.tvpi?id=' + $matches[1]) .
				'"'; 
		}, $html);
# links to other pages
$html = preg_replace('|a\\s+href="\\s*(\\?[^"]*)\\s*"|i',
		"a href=\"${scriptURL}?target=${targetURLBase}" . '${1}"', $html);
$html = preg_replace('|a\\s+href="\\s*(/[^"]*)\\s*"|i',
		"a href=\"${scriptURL}?target=http://${targetHost}" . '${1}"', $html);
$html = preg_replace('|action="\\s*(/[^"]*)\\s*"([^>]*>)|i',
		"action=\"${scriptURL}" . '"${2}' . "\n"
			. '<input type="hidden" name="target" value="http://' . $targetHost . '${1}" />', $html);
$html = str_replace("<head>", "<head>\n<base href=\"${targetURL}\" />", $html);
echo $html;
?>