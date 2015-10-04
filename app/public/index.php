<?php

require '../config/config.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ParameterBag;

$app->get('/', function () use ($app) {
    if ($_SESSION['login'] == "true") {
        return $app->redirect('/monitor');
    }
    return $app->redirect('/login');
});

$app->get('/login', function () use ($app) {
    if ($_SESSION['login'] == "true") {
        return $app->redirect('/monitor');
    }
    return $app['twig']->render('login.twig');
});

$app->post('/login', function (Request $request) use ($app) {

    $username = htmlspecialchars($request->request->get('username'));
    $password = sha1($request->request->get('password'));

    $sql   = "SELECT * FROM `users` WHERE username = ? AND password = ?";
    $login = $app['db']->fetchAssoc($sql, [$username, $password]);

    if ($login) {
        $_SESSION['login'] = "true";
        $_SESSION['token'] = sha1(uniqid('hackstuff', true));
        return $app->redirect('/monitor');
    } else {
        return $app->redirect('/login');
    }
});

$app->get('/logout', function () {
    unset($_SESSION['login']);
    return $app->redirect('/');
});

$app->get('/monitor', function () use ($app) {

    $sql  = "SELECT * FROM `http` ORDER BY timestamp ASC LIMIT 10";
    $http = $app['db']->fetchAll($sql);

    return $app['twig']->render('monitor.twig', [
        'items' => $http,
    ]);
});

$app->run();