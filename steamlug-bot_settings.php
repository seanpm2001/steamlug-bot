<?php
	/*** Database. ***/
	$GLOBALS['db_host'] = '127.0.0.1';
	$GLOBALS['db_user'] = 'root';
	$GLOBALS['db_pass'] = '';
	$GLOBALS['db_dtbs'] = 'steamlug-bot';

	/*** Markup. ***/
	$GLOBALS['bold'] = chr(0x02);
	$GLOBALS['lightgrey'] = chr(0x03) . '15';
	$GLOBALS['italic'] = chr(0x09);
	$GLOBALS['reset'] = chr(0x0f);
	$GLOBALS['strike'] = chr(0x13);
	$GLOBALS['underline'] = chr(0x15);
	$GLOBALS['reverse'] = chr(0x16);

	/*** Twitter API: http://dev.twitter.com/apps ***/
	$GLOBALS['twitter_oauth_token'] = 'SECRET';
	$GLOBALS['twitter_oauth_secret'] = 'SECRET';
	$GLOBALS['twitter_api_key'] = 'SECRET';
	$GLOBALS['twitter_api_secret'] = 'SECRET';

	$GLOBALS['irc_host'] = '130.239.18.172';
	$GLOBALS['irc_port'] = 6667;
	$GLOBALS['botdesc'] = 'SteamLUG bot';
	$GLOBALS['botversion'] = '0.08';
	$GLOBALS['botsource'] = 'https://github.com/SteamLUG/steamlug-bot';
	$GLOBALS['idlesince'] = time();
	$GLOBALS['twitter_names'] = array ('SteamLUG');
	$GLOBALS['steam_groups'] = array ('steamlug');
	$GLOBALS['maxlinelength'] = 520;
	$GLOBALS['useragent'] = 'Mozilla/5.0 (X11; Ubuntu; Linux i686;' .
		' rv:28.0) Gecko/20100101 Firefox/28.0';
	$GLOBALS['maxtitle'] = 200;
	$GLOBALS['maxnews'] = 200;
	$GLOBALS['maxevent'] = 200;
	$GLOBALS['ignore'] = array ('SteamDB', 'travis-ci', 'gitmek');
	$GLOBALS['link'] = FALSE;
	$GLOBALS['socket'] = FALSE;
	$GLOBALS['botnametemp'] = 't87878787';

	$GLOBALS['debug'] = 0; /*** Set to 1 when debugging; #botwar. ***/

	if ($GLOBALS['debug'] == 1)
	{
		$GLOBALS['botname'] = 'defend';
		$GLOBALS['password'] = 'SECRET';
		$GLOBALS['channel'] = '#botwar';
		$GLOBALS['hascloak'] = 0;
	} else {
		$GLOBALS['botname'] = 'steamlug-bot';
		$GLOBALS['password'] = 'SECRET';
		$GLOBALS['channel'] = '#steamlug';
		$GLOBALS['hascloak'] = 1;
	}
?>