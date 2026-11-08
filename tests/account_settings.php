<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use App\Auth\AccountSettingsService;
use App\Auth\AuthService;
use App\Auth\Guard;
use App\Core\Migrator;
use App\Core\Session;

$dsn = getenv('SETTINGS_TEST_DSN') ?: '';
if ($dsn === '' || str_contains(strtolower($dsn), 'dbname=')) {
    throw new InvalidArgumentException('Set SETTINGS_TEST_DSN to a disposable MySQL server DSN without dbname.');
}
$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false];
$pdo = new PDO($dsn, getenv('SETTINGS_TEST_USER') ?: 'root', getenv('SETTINGS_TEST_PASSWORD') ?: '', $options);
$database = 'bunat_settings_test_' . bin2hex(random_bytes(6));
$pdo->exec("CREATE DATABASE `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo->exec("USE `$database`");
$schema = file_get_contents(base_path('database/schema.sql'));
$pdo->exec(substr($schema, strpos($schema, 'CREATE TABLE')));
Migrator::run($pdo, base_path('database/migrations'));

$checks = [];
function verifySettings(bool $condition, string $label): void
{
    global $checks;
    if (! $condition) {
        throw new RuntimeException($label);
    }
    $checks[] = $label;
}
$auth = new AuthService($pdo);
require_once __DIR__ . '/TestAccountMailer.php';
$settingsMailer = new TestAccountMailer();
$settings = new AccountSettingsService($pdo, $settingsMailer);
$insert = $pdo->prepare('INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)');
foreach (['admin', 'user'] as $role) {
    $insert->execute(['Original Name', "$role@example.test", password_hash('Original123', PASSWORD_BCRYPT), $role]);
    $id = (int) $pdo->lastInsertId();
    $original = $auth->find($id);
    foreach (['', 'Incorrect123'] as $password) {
        verifySettings(isset($settings->updateProfile($id, 'Changed Name', 'changed@example.test', $password)['current_password']), "$role rejects missing or wrong profile password");
        verifySettings($auth->find($id) === $original, "$role leaves rejected profile unchanged");
    }
    verifySettings(isset($settings->updateProfile($id, 'A', 'invalid', 'Original123')['name']), "$role rejects invalid profile");
    verifySettings($auth->find($id) === $original, "$role invalid profile makes no write");
    verifySettings($settings->updateProfile($id, 'Updated Name', "$role@example.test", 'Original123') === [], "$role name update succeeds");
    verifySettings($settings->updateProfile($id, 'Updated Name', "new-$role@example.test", 'Original123') === [], "$role email verification request succeeds");
    verifySettings($auth->find($id)['email'] === "$role@example.test", "$role email stays unchanged until verification");
    preg_match('/code is: ([0-9]{6})/', end($settingsMailer->messages)['body'], $code);
    verifySettings($settings->verifyEmail($id, $code[1]) === [], "$role verified email update succeeds");
    verifySettings($settings->updateProfile($id, 'Updated Name', "new-$role@example.test", 'Original123') === [], "$role unchanged profile is accepted");
    $updated = $auth->find($id);
    verifySettings((int) $updated['id'] === $id && $updated['role'] === $role, "$role identity and role preserved");
    foreach ([['', 'Changed456', 'Changed456'], ['Wrong123', 'Changed456', 'Changed456'], ['Original123', 'Changed456', 'Mismatch789'], ['Original123', '', ''], ['Original123', 'Original123', 'Original123'], ['Original123', 'weak', 'weak'], ['Original123', str_repeat('A', 73) . 'a1', str_repeat('A', 73) . 'a1'], ['Original123', "New12345\0", "New12345\0"]] as $values) {
        verifySettings($settings->updatePassword($id, ...$values) !== [], "$role rejects invalid password change");
        verifySettings($auth->find($id)['password'] === $updated['password'], "$role rejected password remains unchanged");
    }
    verifySettings($settings->updatePassword($id, 'Original123', 'Changed456', 'Changed456') === [], "$role password update succeeds");
    verifySettings($auth->attempt("new-$role@example.test", 'Changed456') !== null, "$role new password authenticates");
    verifySettings($auth->attempt("new-$role@example.test", 'Original123') === null, "$role old password rejected");
    $oldSession = session_id();
    Guard::login($auth->find($id));
    verifySettings(session_id() !== $oldSession && Guard::user()['email'] === "new-$role@example.test", "$role session regeneration and refreshed account");
    Guard::logout();
    verifySettings(Guard::id() === null, "$role logout clears authentication");
}
$customer = $auth->findByEmail('new-user@example.test');
verifySettings(isset($settings->updateProfile((int) $customer['id'], 'Duplicate', 'NEW-ADMIN@example.test', 'Changed456')['email']), 'duplicate email rejected by unique constraint');
verifySettings($auth->find((int) $customer['id']) === $customer, 'duplicate email leaves account unchanged');
$before = $pdo->query('SELECT * FROM users ORDER BY id')->fetchAll();
verifySettings(Migrator::run($pdo, base_path('database/migrations')) === [], 'routine migrations are repeatable');
verifySettings($pdo->query('SELECT * FROM users ORDER BY id')->fetchAll() === $before, 'routine migrations preserve names emails and password hashes');
$registered = $auth->register(['name'=>'New Customer','email'=>'signup@example.test','password'=>'Signup123','password_confirmation'=>'Signup123']);
verifySettings(isset($registered['user']) && $auth->attempt('signup@example.test', 'Signup123') !== null, 'existing registration and authentication still work');
Session::flashInput(['name'=>'Safe','current_password'=>'secret','new_password'=>'secret','new_password_confirmation'=>'secret','password'=>'secret','password_confirmation'=>'secret']);
verifySettings(Session::get('_old_input') === ['name'=>'Safe'], 'flashed input excludes all password fields');
Session::clearOldInput();
$pdo->exec('DELETE FROM auth_limits');
foreach ($checks as $label) {
    echo "PASS: $label\n";
}
echo "TEST_DATABASE=$database\n";
