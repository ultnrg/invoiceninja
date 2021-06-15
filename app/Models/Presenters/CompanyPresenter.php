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

namespace App\Models\Presenters;

use App\Models\Country;

/**
 * Class CompanyPresenter.
 */
class CompanyPresenter extends EntityPresenter
{
    /**
     * @return string
     */
    public function name()
    {
        $settings = $this->entity->settings;

        return $this->settings->name ?: ctrans('texts.untitled_account');

        //return $this->entity->name ?: ctrans('texts.untitled_account');
    }

    public function logo($settings = null)
    {
        if (! $settings) {
            $settings = $this->entity->settings;
        }

        if(strlen($settings->company_logo) >= 1 && (strpos($settings->company_logo, 'http') !== false))
            return $settings->company_logo;
        else if(strlen($settings->company_logo) >= 1)
            return url('') . $settings->company_logo;
        else
            return 'https://www.invoiceninja.com/wp-content/uploads/2019/01/InvoiceNinja-Logo-Round-300x300.png';

    }

    public function address($settings = null)
    {
        $str = '';
        $company = $this->entity;

        if (! $settings) {
            $settings = $this->entity->settings;
        }

        if ($address1 = $settings->address1) {
            $str .= e($address1).'<br/>';
        }
        if ($address2 = $settings->address2) {
            $str .= e($address2).'<br/>';
        }
        if ($cityState = $this->getCompanyCityState($settings)) {
            $str .= e($cityState).'<br/>';
        }
        if ($country = Country::find($settings->country_id)) {
            $str .= e($country->name).'<br/>';
        }
        if ($settings->phone) {
            $str .= ctrans('texts.work_phone').': '.e($settings->phone).'<br/>';
        }
        if ($settings->email) {
            $str .= ctrans('texts.work_email').': '.e($settings->email).'<br/>';
        }

        return $str;
    }

    public function getCompanyCityState($settings = null)
    {
        if (! $settings) {
            $settings = $this->entity->settings;
        }

        $country = Country::find($settings->country_id);

        $swap = $country && $country->swap_postal_code;

        $city = e($settings->city);
        $state = e($settings->state);
        $postalCode = e($settings->postal_code);

        if ($city || $state || $postalCode) {
            return $this->cityStateZip($city, $state, $postalCode, $swap);
        } else {
            return false;
        }
    }

    public function getSpcQrCode($client_currency, $invoice_number, $balance_due_raw, $user_iban)
    {
        $settings = $this->entity->settings;

        return 

        "SPC\n0200\n1\n{$user_iban}\nK\n{$this->name}\n{$settings->address1}\n{$settings->postal_code} {$settings->city}\n\n\nCH\n\n\n\n\n\n\n\n{$balance_due_raw}\n{$client_currency}\n\n\n\n\n\n\n\nNON\n\n{$invoice_number}\nEPD\n";
    }

}
