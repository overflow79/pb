var PBAPI = "http://overfloweb.com/pb/";
var data_rank_team = false;
var data_rank_pitcher = {'era':null, 'gp':null, 'w':null, 'l':null, 'sv':null, 'hld':null, 'ip':null, 'so':null};
var data_rank_fielder = {'avg':null, 'ab':null, 'hit':null, 'hr':null, 'rbi':null, 'bb':null, 'sb':null, 'so':null};
var data_players = {'LG':null, 'HT':null, 'WO':null, 'OB':null, 'LT':null, 'SS':null, 'SK':null, 'HH':null, 'NC':null};
var data_player = {};

viewTeamRanking = function(menu, data)
{
	var h = '<table width="100%" border="1" id="rank_table"><thead><tr><th>순위</th><th>팀</th><th>경기</th><th>승</th><th>무</th><th>패</th><th>승률</th><th>게임차</th><th>연속</th></tr></thead><tbody>';
	$.each( data, function( key, value ) {
		h += '<tr><td>' + value.rank + '</td>';
		h += '<td>' + value.team + '</td>';
		h += '<td>' + value.game + '</td>';
		h += '<td>' + value.w + '</td>';
		h += '<td>' + value.d + '</td>';
		h += '<td>' + value.l + '</td>';
		h += '<td>' + value.wpct + '</td>';
		h += '<td>' + value.gb + '</td>';
		h += '<td>' + value.streak + '</td></tr>';
	});
	h += '</tbody></table>';
	$('#' + menu + '_box').append(h);
}

viewPitcherRanking = function(menu, data)
{
	var h = '<table width="100%" border="1" id="rank_table"><thead><tr><th>순위</th><th>선수</th><th>방어율</th><th>승</th><th>패</th><th>세이브</th><th>홀드</th><th>이닝</th><th>삼진</th><th>경기</th></tr></thead><tbody>';
	$.each( data, function( key, value ) {
		h += '<tr><td>' + value.rank + '</td>';
		h += '<td>' + value.player + '</td>';
		h += '<td' + (menu=='era'?' class="highlight"':'') + '>' + value.era + '</td>';
		h += '<td' + (menu=='w'?' class="highlight"':'') + '>' + value.w + '</td>';
		h += '<td' + (menu=='l'?' class="highlight"':'') + '>' + value.l + '</td>';
		h += '<td' + (menu=='sv'?' class="highlight"':'') + '>' + value.sv + '</td>';
		h += '<td' + (menu=='hld'?' class="highlight"':'') + '>' + value.hld + '</td>';
		h += '<td' + (menu=='ip'?' class="highlight"':'') + '>' + value.ip + '</td>';
		h += '<td' + (menu=='so'?' class="highlight"':'') + '>' + value.so + '</td>';
		h += '<td' + (menu=='gp'?' class="highlight"':'') + '>' + value.gp + '</td></tr>';
	});
	h += '</tbody></table>';
	$('#pitcher_rank_box').html(h);
}

viewFielderRanking = function(menu, data)
{
	var h = '<table width="100%" border="1" id="rank_table"><thead><tr><th>순위</th><th>선수</th><th>타율</th><th>안타</th><th>홈런</th><th>타점</th><th>볼넷</th><th>도루</th><th>삼진</th><th>타수</th></tr></thead><tbody>';
	$.each( data, function( key, value ) {
		h += '<tr><td>' + value.rank + '</td>';
		h += '<td>' + value.player + '(' + value.team + ')</td>';
		h += '<td' + (menu=='avg'?' class="highlight"':'') + '>' + value.avg + '</td>';
		h += '<td' + (menu=='hit'?' class="highlight"':'') + '>' + value.hit + '</td>';
		h += '<td' + (menu=='hr'?' class="highlight"':'') + '>' + value.hr + '</td>';
		h += '<td' + (menu=='rbi'?' class="highlight"':'') + '>' + value.rbi + '</td>';
		h += '<td' + (menu=='bb'?' class="highlight"':'') + '>' + value.bb + '</td>';
		h += '<td' + (menu=='sb'?' class="highlight"':'') + '>' + value.sb + '</td>';
		h += '<td' + (menu=='so'?' class="highlight"':'') + '>' + value.so + '</td>';
		h += '<td' + (menu=='ab'?' class="highlight"':'') + '>' + value.ab + '</td></tr>';
	});
	h += '</tbody></table>';
	$('#fielder_rank_box').html(h);
}

viewPlayer = function(data)
{
	var h = '<table width="100%" border="1" id="rank_table"><thead><tr><th width="30%"><img src="http://i1.daumcdn.net/img-section/sports09/player/kbo/' + data.info.CP_PERSON_ID + '.jpg" /></th><td style="text-align:left;padding:5px;"><img src="img/btn_close.gif" class="close_btn" /><span style="margin-left:-19px;font-size:14px;">' + data.info.PERSON_FULL_KNAME + ' No.' + data.info.BACKNUM + '</span><br />';
	if (data.info.POS_DETAIL == "P")
		h += '<span class="record">' + data.info.W + '승 ' + data.info.L + '패 ' + data.info.SV + '세이브 방어율' + data.info.ERA + '</span><br />';
	else
		h += '<span class="record">타율 ' + data.info.AVG + ' 안타' + data.info.HIT + ' 홈런' + data.info.HR + ' 타점' + data.info.RBI + '</span><br />';
	h += '<br /><span class="desc">' + data.info.BIRTH + ' ' + data.info.HEIGHT + ' ' + data.info.WEIGHT + '<br/>' + data.info.POS_DETAIL_NAME + ' ' + data.info.HITTYPE + ' 연봉 ' + data.info.MONEY + '<br/>' + data.info.CP_CAREER + '<br/>' + data.info.DEBUT + ' 데뷔</span>';
	
	h += '</td></tr></thead><tbody>';
	h += '<table width="100%" border="1" id="rank_table"><thead><tr><th>연도</th><th>소속</th>';
	if (data.info.POS_DETAIL == "P")
		h += '<th>방어율</th><th>경기</th><th>승</th><th>패</th><th>세이브</th><th>홀드</th><th>이닝</th><th>삼진</th>';
	else
		h += '<th>타율</th><th>타수</th><th>안타</th><th>홈런</th><th>타점</th><th>볼넷</th><th>도루</th><th>삼진</th>';
	h += '</tr></thead><tbody>';

	$.each( data.career, function( key, value ) {
		if (data.info.POS_DETAIL == "P"){
			h += '<tr><td>' + value.year + '</td>';
			h += '<td>' + (value.team ? value.team : "") + '</td>';
			h += '<td>' + value.era + '</td>';
			h += '<td>' + value.gp + '</td>';
			h += '<td>' + value.w + '</td>';
			h += '<td>' + value.l + '</td>';
			h += '<td>' + value.sv + '</td>';
			h += '<td>' + value.hld + '</td>';
			h += '<td>' + value.ip + '</td>';
			h += '<td>' + value.so + '</td></tr>';
		}
		else{
			h += '<tr><td>' + value.year + '</td>';
			h += '<td>' + (value.team ? value.team : "") + '</td>';
			h += '<td>' + value.avg + '</td>';
			h += '<td>' + value.ab + '</td>';
			h += '<td>' + value.hit + '</td>';
			h += '<td>' + value.hr + '</td>';
			h += '<td>' + value.rbi + '</td>';
			h += '<td>' + value.bb + '</td>';
			h += '<td>' + value.sb + '</td>';
			h += '<td>' + value.so + '</td></tr>';
		}
	});
	h += '</table></tbody></table>';
	$('#Player_box').html(h).show();
	$('#Players_box').hide();
}

viewPlayers = function(menu, data)
{
	$('#Players_box').html('<ul id="player_nav" class="nav_ul5">');
	$.each( data, function ( key, value ) {
		var h = '<li><a class="menu" id="player_link_' + value.person_id + '">' + value.name + value.no + '</a></li>';
		$('#Players_box').append(h);
		$('#player_link_' + value.person_id).click(function(){
			getPlayerInfo(value.person_id);
		});
	});
	$('#Players_box').append('</ul>');
	$('#Player_box').hide();
}

getPlayerInfo = function(id)
{
	if (!data_player[id])
	{
		$.getJSON( PBAPI, {
			m: "getPlayer",
			id: id
		}).done(function(data) {
			viewPlayer(data);
			data_player[id] = data;
			console.log('player '+ id +' load');
		});
	}
	else
		viewPlayer(data_player[id]);
}

$(function() {
	$('.close_btn').click(function(){
		$('#Players_box').show();
		$('#Player_box').hide();
	});

	$('#main_nav .menu').click(function(){
		$('#main_nav').find('a').each(function(){
			$(this).removeClass('on');
		});
		$(this).addClass('on');
		$('.box').each(function(){
			$(this).hide();
		});
		var menu = $(this).attr('title');
		switch (menu)
		{
			case 'Ranking' :
				if (!data_rank_team)
				{
					$.getJSON( PBAPI, {
						m: "getTeamrank"
					}).done(function(data) {
						viewTeamRanking('Team', data);
						data_rank_team = true;
						console.log(menu+' load');
					});
				}
				break;
			case 'Players' :
				break;
		}
		$('#' + menu).show();
	});

	$('#rank_nav .menu').click(function(){
		$('#rank_nav').find('a').each(function(){
			$(this).removeClass('on');
		});
		$(this).addClass('on');
		$('.inbox').each(function(){
			$(this).hide();
		});
		var menu = $(this).attr('title');
		$('#' + menu + '_box').show();
		switch (menu)
		{
			case 'Team' :
				if (!data_rank_team)
				{
					$.getJSON( PBAPI, {
						m: "getTeamrank"
					}).done(function(data) {
						viewTeamRanking('Team', data);
						data_rank_team = true;
						console.log(menu+' load');
					});
				}
				break;
			case 'Pitcher' :
				if (!data_rank_pitcher.era)
				{
					$.getJSON( PBAPI, {
						m: "getPitcherrank",
						sort: "era"
					}).done(function(data) {
						viewPitcherRanking('era', data);
						data_rank_pitcher.era = data;
						console.log(menu+' load');
					});
				}
				break;
			case 'Fielder' :
					$.getJSON( PBAPI, {
						m: "getFielderrank",
						sort: "avg"
					}).done(function(data) {
						viewFielderRanking('avg', data);
						data_rank_fielder.avg = data;
						console.log(menu+' load');
					});
				break;
		}
	});

	$('#pitcher_rank_nav .menu').click(function(){
		$('#pitcher_rank_nav').find('a').each(function(){
			$(this).removeClass('on');
		});
		$(this).addClass('on');
		var menu = $(this).attr('title');
		$('#Pitcher_box').show();
		if (!data_rank_pitcher[menu])
		{
			$.getJSON( PBAPI, {
				m: "getPitcherrank",
				sort: menu
			}).done(function(data) {
				viewPitcherRanking(menu, data);
				data_rank_pitcher[menu] = data;
				console.log(menu+'load');
			});
		}
		else
			viewPitcherRanking(menu, data_rank_pitcher[menu]);
		
	});

	$('#fielder_rank_nav .menu').click(function(){
		$('#fielder_rank_nav').find('a').each(function(){
			$(this).removeClass('on');
		});
		$(this).addClass('on');
		var menu = $(this).attr('title');
		$('#Fielder_box').show();
		if (!data_rank_fielder[menu])
		{
			$.getJSON( PBAPI, {
				m: "getFielderrank",
				sort: menu
			}).done(function(data) {
				viewFielderRanking(menu, data);
				data_rank_fielder[menu] = data;
				console.log(menu+' load');
			});
		}
		else
			viewFielderRanking(menu, data_rank_fielder[menu]);
		
	});

	$('#players_nav .menu').click(function(){
		$('#players_nav').find('a').each(function(){
			$(this).removeClass('on');
		});
		$(this).addClass('on');
		var team = $(this).attr('title');
		$('#Players_box').show();
		if (!data_players[team])
		{
			$.getJSON( PBAPI, {
				m: "getPlayers",
				team: team
			}).done(function(data) {
				viewPlayers(team, data);
				data_players[team] = data;
				console.log(team +' load' + data);
			});
		}
		else
			viewPlayers(team, data_players[team]);
		
	});

	$.getJSON( PBAPI, {
		m: "main"
	}).done(function( data ) {
		var d = new Date();
		var h = '<div class="title">' + (d.getMonth() + 1) + '월' + d.getDate() + '일</div><table width="100%" class="score_table">';
		$.each( data, function( key, value ) {
			h += '<tr><td class="logo"><img src="img/' + value.team1 + '.gif" alt="' + value.team_name1 + '" /></td>';
			h += '<td class="score">' + value.score1 + '</td>';
			h += '<td class="vs">' + value.stadium + '</td>';
			h += '<td class="score">' + value.score2 + '</td>';
			h += '<td class="logo"><img src="img/' + value.team2 + '.gif" alt="' + value.team_name2 + '" /></td></tr>';
			h += '<tr><td class="pitcher">' + value.pitcher1 + '</td><td></td><td></td>';
			h += '<td></td><td class="pitcher">' + value.pitcher2 + '</td></tr>';
		});
		h += '</table>';
		$('#Game').append(h);
	});
});
