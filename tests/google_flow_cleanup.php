<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use App\Auth\GoogleController;
use App\Auth\Guard;
use App\Core\Session;

// No real Google request or database access: disabled provider exercises failure cleanup.
putenv('GOOGLE_CLIENT_ID=');
putenv('GOOGLE_CLIENT_SECRET=');
Guard::login(['id'=>123, 'email'=>'fixture@example.test', 'password'=>null, 'role'=>'user']);
Session::put('google_verified', ['id'=>123, 'stamp'=>'old', 'until'=>time()+300]);
Session::put('google_flow', ['mode'=>'verify', 'started'=>time()]);
$_POST = ['_token'=>Session::csrfToken(), 'mode'=>'verify'];
$mode = $argv[1] ?? 'start';
if (!in_array($mode, ['start', 'callback'], true)) { throw new RuntimeException('Unknown test mode'); }
register_shutdown_function(static function () use ($mode): void {
    if (Session::has('google_verified') || Session::has('google_flow')) {
        throw new RuntimeException('Failed Google flow retained old verification');
    }
    echo "PASS: {$mode} failure removes previous Google approval\n";
});
(new GoogleController())->{$mode}();
