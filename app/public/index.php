<?php

require __DIR__.'/../config/config.php';

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

function redirect(Response $res, string $to): Response
{
    return $res->withHeader('Location', $to)->withStatus(302);
}

/**
 * Parse a dnsmasq DHCP lease file into the devices connected to the fake AP.
 * Each line is "<expiry-epoch> <mac> <ip> <hostname> <client-id>". A missing or
 * unreadable file yields an empty list rather than an error (the AP may not be
 * running, e.g. on a dev box).
 */
function parse_leases(string $path): array
{
    if (!is_readable($path)) {
        return [];
    }

    $devices = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $f = preg_split('/\s+/', trim($line));
        if (count($f) < 4) {
            continue;
        }
        [$expiry, $mac, $ip, $host] = $f;
        $devices[] = [
            'mac'      => $mac,
            'ip'       => $ip,
            'hostname' => ($host === '*' ? '(unknown)' : $host),
            'expiry'   => ctype_digit($expiry) ? date('Y-m-d H:i:s', (int) $expiry) : $expiry,
        ];
    }

    return $devices;
}

$app->get('/', function (Request $req, Response $res) {
    return redirect($res, empty($_SESSION['user']) ? '/login' : '/monitor');
});

$app->get('/login', function (Request $req, Response $res) use ($twig) {
    if (!empty($_SESSION['user'])) {
        return redirect($res, '/monitor');
    }
    $res->getBody()->write($twig->render('login.twig'));
    return $res;
});

$app->post('/login', function (Request $req, Response $res) use ($db) {
    $body     = (array) $req->getParsedBody();
    $username = htmlspecialchars($body['username'] ?? '');
    $password = sha1($body['password'] ?? '');

    $sql   = 'SELECT * FROM `users` WHERE username = ? AND password = ?';
    $login = $db ? $db->fetchAssociative($sql, [$username, $password]) : false;

    if ($login) {
        $_SESSION['user']  = ['username' => $username];
        $_SESSION['token'] = sha1(uniqid($username, true));
        return redirect($res, '/monitor');
    }

    return redirect($res, '/login');
});

$app->get('/logout', function (Request $req, Response $res) {
    unset($_SESSION['user']);
    return redirect($res, '/');
});

$app->get('/monitor', function (Request $req, Response $res) use ($twig, $db) {
    if (empty($_SESSION['user'])) {
        return redirect($res, '/login');
    }

    $sql  = 'SELECT * FROM `http` ORDER BY timestamp ASC LIMIT 10';
    $http = $db ? $db->fetchAllAssociative($sql) : [];

    $res->getBody()->write($twig->render('monitor.twig', ['items' => $http]));
    return $res;
});

$app->get('/monitor/notification', function (Request $req, Response $res) use ($twig, $leaseFile) {
    if (empty($_SESSION['user'])) {
        return redirect($res, '/login');
    }

    $res->getBody()->write($twig->render('notification.twig', [
        'devices' => parse_leases($leaseFile),
    ]));
    return $res;
});

$app->run();
