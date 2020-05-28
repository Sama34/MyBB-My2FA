<?php

namespace My2FA\Methods;

class Skeleton extends AbstractMethod
{
    public const PUBLIC_NAME = "skeleton";

    protected static $definitions = [
        'name' => "Skeleton",
        'description' => "A implementation sample of a 2FA method.",
    ];

    public static function getActivationForm(): string
    {
        global $db, $mybb, $lang, $theme;

        \My2FA\loadLanguage();

        $method = \My2FA\getMethods()[self::PUBLIC_NAME];

        if (isset($mybb->input['otp']))
        {
            if (self::isOtpValid($mybb->input['otp']))
            {
                \My2FA\insertUserMethod([
                    'method' => $method['publicName'],
                    'method_data' => ""
                ]);
                redirect('usercp.php?action=my2fa', $lang->my2fa_success_enabled);
            }
            else
            {
                $errors = inline_error([$lang->my2fa_error_code]);
            }
        }

        eval('$skeletonMethod = "' . \My2FA\template('method_skeleton_activation') . '";');
        return $skeletonMethod;
    }

    public static function getValidationForm(): string
    {
        
    }

    private static function isOtpValid(string $otp): bool
    {
        return (int) $otp === 123;
    }
}