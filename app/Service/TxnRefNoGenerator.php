<?php

namespace App\Service;

use App\Models\Transaction;
use RuntimeException;

/**
 * Generates unique 20-character transaction reference numbers
 * suitable for JazzCash / Easypaisa payment gateway requirements.
 *
 * Format: T + YYYYMMDDHHMMSS + 4 HEX characters + 1 random digit
 * Example: T20260805131522A9F47
 */
class TxnRefNoGenerator
{
    private const MAX_ATTEMPTS = 5;

    /**
     * Generate a unique transaction reference number.
     *
     * Uses cryptographically secure randomness and verifies uniqueness
     * against the indexed transactions.txn_ref_no column before returning.
     *
     * @return string Exactly 20 characters (e.g. T20260805131522A9F47)
     *
     * @throws RuntimeException When a unique reference cannot be generated after 5 attempts
     */
    public function generateTxnRefNo(): string
    {
        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            $txnRefNo = $this->buildTxnRefNo();

            if (! Transaction::where('txn_ref_no', $txnRefNo)->exists()) {
                return $txnRefNo;
            }
        }

        throw new RuntimeException(
            'Unable to generate a unique txn_ref_no after ' . self::MAX_ATTEMPTS . ' attempts.'
        );
    }

    /**
     * Build a single txn_ref_no candidate without checking uniqueness.
     *
     * @return string
     */
    private function buildTxnRefNo(): string
    {
        $dateTime = (new \DateTime())->format('YmdHis');
        $hex = strtoupper(bin2hex(random_bytes(2)));
        $digit = (string) random_int(0, 9);

        return 'T' . $dateTime . $hex . $digit;
    }
}
