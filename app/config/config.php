<?php
require_once __DIR__.'/../../vendor/autoload.php';

session_start();
$app = new Silex\Application();
$app['debug'] = true;
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../temples',
));

$dbpath = __DIR__.'/../../app.db';

if (file_exists($dbpath)) {
    $app->register(new Silex\Provider\DoctrineServiceProvider(), [
        'db.options' => [
            'driver'   => 'pdo_sqlite',
            'path'     => $dbpath,
        ],
    ]);
}