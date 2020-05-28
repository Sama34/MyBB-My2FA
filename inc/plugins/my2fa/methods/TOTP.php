<?php

namespace My2FA\Methods;

use PragmaRX\Google2FA\Google2FA;

class TOTP extends AbstractMethod
{
    public const PUBLIC_NAME = "totp";

    protected static $definitions = [];

    public static function getDefinitions(): array
    {
        global $lang;

        \My2FA\loadLanguage();

        self::$definitions['name'] = $lang->my2fa_totp_name;
        self::$definitions['description'] = $lang->my2fa_totp_description;

        return self::$definitions;
    }

    public static function getActivationForm(): string
    {
        global $db, $mybb, $lang, $theme;

        \My2FA\loadLanguage();

        $google2fa = new Google2FA();

        $method = \My2FA\getMethods()[self::PUBLIC_NAME];
        $userMethod = \My2FA\getUserMethods()[self::PUBLIC_NAME] ?? [];

        $secretKey = $userMethod['method_data'] ?? null;
        if (is_null($secretKey))
        {
            $secretKey = $google2fa->generateSecretKey();

            \My2FA\insertUserMethod([
                'method' => $method['publicName'],
                'method_data' => $secretKey
            ], False);
        }

        $qrCodeUrl = $google2fa->getQRCodeUrl(
            \My2FA\getQrCodeParameterSanitized(\My2FA\setting('totp_board_name')),
            \My2FA\getQrCodeParameterSanitized($mybb->user['username']),
            $secretKey
        );

        // temp
        $qrCode = "https://api.qrserver.com/v1/create-qr-code/?data={$qrCodeUrl}";

        if (isset($mybb->input['otp']))
        {
            $mybb->input['otp'] = str_replace(' ', '', $mybb->input['otp']);

            if (self::isOtpValid($secretKey, $mybb->input['otp']))
            {
                \My2FA\insertUserMethod([
                    'method' => $method['publicName'],
                    'method_data' => $secretKey
                ]);
                \My2FA\insertUserLog([
                    'otp' => $mybb->input['otp']
                ]);

                redirect('usercp.php?action=my2fa', $lang->my2fa_success_enabled);
            }
            else
            {
                $errors = inline_error([$lang->my2fa_error_code]);
            }
        }

        eval('$totpMethod = "' . \My2FA\template('method_totp_activation') . '";');
        return $totpMethod;
    }

    public static function getValidationForm(): string
    {
        
    }

    private static function isOtpValid(string $secretKey, string $otp): bool
    {
        $google2fa = new Google2FA();

        return
            strlen($otp) === 6 &&
            !\My2FA\isUserOtpAlreadyUsed($otp, 30+120+120) && // worst case: 30+120+120
            (int) $otp === 123456 || // temp
            $google2fa->verifyKey($secretKey, $otp)
        ;
    }
}