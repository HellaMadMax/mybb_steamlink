<?php 
if ( !defined("IN_MYBB") )
	die( "Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined." );

function steamlink_info() {
	return array(
		"name"			=> "Steam Link (Edited from Steam Login)",
		"description"	=> "Allows members to link their steam account and (optionally) login with it.",
		"website"		=> "N/A",
		"author"		=> "HellaMadMax (Steam Login by Ryan Stewart)",
		"authorsite"	=> "https://hellamad.ga",
		"version"		=> "0.1",
		"guid" 			=> "",
		"compatibility" => "18*"
	);
}

function steamlink_install() {
	global $db;
	if ( !$db->field_exists("steamid", "users") ) {
		$db->write_query( "ALTER TABLE ".TABLE_PREFIX."users ADD steamid VARCHAR(17) NULL UNIQUE AFTER username;" );
	}
	if ( !$db->field_exists("steamid", "sessions") ) {
		$db->write_query( "ALTER TABLE ".TABLE_PREFIX."sessions ADD steamid VARCHAR(17) NULL AFTER uid;" );
	}
}

function steamlink_is_installed() {
	global $db;
	return ( $db->field_exists("steamid", "users") and $db->field_exists("steamid", "sessions") );
}

function steamlink_uninstall() {
	global $db;
	if ( $db->field_exists("steamid", "users") ) {
		$db->write_query( "ALTER TABLE ".TABLE_PREFIX."users drop steamid;" );
	}
	if ( !$db->field_exists("steamid", "sessions") ) {
		$db->write_query( "ALTER TABLE ".TABLE_PREFIX."sessions drop steamid;" );
	}
}

function steamlink_activate() {
	global $db, $mybb;
	require_once MYBB_ROOT."inc/adminfunctions_templates.php";
	find_replace_templatesets(
		"header_welcomeblock_guest",
		"#".preg_quote('<input name="submit" type="submit" class="button" value="{$lang->login}" />')."#i",
		'<input name="submit" type="submit" class="button" value="{$lang->login}" />{$steamlink_header_login}'
	);
	find_replace_templatesets(
		"member_login", "#".preg_quote('<input type="submit" class="button" name="submit" value="{$lang->login}" />')."#i",
		'<input type="submit" class="button" name="submit" value="{$lang->login}" />{$steamlink_header_login}'
	);
	find_replace_templatesets(
		"member_profile", "#".preg_quote('{$signature}')."#i",
		'{$steamlink_profile_block}{$signature}'
	);
	find_replace_templatesets(
		"postbit", "#".preg_quote('<div class="author_statistics">')."#i",
		'{$post[\'steamlink\']}<div class="author_statistics">'
	);
	find_replace_templatesets(
		"postbit_classic", "#".preg_quote('<div class="author_statistics">')."#i",
		'{$post[\'steamlink\']}<div class="author_statistics">'
	);
	$template = ' OR <a href="{$mybb->settings[\'bburl\']}/member.php?action=login&steam=1">
	<img src="https://steamcommunity-a.akamaihd.net/public/images/signinthroughsteam/sits_small.png" style="vertical-align: middle">
</a>';
	$plugin_templates = array(
		"title" => "steamlink_header_login",
		"template" => $db->escape_string( $template ),
		"sid" => "-1"
	);
	$db->insert_query( "templates", $plugin_templates );
	
	$template = '<tr><td colspan="2">
	<span class="smalltext"><label for="username">Steam Account:</label></span>
</td></tr>
<tr><td colspan="2">
	<a href="https://steamcommunity.com/profiles/{$steamid}" target="_blank">{$steamid32}</a>
</td></tr>';
	$plugin_templates = array(
		"title" => "steamlink_register",
		"template" => $db->escape_string( $template ),
		"sid" => "-1"
	);
	$db->insert_query( "templates", $plugin_templates );
	$template = '<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder tfixed">
	<colgroup>
		<col style="width: 30%;">
	</colgroup>
	<tr>
		<td colspan="2" class="thead">
			<strong>{$memprofile[\'username\']}\'s Steam Info</strong>
		</td>
	</tr>
	<tr>
		<td class="trow1">
			<strong>SteamID 64:</strong>
		</td>
		<td class="trow1">{$memprofile[\'steamid\']}</td>
	</tr>
	<tr>
		<td class="trow1">
			<strong>SteamID 32:</strong>
		</td>
		<td class="trow1">{$steamid32}</td>
	</tr>
	<tr>
		<td class="trow1">
			<strong>Profile Link:</strong>
		</td>
		<td class="trow1">
			<div class="steam_badge">
				<a href="https://steamcommunity.com/profiles/{$memprofile[\'steamid\']}" target="_blank"><img src="https://steamsignature.com/profile/english/{$memprofile[\'steamid\']}.png"></a>
			</div>
		</td>
	</tr>
</table><br/>';
	$plugin_templates = array(
		"title" => "steamlink_profile_block_linked",
		"template" => $db->escape_string( $template ),
		"sid" => "-1"
	);
	$db->insert_query( "templates", $plugin_templates );
	
	$template = '<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
	<tr>
		<td colspan="2" class="thead">
			<strong>{$memprofile[\'username\']}\'s Steam Info</strong>
		</td>
	</tr>
	<tr>
		<td class="trow1" style="text-align: center;">
			<strong>Not Linked</strong>
		</td>
	</tr>
</table><br/>';
	$plugin_templates = array(
		"title" => "steamlink_profile_block_unlinked",
		"template" => $db->escape_string( $template ),
		"sid" => "-1"
	);
	$db->insert_query( "templates", $plugin_templates );
	
	$template = '<div class="steam_badge">
	<a href="https://steamcommunity.com/profiles/{$post[\'steamid\']}" target="_blank"><img src="https://steamsignature.com/profile/english/{$post[\'steamid\']}.png"></a>
</div>';
	$plugin_templates = array(
		"title" => "steamlink_postbit",
		"template" => $db->escape_string( $template ),
		"sid" => "-1"
	);
	$db->insert_query( "templates", $plugin_templates );
}

function steamlink_deactivate() {
	global $db;
	require_once MYBB_ROOT."inc/adminfunctions_templates.php";
	find_replace_templatesets(
		"header_welcomeblock_guest",
		"#".preg_quote('{$steamlink_header_login}')."#i",
		""
	);
	find_replace_templatesets(
		"member_login",
		"#".preg_quote('{$steamlink_header_login}')."#i",
		""
	);
	find_replace_templatesets(
		"member_profile",
		"#".preg_quote('{$steamlink_profile_block}')."#i",
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
}

$plugins->add_hook( "global_start", "steamlink_templates" );
function steamlink_templates() {
	global $db, $mybb, $templates, $templatelist;
	$templatelist .= ",steamlink_header_login";
	if ( THIS_SCRIPT === "member.php" ) {
		$templatelist .= ",steamlink_register";
	}
}

$plugins->add_hook( "global_intermediate", "steamlink_login_btn" );
function steamlink_login_btn() {
	global $db, $mybb, $templates, $theme, $steamlink_header_login;
	eval( "\$steamlink_header_login = \"".$templates->get("steamlink_header_login")."\";" );
}

$plugins->add_hook( "global_end", "steamlink_css" );
function steamlink_css() {
	global $db, $mybb, $headerinclude;
	$headerinclude .= "<style>
.steam_badge {
	border-radius: 4px;
} .steam_badge img {
	max-height: 66px;
}

.post:not( .classic ) .steam_badge {
	float: left;
} .post.classic .steam_badge {
	margin-top: 4px;
	overflow: hidden;
} .post.classic .steam_badge img {
	max-width: none;
	max-height: none;
	height: 66px;
}
</style>";
}

$plugins->add_hook( "global_end", "steamlink_forcelink" );
function steamlink_forcelink() {
	global $db, $mybb, $errors;
	if ( $mybb->user["uid"] and !$mybb->user["steamid"] and !$mybb->usergroup["cancp"] ) {
		if ( THIS_SCRIPT != "member.php" or ($mybb->get_input("action") != "login" and $mybb->get_input("action") != "do_login" and $mybb->get_input("action") != "logout" and $mybb->get_input("action") != "profile") ) {
			redirect( "member.php?action=login" );
		} elseif ( $mybb->request_method !== "post" ) {
			$errors[] = "Your account is currently not linked to Steam. Please login with Steam before continuing.";
		}
	}
}

$plugins->add_hook( "member_register_agreement", "steamlink_register_redirect" );
function steamlink_register_redirect() {
	global $db, $mybb;
	$result = $db->fetch_array( $db->simple_select("sessions", "steamid", "sid = '".$mybb->session->sid."'") );
	if ( $result and $result["steamid"] ) {
		return $result["steamid"];
	}
	header( "Location: member.php?action=login&steam=1&register=1" );
	exit;
}

$plugins->add_hook( "member_login", "steamlink_login" );
function steamlink_login() {
	global $db, $mybb, $settings, $errors;
	if ( $mybb->get_input("steam", MyBB::INPUT_INT) === 1 ) {
		require_once MYBB_ROOT.'inc/plugins/steamlink/class_openid.php';
		$openid = new LightOpenID( parse_url($settings["bburl"], PHP_URL_HOST) );
		if ( $openid->mode and $validate = $openid->validate() ) {
			$steamid = substr( $openid->identity, strlen("http://steamcommunity.com/openid/id/") );
			$user = $db->fetch_array( $db->simple_select("users", "*", "steamid='".$steamid."'") );
			if ( !$user ) {
				if ( $mybb->user["uid"] !== 0 and !$mybb->user["steamid"] ) { // We're updating an existing account that isn't linked to Steam
					$db->update_query( "users", array("steamid" => $steamid), "uid='".$mybb->user["uid"]."'" );
					redirect( "index.php", 'You have successfully been authenticated by <a href="https://www.steampowered.com" target="_blank">Steam</a>.<br />You will now be taken back to the forum index.' );
				} else {
					$db->update_query( "sessions", array("steamid" => $steamid), "sid='".$mybb->session->sid."'" );
					redirect( "member.php?action=register", 'You have successfully been authenticated by <a href="https://www.steampowered.com" target="_blank">Steam</a>.<br />You will now be taken back to registering page.' );
				}
			} elseif ( $mybb->get_input("register", MyBB::INPUT_INT) === 1 or ($mybb->user["uid"] !== 0 and !$mybb->user["steamid"]) ) { // We're trying to register or link an existing account
				$errors[] = "You cannot use that Steam account as it is already linked to ".build_profile_link(format_name($user["username"], $user["usergroup"], $user["displaygroup"]), $user["uid"]).".";
			} else {
				my_setcookie( "loginattempts", 1 );
				my_setcookie( "sid", $mybb->session->sid, -1, true );
				$db->delete_query( "sessions", "ip = ".$db->escape_binary($mybb->session->packedip)." AND sid != '".$mybb->session->sid."'" );
				$db->update_query( "sessions", array("uid" => $user["uid"], "steamid" => NULL), "sid = '".$mybb->session->sid."'" );
				$db->update_query( "users", array("loginattempts" => 1), "uid = '".$user["uid"]."'" );
				my_setcookie( "mybbuser", $user["uid"]."_".$user["loginkey"], null, true );
				redirect( "index.php", 'You have successfully been logged in.<br />You will now be taken back to the forum index.' );
			}
		} elseif ( $validate === false ) {
			$errors[] = "You could not be authenticated with OpenID.";
		} else {
			$openid->identity = "http://steamcommunity.com/openid";
			redirect( $openid->authUrl(), "You are being redirected to Steam to confirm your identity." );
		}
	}
}

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

$plugins->add_hook( "member_register_end", "steamlink_register" );
function steamlink_register() {
	global $db, $mybb, $templates, $passboxes;
	$steamid = steamlink_register_redirect();
	$steamid32 = steam_convert64to32( $steamid );
	$passboxes = eval( "return \"".$templates->get("steamlink_register")."\";" ).$passboxes;
}

$plugins->add_hook( "datahandler_user_insert", "steamlink_user_insert" );
function steamlink_user_insert( &$dh ) {
	global $db, $mybb;
	if ( !defined("IN_ADMINCP") ) {
		$result = $db->fetch_array( $db->simple_select("sessions", "steamid", "sid = '".$mybb->session->sid."'") );
		if ( $result and $result["steamid"] ) {
			$dh->user_insert_data["steamid"] = $result["steamid"];
			$db->update_query( "sessions", array("steamid" => NULL), "sid = '".$mybb->session->sid."'" );
		} else {
			error( "No SteamID found in session, please restart the registration process" );
		}
	}
}

$plugins->add_hook( "member_profile_start", "steamlink_user_profile" );
function steamlink_user_profile() {
	global $db, $mybb, $templates, $theme, $steamid, $steamid32, $steamlink_profile_block;
	$uid = $mybb->input["uid"];
	if ( $uid ) {
		$memprofile = get_user( $uid );
	} elseif( $mybb->user["uid"] ) {
		$memprofile = $mybb->user;
	}
	
	if ( !$memprofile ) {
		return;
	}
	
	$steamid32 = "";
	$template = "steamlink_profile_block_unlinked";
	if ( isset($memprofile["steamid"]) ) {
		$steamid32 = steam_convert64to32( $memprofile["steamid"] );
		$template = "steamlink_profile_block_linked";
	}
	eval( "\$steamlink_profile_block = \"".$templates->get($template)."\";" );
}

$plugins->add_hook( "postbit", "steamlink_postbit" );
$plugins->add_hook( "postbit_prev", "steamlink_postbit" );
$plugins->add_hook( "postbit_pm", "steamlink_postbit" );
$plugins->add_hook( "postbit_announcement", "steamlink_postbit" );
function steamlink_postbit( &$post ) {
	global $db, $mybb, $templates;
	if ( $post["steamid"] ) {
		eval("\$post['steamlink'] = \"".$templates->get("steamlink_postbit")."\";");
	}
}