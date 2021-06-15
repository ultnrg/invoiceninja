<?php
/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2021. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://opensource.org/licenses/AAL
 */

namespace App\Factory;

use App\Models\User;

class UserFactory
{
    public static function create(int $account_id) :User
    {
        $user = new User;

        $user->account_id = $account_id;
        $user->first_name = '';
        $user->last_name = '';
        $user->phone = '';
        $user->email = '';
        $user->last_login = now();
        $user->failed_logins = 0;
        $user->signature = '';
        $user->theme_id = 0;
        
        return $user;
    }
}
