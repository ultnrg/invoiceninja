<?php

namespace App\Exceptions;

use Exception;

class PaymentFailed extends Exception
{
    public function report()
    {
        // ..
    }

    public function render($request)
    {
        if (auth()->user()) {
            return render('gateways.unsuccessful', [
                'message' => $this->getMessage(),
                'code' => $this->getCode(),
            ]);
        }

        return response([
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
        ]);
    }
}
