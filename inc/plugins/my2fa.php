<?php

if (!defined('IN_MYBB'))
    exit("Denied.");

define('MY2FA_ROOT', MYBB_ROOT . 'inc/plugins/my2fa/');

require MY2FA_ROOT . 'core.php';

$GLOBALS['my2faAutoload'] = [
    'My2FA\\Methods\\' => "methods",
    'PragmaRX\\Google2FA\\' => "libs/pragmarx/google2fa/src",
    'ParagonIE\\ConstantTime\\' => "libs/paragonie/constant_time_encoding/src"
];

spl_autoload_register(function ($className)
{
    global $my2faAutoload;

    foreach ($my2faAutoload as $namespace => $path)
    {
        if (strpos($className, $namespace) === 0)
        {
            $classNameUnprefixed = strtr($className, [$namespace => '', '\\' => '/']);
            require MY2FA_ROOT . $path . '/' . $classNameUnprefixed . '.php';

            break;
        }
    }
});

$plugins->add_hook('usercp_menu_built', 'my2fa_usercp_menu_built');
$plugins->add_hook('usercp_start', 'my2fa_usercp_start');

$plugins->add_hook('build_friendly_wol_location_end', 'my2fa_build_wol_location');

$plugins->add_hook("global_end", "my2fa_global_end");

$plugins->add_hook("admin_load", "my2fa_admin_do_login");
$plugins->add_hook("admin_formcontainer_output_row", "my2fa_admin_formcontainer_output_row");

function my2fa_info()
{
    return [
        'name'          => "My2FA",
        'description'   => "yo",
        'author'        => "yo",
        //'authorsite'    => "",
        'version'       => "1.0",
        'compatibility' => "18*"
    ];
}

function my2fa_install()
{
    global $PL, $db;

    my2fa_load_dependencies();

    $db->write_query("
        CREATE TABLE IF NOT EXISTS `".TABLE_PREFIX."my2fa` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `uid` int unsigned NOT NULL,
            `method` varchar(20) NOT NULL,
            `method_data` varchar(255) NOT NULL DEFAULT '',
            `enabled_on` int unsigned NOT NULL DEFAULT 0,
            `last_attempt_on` int unsigned NOT NULL DEFAULT 0,
            `last_auth_on` int unsigned NOT NULL DEFAULT 0,
            `is_verified` tinyint(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (id)
        ) ENGINE=InnoDB" . $db->build_create_table_collation()
    );

    $db->write_query("
        CREATE TABLE IF NOT EXISTS `".TABLE_PREFIX."my2fa_recovery` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `uid` int unsigned NOT NULL,
            `codes` varchar(255) NOT NULL DEFAULT '',
        PRIMARY KEY (id)
        ) ENGINE=InnoDB" . $db->build_create_table_collation()
    );

    $db->write_query("
        CREATE TABLE IF NOT EXISTS `".TABLE_PREFIX."my2fa_logs` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `uid` int unsigned NOT NULL,
            `otp` varchar(6) NOT NULL,
            `used_on` int unsigned NOT NULL,
        PRIMARY KEY (id)
        ) ENGINE=InnoDB" . $db->build_create_table_collation()
    );

    if (!$db->field_exists('has_my2fa', 'users'))
        $db->add_column('users', 'has_my2fa', "tinyint(1) NOT NULL DEFAULT 0");

    if (!$db->field_exists('my2fa_validated_on', 'sessions'))
        $db->add_column('sessions', 'my2fa_validated_on', "int unsigned NOT NULL DEFAULT 0");
}

function my2fa_uninstall()
{
    global $PL, $db;

    my2fa_load_dependencies();

    $PL->settings_delete('my2fa');
    $PL->templates_delete('my2fa');

    require_once MYBB_ROOT . '/inc/adminfunctions_templates.php';

    find_replace_templatesets('usercp_nav_profile',
        '#' . preg_quote('<!-- my2faUsercpNav -->') . '#i',
        ''
    );

    if ($db->field_exists('has_my2fa', 'users'))
        $db->drop_column('users', 'has_my2fa');

    if ($db->field_exists('my2fa_validated_on', 'sessions'))
        $db->drop_column('sessions', 'my2fa_validated_on');

    $db->drop_table('my2fa');
    $db->drop_table('my2fa_logs');
}

function my2fa_is_installed()
{
    global $db;

    return $db->table_exists('my2fa');
}

function my2fa_activate()
{
    global $PL, $mybb, $lang;

    my2fa_load_dependencies();

    \My2FA\loadLanguage();

    $PL->settings('my2fa',
        "My2FA",
        "Some random settings for the My2FA plugin.",
        [
            'totp_board_name' => [
                'title'       => "TOTP: QR Code, Board Name",
                'description' => "Insert your board name that will be viewed in your user authenticator app.
                                  <br><strong>Spaces are not allowed</strong>, they will be automatically replaced by a hyphen (-).",
                'optionscode' => "text",
                'value'       => My2FA\getQrCodeParameterSanitized($mybb->settings['bbname'])
            ],
            'forceacp' => [
                'title'       => $lang->setting_my2fa_forceacp,
                'description' => $lang->setting_my2fa_forceacp_desc,
                'optionscode' => "yesno",
                'value'       => 0
            ],
            'forcemodcp' => [
                'title'       => $lang->setting_my2fa_forcemodcp,
                'description' => $lang->setting_my2fa_forcemodcp_desc,
                'optionscode' => "yesno",
                'value'       => 0
            ],
            'forcegroups' => [
                'title'       => $lang->setting_my2fa_forcegroups,
                'description' => $lang->setting_my2fa_forcegroups_desc,
                'optionscode' => "groupselect",
                'value'       => 0
            ],
        ]
    );

    $templatesDirIterator = new DirectoryIterator(MY2FA_ROOT . 'templates');

    $templates = [];
    foreach ($templatesDirIterator as $template)
    {
        if (!$template->isFile())
            continue;

        $pathName = $template->getPathname();
        $pathInfo = pathinfo($pathName);

        if ($pathInfo['extension'] === 'tpl')
            $templates[$pathInfo['filename']] = file_get_contents($pathName);
    }

    if ($templates)
        $PL->templates('my2fa', "My2FA", $templates);

    require_once MYBB_ROOT . '/inc/adminfunctions_templates.php';

    find_replace_templatesets('usercp_nav_profile',
        '#' . preg_quote('{$changenameop}') . '#i',
        '{$changenameop}<!-- my2faUsercpNav -->'
    );
}

function my2fa_deactivate()
{
    global $PL;

    my2fa_load_dependencies();
}

function my2fa_load_dependencies()
{
    global $PL, $lang;

    if (!defined('PLUGINLIBRARY'))
        define('PLUGINLIBRARY', MYBB_ROOT . 'inc/plugins/pluginlibrary.php');

    if (!file_exists(PLUGINLIBRARY))
    {
        flash_message("PluginLibrary missing.", 'error');
        admin_redirect('index.php?module=config-plugins');
    }

    if (!$PL)
        require_once PLUGINLIBRARY;
}

/*
 * Hooks
 */

function my2fa_usercp_menu_built()
{
    global $mybb, $lang, $usercpnav;

    My2FA\loadLanguage();

    eval('$my2faUsercpNav = "' . My2FA\template('usercp_nav') . '";');
    $usercpnav = str_replace("<!-- my2faUsercpNav -->", $my2faUsercpNav, $usercpnav);
}

#todo: action=my2fa should be validated with a password through a session check
function my2fa_usercp_start()
{
    global $mybb, $db, $lang,
    $headerinclude, $header, $theme, $usercpnav, $footer, $plugins;

    My2FA\loadLanguage();

    if ($mybb->input['action'] !== 'my2fa')
        return;

    $mybb->input['method'] = $mybb->get_input('method');

    add_breadcrumb($lang->nav_usercp, 'usercp.php');

    $methods = My2FA\getMethods();
    $userVerifiedMethods = My2FA\getUserVerifiedMethods();

    $mybb->user['uid'] = (int)$mybb->user['uid'];

    if (
        isset($methods[$mybb->input['method']]) &&
        $methods[$mybb->input['method']]['className']::isUsable()
    ){
        verify_post_check($mybb->get_input('my_post_key'));

        $method = $methods[$mybb->input['method']];

        if (
            $mybb->get_input('disable') === '1' &&
            $method['className']::canBeDisabled() &&
            isset($userVerifiedMethods[$mybb->input['method']])
        ){
            My2FA\removeUserMethod($mybb->input['method']);
            redirect('usercp.php?action=my2fa', $lang->my2fa_success_disabled);
        }
        else
        {
            add_breadcrumb($lang->my2fa_title, 'usercp.php?action=my2fa');
            add_breadcrumb($method['name']);

            if (
                !isset($userVerifiedMethods[$mybb->input['method']]) &&
                $method['className']::canBeEnabled()
            ){
                $my2faUsercpContent = $method['className']::getActivationForm();
            }
            else if (
                $mybb->get_input('manage') === '1' &&
                $method['className']::canBeManaged() &&
                isset($userVerifiedMethods[$mybb->input['method']])
            ){
                $my2faUsercpContent = $method['className']::getManagementForm();
            }
        }
    }
    elseif($mybb->get_input('manage', MyBB::INPUT_STRING) === 'recovery')
    {
        if($mybb->get_input('generate', MyBB::INPUT_INT) === 1)
        {
            $codes = [];

            for($i=1; $i <= 10; ++$i)
            {
                $codes[My2FA\generateCode()] = 1;
            }

            $updateData = [
                'uid' => $mybb->user['uid'],
                'codes' => json_encode($codes),
            ];

	        $plugins->run_hooks("my2fa_recovery_generate_start",  $updateData);

            $query = $db->simple_select('my2fa_recovery', 'id', "uid='{$mybb->user['uid']}'");

            if($id = $db->fetch_field($query, 'id'))
            {
                $db->update_query('my2fa_recovery', $updateData, "uid='{$mybb->user['uid']}'");
            }
            else
            {
                $db->insert_query('my2fa_recovery', $updateData, "uid='{$mybb->user['uid']}'");
            }

            if($lang->settings['charset'])
            {
                $charset = $lang->settings['charset'];
            }
            else
            {
                $charset = "UTF-8";
            }

            header("Content-type: application/json; charset={$charset}");

            $usedClass = $recoveryCodesList = '';

            foreach($codes as $code => $status)
            {
                eval('$recoveryCodesList .= "' . My2FA\template('usercp_button_recovery_codes_code') . '";');
            }

            eval('$recoveryCodes = "' . My2FA\template('usercp_button_recovery_codes') . '";');

            $codes = ['success' => 1, 'content' => $recoveryCodes];

            echo json_encode($codes);

            exit;
        }
    }
    else
    {
        add_breadcrumb($lang->my2fa_title);

        $my2faUsercpMethodRows = null;
        foreach ($methods as $method)
        {
            if (!$method['className']::isUsable())
                continue;

            if (
                !isset($userVerifiedMethods[$method['publicName']]) &&
                $method['className']::canBeEnabled()
            ){
                eval('$my2faUsercpMethodRows .= "' . My2FA\template('usercp_methods_row') . '";');
            }
            else
            {
                $userMethod = $userVerifiedMethods[$method['publicName']];

                $lang->my2fa_user_method_activation_date = $lang->sprintf(
                    $lang->my2fa_user_method_activation_date,
                    my_date('d M Y', $userMethod['enabled_on'])
                );
                $lang->my2fa_disable_user_method_confirmation = $lang->sprintf(
                    $lang->my2fa_disable_user_method_confirmation,
                    $method['name']
                );

                if ($method['className']::canBeDisabled())
                    eval('$my2faUsercpDisableButton = "' . My2FA\template('usercp_button_disable') . '";');

                if ($method['className']::canBeManaged())
                    eval('$my2faUsercpManageButton = "' . My2FA\template('usercp_button_manage') . '";');

                eval('$my2faUsercpMethodRows .= "' . My2FA\template('usercp_methods_row_enabled') . '";');
            }
        }

        $my2faUsercpRecovery = '';

        $query = $db->simple_select('my2fa_recovery', 'codes', "uid='{$mybb->user['uid']}'", ['limit' => 1]);

        $userCodes = $db->fetch_field($query, 'codes');

        if(!empty($userCodes))
        {
            $userCodes = json_decode($userCodes, true);

            $recoveryCodesList = '';

            foreach($userCodes as $code => $status)
            {
                $usedClass = '';

                if(!$status)
                {
                    $usedClass = 'my2fa__code-used';
                }

                eval('$recoveryCodesList .= "' . My2FA\template('usercp_button_recovery_codes_code') . '";');
            }

            eval('$recoveryCodes = "' . My2FA\template('usercp_button_recovery_codes') . '";');
        }
        else
        {
            $recoveryCodes = $lang->my2fa_recovery_empty;
        }

        if(!empty($userMethod))
        {
            eval('$my2faUsercpRecovery = "' . My2FA\template('usercp_button_recovery') . '";');
        }

        eval('$my2faUsercpContent = "' . My2FA\template('usercp_methods') . '";');
    }

    eval('$my2faUsercpPage = "' . My2FA\template('usercp') . '";');

    output_page($my2faUsercpPage);
}

function my2fa_build_wol_location(&$wol)
{
    if (my_strpos($wol['user_activity']['location'], "usercp.php?action=my2fa"))
    {
        global $lang;
        \My2FA\loadLanguage();

        $wol['location_name'] = $lang->my2fa_usercp_wol;
    }
}

function my2fa_global_end()
{
    global $mybb, $lang, $plugins;

    if(!$mybb->user['uid'] || !empty($mybb->user['has_my2fa']))
    {
        return;
    }

    if(is_member($mybb->settings['my2fa_forcegroups']))
    {
        My2FA\loadLanguage();

        redirect('usercp.php?action=my2fa', $lang->my2fa_force_groups, $lang->my2fa_title, true);
    }

    if($mybb->settings['my2fa_forcemodcp'])
    {
        $plugins->add_hook("modcp_start", "my2fa_modcp_start");
    }
}

function my2fa_modcp_start()
{
    global $lang;

    My2FA\loadLanguage();

    redirect('usercp.php?action=my2fa', $lang->my2fa_force_modcp, $lang->my2fa_title, true);
}

function my2fa_admin_do_login()
{
	global $mybb, $lang, $admin_options;

	if($mybb->get_input('module') == 'home-preferences' || !$mybb->user['uid'] || !empty($mybb->user['has_my2fa']))
	{
	    return;
	}

	if($mybb->settings['my2fa_forceacp'] || is_member($mybb->settings['my2fa_forcegroups']))
	{
        My2FA\loadLanguage();

		flash_message($lang->sprintf($lang->my2fa_forced_acp, $mybb->settings['bburl']), 'error');

		admin_redirect('index.php?module=home-preferences');
	}
}

function my2fa_admin_formcontainer_output_row(&$args)
{
	global$lang, $admin_options, $mybb, $db;

	if(empty($args['title']) || empty($lang->my2fa) || ($args['title'] != $lang->my2fa && my_strpos($args['title'], $lang->my2fa_qr) === false))
	{
		return;
	}

	if(!empty($admin_options['authsecret']))
	{
		$uid = (int)$mybb->user['uid'];
        $db->update_query('adminoptions', array('authsecret' => ''), "uid='{$uid}'");
        $db->update_query("adminsessions", array("authenticated" => 0), "uid='{$uid}'");
	}

	$args['title'] = $args['content'] = $args['description'] = '';

	$args['options']['style'] = 'display: none !important;';
}