<?php

namespace My2FA;

function setting(string $name): string
{
    global $mybb;

    return $mybb->settings['my2fa_' . $name];
}

function template(string $name): string
{
    global $templates;

    return $templates->get('my2fa_' . $name);
}

function getMethods(): array
{
    static $methods;

    if (!isset($methods))
    {
        $filenames = scandir(MY2FA_ROOT . 'methods');

        $methods = [];
        foreach ($filenames as $filename)
        {
            $className = "My2FA\Methods\\" . basename($filename, '.php');

            if (
                $filename[0] !== '.' &&
                is_subclass_of($className, 'My2FA\Methods\AbstractMethod')
            ){
                $methods[$className::PUBLIC_NAME] = array_merge(
                    $className::getDefinitions(),
                    [
                        'publicName' => $className::PUBLIC_NAME,
                        'className' => $className
                    ]
                );
            }
        }
    }

    return $methods;
}

function getUserMethods(): array
{
    global $db, $mybb;
    static $userMethods;

    if (!isset($userMethods))
    {
        $query = $db->simple_select('my2fa', '*', "uid = {$mybb->user['uid']}");

        $userMethods = [];
        while ($userMethod = $db->fetch_array($query))
        {
            $userMethods[$userMethod['method']] = $userMethod;
        }
    }

    return $userMethods;
}

function getUserVerifiedMethods(): array
{
    return array_filter(getUserMethods(), function($userMethod) {
        return (bool) $userMethod['is_verified'];
    });
}

function getQrCodeParameterSanitized(string $parameter): string
{
    return preg_replace('/\s+/', '-', $parameter);
}

function isUserOtpAlreadyUsed(string $otp, int $maxAllowedSeconds): bool
{
    global $db, $mybb;

    $otpEscaped = $db->escape_string($otp);

    return $db->fetch_field(
        $db->simple_select('my2fa_logs',
            'COUNT(1) as count',
            "
                uid = {$mybb->user['uid']} AND
                otp = '{$otpEscaped}' AND
                used_on > " . (TIME_NOW - $maxAllowedSeconds) . "
            "
        ),
        'count'
    );
}

function insertUserMethod(array $args, bool $isVerified = True): void
{
    global $db, $mybb;

    $args = array_map([$db, 'escape_string'], $args);
    $args += [
        'uid' => $mybb->user['uid'],
        'enabled_on' => $isVerified ? TIME_NOW : 0,
        'last_attempt_on' => TIME_NOW,
        'is_verified' => (int) $isVerified
    ];

    if ($isVerified)
        $db->update_query('users', ['has_my2fa' => 1], "uid = {$args['uid']}");

    if (isset(getUserMethods()[$args['method']]))
    {
        $db->update_query('my2fa', $args, "uid = {$args['uid']} AND method = '{$args['method']}'");
    }
    else
    {
        $db->insert_query('my2fa', $args);
    }
}

function updateUserMethod(array $args): void
{
    global $db, $mybb;

    $args = array_map([$db, 'escape_string'], $args);
    $args += [
        'uid' => $mybb->user['uid']
    ];

    if (isset(getUserMethods()[$args['method']]))
        $db->update_query('my2fa', $args, "uid = {$args['uid']} AND method = '{$args['method']}'");
}

function removeUserMethod(string $method, int $userId = 0): void
{
    global $db, $mybb;

    $methodEscaped = $db->escape_string($method);
    $userId = $userId ?: $mybb->user['uid'];

    $db->update_query('users', ['has_my2fa' => 0], "uid = {$userId}");
    $db->delete_query('my2fa', "uid = {$userId} AND method = '{$methodEscaped}'");
}

function insertUserLog(array $args): void
{
    global $db, $mybb;

    $args = array_map([$db, 'escape_string'], $args);
    $args += [
        'uid' => $mybb->user['uid'],
        'used_on' => TIME_NOW
    ];

    $db->insert_query('my2fa_logs', $args);
}

function loadLanguage(): void
{
    global $lang;
    static $isLangLoaded;

    if (!isset($isLangLoaded))
    {
        $lang->load('my2fa');
        $isLangLoaded = True;
    }
}