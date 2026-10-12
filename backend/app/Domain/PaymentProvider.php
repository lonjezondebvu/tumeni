<?php
namespace App\Domain;
interface PaymentProvider { public function verify(string $raw,string $signature): array; }