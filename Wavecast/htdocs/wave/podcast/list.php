<?php
header("Content-Type: text/xml; charset=UTF-8");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

$host = $_SERVER['SERVER_ADDR'];

if (!($cn = mysql_connect("localhost", "root", "happy777"))) die;
$rtn = mysql_query("SET NAMES utf8" , $cn);
if (!(mysql_select_db("wavecast55"))) die;
$sql = "select * from v_vidrec ".
	"where del_flg=-1 " .
	"order by vid_sta desc, ch_type asc, ch_code asc";
if (!($rs = mysql_query($sql))) die;
?>
<?='<?xml version="1.0" encoding="UTF-8"?>'?>
<rss xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd" version="2.0">
	<channel>
		<title>録画番組</title>
		<link>http://wavecast.tv/01/</link>
		<language>ja-jp</language>
		<copyright>&#x2117; &amp; &#xA9; 2013 Michiaki Tatsubori</copyright>
		<itunes:author>Michiaki Tatsubori</itunes:author>
		<itunes:summary>日本のテレビ番組（地デジ）録画</itunes:summary>
		<description>日本のテレビ番組（地デジ）を録画したものです。</description>
		<itunes:owner>
			<itunes:name>Michiaki Tatsubori</itunes:name>
			<itunes:email>mich@acm.org</itunes:email>
		</itunes:owner>
		<itunes:category text="TV &amp; Film" />
<?php
$repImg = null;
while ($item = mysql_fetch_array($rs)) {
	if ($item['ch_type']=="UHF") $wStr3 = "地デジ";
	if ($item['ch_type']=="BS") $wStr3 = "BS衛星";
	if ($item['ch_type']=="CS") $wStr3 = "CS衛星";

    $wDate = strtotime($item['vid_sta']);
    $wDate2 = date("Ymd", $wDate);
    $wDate3 = date("Ymd-Hi", $wDate);
	$wRSSDate = date(DATE_RSS, $wDate);

	$filename = "/media/".$wDate2."/".$item['ch_type']."-".$item['ch_code']."-".$wDate3;
	$wVideo = "http://${host}${filename}-s.mp4";
	$wGUID = "http://tatsubori.net/wavecast${filename}-s.mp4";
	$filesize = filesize($_SERVER['DOCUMENT_ROOT'] . "${filename}-s.mp4");
	
	if ($item['err_flg'] == 1) {
        $wImg = "http://${host}/wave/jp/error.png";
	} else if ($item['rec_sts'] == -1 || $item['rec_sts'] == 0) {
		$wImg = "http://${host}/wave/jp/recording.png";
	} else {
		$wImg = "http://${host}${filename}.jpg";
        $repImg = ($repImg == null) ? $wImg : $repImg;
    }
?>
		<item>
			<title><?=$item['vid_name']?></title>
			<itunes:subtitle><?=date('n/j', $wDate) . ' | ' . $item['vid_name']?></itunes:subtitle>
			<description><?=date('D, d M Y H:i', $wDate) . ' | ' . $item['vid_name']?></description>
			<category>News</category>
			<itunes:author><?=$item['ch_name']?></itunes:author>
			<itunes:summary>
				<?=$item['ch_name']?>
				<?=date('D, d M Y H:i', $wDate)?>
				<?=$item['vid_name']?>
				<?=$item['vid_time']?>分
			</itunes:summary>
			<itunes:image href="<?=$wImg?>" />
			<enclosure url="<?=$wVideo?>" length="<?=$filesize?>" type="video/mp4" />
			<guid><?=$wGUID?></guid>
			<lastBuildDate><?=$wRSSDate?></lastBuildDate>
			<pubDate><?=$wRSSDate?></pubDate>
			<itunes:duration><?=$item['vid_time']?>:00</itunes:duration>
		</item>
<?php
}
?>
		<itunes:image href="<?=$repImg?>" />
	</channel>
</rss>