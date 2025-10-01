<?php
// Bootstrap Laravel to run simple queries outside tinker
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\BudgetHolder;
use App\Models\TreasuryAccount;

echo "BudgetHolders:\n";
$rows = BudgetHolder::orderBy('created_at','desc')->limit(5)->get(['id','tin','name','created_by','created_at']);
foreach ($rows as $r) {
    echo json_encode($r->toArray(), JSON_UNESCAPED_UNICODE) . "\n";
}

echo "TreasuryAccounts:\n";
$rows = TreasuryAccount::orderBy('created_at','desc')->limit(5)->get(['id','account','name','created_by','created_at']);
foreach ($rows as $r) {
    echo json_encode($r->toArray(), JSON_UNESCAPED_UNICODE) . "\n";
}
