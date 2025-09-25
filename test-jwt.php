<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    echo "JWT Secret: " . config('jwt.secret') . "\n";

    $user = App\Models\User::where('email', 'admin@bellgas.com.au')->first();

    if ($user) {
        echo "User found: " . $user->email . "\n";

        try {
            $token = auth('api')->login($user);
            echo "Token generated successfully: " . substr($token, 0, 50) . "...\n";
        } catch (Exception $e) {
            echo "Token generation failed: " . $e->getMessage() . "\n";
        }
    } else {
        echo "User not found\n";
    }
} catch (Exception $e) {
    echo "General error: " . $e->getMessage() . "\n";
}