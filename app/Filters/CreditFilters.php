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

namespace App\Filters;

use App\Models\Credit;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class CreditFilters extends QueryFilters
{
    /**
     * Filter based on client status.
     *
     * Statuses we need to handle
     * - all
     * - paid
     * - unpaid
     * - overdue
     * - reversed
     *
     * @param string credit_status The credit status as seen by the client
     * @return Builder
     */
    public function credit_status(string $value = '') :Builder
    {
        if (strlen($value) == 0) {
            return $this->builder;
        }

        $status_parameters = explode(',', $value);

        if (in_array('all', $status_parameters)) {
            return $this->builder;
        }

        if (in_array('draft', $status_parameters)) {
            $this->builder->where('status_id', Credit::STATUS_DRAFT);
        }

        if (in_array('partial', $status_parameters)) {
            $this->builder->where('status_id', Credit::STAUTS_PARTIAL);
        }

        if (in_array('applied', $status_parameters)) {
            $this->builder->where('status_id', Credit::STATUS_APPLIED);
        }

        //->where('due_date', '>', Carbon::now())
        //->orWhere('partial_due_date', '>', Carbon::now());

        return $this->builder;
    }

    /**
     * Filter based on search text.
     *
     * @param string query filter
     * @return Builder
     * @deprecated
     */
    public function filter(string $filter = '') : Builder
    {
        if (strlen($filter) == 0) {
            return $this->builder;
        }

        return  $this->builder->where(function ($query) use ($filter) {
            $query->where('credits.number', 'like', '%'.$filter.'%')
                          ->orWhere('credits.number', 'like', '%'.$filter.'%')
                          ->orWhere('credits.date', 'like', '%'.$filter.'%')
                          ->orWhere('credits.amount', 'like', '%'.$filter.'%')
                          ->orWhere('credits.balance', 'like', '%'.$filter.'%')
                          ->orWhere('credits.custom_value1', 'like', '%'.$filter.'%')
                          ->orWhere('credits.custom_value2', 'like', '%'.$filter.'%')
                          ->orWhere('credits.custom_value3', 'like', '%'.$filter.'%')
                          ->orWhere('credits.custom_value4', 'like', '%'.$filter.'%');
        });
    }

    /**
     * Filters the list based on the status
     * archived, active, deleted - legacy from V1.
     *
     * @param string filter
     * @return Builder
     */
    public function status(string $filter = '') : Builder
    {
        if (strlen($filter) == 0) {
            return $this->builder;
        }

        $table = 'credits';
        $filters = explode(',', $filter);

        return $this->builder->where(function ($query) use ($filters, $table) {
            $query->whereNull($table.'.id');

            if (in_array(parent::STATUS_ACTIVE, $filters)) {
                $query->orWhereNull($table.'.deleted_at');
            }

            if (in_array(parent::STATUS_ARCHIVED, $filters)) {
                $query->orWhere(function ($query) use ($table) {
                    $query->whereNotNull($table.'.deleted_at');

                    if (! in_array($table, ['users'])) {
                        $query->where($table.'.is_deleted', '=', 0);
                    }
                });
            }

            if (in_array(parent::STATUS_DELETED, $filters)) {
                $query->orWhere($table.'.is_deleted', '=', 1);
            }
        });
    }

    /**
     * Sorts the list based on $sort.
     *
     * @param string sort formatted as column|asc
     * @return Builder
     */
    public function sort(string $sort) : Builder
    {
        $sort_col = explode('|', $sort);

        return $this->builder->orderBy($sort_col[0], $sort_col[1]);
    }

    /**
     * Returns the base query.
     *
     * @param int company_id
     * @param User $user
     * @return Builder
     * @deprecated
     */
    public function baseQuery(int $company_id, User $user) : Builder
    {
        // ..
    }

    /**
     * Filters the query by the users company ID.
     *
     * We need to ensure we are using the correct company ID
     * as we could be hitting this from either the client or company auth guard
     *
     * @return Illuminate\Database\Query\Builder
     */
    public function entityFilter()
    {
        if (auth('contact')->user()) {
            return $this->contactViewFilter();
        } else {
            return $this->builder->company();
        }

//            return $this->builder->whereCompanyId(auth()->user()->company()->id);
    }

    /**
     * We need additional filters when showing credits for the
     * client portal. Need to automatically exclude drafts and cancelled credits.
     *
     * @return Builder
     */
    private function contactViewFilter() : Builder
    {
        return $this->builder
                    ->whereCompanyId(auth('contact')->user()->company->id)
                    ->whereNotIn('status_id', [Credit::STATUS_DRAFT]);
    }
}
