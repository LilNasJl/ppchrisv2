<?php

namespace App\Exceptions;

use RuntimeException;

class PendingOvertimeApprovalsException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Resolve all pending overtime approvals before locking this payroll period.');
    }
}
