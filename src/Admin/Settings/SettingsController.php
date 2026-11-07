<?php

declare(strict_types=1);

namespace App\Admin\Settings;

use App\Auth\AccountSettingsController;

final class SettingsController extends AccountSettingsController
{
    protected const ADMIN = true;
}
