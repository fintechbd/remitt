<?php

namespace Fintech\Remit\Contracts;

use Fintech\Remit\Support\WalletVerificationVerdict;

interface WalletVerification
{
    /**
     * Run a wallet verification
     * and return conclusion if it is valid or not
     *
     * @return WalletVerificationVerdict
     */
    public function __invoke(): WalletVerificationVerdict;
}