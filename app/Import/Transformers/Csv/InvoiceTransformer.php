<?php
/**
 * client Ninja (https://clientninja.com).
 *
 * @link https://github.com/clientninja/clientninja source repository
 *
 * @copyright Copyright (c) 2021. client Ninja LLC (https://clientninja.com)
 *
 * @license https://opensource.org/licenses/AAL
 */

namespace App\Import\Transformers\Csv;

use App\Import\ImportException;
use App\Import\Transformers\BaseTransformer;
use App\Models\Invoice;

/**
 * Class InvoiceTransformer.
 */
class InvoiceTransformer extends BaseTransformer {
	/**
	 * @param $data
	 *
	 * @return bool|array
	 */
	public function transform( $line_items_data ) {
		$invoice_data = reset( $line_items_data );

		if ( $this->hasInvoice( $invoice_data['invoice.number'] ) ) {
			throw new ImportException( 'Invoice number already exists' );
		}

		$invoiceStatusMap = [
			'sent'  => Invoice::STATUS_SENT,
			'draft' => Invoice::STATUS_DRAFT,
		];

		$transformed = [
			'company_id'        => $this->maps['company']->id,
			'number'            => $this->getString( $invoice_data, 'invoice.number' ),
			'user_id'           => $this->getString( $invoice_data, 'invoice.user_id' ),
			'amount'            => $amount = $this->getFloat( $invoice_data, 'invoice.amount' ),
			'balance'           => isset( $invoice_data['invoice.balance'] ) ? $this->getFloat( $invoice_data, 'invoice.balance' ) : $amount,
			'client_id'         => $this->getClient( $this->getString( $invoice_data, 'client.name' ), $this->getString( $invoice_data, 'client.email' ) ),
			'discount'          => $this->getFloat( $invoice_data, 'invoice.discount' ),
			'po_number'         => $this->getString( $invoice_data, 'invoice.po_number' ),
			'date'              => isset( $invoice_data['invoice.date'] ) ? date( 'Y-m-d', strtotime( $invoice_data['invoice.date'] ) ) : null,
			'due_date'          => isset( $invoice_data['invoice.due_date'] ) ? date( 'Y-m-d', strtotime( $invoice_data['invoice.due_date'] ) ) : null,
			'terms'             => $this->getString( $invoice_data, 'invoice.terms' ),
			'public_notes'      => $this->getString( $invoice_data, 'invoice.public_notes' ),
			'is_sent'           => $this->getString( $invoice_data, 'invoice.is_sent' ),
			'private_notes'     => $this->getString( $invoice_data, 'invoice.private_notes' ),
			'tax_name1'         => $this->getString( $invoice_data, 'invoice.tax_name1' ),
			'tax_rate1'         => $this->getFloat( $invoice_data, 'invoice.tax_rate1' ),
			'tax_name2'         => $this->getString( $invoice_data, 'invoice.tax_name2' ),
			'tax_rate2'         => $this->getFloat( $invoice_data, 'invoice.tax_rate2' ),
			'tax_name3'         => $this->getString( $invoice_data, 'invoice.tax_name3' ),
			'tax_rate3'         => $this->getFloat( $invoice_data, 'invoice.tax_rate3' ),
			'custom_value1'     => $this->getString( $invoice_data, 'invoice.custom_value1' ),
			'custom_value2'     => $this->getString( $invoice_data, 'invoice.custom_value2' ),
			'custom_value3'     => $this->getString( $invoice_data, 'invoice.custom_value3' ),
			'custom_value4'     => $this->getString( $invoice_data, 'invoice.custom_value4' ),
			'footer'            => $this->getString( $invoice_data, 'invoice.footer' ),
			'partial'           => $this->getFloat( $invoice_data, 'invoice.partial' ),
			'partial_due_date'  => $this->getString( $invoice_data, 'invoice.partial_due_date' ),
			'custom_surcharge1' => $this->getString( $invoice_data, 'invoice.custom_surcharge1' ),
			'custom_surcharge2' => $this->getString( $invoice_data, 'invoice.custom_surcharge2' ),
			'custom_surcharge3' => $this->getString( $invoice_data, 'invoice.custom_surcharge3' ),
			'custom_surcharge4' => $this->getString( $invoice_data, 'invoice.custom_surcharge4' ),
			'exchange_rate'     => $this->getString( $invoice_data, 'invoice.exchange_rate' ),
			'status_id'         => $invoiceStatusMap[ $status =
					strtolower( $this->getString( $invoice_data, 'invoice.status' ) ) ] ??
				Invoice::STATUS_SENT,
			'viewed'            => $status === 'viewed',
			'archived'          => $status === 'archived',
		];

		if ( isset( $invoice_data['payment.amount'] ) ) {
			$transformed['payments'] = [
				[
					'date'                  => isset( $invoice_data['payment.date'] ) ? date( 'Y-m-d', strtotime( $invoice_data['payment.date'] ) ) : date( 'y-m-d' ),
					'transaction_reference' => $this->getString( $invoice_data, 'payment.transaction_reference' ),
					'amount'                => $this->getFloat( $invoice_data, 'payment.amount' ),
				],
			];
		} elseif ( $status === 'paid' ) {
			$transformed['payments'] = [
				[
					'date'                  => isset( $invoice_data['payment.date'] ) ? date( 'Y-m-d', strtotime( $invoice_data['payment.date'] ) ) : date( 'y-m-d' ),
					'transaction_reference' => $this->getString( $invoice_data, 'payment.transaction_reference' ),
					'amount'                => $this->getFloat( $invoice_data, 'invoice.amount' ),
				],
			];
		} elseif ( isset( $transformed['amount'] ) && isset( $transformed['balance'] ) ) {
			$transformed['payments'] = [
				[
					'date'                  => isset( $invoice_data['payment.date'] ) ? date( 'Y-m-d', strtotime( $invoice_data['payment.date'] ) ) : date( 'y-m-d' ),
					'transaction_reference' => $this->getString( $invoice_data, 'payment.transaction_reference' ),
					'amount'                => $transformed['amount'] - $transformed['balance'],
				],
			];
		}

		$line_items = [];
		foreach ( $line_items_data as $record ) {
			$line_items[] = [
				'quantity'           => $this->getFloat( $record, 'item.quantity' ),
				'cost'               => $this->getFloat( $record, 'item.cost' ),
				'product_key'        => $this->getString( $record, 'item.product_key' ),
				'notes'              => $this->getString( $record, 'item.notes' ),
				'discount'           => $this->getFloat( $record, 'item.discount' ),
				'is_amount_discount' => filter_var( $this->getString( $record, 'item.is_amount_discount' ), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE ),
				'tax_name1'          => $this->getString( $record, 'item.tax_name1' ),
				'tax_rate1'          => $this->getFloat( $record, 'item.tax_rate1' ),
				'tax_name2'          => $this->getString( $record, 'item.tax_name2' ),
				'tax_rate2'          => $this->getFloat( $record, 'item.tax_rate2' ),
				'tax_name3'          => $this->getString( $record, 'item.tax_name3' ),
				'tax_rate3'          => $this->getFloat( $record, 'item.tax_rate3' ),
				'custom_value1'      => $this->getString( $record, 'item.custom_value1' ),
				'custom_value2'      => $this->getString( $record, 'item.custom_value2' ),
				'custom_value3'      => $this->getString( $record, 'item.custom_value3' ),
				'custom_value4'      => $this->getString( $record, 'item.custom_value4' ),
				'type_id'            => $this->getInvoiceTypeId( $record, 'item.type_id' ),
			];
		}
		$transformed['line_items'] = $line_items;

		return $transformed;
	}
}
