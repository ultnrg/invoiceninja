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

namespace App\Models;

use App\Models\GatewayType;
use App\PaymentDrivers\BasePaymentDriver;
use App\Utils\Number;
use Illuminate\Database\Eloquent\SoftDeletes;
use stdClass;

class CompanyGateway extends BaseModel
{
    use SoftDeletes;

    public const GATEWAY_CREDIT = 10000000;

    protected $casts = [
        'fees_and_limits' => 'object',
        'updated_at' => 'timestamp',
        'created_at' => 'timestamp',
        'deleted_at' => 'timestamp',
    ];

    protected $fillable = [
        'gateway_key',
        'accepted_credit_cards',
        'require_cvv',
        'require_billing_address',
        'require_shipping_address',
        'require_client_name',
        'require_postal_code',
        'require_client_phone',
        'require_contact_name',
        'update_details',
        'config',
        'fees_and_limits',
        'custom_value1',
        'custom_value2',
        'custom_value3',
        'custom_value4',
        'token_billing',
        'label',
    ];

    public static $credit_cards = [
            1 => ['card' => 'images/credit_cards/Test-Visa-Icon.png', 'text' => 'Visa'],
            2 => ['card' => 'images/credit_cards/Test-MasterCard-Icon.png', 'text' => 'Master Card'],
            4 => ['card' => 'images/credit_cards/Test-AmericanExpress-Icon.png', 'text' => 'American Express'],
            8 => ['card' => 'images/credit_cards/Test-Diners-Icon.png', 'text' => 'Diners'],
            16 => ['card' => 'images/credit_cards/Test-Discover-Icon.png', 'text' => 'Discover'],
        ];

    public $gateway_consts = [
        '38f2c48af60c7dd69e04248cbb24c36e' => 300,
        'd14dd26a37cecc30fdd65700bfb55b23' => 301,
        '3758e7f7c6f4cecf0f4f348b9a00f456' => 304,
        '3b6621f970ab18887c4f6dca78d3f8bb' => 305,
        '54faab2ab6e3223dbe848b1686490baa' => 306,
        'd14dd26a47cecc30fdd65700bfb67b34' => 301,
    ];

    protected $touches = [];

    public function getEntityType()
    {
        return self::class;
    }

    public function system_logs()
    {

        return $this->company
                    ->system_log_relation
                    ->where('type_id', $this->gateway_consts[$this->gateway->key])
                    ->take(50);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function client_gateway_tokens()
    {
        return $this->hasMany(ClientGatewayToken::class);
    }

    public function gateway()
    {
        return $this->belongsTo(Gateway::class, 'gateway_key', 'key');
    }

    public function getTypeAlias($gateway_type_id)
    {
        return GatewayType::getAlias($gateway_type_id);
    }

    /* This is the public entry point into the payment superclass */
    public function driver(Client $client)
    {
        $class = static::driver_class();

        return new $class($this, $client);
    }

    private function driver_class()
    {
        $class = 'App\\PaymentDrivers\\'.$this->gateway->provider.'PaymentDriver';
        $class = str_replace('_', '', $class);

        if (class_exists($class)) {
            return $class;
        } else {
            return BasePaymentDriver::class;
        }
    }

    /**
     * @param $config
     */
    public function setConfig($config)
    {
        $this->config = encrypt(json_encode($config));
    }

    /**
     * @return mixed
     */
    public function getConfig()
    {
        //return decrypt($this->config);
        return json_decode(decrypt($this->config));
    }

    public function getConfigTransformed()
    {
        return $this->config ? decrypt($this->config) : '';
    }

    /**
     * @param $field
     *
     * @return mixed
     */
    public function getConfigField($field)
    {
        return object_get($this->getConfig(), $field, false);
    }

    /**
     * @return bool
     */
    public function getAchEnabled()
    {
        return ! empty($this->getConfigField('enable_ach'));
    }

    /**
     * @return bool
     */
    public function getApplePayEnabled()
    {
        return ! empty($this->getConfigField('enable_apple_pay'));
    }

    /**
     * @return bool
     */
    public function getAlipayEnabled()
    {
        return ! empty($this->getConfigField('enable_alipay'));
    }

    /**
     * @return bool
     */
    public function getSofortEnabled()
    {
        return ! empty($this->getConfigField('enable_sofort'));
    }

    /**
     * @return bool
     */
    public function getSepaEnabled()
    {
        return ! empty($this->getConfigField('enable_sepa'));
    }

    /**
     * @return bool
     */
    public function getBitcoinEnabled()
    {
        return ! empty($this->getConfigField('enable_bitcoin'));
    }

    /**
     * @return bool
     */
    public function getPayPalEnabled()
    {
        return ! empty($this->getConfigField('enable_pay_pal'));
    }

    public function feesEnabled()
    {
        return floatval($this->fee_amount) || floatval($this->fee_percent);
    }

    /**
     * Returns the current test mode of the gateway.
     *
     * @return bool whether the gateway is in testmode or not.
     */
    public function isTestMode() :bool
    {
        $config = $this->getConfig();

        if ($this->gateway->provider == 'Stripe' && property_exists($config, 'publishableKey') && strpos($config->publishableKey, 'test')) {
            return true;
        }

        if ($config && property_exists($config, 'testMode') && $config->testMode) {
            return true;
        }

        return false;
    }

    /**
     * Get Publishable Key
     * Only works for STRIPE and PAYMILL.
     * @return string The Publishable key
     */
    public function getPublishableKey() :string
    {
        return $this->getConfigField('publishableKey');
    }

    public function getFeesAndLimits($gateway_type_id)
    {
        if (is_null($this->fees_and_limits) || empty($this->fees_and_limits) || !property_exists($this->fees_and_limits, $gateway_type_id)) {
            return false;
        }

        if ($gateway_type_id == GatewayType::CUSTOM) {
            $gateway_type_id = GatewayType::CREDIT_CARD;
        }

        return $this->fees_and_limits->{$gateway_type_id};
    }

    /**
     * Returns the formatted fee amount for the gateway.
     *
     * @param float $amount The payment amount
     * @param Client $client The client object
     * @param int $gateway_type_id
     * @return string           The fee amount formatted in the client currency
     */
    public function calcGatewayFeeLabel($amount, Client $client, $gateway_type_id = GatewayType::CREDIT_CARD) :string
    {
        $label = ' ';

        $fee = $this->calcGatewayFee($amount, $gateway_type_id);

        // if ($fee > 0) {
        //     $fee =  Number::formatMoney(round($fee, 2), $client);
        //     $label = ' - '.$fee.' '.ctrans('texts.fee');
        // }

        if($fee > 0) {

            $fees_and_limits = $this->fees_and_limits->{$gateway_type_id};

            if(strlen($fees_and_limits->fee_percent) >=1)
                $label .= $fees_and_limits->fee_percent . '%';

            if(strlen($fees_and_limits->fee_amount) >=1){

                if(strlen($label) > 1) {

                    $label .= ' + ' . Number::formatMoney($fees_and_limits->fee_amount, $client);

                }else {
                    $label .= Number::formatMoney($fees_and_limits->fee_amount, $client);
                }
            }


        }


        return $label;
    }

    public function calcGatewayFee($amount, $gateway_type_id, $include_taxes = false)
    {
        $fees_and_limits = $this->getFeesAndLimits($gateway_type_id);

        if (! $fees_and_limits) {
            return false;
        }

        $fee = 0;

        if ($fees_and_limits->fee_amount) {
            $fee += $fees_and_limits->fee_amount;
            nlog("fee after adding fee amount = {$fee}");
        }

        if ($fees_and_limits->fee_percent) {
            if ($fees_and_limits->adjust_fee_percent) {
                $fee += round(($amount / (1 - $fees_and_limits->fee_percent / 100) - $amount), 2);
            } else {
                $fee += round(($amount * $fees_and_limits->fee_percent / 100), 2);
            }
            nlog("fee after adding fee percent = {$fee}");
        }

        /* Cap fee if we have to here. */
        if ($fees_and_limits->fee_cap > 0 && ($fee > $fees_and_limits->fee_cap)) {
            $fee = $fees_and_limits->fee_cap;
        }

        $pre_tax_fee = $fee;
            nlog("fee after adding fee percent = {$fee}");

        /**/
        if ($include_taxes) {
            if ($fees_and_limits->fee_tax_rate1) {
                $fee += round(($pre_tax_fee * $fees_and_limits->fee_tax_rate1 / 100), 2);
                // info("fee after adding fee tax 1 = {$fee}");
            }

            if ($fees_and_limits->fee_tax_rate2) {
                $fee += round(($pre_tax_fee * $fees_and_limits->fee_tax_rate2 / 100), 2);
                // info("fee after adding fee tax 2 = {$fee}");
            }

            if ($fees_and_limits->fee_tax_rate3) {
                $fee += round(($pre_tax_fee * $fees_and_limits->fee_tax_rate3 / 100), 2);
                // info("fee after adding fee tax 3 = {$fee}");
            }
        }
            nlog("fee after adding fee percent = {$fee}");

        return $fee;
    }

    /**
     * we need to average out the gateway fees across all the invoices
     * so lets iterate.
     *
     * we MAY need to adjust the final fee to ensure our rounding makes sense!
     * @param $amount
     * @param $invoice_count
     * @return stdClass
     */
    // public function calcGatewayFeeObject($amount, $invoice_count)
    // {
    //     $total_gateway_fee = $this->calcGatewayFee($amount);

    //     $fee_object = new stdClass;

    //     $fees_and_limits = $this->getFeesAndLimits();

    //     if (! $fees_and_limits) {
    //         return $fee_object;
    //     }

    //     $fee_component_amount = $fees_and_limits->fee_amount ?: 0;
    //     $fee_component_percent = $fees_and_limits->fee_percent ? ($amount * $fees_and_limits->fee_percent / 100) : 0;

    //     $combined_fee_component = $fee_component_amount + $fee_component_percent;

    //     $fee_component_tax_name1 = $fees_and_limits->fee_tax_name1 ?: '';
    //     $fee_component_tax_rate1 = $fees_and_limits->fee_tax_rate1 ? ($combined_fee_component * $fees_and_limits->fee_tax_rate1 / 100) : 0;

    //     $fee_component_tax_name2 = $fees_and_limits->fee_tax_name2 ?: '';
    //     $fee_component_tax_rate2 = $fees_and_limits->fee_tax_rate2 ? ($combined_fee_component * $fees_and_limits->fee_tax_rate2 / 100) : 0;

    //     $fee_component_tax_name3 = $fees_and_limits->fee_tax_name3 ?: '';
    //     $fee_component_tax_rate3 = $fees_and_limits->fee_tax_rate3 ? ($combined_fee_component * $fees_and_limits->fee_tax_rate3 / 100) : 0;
    // }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this
            ->where('id', $this->decodePrimaryKey($value))->firstOrFail();
    }
}
