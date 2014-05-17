<?php
header('Content-Type: application/json');

$errors = array ();

try {
	// escape input characters
	$vid_sta = htmlspecialchars ( $_POST ['vid_sta'], ENT_QUOTES );
	$ch_name = htmlspecialchars ( $_POST ['ch_name'], ENT_QUOTES );
	$vid_name = htmlspecialchars ( $_POST ['vid_name'], ENT_QUOTES );
	$vid_time = htmlspecialchars ( $_POST ['vid_time'], ENT_QUOTES );
	$ch_type = "UHF";
	$ch_code = $_POST ['ch_code'];
	$vid_cycle = $_POST ['vid_cycle'];
	
	// input check
	if (strlen ( $ch_code ) == "") {
		array_push ( $errors, "※チャンネル番号を選択して下さい。" );
	}
	$wDt77 = strtotime ( $vid_sta );
	if ($wDt77 . "" == "") {
		array_push ( $errors, "※予約日時は年以外は２桁です。合計１６桁で入力下さい。" );
	} else {
		$now = new DateTime ();
		$wDt78 = new DateTime ( $vid_sta );
		if ($now > $wDt78) {
			array_push ( $errors, "※過去の日付は指定できません。" );
		}
	}
	if (strlen ( $vid_name ) < 1) {
		array_push ( $errors, "※番組名は必須項目です。" );
	}
	if (strlen ( $vid_time ) < 1 || ! is_numeric ( $vid_time )) {
		array_push ( $errors, "※録画時間は数値の必須項目です。" );
	}
	
	// store in DB
	if (count ( $errors ) == 0) {
		function add_vidrecsrc($errors, $vid_sta, $vid_time, $vid_namesuffix = '') {
			// 録画日時
			$vid_sta2 = new DateTime ( $vid_sta );
			$vid_end2 = new DateTime ( $vid_sta );
			
			// 録画の時間のみ
			$vid_sta2_2 = new DateTime ( $vid_sta );
			$vid_end2_2 = new DateTime ( $vid_sta );
			$vid_sta2_2->setDate ( 1900, 1, 1 );
			$vid_end2_2->setDate ( 1900, 1, 1 );
			
			$vid_end2->add ( new DateInterval ( 'PT' . $vid_time . 'M' ) );
			$vid_end2_2->add ( new DateInterval ( 'PT' . $vid_time . 'M' ) );
			
			$vid_wk = $vid_sta2->format ( "D" );
			
			$msg = $vid_sta2->format ( "Y-m-d H:i:s" ) . "|" . $vid_end2->format ( "Y-m-d H:i:s" );
			
			$sql = "select * from vidrecsrc where (";
			
			$wstr0 = "";
			$wstr1 = "";
			$wstr2 = "";
			$wstr3 = "";
			$wstr4 = "";
			
			if ($vid_cycle == "一回のみ") {
				$wstr2 = "and vid_wk='" . $vid_wk . "'";
				$wstr4 = " and vid_sta<='" . $vid_end2->format ( "Y-m-d H:i:s" ) . "'";
			}
			if ($vid_cycle == "毎週") {
				$wstr0 = "_2";
				$wstr1 = "2";
				$wstr3 = " and vid_end>='" . $vid_sta2->format ( "Y-m-d H:i:s" ) . "'";
				$wstr2 = "and vid_wk='" . $vid_wk . "'";
			}
			if ($vid_cycle == "毎日") {
				$wstr0 = "_2";
				$wstr1 = "2";
				$wstr3 = " and vid_end>='" . $vid_sta2->format ( "Y-m-d H:i:s" ) . "'";
				$wstr2 = "";
			}
			
			$sql .= "(((vid_sta" . $wstr1 . "<='" . ${$vid_sta2 . $wstr0}->format ( "Y-m-d H:i:s" ) . "' and vid_end" . $wstr1 . ">'" . ${$vid_sta2 . $wstr0}->format ( "Y-m-d H:i:s" ) . "') or ";
			$sql .= "(vid_sta" . $wstr1 . "<'" . ${$vid_end2 . $wstr0}->format ( "Y-m-d H:i:s" ) . "' and vid_end" . $wstr1 . ">='" . ${$vid_end2 . $wstr0}->format ( "Y-m-d H:i:s" ) . "')) " . $wstr3 . " and vid_cycle='一回のみ' " . $wstr2 . " ) or ";
			
			$sql .= "(((vid_sta2<='" . $vid_sta2_2->format ( "Y-m-d H:i:s" ) . "' and vid_end2>'" . $vid_sta2_2->format ( "Y-m-d H:i:s" ) . "') or ";
			$sql .= "(vid_sta2<'" . $vid_end2_2->format ( "Y-m-d H:i:s" ) . "' and vid_end2>='" . $vid_end2_2->format ( "Y-m-d H:i:s" ) . "')) " . $wstr4 . " and vid_cycle='毎週' " . $wstr2 . " ) or ";
			
			$sql .= "(((vid_sta2<='" . $vid_sta2_2->format ( "Y-m-d H:i:s" ) . "' and vid_end2>'" . $vid_sta2_2->format ( "Y-m-d H:i:s" ) . "') or ";
			$sql .= "(vid_sta2<'" . $vid_end2_2->format ( "Y-m-d H:i:s" ) . "' and vid_end2>='" . $vid_end2_2->format ( "Y-m-d H:i:s" ) . "')) " . $wstr4 . " and vid_cycle='毎日')";
			
			$sql .= ")";
			if (! ($rs = mysql_query ( $sql )))
				die ();
			
			if (mysql_num_rows ( $rs ) == 0) {
				$sql = "INSERT INTO vidrecsrc (ch_type,ch_code,ch_name,vid_stayy,vid_stamm,vid_wk,vid_sta,vid_end,vid_sta2,vid_end2,vid_time,vid_cycle,vid_name,vid_file) VALUES ('";
				$sql .= $ch_type . "','" . $ch_code . "','" . $ch_name . "','";
				$sql .= $vid_sta2->format ( "Y" ) . "','" . $vid_sta2->format ( "m" ) . "','" . $vid_wk . "','";
				$sql .= $vid_sta2->format ( "Y-m-d H:i:s" ) . "','" . $vid_end2->format ( "Y-m-d H:i:s" ) . "','";
				$sql .= $vid_sta2_2->format ( "Y-m-d H:i:s" ) . "','" . $vid_end2_2->format ( "Y-m-d H:i:s" ) . "',";
				$sql .= $vid_time . ",'" . $vid_cycle . "','" . $vid_name . $vid_namesuffix . "','";
				$sql .= $ch_type . "-" . $ch_code . "-" . $vid_sta2->format ( "Ymd-Hi" ) . "')";
				
				if (! (mysql_query ( $sql )))
					die ();
				
				if ($ch_name != "") {
					$sql = "UPDATE tuneinfo SET data3='" . $ch_name . "' WHERE code='" . $ch_code . "'";
					if (! (mysql_query ( $sql )))
						die ();
				}
			} else {
				array_push ( $errors, "＜録画時間が重複してます＞" );
				$i = 0;
				while ( $item = mysql_fetch_array ( $rs ) ) {
					$i += 1;
					if ($item ['ch_type'] == "UHF")
						$wStr3 = "地デジ";
					if ($item ['ch_type'] == "BS")
						$wStr3 = "BS衛星";
					if ($item ['ch_type'] == "CS")
						$wStr3 = "CS衛星";
					array_push ( $errors, $i . ")  ﾀｲﾌﾟ:" . $wStr3 . ", ﾁｬﾝﾈﾙ#:" . substr ( $item ['ch_code'], 2, 4 ) . ", 番組:" . $item ['vid_name'] . ", 日時:" . $item ['vid_sta'] . ", 時間:" . $item ['vid_time'], "分" . ", 周期:" . $item ['vid_cycle'] );
				}
			}
		}
		if ($vid_time < 120) {
			add_vidrecsrc ( $errors, $vid_sta, $vid_time );
		} else {
			$vid_div = floor ( $vid_time / 60 );
			for($i = 0; $i < $vid_div; $i ++) {
				if ($i == $vid_div - 1) {
					// last
					$vid_time = $vid_time - $i * 60;
				} else {
					$vid_time = 60; // or 59
				}
				
				$vid_sta = new DateTime ( $vid_sta );
				$vid_sta->add ( new DateInterval ( 'PT' . (60 * $i) . 'M' ) );
				
				add_vidrecsrc ( $errors, $vid_sta->format ( "Y-m-d H:i:s" ), $vid_time, ' ' . ($i + 1) . '/' . $vid_div );
			}
		}
	}
} catch ( Exception $e ) {
	array_push ( $errors, "※入力されたデータをお確かめください。" . $e->getMessage () );
}

if (count($erros) > 0) {
	echo json_encode(true);
} else {
	echo json_encode(array("errors" => $errors));
}
?>
