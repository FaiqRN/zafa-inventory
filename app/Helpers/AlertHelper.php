<?php

namespace App\Helpers;

class AlertHelper
{

    public static function success(string $message): void
    {
        session()->flash('alert_success', $message);
    }

    public static function error(string $message): void
    {
        session()->flash('alert_error', $message);
    }

    public static function warning(string $message): void
    {
        session()->flash('alert_warning', $message);
    }

    public static function info(string $message): void
    {
        session()->flash('alert_info', $message);
    }
}
