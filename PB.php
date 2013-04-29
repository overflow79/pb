<?
class PB
{
	private static $score_data = array();
	private static $url = array();
	private static $teamnames = array();
	private static $team_id = array();

	public function __construct()
	{
		$year = date('Y');	
		$this->url = array(
			#"status" => "is_playing_sample.html",
			"status" => "http://sports.media.daum.net/baseball/kbo/schedule/sbrd_main.daum",
			"teamrank" => "http://sports.media.daum.net/baseball/kbo/record/main.daum",
			"preview" => "http://www.koreabaseball.com/Default.aspx",
			"pitcherrank" => "http://sports.media.daum.net/baseball/kbo/record/prnk_bysn.daum?season_id=".$year."&col=",
			"fielderrank" => "http://sports.media.daum.net/baseball/kbo/record/brnk_bysn.daum?season_id=".$year."&col=",
			"players" => "http://sports.media.daum.net/planus_proxy/planus/do/baseball/kbo/record/plrinf_sch_player_list.daum?team_id=",
			"player" => "http://sports.media.daum.net/planus_proxy/planus/do/baseball/kbo/record/plrinf_common.daum?person_id="
		);
		$this->teamnames = array(
			"LT" => "롯데",
			"SK" => "SK",
			"SS" => "삼성",
			"OB" => "두산",
			"LG" => "LG",
			"WO" => "넥센",
			"HT" => "기아",
			"HH" => "한화",
			"NC" => "NC"
		);
		$this->team_id = array("WO"=>382, "SS"=>383, "SK"=>384, "OB"=>385, "LT"=>386, "LG"=>387, "HT"=>389, "HH"=>390, "NC"=>172615);
	}

	public function init($m)
	{
		if (!$m || !method_exists('PB', $m)) die($m);
		$this->$m();
	}

	private function cronjobPreview()
	{
		$this->setLog('Preview');
	}

	private function cronjobTeamrank()
	{
		$this->setLog('Teamrank');
	}

	private function cronjobPitcherrank()
	{
		$sort_arr = array('era', 'gp', 'w', 'l', 'sv', 'hld', 'ip', 'so');
		foreach ($sort_arr as $v)
			$this->setLog('Pitcherrank', $v);
	}

	private function cronjobFielderrank()
	{
		$sort_arr = array('avg', 'ab', 'hit', 'hr', 'rbi', 'bb', 'sb', 'so');
		foreach ($sort_arr as $v)
			$this->setLog('Fielderrank', $v);
	}

	private function cronjobPlayers()
	{
		foreach ($this->team_id as $k=>$v)
			$this->setLog('Players', $v, true);
	}

	private function cronjobPlayer()
	{
		foreach ($this->team_id as $k=>$v)
		{
			$tp = json_decode($this->_getPlayersInfo($k), true);
			foreach ($tp as $key=>$val)
				$this->setLog('Player', $val['person_id'], true);
		}
	}

	protected function setLog($m, $sort=null)
	{
		$file_name = $sort ? $sort : $m;
		ob_start();
		$_func_name = "_get{$m}InfoData";
		if ($sort)
			echo $this->$_func_name($sort, true);
		else
			echo $this->$_func_name(true);
		$size = ob_get_length();
		if ($size > 0)
		{
			$contents = ob_get_contents();
			file_put_contents("/var/www/pb/log/".strtolower($m)."/".strtolower($file_name), $contents);
			ob_clean();
		}
	}

	private function main()
	{
		$version = $_REQUEST['version'];
		if (!$version)
		{
			echo $this->_getPreviewInfo();
			exit;
		}
		else
			$is_playing = $this->_getGamePlayingStatus();

		if ($is_playing)
			$data = $this->_getScoreBoard();
		else
			$data = $this->_getPreviewInfo();

		echo '{"is_playing":'.($is_playing ? "true" : "false").', "data":'.$data.'}';
	}

	private function getTeamrank()
	{
		echo $this->_getTeamrankInfo();
	}

	private function getPitcherrank()
	{
		$sort = $_REQUEST['sort'];
		echo $this->_getPitcherrankInfo($sort);
	}

	private function getFielderrank()
	{
		$sort = $_REQUEST['sort'];
		echo $this->_getFielderrankInfo($sort);
	}

	private function getPlayers()
	{
		$team = $_REQUEST['team'];
		echo $this->_getPlayersInfo($team);
	}

	private function getPlayer()
	{
		$id = $_REQUEST['id'];
		echo $this->_getPlayerInfo($id);
	}

	private function _getScoreBoard()
	{
		return json_encode($this->score_data);
	}

	private function _setScoreBoard($data=array())
	{
		$data2 = str_replace(array("\t","\n"), "", $data);
		preg_match_all("|createImgScore\(\'(.*)\'|U", $data, $ars_score);
		preg_match_all("|<strong><a href=[^>]+>(.*)</a></strong>|U", $data, $ars_teams);
		preg_match_all("|<p class=\"state\">(.*)</p>|U", $data, $ars_vs);
		preg_match_all("|<tr class=\"kbo\">(.*)</tr>|U", $data2, $ars_scoreboard);
		$scores = $ars_score[1];
		$teams = $ars_teams[1];
		$state = $ars_vs[1];
		$sb = $ars_scoreboard[1];
		$r = array();$i = 0;$j = 0;
		for ($l=0; $l<4; $l++)
		{
			$j = $i + 1;
			$r[] = array(
				"info" => array(
					"state" => $state[$l],
					"home_team" => $teams[$i],
					"home_score" => $scores[$i],
					"away_team" => $teams[$j],
					"away_score" => $scores[$j]
				),
				"score" => array(
					"home" => $sb[$i],
					"away" => $sb[$j]
				)
			);
			$i = $i + 2;
		}
		$this->score_data = $r;
	}

	private function _getPreviewInfo($force=false)
	{
		$file_path = "/var/www/pb/log/preview/preview";
		if (!$force && file_exists($file_path))
			return $this->_getData($file_path);
		else
			return $this->_getPreviewInfoData();
	}

	private function _getTeamrankInfo($force=false)
	{
		$file_path = "/var/www/pb/log/teamrank/teamrank";
		if (!$force && file_exists($file_path))
			return $this->_getData($file_path);
		else
			return $this->_getTeamrankInfoData();
	}

	private function _getPitcherrankInfo($sort, $force=false)
	{
		$file_path = "/var/www/pb/log/pitcherrank/".$sort;
		if (!$force && file_exists($file_path))
			return $this->_getData($file_path);
		else
			return $this->_getPitcherrankInfoData($sort);
	}

	private function _getFielderrankInfo($sort, $force=false)
	{
		$file_path = "/var/www/pb/log/fielderrank/".$sort;
		if (!$force && file_exists($file_path))
			return $this->_getData($file_path);
		else
			return $this->_getFielderrankInfoData($sort);
	}

	private function _getPlayersInfo($team, $force=false)
	{
		$file_path = "/var/www/pb/log/players/".$this->team_id[$team];
		if (!$force && file_exists($file_path))
			return $this->_getData($file_path);
		else
			return $this->_getPlayersInfoData($team);
	}

	private function _getPlayerInfo($id, $force=false)
	{
		$file_path = "/var/www/pb/log/player/".$id;
		if (!$force && file_exists($file_path))
			return $this->_getData($file_path);
		else
			return $this->_getPlayerInfoData($id);
	}

	private function _getPitcherrankInfoData($sort)
	{
		$r = array();
		//echo $this->url['pitcherrank'].strtoupper($sort);
		$data = $this->_getData($this->url['pitcherrank'].strtoupper($sort));
		preg_match_all("|<td class=\"rank\">(.*)</td>|U", $data, $arp_rank);
		preg_match_all("|<td class=\"player\"><em><a href=[^>]+>(.*)</td>|U", $data, $arp_player);
		preg_match_all("|<td class=\"era\">(.*)</td>|U", $data, $arp_era);//방어율
		preg_match_all("|<td class=\"gp\">(.*)</td>|U", $data, $arp_gp);//경기
		preg_match_all("|<td class=\"w\">(.*)</td>|U", $data, $arp_w);//승
		preg_match_all("|<td class=\"l\">(.*)</td>|U", $data, $arp_l);//패
		preg_match_all("|<td class=\"sv\">(.*)</td>|U", $data, $arp_sv);//세이브
		preg_match_all("|<td class=\"hld\">(.*)</td>|U", $data, $arp_hld);//홀드
		preg_match_all("|<td class=\"ip\">(.*)</td>|U", $data, $arp_ip);//이닝
		preg_match_all("|<td class=\"so\">(.*)</td>|U", $data, $arp_so);//삼진
		$total = count($arp_rank[1]);
		for($i=0; $i<$total; $i++)
		{
			$r[] = array(
				"rank" => strip_tags($arp_rank[1][$i]),
				"player" => strip_tags($arp_player[1][$i]),
				"era" => strip_tags($arp_era[1][$i]),
				"gp" => strip_tags($arp_gp[1][$i]),
				"w" => strip_tags($arp_w[1][$i]),
				"l" => strip_tags($arp_l[1][$i]),
				"sv" => strip_tags($arp_sv[1][$i]),
				"hld" => strip_tags($arp_hld[1][$i]),
				"ip" => strip_tags($arp_ip[1][$i]),
				"so" => strip_tags($arp_so[1][$i])
			);
		}
		return json_encode($r);
	}

	private function _getPlayersInfoData($team)
	{
		$r = array();
		$data = $this->_getData($this->url['players'].strtoupper($team));
		preg_match_all("|sports.media.daum.net/baseball/kbo/record/plrinf_[^>]+>(.*)</span></li>|U", $data, $artp);
		$total = count($artp[1]);
		for($i=0; $i<$total; $i++)
		{
			$tmpTp0 = explode("</a></span>", $artp[0][$i]);
			$tmpTp1= explode("</a></span>", $artp[1][$i]);
			$pname = explode("</a><span>", $tmpTp1[0]);
			preg_match("|person_id=(.*)\" target=\"_top\">|U", $tmpTp0[0], $pid);
			$r[] = array(
				"person_id" => strip_tags($pid[1]),
				"name" => strip_tags($pname[0]),
				"no" => strip_tags($pname[1])
			);
		}
		return json_encode($r);
	}

	private function _getPlayerInfoData($id)
	{
		$r = array();
		$data = $this->_getData($this->url['player'].$id);
		$arpd = trim(str_replace(array("selectBoxPlayerInfo({","\"root\" : [","]","});","\n","\t","\'"), "", $data));
		$info = json_decode($arpd, true);
		$url = "http://sports.media.daum.net/baseball/kbo/record/plrinf_".(($info['POS_DETAIL'] == "P")?"pit":"bat")."_rechist.daum?person_id=".$id;
		$data = $this->_getData($url);
		if ($info['POS_DETAIL'] == "P"){
			preg_match_all("|<td class=\"year\">(.*)</td>|U", $data, $arp_year);
			preg_match_all("|<td class=\"team_name\"><a href=[^>]+>(.*)</td>|U", $data, $arp_team);
			preg_match_all("|<td class=\"era\">(.*)</td>|U", $data, $arp_era);//방어율
			preg_match_all("|<td class=\"gp\">(.*)</td>|U", $data, $arp_gp);//경기
			preg_match_all("|<td class=\"w\">(.*)</td>|U", $data, $arp_w);//승
			preg_match_all("|<td class=\"l\">(.*)</td>|U", $data, $arp_l);//패
			preg_match_all("|<td class=\"sv\">(.*)</td>|U", $data, $arp_sv);//세이브
			preg_match_all("|<td class=\"hld\">(.*)</td>|U", $data, $arp_hld);//홀드
			preg_match_all("|<td class=\"ip\">(.*)</td>|U", $data, $arp_ip);//이닝
			preg_match_all("|<td class=\"so\">(.*)</td>|U", $data, $arp_so);//삼진
			//preg_match_all("|<td class=\"bb\">(.*)</td>|U", $data, $arp_bb);//볼넷
			//preg_match_all("|<td class=\"r\">(.*)</td>|U", $data, $arp_r);//실점
			//preg_match_all("|<td class=\"er\">(.*)</td>|U", $data, $arp_er);//자책
			//preg_match_all("|<td class=\"wpct\">(.*)</td>|U", $data, $arp_wpct);//승률
			//preg_match_all("|<td class=\"whip\">(.*)</td>|U", $data, $arp_whip);//WHIP
			$total = count($arp_year[1]);
			for($i=0; $i<$total; $i++){
				$r[] = array(
					"year" => $arp_year[1][$i],
					"team" => isset($arp_team[1][$i])?$arp_team[1][$i]:null,
					"era" => $arp_era[1][$i],
					"gp" => $arp_gp[1][$i],
					"w" => $arp_w[1][$i],
					"l" => $arp_l[1][$i],
					"sv" => $arp_sv[1][$i],
					"hld" => $arp_hld[1][$i],
					"ip" => $arp_ip[1][$i],
					"so" => $arp_so[1][$i]
				);
			}
		}
		else{
			preg_match_all("|<td class=\"year\">(.*)</td>|U", $data, $arb_year);
			preg_match_all("|<td class=\"team_name\"><a href=[^>]+>(.*)</td>|U", $data, $arb_team);
			preg_match_all("|<td class=\"avg\">(.*)</td>|U", $data, $arb_avg);//타율
			preg_match_all("|<td class=\"ab\">(.*)</td>|U", $data, $arb_ab);//타수
			preg_match_all("|<td class=\"hit\">(.*)</td>|U", $data, $arb_hit);//안타
			preg_match_all("|<td class=\"hr\">(.*)</td>|U", $data, $arb_hr);//홈런
			preg_match_all("|<td class=\"rbi\">(.*)</td>|U", $data, $arb_rbi);//타점
			preg_match_all("|<td class=\"bb\">(.*)</td>|U", $data, $arb_bb);//볼넷
			preg_match_all("|<td class=\"sb\">(.*)</td>|U", $data, $arb_sb);//도루
			preg_match_all("|<td class=\"so\">(.*)</td>|U", $data, $arb_so);//삼진
			//preg_match_all("|<td class=\"obp\">(.*)</td>|U", $data, $arb_obp);//출루율
			//preg_match_all("|<td class=\"slg\">(.*)</td>|U", $data, $arb_slg);//장타율
			//preg_match_all("|<td class=\"ops\">(.*)</td>|U", $data, $arb_ops);//OPS
			//preg_match_all("|<td class=\"b2\">(.*)</td>|U", $data, $arb_b2);//2루타
			//preg_match_all("|<td class=\"b3\">(.*)</td>|U", $data, $arb_b3);//3루타
			$total = count($arb_year[1]);
			for($i=0; $i<$total; $i++){
				$r[] = array(
					"year" => $arb_year[1][$i],
					"team" => isset($arb_team[1][$i])?$arb_team[1][$i]:null,
					"avg" => $arb_avg[1][$i],
					"ab" => $arb_ab[1][$i],
					"hit" => $arb_hit[1][$i],
					"hr" => $arb_hr[1][$i],
					"rbi" => $arb_rbi[1][$i],
					"bb" => $arb_bb[1][$i],
					"sb" => $arb_sb[1][$i],
					"so" => $arb_so[1][$i]
				);
			}
		}
		//$result = "{\"info\":".$text.", \"career\":".json_encode($r)."}";
		$result = json_encode(array("info"=>$info, "career"=>$r));
		return $result;
	}

	private function _getFielderrankInfoData($sort)
	{
		$r = array();
		//echo $this->url['fielderrank'].$this->sort;
		$data = $this->_getData($this->url['fielderrank'].strtoupper($sort));
		preg_match_all("|<td class=\"rank\">(.*)</td>|U", $data, $arb_rank);
		preg_match_all("|<td class=\"player\"><em><a href=[^>]+>(.*)</td>|U", $data, $arb_playerinfo);
		preg_match_all("|<td class=\"avg\">(.*)</td>|U", $data, $arb_avg);//타율
		preg_match_all("|<td class=\"ab\">(.*)</td>|U", $data, $arb_ab);//타수
		preg_match_all("|<td class=\"hit\">(.*)</td>|U", $data, $arb_hit);//안타
		preg_match_all("|<td class=\"hr\">(.*)</td>|U", $data, $arb_hr);//홈런
		preg_match_all("|<td class=\"rbi\">(.*)</td>|U", $data, $arb_rbi);//타점
		preg_match_all("|<td class=\"bb\">(.*)</td>|U", $data, $arb_bb);//볼넷
		preg_match_all("|<td class=\"sb\">(.*)</td>|U", $data, $arb_sb);//도루
		preg_match_all("|<td class=\"so\">(.*)</td>|U", $data, $arb_so);//삼진
		foreach($arb_playerinfo[1] as $v){
			$tmp = explode(" ", str_replace("</a></em> (", " ", $v));
			//echo $tmp[0]."/".$tmp[1]."/".$tmp[2]."<br />";
			if (isset($tmp[3]))
			{
				$arb_player[1][] = $tmp[0].$tmp[1];
				$arb_team[1][] = $tmp[2];
			}
			else
			{
				$arb_player[1][] = $tmp[0];
				$arb_team[1][] = $tmp[1];
			}
		}
		$total = count($arb_rank[1]);
		for($i=0; $i<$total; $i++)
		{
			$r[] = array(
				"rank" => strip_tags($arb_rank[1][$i]),
				"player" => strip_tags($arb_player[1][$i]),
				"team" => strip_tags($arb_team[1][$i]),
				"avg" => strip_tags($arb_avg[1][$i]),
				"ab" => strip_tags($arb_ab[1][$i]),
				"hit" => strip_tags($arb_hit[1][$i]),
				"hr" => strip_tags($arb_hr[1][$i]),
				"rbi" => strip_tags($arb_rbi[1][$i]),
				"bb" => strip_tags($arb_bb[1][$i]),
				"sb" => strip_tags($arb_sb[1][$i]),
				"so" => strip_tags($arb_so[1][$i])
			);
		}
		return json_encode($r);
	}

	private function _getTeamrankInfoData()
	{
		$r = array();
		$data = $this->_getData($this->url['teamrank']);
		preg_match_all("|<td class=\"rank\">(.*)</td>|U", $data, $art_rank);
		preg_match_all("|<td class=\"team\"><a href=[^>]+>(.*)</a></td>|U", $data, $art_team);
		preg_match_all("|<td class=\"game\">(.*)</td>|U", $data, $art_game);
		preg_match_all("|<td class=\"w\">(.*)</td>|U", $data, $art_w);
		preg_match_all("|<td class=\"d\">(.*)</td>|U", $data, $art_d);
		preg_match_all("|<td class=\"l\">(.*)</td>|U", $data, $art_l);
		preg_match_all("|<td class=\"wpct\">(.*)</td>|U", $data, $art_wpct);
		preg_match_all("|<td class=\"gb\">(.*)</td>|U", $data, $art_gb);
		preg_match_all("|<td class=\"streak\">(.*)</td>|U", $data, $art_streak);
		for($i=0; $i<=8; $i++)
		{
			$r[] = array(
				"rank" => $art_rank[1][$i],
				"team" => $art_team[1][$i],
				"game" => $art_game[1][$i],
				"w" => $art_w[1][$i],
				"d" => $art_d[1][$i],
				"l" => $art_l[1][$i],
				"wpct" => $art_wpct[1][$i],
				"gb" => $art_gb[1][$i],
				"streak" => $art_streak[1][$i]
			);
		}
		return json_encode($r);
	}

	private function _getPreviewInfoData()
	{
		$data = $this->_getData($this->url['preview']);
		preg_match_all("|<p class=\"stadium\">(.*)</p>|U", $data, $ars_stadium);
		preg_match_all("|<span class=\"player lp\">(.*)</span>|U", $data, $ars_left_pitcher);
		preg_match_all("|<span class=\"player rp\">(.*)</span>|U", $data, $ars_right_pitcher);
		preg_match_all("|<span class=\"team\">(.*)</span>|U", $data, $ars_team);
		$l = 0;$r = 0;$k = 0;$j = 0;$h = 0;
		foreach($ars_team[0] as $k=>$v):
			if ($r == 8) break;
			preg_match_all("|alt=\"(.*)\"|U", $v, $tmp_ars_team);
			$team = $tmp_ars_team[1][0];
			$team_name = $this->teamnames[$team];
			if ($r % 2 == 0)
			{
				$left_team[] = $team;
				$left_team_name[] = $team_name;
			}
			else
			{
				$right_team[] = $team;
				$right_team_name[] = $team_name;
			}
			$r++;
		endforeach;
		foreach($ars_left_pitcher[1] as $k=>$v):
			if (++$j == 5) break;
			$left_pitcher[] = strip_tags($v);
		endforeach;
		foreach($ars_right_pitcher[1] as $k=>$v):
			if (++$h == 5) break;
			$right_pitcher[] = strip_tags($v);
		endforeach;
		foreach($ars_stadium[1] as $k=>$v):
			if (++$k == 5) break;
			$stadium[] = strip_tags($v);
		endforeach;
		$r = array(
			array(
				'team1' => $left_team[0], 
				'team_name1' => $left_team_name[0],
				'pitcher1' => $left_pitcher[0], 
				'score1' => '',
				'team2' => $right_team[0], 
				'team_name2' => $right_team_name[0], 
				'pitcher2' => $right_pitcher[0], 
				'score2' => '',
				'stadium' => $stadium[0]
			),
			array(
				'team1' => $left_team[1], 
				'team_name1' => $left_team_name[1],
				'pitcher1' => $left_pitcher[1], 
				'score1' => '',
				'team2' => $right_team[1], 
				'team_name2' => $right_team_name[1], 
				'pitcher2' => $right_pitcher[1], 
				'score2' => '',
				'stadium' => $stadium[1]
			),
			array(
				'team1' => $left_team[2], 
				'team_name1' => $left_team_name[2],
				'pitcher1' => $left_pitcher[2], 
				'score1' => '',
				'team2' => $right_team[2], 
				'team_name2' => $right_team_name[2], 
				'pitcher2' => $right_pitcher[2], 
				'score2' => '',
				'stadium' => $stadium[2]
			),
			array(
				'team1' => $left_team[3], 
				'team_name1' => $left_team_name[3],
				'pitcher1' => $left_pitcher[3], 
				'score1' => '',
				'team2' => $right_team[3], 
				'team_name2' => $right_team_name[3], 
				'pitcher2' => $right_pitcher[3], 
				'score2' => '',
				'stadium' => $stadium[3]
			)
		);
		return json_encode($r);
	}

	protected function _getData($url)
	{
		return file_get_contents($url);
	}

	protected function _getGamePlayingStatus()
	{
		$week = date('w');
		$hour = date('H');
		if ($week == 1)
			return false;
		else if (in_array($week, array(0, 6)) && $hour < 12)
			return false;
		else if (!in_array($week, array(0, 6)) && $hour < 17)
			return false;
		else
		{
			$data = $this->_getData($this->url['status']);
			preg_match_all("|<p class=\"state\">(.*)</p>|U", $data, $ars_vs);
			if (
				trim(strip_tags($ars_vs[1][0])) == "진행중" || 
				trim(strip_tags($ars_vs[1][1])) == "진행중" || 
				trim(strip_tags($ars_vs[1][2])) == "진행중" || 
				trim(strip_tags($ars_vs[1][3])) == "진행중" ||
				trim(strip_tags($ars_vs[1][0])) == "종료" || 
				trim(strip_tags($ars_vs[1][1])) == "종료" || 
				trim(strip_tags($ars_vs[1][2])) == "종료" || 
				trim(strip_tags($ars_vs[1][3])) == "종료" ||
				trim(strip_tags($ars_vs[1][0])) == "경기취소" || 
				trim(strip_tags($ars_vs[1][1])) == "경기취소" || 
				trim(strip_tags($ars_vs[1][2])) == "경기취소" || 
				trim(strip_tags($ars_vs[1][3])) == "경기취소" 
			)
			{
				$this->_setScoreBoard($data);
				return true;
			}
			else
				return false;
		}
	}

}
?>
