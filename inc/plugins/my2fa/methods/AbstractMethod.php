<?php

namespace My2FA\Methods;

abstract class AbstractMethod
{
    public const PUBLIC_NAME = null;

    protected static $definitions = [
        'name' => null,
        'description' => null
    ];

    public static function getDefinitions(): array
    {
        return static::$definitions;
    }

    abstract public static function getActivationForm(): string;
    abstract public static function getValidationForm(): string;

    public static function getManagementForm(): string
    {
        return "";
    }

    public static function isUsable(): bool
    {
        return True;
    }

    public static function canBeEnabled(): bool
    {
        return True;
    }

    public static function canBeDisabled(): bool
    {
        return True;
    }

    public static function canBeManaged(): bool
    {
        return False;
    }
}