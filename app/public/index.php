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

/** Absolute callback URL GitHub redirects back to, derived from the request. */
function github_redirect_uri(Request $req): string
{
    $uri = $req->getUri();
    $base = $uri->getScheme() . '://' . $uri->getAuthority();
    return $base . '/login/github/callback';
}

/**
 * Exchange an OAuth code for an access token and return the GitHub login name,
 * or null on any failure. Kept dependency-free (curl) since the app has no HTTP
 * client. Requires a registered GitHub OAuth app (client id/secret via env).
 */
function github_login(array $github, string $code, string $redirectUri): ?string
{
    $token = github_post('https://github.com/login/oauth/access_token', [
        'client_id'     => $github['client_id'],
        'client_secret' => $github['client_secret'],
        'code'          => $code,
        'redirect_uri'  => $redirectUri,
    ]);
    if (empty($token['access_token'])) {
        return null;
    }

    $user = github_get('https://api.github.com/user', $token['access_token']);
    return $user['login'] ?? null;
}

function github_post(string $url, array $body): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($body),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => ['Accept: application/json', 'User-Agent: SHIT'],
    ]);
    $out = curl_exec($ch);
    curl_close($ch);
    return $out ? (json_decode($out, true) ?: []) : [];
}

function github_get(string $url, string $accessToken): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => [
            'Accept: application/vnd.github+json',
            'Authorization: Bearer ' . $accessToken,
            'User-Agent: SHIT',
        ],
    ]);
    $out = curl_exec($ch);
    curl_close($ch);
    return $out ? (json_decode($out, true) ?: []) : [];
}

$app->get('/', function (Request $req, Response $res) {
    return redirect($res, empty($_SESSION['user']) ? '/login' : '/monitor');
});

$app->get('/login', function (Request $req, Response $res) use ($twig, $github) {
    if (!empty($_SESSION['user'])) {
        return redirect($res, '/monitor');
    }
    $res->getBody()->write($twig->render('login.twig', [
        'github_enabled' => $github['client_id'] !== '',
    ]));
    return $res;
});

// Step 1 of GitHub OAuth: bounce the user to GitHub's authorize page with a
// CSRF state we stash in the session (issue #8).
$app->get('/login/github', function (Request $req, Response $res) use ($github) {
    if ($github['client_id'] === '') {
        return redirect($res, '/login');
    }
    $state = bin2hex(random_bytes(16));
    $_SESSION['oauth_state'] = $state;

    $url = 'https://github.com/login/oauth/authorize?' . http_build_query([
        'client_id'    => $github['client_id'],
        'redirect_uri' => github_redirect_uri($req),
        'scope'        => 'read:user',
        'state'        => $state,
    ]);
    return redirect($res, $url);
});

// Step 2: GitHub redirects back with ?code&state. Verify state, swap the code
// for a token, fetch the user, and log them in.
$app->get('/login/github/callback', function (Request $req, Response $res) use ($github) {
    $q = $req->getQueryParams();
    if ($github['client_id'] === ''
        || empty($q['state']) || empty($_SESSION['oauth_state'])
        || !hash_equals($_SESSION['oauth_state'], $q['state'])
        || empty($q['code'])) {
        unset($_SESSION['oauth_state']);
        return redirect($res, '/login');
    }
    unset($_SESSION['oauth_state']);

    $login = github_login($github, $q['code'], github_redirect_uri($req));
    if ($login) {
        $_SESSION['user']  = ['username' => $login];
        $_SESSION['token'] = sha1(uniqid($login, true));
        return redirect($res, '/monitor');
    }
    return redirect($res, '/login');
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
