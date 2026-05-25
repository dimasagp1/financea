<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$coll = collect([10 => collect([73 => 100])]);
echo "data_get integer keys: " . data_get($coll, '10.73', 0) . "\n";
echo "data_get string keys: " . data_get($coll, 10 . '.' . 73, 0) . "\n";
