<?php 
require_once __DIR__ . '/../vendor/autoload.php';

use DI\ContainerBuilder;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Pleb\dataaccess\User;
use Pleb\dataaccess\UserData;
use Pleb\dataaccess\Template;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Slim\Factory\AppFactory;
use Tuupola\Middleware\JwtAuthentication;

// Temp
putenv("JWT_SECRET=1234abc");

$containerBuilder = new ContainerBuilder();
// configure PHP-DI here
$containerBuilder->addDefinitions([
    'settings' => [
        'displayErrorDetails' => true, // Should be set to false in production
        'logger' => [
            'name' => 'Pleb',
            'path' => '../log/log.txt',
            'level' => Logger::DEBUG,
        ],
        'database' => [
            'user' => 'Pleb',
            'password' => 'RNjjJBkgduyTx7zl',
            'host' => '172.17.0.4',
            'database' => 'Pleb'
        ]
    ],
]);

$containerBuilder->addDefinitions([
    LoggerInterface::class => function (ContainerInterface $c) {
        $settings = $c->get('settings')['logger'];
        
        $logger = new Logger($settings['name']);
        $handler = new StreamHandler($settings['path'], $settings['level']);
        $logger->pushHandler($handler);
        
        return $logger;
    },
    ]);

$containerBuilder->addDefinitions([
    PDO::class => function (ContainerInterface $c) {
        $settings = $c->get('settings')['database'];
        
        $db = new PDO("mysql:host={$settings['host']};dbname={$settings['database']}",
            $settings['user'], $settings['password']);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        return $db;
    },
    ]);

$containerBuilder->addDefinitions([
    User::class => function (ContainerInterface $c) {
        return new User($c->get(PDO::class));
    },
    ]);
$containerBuilder->addDefinitions([
    UserData::class => function (ContainerInterface $c) {
        return new UserData($c->get(PDO::class), $c->get(Template::class));
    },
    ]);
$containerBuilder->addDefinitions([
    Template::class => function (ContainerInterface $c) {
        return new Template($c->get(PDO::class));
    },
    ]);

AppFactory::setContainer($containerBuilder->build());

$app = AppFactory::create();

$app->addBodyParsingMiddleware();

$container = $app->getContainer();

$app->add( new JwtAuthentication([
    "secure" => true,
    "relaxed" => ["172.17.0.1", "172.17.0.3"],
    "algorithm" => ["HS256", "HS384"],
    "path" => "/",
    "ignore" => ["/login", "/home", "/favicon.ico", "/ui/resources/*"],
    "secret" => getenv("JWT_SECRET"),
    "logger" => $container->get(LoggerInterface::class),
    "attribute" => "jwt",
    "error" => function ($response, $arguments) {
    $data["status"] = "error";
    $data["message"] = $arguments["message"];
    return $response
    ->withHeader("Content-Type", "application/json")
    ->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    },
    //     "before" => function ($request, $arguments) use ($container) {
    //         $container["token"]->populate($arguments["decoded"]);
    //     }
    ]));
