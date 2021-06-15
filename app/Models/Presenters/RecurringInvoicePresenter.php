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

use App\Utils\Number;
use App\Utils\Traits\MakesDates;
use Laracasts\Presenter\PresentableTrait;

/**
 * Class InvoicePresenter.
 *
 * For convenience and to allow users to easiliy
 * customise their invoices, we provide all possible
 * invoice variables to be available from this presenter.
 *
 * Shortcuts to other presenters are here to facilitate
 * a clean UI / UX
 */
class RecurringInvoicePresenter extends InvoicePresenter
{

}
