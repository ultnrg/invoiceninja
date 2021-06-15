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

/**
 * Simple helper function that will log into "invoiceninja.log" file
 * only when extended logging is enabled.
 *
 * @param mixed $output
 * @param array $context
 *
 * @return void
 */
function nlog($output, $context = []): void
{
    
    if (!config('ninja.expanded_logging')) 
        return;

        if (gettype($output) == 'object') {
            $output = print_r($output, 1);
        }

        $trace = debug_backtrace();
        //nlog( debug_backtrace()[1]['function']);
        // \Illuminate\Support\Facades\Log::channel('invoiceninja')->info(print_r($trace[1]['class'],1), []);
        \Illuminate\Support\Facades\Log::channel('invoiceninja')->info($output, $context);
    
}

// if (!function_exists('ray'))   {
// 	function ray($payload)
// 	{
// 		return true;
// 	}
// }

