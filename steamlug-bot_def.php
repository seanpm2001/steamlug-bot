<?php
	error_reporting (E_ALL);
	ini_set ('display_errors', '1');
	date_default_timezone_set ('UTC');
	ini_set ('mbstring.internal_encoding', 'UTF-8');
	mb_internal_encoding ('UTF-8');

/***********************************************/
function ConnectToMySQL ()
/***********************************************/
{
	$GLOBALS['link'] = mysqli_init();
	if ($GLOBALS['link'] == FALSE)
	{
		print ('[FAILED] Could not use mysqli_init!' . "\n");
		exit();
	}
	if (!mysqli_real_connect ($GLOBALS['link'], $GLOBALS['db_host'],
		$GLOBALS['db_user'], $GLOBALS['db_pass'], $GLOBALS['db_dtbs']))
	{
		print ('[FAILED] Error with mysqli_real_connect: ' .
			mysqli_connect_error() . "\n");
		exit();
	}
}
/***********************************************/
function BaseString ($sUrl, $sMethod, $arOauth)
/***********************************************/
{
	$arEntry = array();
	ksort ($arOauth);
	foreach ($arOauth as $key=>$value)
	{
		$arEntry[] = $key . '=' . rawurlencode ($value);
	}
	$sResult = $sMethod . '&' . rawurlencode ($sUrl) .
		'&' . rawurlencode (implode ('&', $arEntry));

	return ($sResult);
}
/***********************************************/
function Authorization ($arOauth)
/***********************************************/
{
	$arEntry = array();
	foreach ($arOauth as $key=>$value)
	{
		$arEntry[] = $key . '="' . rawurlencode ($value) . '"';
	}
	$sResult = 'Authorization: OAuth ' . implode (', ', $arEntry);

	return ($sResult);
}
/***********************************************/
function GetTweetsArray ($sName, $iAmount)
/***********************************************/
{
	$sUrl = 'https://api.twitter.com/1.1/statuses/user_timeline.json';

	$arOauth = array (
		'screen_name' => $sName,
		'count' => $iAmount,
		'oauth_consumer_key' => $GLOBALS['twitter_api_key'],
		'oauth_nonce' => time(),
		'oauth_signature_method' => 'HMAC-SHA1',
		'oauth_token' => $GLOBALS['twitter_oauth_token'],
		'oauth_timestamp' => time(),
		'oauth_version' => '1.0');

	$sBase = BaseString ($sUrl, 'GET', $arOauth);
	$sKey = rawurlencode ($GLOBALS['twitter_api_secret']) .
		'&' . rawurlencode ($GLOBALS['twitter_oauth_secret']);
	$oauth_signature = base64_encode (hash_hmac ('sha1',
		$sBase, $sKey, TRUE));
	$arOauth['oauth_signature'] = $oauth_signature;

	$header = array (Authorization ($arOauth), 'Expect:');
	$options = array (
		CURLOPT_HTTPHEADER => $header,
		CURLOPT_HEADER => FALSE,
		CURLOPT_URL => $sUrl . '?screen_name=' .
			rawurlencode ($sName) . '&count=' . $iAmount,
		CURLOPT_RETURNTRANSFER => TRUE,
		CURLOPT_SSL_VERIFYPEER => FALSE);

	$feed = curl_init();
	curl_setopt_array ($feed, $options);
	$jsn = curl_exec ($feed);
	curl_close ($feed);

	return (json_decode ($jsn, TRUE));
}
/***********************************************/
function TweetsToMySQL ($arTweets)
/***********************************************/
{
	foreach ($arTweets as $key=>$value)
	{
		$tweet_id = $value['id_str'];
		$tweet_date = date ('Y-m-d H:i:s',
			strtotime ($value['created_at'] . 'UTC'));
		$tweet_text = $value['text'];
		$tweet_text = mysqli_real_escape_string ($GLOBALS['link'], $tweet_text);
		$tweet_user = $value['user']['screen_name'];

		$query = "INSERT IGNORE INTO `tweets` VALUES ('" .
			$tweet_id . "', '" .
			$tweet_date . "', '" .
			$tweet_text . "', '" .
			$tweet_user . "');";
		$result = mysqli_query ($GLOBALS['link'], $query);
	}
}
/***********************************************/
function GetPage ($sUrl)
/***********************************************/
{
	$ch = curl_init ($sUrl);
	curl_setopt ($ch, CURLOPT_HTTPGET, TRUE);
	curl_setopt ($ch, CURLOPT_POST, FALSE);
	curl_setopt ($ch, CURLOPT_HEADER, FALSE);
	curl_setopt ($ch, CURLOPT_NOBODY, FALSE);
	curl_setopt ($ch, CURLOPT_VERBOSE, FALSE);
	curl_setopt ($ch, CURLOPT_REFERER, '');
	curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, TRUE);
	curl_setopt ($ch, CURLOPT_MAXREDIRS, 4);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt ($ch, CURLOPT_USERAGENT, $GLOBALS['useragent']);
	$sPage = curl_exec ($ch);

	return ($sPage);
}
/***********************************************/
function Wikipedia ($sSearch)
/***********************************************/
{
	$enWikiS = 'http://en.wikipedia.org/w/api.php?action=opensearch&search=';
	$enWikiE = '&format=xml&limit=10';
	$sPage = GetPage ($enWikiS . rawurlencode ($sSearch) . $enWikiE);
	$xmlpage = simplexml_load_string ($sPage);
	$iResults = count ($xmlpage->Section->Item);
	if ($iResults != 0)
	{
		$arWiki = array();
		$sRest = '';
		for ($iCount = 0; $iCount < $iResults; $iCount++)
		{
			if ($iCount == 0)
			{
				$arWiki['desc'] = $xmlpage->Section->Item[0]->Description;
			} else {
				if ($iCount > 1) { $sRest = $sRest . ', '; }
				$sRest = $sRest . '"' . $xmlpage->Section->Item[$iCount]->Text . '"';
			}
		}
		$arWiki['rest'] = $sRest;
	} else { $arWiki = FALSE; }
	return ($arWiki);
}
/***********************************************/
function GetGroupNews ($sGroup)
/***********************************************/
{
	ini_set ('user_agent', $GLOBALS['useragent']);
	$xmlXML = simplexml_load_file ('http://steamcommunity.com/groups/' .
		$sGroup . '/rss/', NULL, LIBXML_NOCDATA);
	if ($xmlXML === FALSE)
	{
		print ('[ WARN ] Could not retrieve XML data!' . "\n");
	}
	$arXML = json_decode (json_encode ((array)$xmlXML), TRUE);

	return ($arXML['channel']['item']);
}
/***********************************************/
function NewsToMySQL ($arNews, $sGroup)
/***********************************************/
{
	foreach ($arNews as $key=>$value)
	{
		$sTitle = (string)$value['title'];
		$sTitle = mysqli_real_escape_string ($GLOBALS['link'], $sTitle);

		$sText = (string)$value['description'];
		$sText = html_entity_decode (strip_tags ($sText), ENT_QUOTES, 'utf-8');
		$sText = preg_replace ('/\r|\n/', ' ', $sText);
		$sText = preg_replace ('/\s+/', ' ', $sText);
		$sText = mysqli_real_escape_string ($GLOBALS['link'], $sText);

		$sLink = (string)$value['link'];
/***
		$sGuid = (string)$value['guid'];
		if ($sLink != $sGuid)
		{
			print ('[ WARN ] Apparently, link and guid are different!' . "\n");
			print ($sLink . "\n");
			print ($sGuid . "\n");
		}
***/

		$iDateTime = strtotime ((string)$value['pubDate']);
		$sDateTime = date ('Y-m-d H:i:s', $iDateTime);

		/*** Prevent duplicates. Here we do not use INSERT IGNORE ***/
		/*** because of the AUTO_INCREMENT column. ***/
		$query_found = "SELECT COUNT(*) AS found FROM `news` WHERE" .
			" (news_link='" . $sLink . "');";
		$result_found = mysqli_query ($GLOBALS['link'], $query_found);
		$row_found = mysqli_fetch_assoc ($result_found);
		if ($row_found['found'] == 0)
		{
			$query = "INSERT INTO `news` VALUES (NULL, '" .
				$sGroup . "', '" .
				$sTitle . "', '" .
				$sText . "', '" .
				$sLink . "', '" .
				$sDateTime . "');";
			$result = mysqli_query ($GLOBALS['link'], $query);
		}
	}
}
/***********************************************/
function GetEvents ()
/***********************************************/
{
	ini_set ('user_agent', $GLOBALS['useragent']);
	$xmlXML = simplexml_load_file ('https://steamlug.org/feed/events',
		NULL, LIBXML_NOCDATA);
	if ($xmlXML === FALSE)
	{
		print ('[ WARN ] Could not retrieve XML data!' . "\n");
	}
	$arXML = json_decode (json_encode ((array)$xmlXML), TRUE);

	return ($arXML['channel']['item']);
}
/***********************************************/
function EventsToMySQL ($arEvents)
/***********************************************/
{
	foreach ($arEvents as $key=>$value)
	{
		$sTitle = (string)$value['title'];
		$sTitle = mysqli_real_escape_string ($GLOBALS['link'], $sTitle);

		$sText = (string)$value['description'];
		$sText = html_entity_decode (strip_tags ($sText), ENT_QUOTES, 'utf-8');
		$sText = preg_replace ('/\r|\n/', ' ', $sText);
		$sText = preg_replace ('/\s+/', ' ', $sText);
		$sText = mysqli_real_escape_string ($GLOBALS['link'], $sText);

		$sLink = (string)$value['link'];
/***
		$sGuid = (string)$value['guid'];
		if ($sLink != $sGuid)
		{
			print ('[ WARN ] Apparently, link and guid are different!' . "\n");
			print ($sLink . "\n");
			print ($sGuid . "\n");
		}
***/

		$iDateTime = strtotime ((string)$value['pubDate']);
		$sDateTime = date ('Y-m-d H:i:s', $iDateTime);

		$sCategory = (string)$value['category'];

		/*** Prevent duplicates. Here we do not use INSERT IGNORE ***/
		/*** because of the AUTO_INCREMENT column. ***/
		$query_found = "SELECT COUNT(*) AS found FROM `events` WHERE" .
			" (event_link='" . $sLink . "');";
		$result_found = mysqli_query ($GLOBALS['link'], $query_found);
		$row_found = mysqli_fetch_assoc ($result_found);
		if ($row_found['found'] == 0)
		{
			$query = "INSERT INTO `events` VALUES (NULL, '" .
				$sCategory . "', '" .
				$sTitle . "', '" .
				$sText . "', '" .
				$sLink . "', '" .
				$sDateTime . "');";
			$result = mysqli_query ($GLOBALS['link'], $query);
		}
	}
}
/***********************************************/

	ConnectToMySQL();
?>
