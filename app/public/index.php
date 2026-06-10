<?php

require __DIR__.'/../config/config.php';

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

function redirect(Response $res, string $to): Response
{
    return $res->withHeader('Location', $to)->withStatus(302);
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

$app->run();
