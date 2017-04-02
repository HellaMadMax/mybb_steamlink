<?php 
if ( !defined("IN_MYBB") )
	die( "Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined." );

function steam_convert64to32( $steamid_64 ) {
	$id = array( "STEAM_0" );
	$id[1] = substr( $steamid_64, -1, 1 ) % 2 == 0 ? 0 : 1;
	$id[2] = bcsub( $steamid_64, "76561197960265728" );
	if ( bccomp($id[2], "0") != 1 ) {
		return false;
	}
	$id[2] = bcsub( $id[2], $id[1] );
	list( $id[2], ) = explode( ".", bcdiv($id[2], 2), 2 );
	return implode( ":", $id );
}

function steam_convert64toMini( $steamid_64 ) {
	return floor( bcsub($steamid_64, "76561197960265728") );
}

function steamlink_info() {
	global $db, $mybb, $settings, $lang, $templates, $theme;
	$lang->load( "steamlink" );
	return array(
		"name"			=> "Steam Link",
		"description"	=> $lang->steamlink_description,
		"website"		=> "https://gitlab.com/HellaMadMax/mybb_steamlink",
		"author"		=> "HellaMadMax",
		"version"		=> "0.3.1",
		"compatibility" => "18*"
	);
}

function steamlink_install() {
	global $db, $mybb, $settings, $lang, $templates, $theme;
	if ( !$db->field_exists("steamid", "users") ) {
		$db->write_query( "ALTER TABLE ".TABLE_PREFIX."users ADD steamid VARCHAR(17) NULL UNIQUE AFTER username;" );
	}
	if ( !$db->field_exists("steamid", "sessions") ) {
		$db->write_query( "ALTER TABLE ".TABLE_PREFIX."sessions ADD steamid VARCHAR(17) NULL AFTER uid;" );
	}
}

function steamlink_is_installed() {
	global $db, $mybb, $settings, $lang, $templates, $theme;
	return ( $db->field_exists("steamid", "users") and $db->field_exists("steamid", "sessions") );
}

function steamlink_uninstall() {
	global $db, $mybb, $settings, $lang, $templates, $theme;
	if ( $db->field_exists("steamid", "users") ) {
		$db->write_query( "ALTER TABLE ".TABLE_PREFIX."users drop steamid;" );
	}
	if ( !$db->field_exists("steamid", "sessions") ) {
		$db->write_query( "ALTER TABLE ".TABLE_PREFIX."sessions drop steamid;" );
	}
}

function steamlink_activate() {
	global $db, $mybb, $settings, $lang, $templates, $theme;
	$lang->load( "steamlink" );
	require_once MYBB_ROOT."inc/adminfunctions_templates.php";
	$templates = [
"headerlogin" =>
'<input type="submit" class="button steamlink_login" name="steam" value="1">',

"register_linked" =>
'<tr><td colspan="2">
	<span class="smalltext"><label for="username">{$lang->steam_account}</label></span>
</td></tr>
<tr><td colspan="2">
	<a href="https://steamcommunity.com/profiles/{$steamid}" target="_blank">{$steamid32}</a>
</td></tr>',

"register_unlinked" =>
'<tr><td colspan="2">
	<span class="smalltext"><label for="username">{$lang->steam_account}</label></span>
</td></tr>
<tr><td colspan="2">
	<a href="{$mybb->settings[\'bburl\']}/member.php?action=register&steam=1" class="steamlink_login"></a>
</td></tr>',

"linkrequired" =>
'<div class="red_alert">{$lang->steam_linkrequired}</div>',

"dolink" =>
'<html>
<head>
<title>{$mybb->settings[\'bbname\']} - Steam Link</title>
{$headerinclude}
</head>
<body>
{$header}
{$steamlink_linkrequired}
{$linkerrors}
<form method="get">
	<input type="hidden" name="steamlink" value="1">
	{$getparms}
	<div style="text-align: center;">
		<input type="submit" class="button steamlink_login" name="steam" value="1">
	</div>
</form>
{$footer}
</body>
</html>',

"profileblock_linked" =>
'<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder tfixed">
	<colgroup>
		<col style="width: 30%;">
	</colgroup>
	<tr>
		<td colspan="2" class="thead">
			<strong>{$lang->users_steam_info}</strong>
		</td>
	</tr>
	<tr>
		<td class="trow1">
			<strong>{$lang->steamid_64}</strong>
		</td>
		<td class="trow1">{$memprofile[\'steamid\']}</td>
	</tr>
	<tr>
		<td class="trow1">
			<strong>{$lang->steamid_32}</strong>
		</td>
		<td class="trow1">{$steamid32}</td>
	</tr>
	<tr>
		<td class="trow1">
			<strong>{$lang->profile_link}</strong>
		</td>
		<td class="trow1">
			<a href="https://steamcommunity.com/profiles/{$memprofile[\'steamid\']}" target="_blank" class="steamlink_badge">
				<div></div>
				<iframe src="https://steamcommunity.com/miniprofile/{$steamidMini}?client=1" scrolling="no"></iframe>
			</a>
		</td>
	</tr>
</table><br/>',

"profileblock_unlinked" =>
'<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
	<tr>
		<td colspan="2" class="thead">
			<strong>{$lang->users_steam_info}</strong>
		</td>
	</tr>
	<tr>
		<td class="trow1" style="text-align: center;">
			<strong>{$lang->not_linked}</strong>
		</td>
	</tr>
</table><br/>',

"postbit" =>
'<a href="https://steamcommunity.com/profiles/{$post[\'steamid\']}" target="_blank" class="steamlink_badge">
	<div></div>
	<iframe src="https://steamcommunity.com/miniprofile/{$steamidMini}?client=1" scrolling="no"></iframe>
</a>'
	];	
	foreach ( $templates as $title => $template ) {
		$db->insert_query( "templates", array(
			"title" => "steamlink_".$title,
			"template" => $db->escape_string( $template ),
			"sid" => "-1"
		) );
	}

	$stylesheet =
'.steamlink_login {
    background: transparent url(https://steamcommunity-a.akamaihd.net/public/images/signinthroughsteam/sits_small.png) no-repeat 1px !important;
    color: transparent !important;
    font-weight: bold;
    width: 156px;
    border: 0px !important;
    height: 23px;
}

.steamlink_badge {
    position: relative;
    display: block;
	width: 224px;
	height: 66px;
	border-radius: 4px;
	overflow: hidden;
    max-width: 100%;
	transition: max-width 200ms,height 200ms;
} .steamlink_badge:hover {
	height: 154px;
} .steamlink_badge > div {
	position: absolute;
    height: 100%;
    width: 100%;
    z-index: 1;
} .steamlink_badge > iframe {
	border: 0px;
    z-index: 2;
    transform: scale(0.75);
    transform-origin: 0 0;
	height: 206px;
}

.post:not( .classic ) .steamlink_badge {
	float: left;
    height: 76px;
    width: 256px;
} .post:not( .classic ) .steamlink_badge > iframe {
    transform: scale(0.86);
} .post:not( .classic ) .steamlink_badge:hover {
    height: 176px;
}

.post.classic .post_author {
	overflow: visible;
} .post.classic .steamlink_badge {
	margin-top: 4px;
} .post.classic .steamlink_badge:hover {
	max-width: 224px;
}';
	$db->insert_query( "themestylesheets", array(
		"name" => "steamlink.css",
		"tid" => 1,
		"attachedto" => "",
		"stylesheet" => $db->escape_string( $stylesheet ),
		"cachefile" => "steamlink.css",
		"lastmodified" => TIME_NOW
	) );
	require_once MYBB_ADMIN_DIR."inc/functions_themes.php";
	cache_stylesheet( 1, "steamlink.css", $stylesheet );
	update_theme_stylesheet_list( 1, false, true );
	
	$settinggroup = array(
		"name" => "steamlink",
		"title" => $lang->steamlink_settings,
		"description" => $lang->steamlink_settings_desc
	);
	$query = $db->simple_select( "settinggroups", "gid", "name='".$settinggroup["name"]."'" );
	if ( $gid = (int)$db->fetch_field($query, "gid") ) {
		$db->update_query( "settinggroups", $settinggroup, "gid='{$gid}'" );
	} else {
		$query = $db->simple_select( "settinggroups", "MAX(disporder) AS disporder" );
		$disporder = (int)$db->fetch_field( $query, "disporder" );
		$settinggroup["disporder"] = ++$disporder;
		$gid = (int)$db->insert_query( "settinggroups", $settinggroup );
	}
	$settings_array = array(
		"regforcelink" => array(
			"title" => $lang->steamlink_regforcelink,
			"description" => $lang->steamlink_regforcelink_desc,
			"optionscode" => "yesno",
			"value" => 1,
			"disporder" => 1
		),
		"exforcelink" => array(
			"title" => $lang->steamlink_exforcelink,
			"description" => $lang->steamlink_exforcelink_desc,
			"optionscode" => "yesno",
			"value" => 1,
			"disporder" => 2
		),
		"allowlogin" => array(
			"title" => $lang->steamlink_allowlogin,
			"description" => $lang->steamlink_allowlogin_desc,
			"optionscode" => "yesno",
			"value" => 1,
			"disporder" => 3
		),
		"profileinfo" => array(
			"title" => $lang->steamlink_profileinfo,
			"description" => $lang->steamlink_profileinfo_desc,
			"optionscode" => "yesno",
			"value" => 1,
			"disporder" => 4
		),
		"postbitinfo" => array(
			"title" => $lang->steamlink_postbitinfo,
			"description" => $lang->steamlink_postbitinfo_desc,
			"optionscode" => "yesno",
			"value" => 1,
			"disporder" => 5
		)
	);
	foreach( $settings_array as $name => $setting ) {
		$setting["name"] = "steamlink_".$name;
		$setting["title"] = $db->escape_string( $setting["title"] );
		$setting["description"] = $db->escape_string( $setting["description"] );
		$setting["gid"] = $gid;
		$db->insert_query( "settings", $setting );
	}
	rebuild_settings();

	find_replace_templatesets(
		"header_welcomeblock_guest",
		"#".preg_quote('<input name="submit" type="submit" class="button" value="{$lang->login}" />')."#i",
		'<input name="submit" type="submit" class="button" value="{$lang->login}" />{$steamlink_headerlogin}'
	);
	find_replace_templatesets(
		"member_login", "#".preg_quote('<input type="submit" class="button" name="submit" value="{$lang->login}" />')."#i",
		'<input type="submit" class="button" name="submit" value="{$lang->login}" />{$steamlink_headerlogin}'
	);
	find_replace_templatesets(
		"member_register", "#".preg_quote('{$passboxes}')."#i",
		'{$steamlink_register}{$passboxes}'
	);
	find_replace_templatesets(
		"header", "#".preg_quote('<navigation>')."#i",
		'{$steamlink_linkrequired}<navigation>'
	);
	find_replace_templatesets(
		"member_profile", "#".preg_quote('{$signature}')."#i",
		'{$steamlink_profileblock}{$signature}'
	);
	find_replace_templatesets(
		"postbit", "#".preg_quote('<div class="author_statistics">')."#i",
		'{$post[\'steamlink\']}<div class="author_statistics">'
	);
	find_replace_templatesets(
		"postbit_classic", "#".preg_quote('<div class="author_statistics">')."#i",
		'{$post[\'steamlink\']}<div class="author_statistics">'
	);
}

function steamlink_deactivate() {
	global $db, $mybb, $settings, $lang, $templates, $theme;
	require_once MYBB_ROOT."inc/adminfunctions_templates.php";
	find_replace_templatesets(
		"header_welcomeblock_guest",
		"#".preg_quote('{$steamlink_headerlogin}')."#i",
		""
	);
	find_replace_templatesets(
		"member_login",
		"#".preg_quote('{$steamlink_headerlogin}')."#i",
		""
	);
	find_replace_templatesets(
		"member_register",
		"#".preg_quote('{$steamlink_register}')."#i",
		""
	);
	find_replace_templatesets(
		"header",
		"#".preg_quote('{$steamlink_linkrequired}')."#i",
		""
	);
	find_replace_templatesets(
		"member_profile",
		"#".preg_quote('{$steamlink_profileblock}')."#i",
		""
	);
	find_replace_templatesets(
		"postbit",
		"#".preg_quote('{$post[\'steamlink\']}')."#i",
		""
	);
	find_replace_templatesets(
		"postbit_classic",
		"#".preg_quote('{$post[\'steamlink\']}')."#i",
		""
	);
	$db->delete_query( "templates", "title LIKE 'steamlink_%' AND sid='-1'" );
	$db->delete_query( "themestylesheets", "name='steamlink.css'" );
	$query = $db->simple_select( "themes", "tid" );
	while( $tid = $db->fetch_field($query, "tid") ) {
		@unlink( MYBB_ROOT."cache/themes/theme{$tid}/steamlink.css" );
	}
	require_once MYBB_ADMIN_DIR."inc/functions_themes.php";
	update_theme_stylesheet_list( 1, false, true );
	$db->delete_query( "settinggroups", "name='steamlink'" );
	$db->delete_query( "settings", "name LIKE 'steamlink_%'" );
	rebuild_settings();
}

$plugins->add_hook( "global_start", "steamlink_templates" );
function steamlink_templates() {
	global $db, $mybb, $settings, $lang, $templates, $theme, $templatelist;
	if ( isset($templatelist) ) {
		$templatelist .= ",";
	}
	$templatelist .= "steamlink_headerlogin,steamlink_linkrequired,steamlink_dolink";
	$templatearray = explode( ",", $templatelist );
	if ( in_array("member_register", $templatearray) ) {
		$templatelist .= ",steamlink_register_linked,steamlink_register_unlinked";
	}
	if ( in_array("member_profile", $templatearray) and $settings["steamlink_profileinfo"] ) {
		$templatelist .= ",steamlink_profileblock_linked,steamlink_profileblock_unlinked";
	}
	if ( in_array("postbit", $templatearray) and $settings["steamlink_postbitinfo"] ) {
		$templatelist .= ",steamlink_postbit";
	}
}

$plugins->add_hook( "global_intermediate", "steamlink_global" );
function steamlink_global() {
	global $db, $mybb, $settings, $lang, $templates, $theme, $steamlink_linkrequired, $steamlink_headerlogin, $fromreg;
	$lang->load( "steamlink" );
	if ( $settings["steamlink_allowlogin"] ) {
		eval( "\$steamlink_headerlogin = \"".$templates->get("steamlink_headerlogin")."\";" );
	}
	if ( THIS_SCRIPT === "member.php" and $mybb->input["action"] === "register" and $mybb->input["steam"] ) {
		$mybb->request_method = "post";
		$fromreg = 1;
	} elseif ( THIS_SCRIPT === "member.php" and $mybb->input["action"] === "do_login" and $mybb->input["steam"] ) {
		$mybb->input["action"] = "login";
	}
}

$plugins->add_hook( "member_register_start", "steamlink_register" );
function steamlink_register() {
	global $db, $mybb, $settings, $lang, $templates, $theme, $errors, $regerrors, $steamlink_register;
	if ( $mybb->get_input("steam", MyBB::INPUT_INT) === 1 ) {
		require_once MYBB_ROOT.'inc/plugins/steamlink/openid.php';
		$openid = new LightOpenID( parse_url($settings["bburl"], PHP_URL_HOST) );
		if ( $openid->mode and $validate = $openid->validate() ) {
			$steamid = substr( $openid->identity, strlen("http://steamcommunity.com/openid/id/") );
			$user = $db->fetch_array( $db->simple_select("users", "*", "steamid='".$steamid."'") );
			if ( $user ) {
				$errors[] = $lang->sprintf( $lang->steam_regexists, build_profile_link(format_name($user["username"], $user["usergroup"], $user["displaygroup"]), $user["uid"]) );
			} else {
				$db->update_query( "sessions", array("steamid" => $steamid), "sid='".$mybb->session->sid."'" );
			}
		} elseif ( $validate === false ) {
			$errors[] = $lang->steam_authfail;
		} else {
			$openid->identity = "http://steamcommunity.com/openid";
			redirect( $openid->authUrl(), $lang->steam_redirect );
		}
	} else {
		$steamid = $db->fetch_field( $db->simple_select("sessions", "steamid", "sid = '".$mybb->session->sid."'"), "steamid" );
		if ( !$steamid and $settings["steamlink_regforcelink"] ) {
			header( "Location: member.php?action=register&steam=1" );
			exit;
		}
	}
	if ( isset($steamid) ) {
		$steamid32 = steam_convert64to32( $steamid );
		$steamidMini = steam_convert64toMini( $steamid );
	}
	eval( "\$steamlink_register = \"".$templates->get("steamlink_register_".($steamid32 ? "linked" : "unlinked"))."\";" );
	if ( $errors ) {
		$regerrors = inline_error( $errors );
	}
}

$plugins->add_hook( "datahandler_user_validate", "steamlink_user_validate" );
function steamlink_user_validate( &$dh ) {
	global $db, $mybb, $settings, $lang, $templates, $theme;
	if ( THIS_SCRIPT === "member.php" and $mybb->input["action"] == "do_register" and $mybb->request_method == "post" ) {
		$steamid = $db->fetch_field( $db->simple_select("sessions", "steamid", "sid = '".$mybb->session->sid."'"), "steamid" );
		if ( $steamid ) {
			$user = $db->fetch_array( $db->simple_select("users", "*", "steamid='".$steamid."'") );
			if ( $user ) {
				$dh->set_error( $lang->sprintf($lang->steam_regexists, build_profile_link(format_name($user["username"], $user["usergroup"], $user["displaygroup"]), $user["uid"])) );
			} else {
				$dh->data["steamid"] = $steamid;
			}
		} elseif ( $settings["steamlink_regforcelink"] ) {
			$dh->set_error( $lang->steam_linkrequired );
		}
	}
}

$plugins->add_hook( "datahandler_user_insert", "steamlink_user_insert" );
function steamlink_user_insert( &$dh ) {
	global $db, $mybb, $settings, $lang, $templates, $theme;
	if ( $dh->data["steamid"] ) {
		$dh->user_insert_data["steamid"] = $dh->data["steamid"];
		$db->update_query( "sessions", array("steamid" => NULL), "sid = '".$mybb->session->sid."'" );
	}
}

$plugins->add_hook( "global_end", "steamlink_dolink" );
function steamlink_dolink() {
	global $db, $mybb, $settings, $lang, $templates, $theme, $headerinclude, $header, $footer;
	if ( $mybb->get_input("steamlink", MyBB::INPUT_INT) === 1 ) {
		require_once MYBB_ROOT.'inc/plugins/steamlink/openid.php';
		$openid = new LightOpenID( parse_url($settings["bburl"], PHP_URL_HOST) );
		if ( $openid->mode and $validate = $openid->validate() ) {
			$steamid = substr( $openid->identity, strlen("http://steamcommunity.com/openid/id/") );
			$user = $db->fetch_array( $db->simple_select("users", "*", "steamid='".$steamid."'") );
			if ( $mybb->user["uid"] == 0 ) { // not logged in
				$errors[] = $lang->steam_linkguest;
			} elseif ( $mybb->user["steamid"] ) { // forum account already linked
				$errors[] = $lang->sprintf( $lang->steam_linktwice, $mybb->user["steamid"], steam_convert64to32($mybb->user["steamid"]) );
			} elseif ( $user ) { // steam account already linked
				$errors[] = $lang->sprintf( $lang->steam_linkexists, build_profile_link(format_name($user["username"], $user["usergroup"], $user["displaygroup"]), $user["uid"]) );
			} else {
				$db->update_query( "users", array("steamid" => $steamid), "uid='".$mybb->user["uid"]."'" );
				$mybb->user["steamid"] = $steamid;
				$redirect_url = $_SERVER["PHP_SELF"];
				foreach( $_GET as $name => $value ) {
					if ( strpos($name, "openid_") === 0 or in_array($name, ["steamlink", "x", "y"]) ) {
						unset( $_GET[$name] );
					}
				}
				if ( !empty($_GET) ) {
					$redirect_url .= "?" . http_build_query($_GET);
				}
				parse_str( parse_url($redirect_url)["query"], $query );
				if ( !empty($redirect_url) and (my_strpos(basename($redirect_url), "member.php") === false or $query["action"] == "profile") and !preg_match('#^javascript:#i', $redirect_url)) {
					if ( (my_strpos(basename($redirect_url), "newthread.php") !== false or my_strpos(basename($redirect_url), "newreply.php") !== false) and my_strpos($redirect_url, "&processed=1") !== false ) {
						$redirect_url = str_replace( "&processed=1", "", $redirect_url );
					}
					$redirect_url = str_replace( "&amp;", "&", $redirect_url );
					redirect( htmlentities($redirect_url), $lang->steam_linked . $lang->redirect_from );
				} else {
					redirect( "index.php", $lang->steam_linked . $lang->redirect_index );
				}
			}
		} elseif ( $validate === false ) {
			$errors[] = $lang->steam_authfail;
		} else {
			$openid->identity = "http://steamcommunity.com/openid";
			redirect( $openid->authUrl(), $lang->steam_redirect );
		}
	}
	if ( ($mybb->user["uid"] and !$mybb->user["steamid"] and $settings["steamlink_exforcelink"]) or $errors ) {
		if ( !(THIS_SCRIPT == "member.php" and $mybb->input["action"] == "logout")
		and !(THIS_SCRIPT == "misc.php" and $mybb->input["action"] == "buddypopup") ) {
			if ( $settings["steamlink_exforcelink"] ) {
				eval( "\$steamlink_linkrequired = \"".$templates->get("steamlink_linkrequired")."\";" );
			}
			if ( $errors ) {
				$linkerrors = inline_error( $errors );
			}
			// add the original GET paramaters so we end up back at the same place
			$getparms = "";
			foreach( $_GET as $name => $value ) {
				if ( strpos($name, "openid_") === 0 or in_array($name, ["steamlink", "x", "y"]) ) {
					continue;
				}
				$getparms .= '<input type="hidden" name="'. htmlspecialchars_uni($name) .'" value="'. htmlspecialchars_uni($value) .'">';
			}
			eval( "\$dolink = \"".$templates->get("steamlink_dolink")."\";" );
			output_page( $dolink );
			exit;
		}
	}
}

$plugins->add_hook( "member_login", "steamlink_login" );
function steamlink_login() {
	global $db, $mybb, $settings, $lang, $templates, $theme, $errors;
	if ( $mybb->get_input("steam", MyBB::INPUT_INT) === 1 ) {
		require_once MYBB_ROOT.'inc/plugins/steamlink/openid.php';
		$openid = new LightOpenID( parse_url($settings["bburl"], PHP_URL_HOST) );
		if ( $openid->mode and $validate = $openid->validate() ) {
			$steamid = substr( $openid->identity, strlen("http://steamcommunity.com/openid/id/") );
			$user = $db->fetch_array( $db->simple_select("users", "*", "steamid='".$steamid."'") );
			if ( !$user ) {
				$errors[] = $lang->steam_notlinked;
			} elseif ( !$settings["steamlink_allowlogin"] ) {
				$errors[] = $lang->steam_nologin;
			} else {
				my_setcookie( "loginattempts", 1 );
				my_setcookie( "sid", $mybb->session->sid, -1, true );
				$db->delete_query( "sessions", "ip = ".$db->escape_binary($mybb->session->packedip)." AND sid != '".$mybb->session->sid."'" );
				$db->update_query( "sessions", array("uid" => $user["uid"], "steamid" => NULL), "sid = '".$mybb->session->sid."'" );
				$db->update_query( "users", array("loginattempts" => 1), "uid = '".$user["uid"]."'" );
				$remember = null;
				if ( !isset($mybb->input["remember"]) or $mybb->input["remember"] != "yes" ) {
					$remember = -1;
				}
				my_setcookie( "mybbuser", $user["uid"]."_".$user["loginkey"], $remember, true );
				$mybb->input["url"] = $mybb->get_input( "url" );
				parse_str( parse_url($mybb->input["url"])["query"], $query );
				if ( !empty($mybb->input["url"]) and (my_strpos(basename($mybb->input["url"]), "member.php") === false or $query["action"] == "profile") and !preg_match('#^javascript:#i', $mybb->input["url"])) {
					if ( (my_strpos(basename($mybb->input["url"]), "newthread.php") !== false or my_strpos(basename($mybb->input["url"]), "newreply.php") !== false) and my_strpos($mybb->input["url"], "&processed=1") !== false ) {
						$mybb->input["url"] = str_replace( "&processed=1", "", $mybb->input["url"] );
					}
					$mybb->input["url"] = str_replace( "&amp;", "&", $mybb->input["url"] );
					redirect( htmlentities($mybb->input["url"]), $lang->steam_loggedin . $lang->redirect_from );
				} else {
					redirect( "index.php", $lang->steam_loggedin . $lang->redirect_index );
				}
			}
		} elseif ( $validate === false ) {
			$errors[] = $lang->steam_authfail;
		} else {
			$openid->identity = "http://steamcommunity.com/openid";
			$parms = [];
			$parms["action"] = "login";
			$parms["steam"] = 1;
			if ( $mybb->input["url"] ) {
				$parms["url"] = $mybb->input["url"];
			}
			if ( $mybb->input["remember"] == "yes" or $mybb->input["quick_remember"] == "yes" ) {
				$parms["remember"] = "yes";
			}
			$openid->returnUrl = $openid->trustRoot . strtok( $_SERVER["REQUEST_URI"], "?" ) . "?" . http_build_query( $parms );
			redirect( $openid->authUrl(), $lang->steam_redirect );
		}
	}
}

$plugins->add_hook( "member_profile_start", "steamlink_user_profile" );
function steamlink_user_profile() {
	global $db, $mybb, $settings, $lang, $templates, $theme, $steamlink_profileblock;
	if ( !$settings["steamlink_profileinfo"] ) {
		return;
	}
	$uid = $mybb->input["uid"];
	if ( $uid ) {
		$memprofile = get_user( $uid );
	} elseif( $mybb->user["uid"] ) {
		$memprofile = $mybb->user;
	}
	if ( !$memprofile ) {
		return;
	}

	if ( isset($memprofile["steamid"]) ) {
		$steamid32 = steam_convert64to32( $memprofile["steamid"] );
		$steamidMini = steam_convert64toMini( $memprofile["steamid"] );
	}
	$lang->users_steam_info = $lang->sprintf( $lang->users_steam_info, $memprofile["username"] );
	eval( "\$steamlink_profileblock = \"".$templates->get("steamlink_profileblock_".($steamid32 ? "linked" : "unlinked"))."\";" );
}

$plugins->add_hook( "postbit", "steamlink_postbit" );
$plugins->add_hook( "postbit_prev", "steamlink_postbit" );
$plugins->add_hook( "postbit_pm", "steamlink_postbit" );
$plugins->add_hook( "postbit_announcement", "steamlink_postbit" );
function steamlink_postbit( &$post ) {
	global $db, $mybb, $settings, $lang, $templates, $theme;
	if ( !$settings["steamlink_postbitinfo"] ) {
		return;
	}
	if ( isset($post["steamid"]) ) {
		$steamid32 = steam_convert64to32( $post["steamid"] );
		$steamidMini = steam_convert64toMini( $post["steamid"] );
		eval("\$post['steamlink'] = \"".$templates->get("steamlink_postbit")."\";");
	}
}
