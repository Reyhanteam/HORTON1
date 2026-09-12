<?php

namespace App\Contracts;

use App\Models\Invoice;
use App\Models\Order;

interface InvoiceService
{
    public function issue(Order $order): Invoice;

    public function markPaid(Order $order): Invoice;
}
