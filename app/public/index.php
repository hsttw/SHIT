<?php

require '../config/config.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ParameterBag;

$app->get('/', function () use ($app) {
    if ($app['session']->get('user') == null) {
        return $app->redirect('/login');
    }

    return $app->redirect('/monitor');
});

$app->get('/login', function () use ($app) {
    if ($app['session']->get('user') == null) {
        return $app['twig']->render('login.twig');
    }

    return $app->redirect('/monitor');
});

$app->post('/login', function (Request $request) use ($app) {

    $username = htmlspecialchars($request->request->get('username'));
    $password = sha1($request->request->get('password'));

    $sql   = "SELECT * FROM `users` WHERE username = ? AND password = ?";
    $login = $app['db']->fetchAssoc($sql, [$username, $password]);

    if ($login) {
        $app['session']->set('user', ['username' => $username]);
        $app['session']->set('token', sha1(uniqid($username, true)));
        return $app->redirect('/monitor');
    } else {
        return $app->redirect('/login');
    }
});

$app->get('/logout', function () use ($app) {
    $app['session']->set('user', null);
    return $app->redirect('/');
});

$app->get('/monitor', function () use ($app) {
    if ($app['session']->get('user') == null) {
        return $app->redirect('/login');
    }

    $sql  = "SELECT * FROM `http` ORDER BY timestamp ASC LIMIT 10";
    $http = $app['db']->fetchAll($sql);

    return $app['twig']->render('monitor.twig', [
        'items' => $http,
    ]);
});

$app->run();