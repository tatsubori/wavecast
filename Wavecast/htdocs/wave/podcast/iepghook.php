<?php
// scriptURL = 'http://' . $_SERVER['SERVER_ADDR'] . $_SERVER['PHP_SELF'];
$rootURL = (empty ( $_SERVER ['HTTPS'] ) ? 'http://' : 'https://') . $_SERVER ['HTTP_HOST'];
$scriptURL = $rootURL . $_SERVER ['PHP_SELF']; // ports ignored

if (! isset ( $_REQUEST ['target'] )) {
	$targetURL = 'http://tv.so-net.ne.jp/chart/';
} else {
	$targetURL = $_REQUEST ['target'];
	if (strncmp ( $targetURL, 'http://tv.so-net.ne.jp/', strlen ( 'http://tv.so-net.ne.jp/' ) )) {
		die ();
	}
	foreach ( $_REQUEST as $key => $value ) {
		if ($key == 'target')
			continue;
		$targetURL .= strpos ( $targetURL, '?' ) ? '&' : '?';
		$targetURL .= $key . '=' . $value;
	}
}

list ( $targetURL, $html ) = get_html ( $targetURL );

$parsed = parse_url ( $targetURL );
$targetRootURL = $parsed ['scheme'] . '://' . $parsed ['host'];
$targetURLBase = $targetRootURL . $parsed ['path'];

// accesses to iepg files
$html = preg_replace_callback ( '|href\s*=\s*"/iepg\\.tvpi\\?id=(\d+)"|i', function ($matches) {
	return "href=\"http://${_SERVER['SERVER_NAME']}/wave/jp/vidrecsrc.php?proc=iepg&iepg=" . urlencode ( 'http://tv.so-net.ne.jp/iepg.tvpi?id=' . $matches [1] ) . '"';
}, $html );
$html = preg_replace_callback ( '|href\s*=\s*"/iepgCompleted\\.action\\?id=(\d+)"|i', function ($matches) {
	return "href=\"http://${_SERVER['SERVER_NAME']}/wave/jp/vidrecsrc.php?proc=iepg&iepg=" . urlencode ( 'http://tv.so-net.ne.jp/iepg.tvpi?id=' . $matches [1] ) . '"';
}, $html );
// links to other pages
#$html = preg_replace ( '|a(\s+(?:[^>]+\s+)*)href\s*=\s*"(http\://tv\.so-net\.ne\.jp/[^"]*)\s*"|i', 'a${1}href="' . "${scriptURL}?target=" . '${2}"', $html );
$html = preg_replace ( '|a(\s+)href\s*=\s*"(http\://tv\.so-net\.ne\.jp/[^"]*)\s*"|i', 'a${1}href="' . "${scriptURL}?target=" . '${2}"', $html );
$html = preg_replace ( '|a(\s+)href\s*=\s*"\s*(\?[^"]*)\s*"|i', 'a${1}href="' . "${scriptURL}?target=${targetURLBase}" . '${2}"', $html );
$html = preg_replace ( '|a(\s+)href\s*=\s*"\s*(/[^"]*)\s*"|i', 'a${1}href="' . "${scriptURL}?target=${targetRootURL}" . '${2}"', $html );
$html = preg_replace ( '|action="\s*(/[^"]*)\s*"([^>]*>)|i', "action=\"${scriptURL}" . '"${2}' . "\n" . '<input type="hidden" name="target" value="' . $targetRootURL . '${1}" />', $html );
$html = str_replace ( "<head>", "<head>\n<base href=\"${targetURL}\" />", $html );
echo $html;
function get_html($targetURL) {
	global $scriptURL;
	
	$context = array (
			'http' => array (
					'follow_location' => false 
			) 
	);
	if (isset($_COOKIE['_tc'])) {
		$context['http']['header'] = 'Cookie: ' . urldecode($_COOKIE['_tc']) . "\r\n";
	}
	
	$html = file_get_contents ( $targetURL, false, stream_context_create ( $context ) );
	$location = false;
	foreach ( $http_response_header as $header ) {
		header('X-Debug: ' . $header, false);
		if (! strncmp ( $header, 'Set-Cookie: ', strlen ( 'Set-Cookie: ' ) )) {
			$cookie = substr ( $header, strlen ( 'Set-Cookie: ' ) );
			header('Set-Cookie: _tc=' . urlencode($cookie), true);
		} else if (! strncmp ( $header, 'Location: ', strlen ( 'Location: ' ) )) {
			$location = true;
			$targetURL = substr ( $header, strlen ( 'Location: ' ) );
			header('Location: ' . "${scriptURL}?target=${targetURL}");
		}
	}
	if ($location) {
		exit();
	}
	
	return array (
			$targetURL,
			$html 
	);
}
function get_html_auto($targetURL) {
	$context = array (
			'http' => array (
					'follow_location' => false 
			) 
	);
	do {
		$html = file_get_contents ( $targetURL, false, stream_context_create ( $context ) );
		$location = false;
		foreach ( $http_response_header as $header ) {
			if (! strncmp ( $header, 'Set-Cookie: ', strlen ( 'Set-Cookie: ' ) )) {
				$cookie = substr ( $header, strlen ( 'Set-Cookie: ' ) );
				$context ['http'] ['header'] = "Cookie: ${cookie}\r\n";
			} else if (! strncmp ( $header, 'Location: ', strlen ( 'Location: ' ) )) {
				$location = true;
				$targetURL = substr ( $header, strlen ( 'Location: ' ) );
			}
		}
	} while ( $location );
	
	return array (
			$targetURL,
			$html 
	);
}
?>