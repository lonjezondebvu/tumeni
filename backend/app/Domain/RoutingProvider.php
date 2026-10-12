<?php
namespace App\Domain;
interface RoutingProvider { public function route(array $pickup,array $destination): ?array; }