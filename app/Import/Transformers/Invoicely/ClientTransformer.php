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

namespace App\Import\Transformers\Invoicely;

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
		if ( isset( $data['Client Name'] ) && $this->hasClient( $data['Client Name'] ) ) {
			throw new ImportException('Client already exists');
		}

		return [
			'company_id'     => $this->maps['company']->id,
			'name'           => $this->getString( $data, 'Client Name' ),
			'work_phone'     => $this->getString( $data, 'Phone' ),
			'country_id'     => isset( $data['Country'] ) ? $this->getCountryIdBy2( $data['Country'] ) : null,
			'credit_balance' => 0,
			'settings'       => new \stdClass,
			'client_hash'    => Str::random( 40 ),
			'contacts'       => [
				[
					'email'         => $this->getString( $data, 'Email' ),
					'phone'         => $this->getString( $data, 'Phone' ),
				],
			],
		];
	}
}
