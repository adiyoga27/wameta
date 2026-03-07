<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$broadcast = \App\Models\Broadcast::where('status', 'draft')->orWhere('status', 'completed')->latest()->first();
if (!$broadcast) {
    echo "No broadcast found.\n";
    exit;
}
echo "Sending broadcast ID: {$broadcast->id}\n";
$controller = new \App\Http\Controllers\BroadcastController();
$controller->send($broadcast);
echo "Broadcast processing complete.\n";
