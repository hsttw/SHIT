<?php
require_once __DIR__.'/../../vendor/autoload.php';

$app = new Silex\Application();
$app['debug'] = true;
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../temples',
));
$app->register(new Silex\Provider\SessionServiceProvider());

$dbpath = __DIR__.'/../../backend/shit.db';

if (file_exists($dbpath)) {
    $app->register(new Silex\Provider\DoctrineServiceProvider(), [
        'db.options' => [
            'driver'   => 'pdo_sqlite',
            'path'     => $dbpath,
        ],
    ]);
}