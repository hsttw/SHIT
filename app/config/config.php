<?php
require_once __DIR__.'/../../vendor/autoload.php';

use Slim\Factory\AppFactory;

// Native PHP session (replaces the old Silex SessionServiceProvider).
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$app = AppFactory::create();
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

// Twig 3, wired directly (no framework provider to keep us off abandoned deps).
$twig = new \Twig\Environment(
    new \Twig\Loader\FilesystemLoader(__DIR__.'/../temples'),
    ['debug' => true]
);

// SQLite via Doctrine DBAL 3.
$dbpath = __DIR__.'/../../backend/shit.db';
$db = null;
if (file_exists($dbpath)) {
    $db = \Doctrine\DBAL\DriverManager::getConnection([
        'driver' => 'pdo_sqlite',
        'path'   => $dbpath,
    ]);
}

// dnsmasq DHCP lease file. fakeAP's dnsmasqStart() pins this to /tmp/dnsmasq.lease;
// overridable for dev/testing where the path differs.
$leaseFile = getenv('SHIT_LEASE_FILE') ?: '/tmp/dnsmasq.lease';

// GitHub OAuth (issue #8). Credentials come from the environment so secrets
// never land in the repo; if unset, the GitHub login button is simply hidden
// and password login keeps working unchanged.
$github = [
    'client_id'     => getenv('GITHUB_CLIENT_ID') ?: '',
    'client_secret' => getenv('GITHUB_CLIENT_SECRET') ?: '',
];
