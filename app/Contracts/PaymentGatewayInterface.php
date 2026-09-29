<?php

namespace App\Contracts;

interface PaymentGatewayInterface
{
    public function generateQris($transaction, $invoice): ?array;
    public function verifyPayment(string $reference): array;
    public function cancelPayment(string $reference): bool;
}