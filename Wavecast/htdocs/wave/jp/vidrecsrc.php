<?php
//セッションの有効期限をプライベートに調整する
ini_set('session.cache_limiter', 'private');
session_start();

//インクルード
//require_once 'himitsu.php';

//エラー処理
$_SESSION['msg']="";
///*
function errorHandler ($errno, $errstr, $errfile, $errline){
    // $errno は error_reporting の値を参照。
    if($errno == 1 || $errno == 2 | $errno == 4){
        // エラー通知処理
        $_SESSION['msg']=$errno."|".$errstr."|".$errfile."|".$errline;
    }
}
set_error_handler("errorHandler");
//*/

//ユーザ関数
function getURL2($wurl2){
    $_data = null;
    $_http = fopen($wurl2,"r");
    if($_http){
        while(!feof($_http))$_data.=fgets($_http,1024);
        fclose($_http);
    }
    return($_data);
}

$_SESSION['dev']="";
$Agent = getenv( "HTTP_USER_AGENT" );
if (ereg("Safari", $Agent)) $_SESSION['dev'] = "Safari";
if (ereg("Firefox", $Agent)) $_SESSION['dev'] = "Firefox";
if (ereg("MSIE", $Agent)) $_SESSION['dev'] = "MSIE";
if( ereg( "PSP", $Agent)) $_SESSION['dev']="PSP";
if( ereg( "Chrome", $Agent)) $_SESSION['dev']="Chrome";
if( ereg( "iPod", $Agent)) $_SESSION['dev']="iPod";
if( ereg( "iPhone", $Agent)) $_SESSION['dev']="iPhone";
if( ereg( "iPad", $Agent)) $_SESSION['dev']="iPad";
if( ereg( "Android", $Agent)) $_SESSION['dev']="Android";
if($_SESSION['dev']!="Chrome" && $_SESSION['dev']!="PSP" && $_SESSION['dev']!="Android" && $_SESSION['dev']!="iPod" && $_SESSION['dev']!="iPhone" && $_SESSION['dev']!="iPad"){
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: browser.php");
}


$aaaa="";

//マスター配列 一回　毎日　毎週
$ch_typs = array(""=>"必ず設定してください", "UHF"=>"地上波デジタル", "BS"=>"ＢＳ衛星放送", "CS"=>"ＣＳ衛星放送");
$vid_cycles = array("一回のみ"=>"一回のみ", "毎週"=>"毎週", "毎日"=>"毎日");

$errors = array();

//ＤＢへ接続
if (!($cn = mysql_connect("localhost", "root", "happy777"))) die;
$rtn = mysql_query("SET NAMES utf8" , $cn);
if (!(mysql_select_db("wavecast55"))) die;

$now = new DateTime();
$proc = $_REQUEST['proc']."";
if($proc == ""){
    $_SESSION['iepg'] = "";
    $_SESSION['vid_sta'] = "";
    $_SESSION['ch_name'] = "";
    $_SESSION['vid_name'] = "";
    $_SESSION['vid_time'] = "";
    $_SESSION['ch_type'] = "UHF";
    $_SESSION['ch_code'] = "";
    $_SESSION['vid_cycle'] = "";

    $sql = "delete from vidrecsrc where (vid_sta <= '".$now->format("Y-m-d H:i:s")."' and vid_cycle='一回のみ')";
    if (!($rs = mysql_query($sql))) die;
}

if($proc == "back"){
  header("HTTP/1.1 301 Moved Permanently");
  header("Location: menu.php?proc=top");
  exit();
}
if($proc == "vidreclist"){
  header("HTTP/1.1 301 Moved Permanently");
  header("Location: vidreclist.php");
  exit();
}if($proc == "settune"){
  header("HTTP/1.1 301 Moved Permanently");
  header("Location: settune.php");
  exit();
}

if($proc == "iepg"){

    try{
        if($_REQUEST['iepg']==""){
            array_push($errors, "※ｉＥＰＧが入力されておりません。");
        }

        if (count($errors) == 0) {

            $_SESSION['iepg'] = $_REQUEST['iepg']."";

            //http://tv.so-net.ne.jp/iepgCompleted.action?id=133840201104172330
            //http://tv.so-net.ne.jp/iepg.tvpi?id=133840201104172330
            //http://tv.so-net.ne.jp/iepgCompleted.action?id=133840201104172330
            //http://tv.so-net.ne.jp/iepg.tvpid?id=133840201104172330

            //Degital iEPG
            $_SESSION['iepg'] = str_replace("tv.so-net.ne.jp/iepg.tvpid","tv.so-net.ne.jp/iepg.tvpi",$_SESSION['iepg']."");
            //Completed
            $_SESSION['iepg'] = str_replace("tv.so-net.ne.jp/iepgCompleted.action","tv.so-net.ne.jp/iepg.tvpi",$_SESSION['iepg']."");

            $data = file_get_contents($_SESSION['iepg']."", false, stream_context_create(array("http"=>array("header"=>"User-Agent: Mozilla/4.0 (compatible; MSIE 6.0; Windows XP)"))));

            if($data != false){

                $data1 = mb_convert_encoding($data, 'utf8', 'sjis-win');
                $data2 = explode("\r\n", $data1."");

                $wYear = str_replace("year: ","",$data2[3]."")."-".str_replace("month: ","",$data2[4]."")."-".str_replace("date: ","",$data2[5]."")." ".str_replace("start: ","",$data2[6]."").":00";
                $wChanel = str_replace("station: ","",$data2[2]."");
                $wTitle = str_replace("program-title: ","",$data2[8]."");

                $wDt1 = new DateTime(str_replace("year: ","",$data2[3]."")."-".str_replace("month: ","",$data2[4]."")."-".str_replace("date: ","",$data2[5]."")." ".str_replace("start: ","",$data2[6]."").":00");
                //$wDt2 = new DateTime(str_replace("year: ","",$data2[3]."")."-".str_replace("month: ","",$data2[4]."")."-".str_replace("date: ","",$data2[5]."")." ".str_replace("start: ","",$data2[6]."").":00");
                $wDt2 = new DateTime(str_replace("year: ","",$data2[3]."")."-".str_replace("month: ","",$data2[4]."")."-".str_replace("date: ","",$data2[5]."")." ".str_replace("end: ","",$data2[7]."").":00");

                if($wDt1 > $wDt2){
                    $wDt2=$wDt2->add(new DateInterval('P1D'));
                }
                $interval = $wDt1->diff($wDt2);

                $wDate = explode("|", $_rawData);
                //2011/01/10 00:55:00|テレビ朝日|Get　Sports|1/10/2011 12:55:00 AM|1/10/2011 2:50:00 AM|115
                $_SESSION['vid_sta'] = substr($wYear,0,16);
                $_SESSION['ch_name'] = $wChanel;
                $_SESSION['vid_name'] = $wTitle;
                $_SESSION['vid_time'] = ($interval->h*60)+$interval->i."";

                $_SESSION['ch_type'] = "";
                $_SESSION['ch_code'] = "";

                $_SESSION['vid_sta2'] = $wDt1;
                $_SESSION['vid_end2'] = $wDt2;

                $sql = "select * from tuneinfo where data3='".$wChanel."'";
                if (!($rs = mysql_query($sql))) die;
                if(mysql_num_rows($rs)!=0){
                    $item = mysql_fetch_array($rs);
                    $_SESSION['ch_code']=$item[0];
                }
                //$_SESSION['msg'] = str_replace("/","-",$wDate[0]."");
                $_SESSION['msg'] = $_SESSION['vid_sta2']->format("Y-m-d H:i:s")."|".$_SESSION['vid_end2']->format("Y-m-d H:i:s");
            }else{
                array_push($errors, "※このｉＥＰＧは無効です。恐れ入りますが、②予約録画入力欄を手入力でお入れください。");
            }
        }
    } catch (Exception $e) {
        //echo "例外キャッチ：", $e->getMessage(), "\n";
        array_push($errors, "※このｉＥＰＧは無効です。恐れ入りますが、②予約録画入力欄を手入力でお入れください。");
    }
}

if($proc == "save"){

    try {

        $_SESSION['vid_sta'] = htmlspecialchars($_REQUEST['vid_sta'], ENT_QUOTES);
        $_SESSION['ch_name'] = htmlspecialchars($_REQUEST['ch_name'], ENT_QUOTES);
        $_SESSION['vid_name'] = htmlspecialchars($_REQUEST['vid_name'], ENT_QUOTES);
        $_SESSION['vid_time'] = htmlspecialchars($_REQUEST['vid_time'], ENT_QUOTES);

        //$_SESSION['ch_type'] = htmlspecialchars($_REQUEST['ch_type'], ENT_QUOTES);
        $_SESSION['ch_type'] = "UHF";
        $_SESSION['ch_code'] = $_REQUEST['ch_code'];
        $_SESSION['vid_cycle'] = $_REQUEST['vid_cycle'];

        //エラーチェック
        //if (strlen($_SESSION['ch_type']) < 1) {
        //    array_push($errors, "※放送タイプは必須項目です。");
        //}
        if (strlen($_SESSION['ch_code']) == "") {
            array_push($errors, "※チャンネル番号を選択して下さい。");
        }
        $wDt77 = strtotime($_SESSION['vid_sta']);
        if ($wDt77."" == "" ) {
            array_push($errors, "※予約日時は年以外は２桁です。例のように合計１６桁で入力下さい。");
        }else{
            $now = new DateTime();
            //$defaultZone = Date_TimeZone::getDefault();
            //$now->convertTZ($defaultZone);
            $wDt78 = new DateTime($_SESSION['vid_sta']);
            if ($now > $wDt78) {
                array_push($errors, "※過去の日付は指定できません。");
             }
        }
        if (strlen($_SESSION['vid_name']) < 1) {
            array_push($errors, "※番組名は必須項目です。");
        }
        if (strlen($_SESSION['vid_time']) < 1 || !is_numeric($_SESSION['vid_time'])) {
            array_push($errors, "※録画時間は数値の必須項目です。");
        }


        //データベースへの登録
        if (count($errors) == 0) {
            //$_SESSION['msg'] = $_REQUEST['ch_type']."|".$_REQUEST['ch_code'];

            //録画日時
            $_SESSION['vid_sta2'] = new DateTime($_SESSION['vid_sta']);
            $_SESSION['vid_end2'] = new DateTime($_SESSION['vid_sta']);

            //録画の時間のみ
            $_SESSION['vid_sta2_2'] = new DateTime($_SESSION['vid_sta']);
            $_SESSION['vid_end2_2'] = new DateTime($_SESSION['vid_sta']);
            $_SESSION['vid_sta2_2']->setDate(1900, 1, 1);
            $_SESSION['vid_end2_2']->setDate(1900, 1, 1);

            //$_SESSION['vid_sta2']->modify("+".$_SESSION['vid_time'].""." minuites");
            $_SESSION['vid_end2']->add(new DateInterval('PT'.$_SESSION['vid_time'].'M'));
            $_SESSION['vid_end2_2']->add(new DateInterval('PT'.$_SESSION['vid_time'].'M'));

            $_SESSION['vid_wk']=$_SESSION['vid_sta2']->format("D");

            $_SESSION['msg'] = $_SESSION['vid_sta2']->format("Y-m-d H:i:s")."|".$_SESSION['vid_end2']->format("Y-m-d H:i:s");

            //$vid_cycles = array("一回のみ"=>"一回のみ", "毎日"=>"毎日", "毎週"=>"毎週");
            $sql = "select * from vidrecsrc where (";

            $wstr0="";
            $wstr1="";
            $wstr2="";
            $wstr3="";
            $wstr4="";

            if($_SESSION['vid_cycle']=="一回のみ"){ $wstr2="and vid_wk='".$_SESSION['vid_wk']."'"; $wstr4=" and vid_sta<='".$_SESSION['vid_end2']->format("Y-m-d H:i:s")."'"; }
            if($_SESSION['vid_cycle']=="毎週"){ $wstr0="_2"; $wstr1="2"; $wstr3=" and vid_end>='".$_SESSION['vid_sta2']->format("Y-m-d H:i:s")."'"; $wstr2="and vid_wk='".$_SESSION['vid_wk']."'"; }
            if($_SESSION['vid_cycle']=="毎日"){ $wstr0="_2"; $wstr1="2"; $wstr3=" and vid_end>='".$_SESSION['vid_sta2']->format("Y-m-d H:i:s")."'"; $wstr2="";}

            $sql.= "(((vid_sta".$wstr1."<='".$_SESSION['vid_sta2'.$wstr0]->format("Y-m-d H:i:s")."' and vid_end".$wstr1.">'".$_SESSION['vid_sta2'.$wstr0]->format("Y-m-d H:i:s")."') or ";
            $sql.= "(vid_sta".$wstr1."<'".$_SESSION['vid_end2'.$wstr0]->format("Y-m-d H:i:s")."' and vid_end".$wstr1.">='".$_SESSION['vid_end2'.$wstr0]->format("Y-m-d H:i:s")."')) ".$wstr3." and vid_cycle='一回のみ' ".$wstr2." ) or ";

            $sql.= "(((vid_sta2<='".$_SESSION['vid_sta2_2']->format("Y-m-d H:i:s")."' and vid_end2>'".$_SESSION['vid_sta2_2']->format("Y-m-d H:i:s")."') or ";
            $sql.= "(vid_sta2<'".$_SESSION['vid_end2_2']->format("Y-m-d H:i:s")."' and vid_end2>='".$_SESSION['vid_end2_2']->format("Y-m-d H:i:s")."')) ".$wstr4." and vid_cycle='毎週' ".$wstr2." ) or ";

            $sql.= "(((vid_sta2<='".$_SESSION['vid_sta2_2']->format("Y-m-d H:i:s")."' and vid_end2>'".$_SESSION['vid_sta2_2']->format("Y-m-d H:i:s")."') or ";
            $sql.= "(vid_sta2<'".$_SESSION['vid_end2_2']->format("Y-m-d H:i:s")."' and vid_end2>='".$_SESSION['vid_end2_2']->format("Y-m-d H:i:s")."')) ".$wstr4." and vid_cycle='毎日')";

            $sql.= ")";
            if (!($rs = mysql_query($sql))) die;

            if(mysql_num_rows($rs)==0){
                $sql = "INSERT INTO vidrecsrc (ch_type,ch_code,ch_name,vid_stayy,vid_stamm,vid_wk,vid_sta,vid_end,vid_sta2,vid_end2,vid_time,vid_cycle,vid_name,vid_file) VALUES ('";
                $sql.=$_SESSION['ch_type']."','".$_SESSION['ch_code']."','".$_SESSION['ch_name']."','";
                $sql.=$_SESSION['vid_sta2']->format("Y")."','".$_SESSION['vid_sta2']->format("m")."','".$_SESSION['vid_wk']."','";
                $sql.=$_SESSION['vid_sta2']->format("Y-m-d H:i:s")."','".$_SESSION['vid_end2']->format("Y-m-d H:i:s")."','";
                $sql.=$_SESSION['vid_sta2_2']->format("Y-m-d H:i:s")."','".$_SESSION['vid_end2_2']->format("Y-m-d H:i:s")."',";
                $sql.=$_SESSION['vid_time'].",'".$_SESSION['vid_cycle']."','".$_SESSION['vid_name']."','";
                $sql.=$_SESSION['ch_type']."-".$_SESSION['ch_code']."-".$_SESSION['vid_sta2']->format("Ymd-Hi")."')";

                if (!(mysql_query($sql))) die;

                if($_SESSION['ch_name']!=""){
                    $sql = "UPDATE tuneinfo SET data3='".$_SESSION['ch_name']."' WHERE code='".$_SESSION['ch_code']."'";
                    if (!(mysql_query($sql))) die;
                }

                $_SESSION['iepg'] = "";
                $_SESSION['vid_sta'] = "";
                $_SESSION['ch_name'] = "";
                $_SESSION['vid_name'] = "";
                $_SESSION['vid_time'] = "";
                $_SESSION['ch_type'] = "UHF";
                $_SESSION['ch_code'] = "";
                $_SESSION['vid_cycle'] = "";

            }else{

                array_push($errors, "＜録画時間が重複してます＞");
                $i=0;
                while ($item = mysql_fetch_array($rs)) {
                    $i+=1;
                    if($item['ch_type']=="UHF") $wStr3="地デジ";
                    if($item['ch_type']=="BS") $wStr3="BS衛星";
                    if($item['ch_type']=="CS") $wStr3="CS衛星";
                    array_push($errors, $i.")  ﾀｲﾌﾟ:".$wStr3.", ﾁｬﾝﾈﾙ#:".substr($item['ch_code'],2,4) .", 番組:".$item['vid_name'].", 日時:".$item['vid_sta'].", 時間:".$item['vid_time'],"分".", 周期:".$item['vid_cycle']);
                }
            }
        }

    } catch (Exception $e) {
        //echo "例外キャッチ：", $e->getMessage(), "\n";
        array_push($errors, "※入力されたデータをお確かめください。");
    }
}

$onload = "";
if (count($errors) == 0) {
    if($proc == "save"){
        $onload = "window.alert('予約登録が完了しました。\\n\\n');";
    }
} else {
    $onload = "window.alert('記入エラー！\\n\\n";
    foreach ($errors as $errMessage) {
        $onload .= "$errMessage\\n";
    }
    $onload .= "');";
}

//テスト
$bbbb="aaaaa";

//キャッシュを無効
header("Content-Type: text/html; charset=UTF-8");
header("Expires: Thu, 01 Dec 1994 16:00:00 GMT");
header("Last-Modified: ". gmdate("D, d M Y H:i:s"). " GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

?>
<?php if($_SESSION['wavecast_flg']==true){ ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="ja">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>予約登録</title>
        <style type="text/css" media="screen">@import "../jqtouch/jqtouch.min.css";</style>
        <style type="text/css" media="screen">@import "../themes/jqt/theme.min.css";</style>
        <script src="../jqtouch/jquery.1.3.2.min.js" type="text/javascript" charset="utf-8"></script>
        <script src="../jqtouch/jqtouch.min.js" type="application/x-javascript" charset="utf-8"></script>
        <script type="text/javascript" charset="utf-8">
            var jQT = new $.jQTouch({
                icon: 'jqtouch.png',
                addGlossToIcon: true,
                fullScreen: true,
                startupScreen: 'jqt_startup.png',
                statusBar: 'black',
                preloadImages: [
                    '../themes/jqt/img/back_button.png',
                    '../themes/jqt/img/back_button_clicked.png',
                    '../themes/jqt/img/button_clicked.png',
                    '../themes/jqt/img/grayButton.png',
                    '../themes/jqt/img/whiteButton.png',
                    '../themes/jqt/img/loading.gif'
                    ]
            });
            $(function (){
                $("body > *").css({display:"none", position:"absolute"});
            });

            function goto2(url){
	        //ajax2("put",dev777+"-pre",""+$(this).scrollTop(),"goto",url);
	        location.href=url;
            }
            function settyp2(code){
                //if($("#proc").val()==""){
                    $("#proc").val(code);
                    $("#form1").attr("action","vidrecsrc.php");
                    document.entryForm.submit();
                //}
            }
            function init2(){
	        //$("#proc").val("");

                //$("#iepg").contentDocument.designMode="on";
            }
            function paste(){
	        //$("#proc").val("");
                //$("#iepg").select();
                //document.execCommand("Paste");
            }


        </script>

    </head>
    <body onload="init2();<?php print $onload ?>">
        <div>
            <div class="toolbar">
                <h1>予約登録</h1>
                <a class="back" href="javascript:settyp2('back');" rel="external">メニュー</a>
                 <a class="button" href="javascript:settyp2('vidreclist');" rel="external">予約リスト</a>
            </div>
            <form name="entryForm" action="" method="POST" class="form" id="form1" >
                <input type="hidden" name="proc" id="proc" style="width:100px;" value="" />
                <input type="hidden" name="msg" id="msg" style="width:100px;" value="<?php print $_SESSION['msg'] ?>" />
                
                <h2 style="color: lightblue;">①ネット番組表(iEPG)を利用</h2>
                <ul class="rounded">
                    <li><span style="color:white; font-weight:normal;font-size: 16px;" >テレビ王国などのサイトから、iEPGのURLアドレスを下部の入力欄にコピーして"iEPGを利用する"ボタンを押してください。自動的に②の項目欄に挿入されます。<br /></span>
                        <input type="button" style="height:24px; border-color: gray;cursor: pointer;" value=" クリア " onclick="javascript: $('#iepg').val('');"><input style="background-color:white; width:85%; " type="text" name="iepg" id="iepg" value="<?php print $_SESSION['iepg'] ?>" />
                    </li>
                    <li>
                        <table border="0" width="100%"><tr>
                            <td width="40%">
                               <a class="grayButton" style="" href="javascript:settyp2('iepg');" rel="external" >iEPGを利用</a>
                            </td>
                            <td>
                                <a href="/wave/podcast/iepghook.php" style="margin-left: 10px; color:white; font-weight:normal; font-size: 14px; text-decoration:underline;" >番組表から直接予約</a>　|
                                <a href="http://tv.so-net.ne.jp/" target="_blank" rel="external" style="margin-left: 10px; color:white; font-weight:normal; font-size: 14px; text-decoration:underline;" >iEPG,テレビ王国へ</a>
                            </td>
                            </tr>
                        </table>
                    </li>
                </ul>
                <h2 style="color: lightblue;">②予約録画入力 (現時:<?php print $now->format("Y-m-d H:i:s") ?>)</h2>
                <ul class="rounded">
                    <li><span style="color:red; font-weight:normal; font-size:16px;" >＊マークは必須項目を意味します</span></li>
                    <?php if($aaa=="aaa"){ ?>
                    <li><span style="color:white; font-weight:normal;" >放送タイプ<span style="color:red;">＊</span>:<br /></span>
                        <select name="ch_type" id="ch_type" style="background-color:white; width:180px; margin-left: 4px;cursor: pointer;" >
                            <?php
                            foreach ($ch_typs as $key => $value) {
                                $wFlg1="";
                                if($key==$_SESSION['ch_type']) $wFlg1="selected";
                            ?>
                              <option value="<?php print $key ?>" <?php print $wFlg1 ?>><?php print $value ?></option>
                            <?php } ?>
                        </select>
                    </li>
                    <?php } ?>
                    <li><span style="color:white; font-weight:normal;" >チャンネル番号<span style="color:red;">＊</span>:<span style="color:red;">次項目のチャンネル名と合わせてください。<br />各チャンネル名に対して１度だけ設定が必要です。次回からは自動認識します。</span><br /></span>
                        <select name="ch_code" id="ch_code" style="background-color:white; width:300px; margin-left: 4px;cursor: pointer;" >
                            <option value="" >こちらをクリック</option>
                            <?php
                            $sql = "select * from tuneinfo where code like '01__' and data1<>''  order by code";
                            if (!($rs = mysql_query($sql))) die;
                            $m=0;
                            while ($item = mysql_fetch_array($rs)) {
                                $wFlg1="";if($item['code']==$_SESSION['ch_code']) $wFlg1="selected";
                                $m++;
                            ?>
                              <option value="<?php print $item['code'] ?>" <?php print $wFlg1 ?>><?php print "#".$m.": ".$item['data1']." [".$item['data2']."]" ?></option>
                            <?php } ?>
                        </select>
                    </li>
                    <li><span style="color:white; font-weight:normal;" >チャンネル名:<br /></span>
                        <input style="background-color:lightblue; width:300px; margin-left: 4px;" type="text" name="ch_name" id="ch_name" value="<?php print $_SESSION['ch_name'] ?>" readonly="readonly" />
                    </li>
                    <li><span style="color:white; font-weight:normal;" >録画日時<span style="color:red;">＊</span>: (例 2011-01-07 08:09) 24時間で入力<br /></span>
                        <input style="background-color:white; width:180px; margin-left: 4px;" type="text" name="vid_sta" id="vid_sta" value="<?php print $_SESSION['vid_sta'] ?>"  />
                    </li>
                    <li><span style="color:white; font-weight:normal;" >録画時間<span style="color:red;">＊</span>: (例 15) 分を入力<br /></span>
                        <input style="background-color:white; width:120px; margin-left: 4px;" type="text" name="vid_time" id="vid_time" value="<?php print $_SESSION['vid_time'] ?>" />
                    </li>
                    <li><span style="color:white; font-weight:normal;" >番組名<span style="color:red;">＊</span>:<br /></span>
                        <input style="background-color:white;" type="text" name="vid_name" id="vid_name" value="<?php print $_SESSION['vid_name'] ?>" />
                    </li>
                    <li><span style="color:white; font-weight:normal;" >録画周期<span style="color:red;">＊</span>:<br /></span>
                        <select name="vid_cycle" id="vid_cycle" style="background-color:white; width:180px; margin-left: 4px;cursor: pointer;" >
                            <?php
                            foreach ($vid_cycles as $key => $value) {
                                $wFlg1="";
                                if($key==$_SESSION['vid_cycle']) $wFlg1="selected";
                            ?>
                              <option value="<?php print $key ?>" <?php print $wFlg1 ?>><?php print $value ?></option>
                            <?php } ?>
                        </select>
                    </li>
                    <li>
                        <a class="grayButton" href="javascript:settyp2('save');" rel="external" >予約を登録する</a>
                    </li>
                </ul>
                
            </form>
            <div class="info">
                <p><strong>Powered by WAVEUSA.COM</strong></p>
            </div>
        </div>
    </body>
</html>
<?php } ?>
