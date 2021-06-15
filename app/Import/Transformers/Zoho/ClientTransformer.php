<?php
/**
 * Invoice Ninja (https://clientninja.com).
 *
 * @link https://github.com/clientninja/clientninja source repository
 *
 * @copyright Copyright (c) 2021. client Ninja LLC (https://clientninja.com)
 *
 * @license https://opensource.org/licenses/AAL
 */

namespace App\Import\Transformers\Zoho;

use App\Import\ImportException;
use App\Import\Transformers\BaseTransformer;
use Illuminate\Support\Str;

/**
 * Class ClientTransformer.
 */
class ClientTransformer extends BaseTransformer {
	/**
	 * @param $data
	 *
	 * @return array|bool
	 */
	public function transform( $data ) {
		if ( isset( $data['Company Name'] ) && $this->hasClient( $data['Company Name'] ) ) {
			throw new ImportException( 'Client already exists' );
		}

		$settings              = new \stdClass;
		$settings->currency_id = (string) $this->getCurrencyByCode( $data, 'Currency' );

		if ( strval( $data['Payment Terms'] ?? '' ) > 0 ) {
			$settings->payment_terms = $data['Payment Terms'];
		}

		return [
			'company_id'    => $this->maps['company']->id,
			'name'          => $this->getString( $data, 'Company Name' ),
			'work_phone'    => $this->getString( $data, 'Phone' ),
			'private_notes' => $this->getString( $data, 'Notes' ),
			'website'       => $this->getString( $data, 'Website' ),

			'address1'    => $this->getString( $data, 'Billing Address' ),
			'address2'    => $this->getString( $data, 'Billing Street2' ),
			'city'        => $this->getString( $data, 'Billing City' ),
			'state'       => $this->getString( $data, 'Billing State' ),
			'postal_code' => $this->getString( $data, 'Billing Code' ),
			'country_id'  => isset( $data['Billing Country'] ) ? $this->getCountryId( $data['Billing Country'] ) : null,

			'shipping_address1'    => $this->getString( $data, 'Shipping Address' ),
			'shipping_address2'    => $this->getString( $data, 'Shipping Street2' ),
			'shipping_city'        => $this->getString( $data, 'Shipping City' ),
			'shipping_state'       => $this->getString( $data, 'Shipping State' ),
			'shipping_postal_code' => $this->getString( $data, 'Shipping Code' ),
			'shipping_country_id'  => isset( $data['Shipping Country'] ) ? $this->getCountryId( $data['Shipping Country'] ) : null,
			'credit_balance' => 0,
			'settings'       => $settings,
			'client_hash'    => Str::random( 40 ),
			'contacts'       => [
				[
					'first_name' => $this->getString( $data, 'First Name' ),
					'last_name'  => $this->getString( $data, 'Last Name' ),
					'email'      => $this->getString( $data, 'Email' ),
					'phone'      => $this->getString( $data, 'Phone' ),
				],
			],
		];
	}
}
