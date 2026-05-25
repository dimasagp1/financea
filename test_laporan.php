<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::first();
auth()->login($user);

$request = Illuminate\Http\Request::create('/fat/laporan?period=3', 'GET');
$response = app()->handle($request);

if ($response->getStatusCode() == 500) {
    if (isset($response->exception)) {
        echo $response->exception->getMessage() . " in " . $response->exception->getFile() . ":" . $response->exception->getLine() . "\n";
    } else {
        echo "500 Error but no exception.\n";
    }
} else {
   echo "Success: " . $response->getStatusCode() . "\n";
}
