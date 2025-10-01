<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$u = User::where('email','test@example.com')->first();
if (!$u) {
    echo "no user\n";
    exit(1);
}
// if using Laravel Passport, users have createToken
$token = $u->createToken('cli-token')->accessToken ?? null;
if (!$token) {
    // In newer Laravel, createToken returns PersonalAccessTokenResult with 'accessToken'
    $t = $u->createToken('cli-token');
    if (is_object($t) && isset($t->accessToken)) {
        $token = $t->accessToken;
    }
}
if (!$token) {
    echo "failed to create token\n";
    exit(1);
}

echo $token . PHP_EOL;
