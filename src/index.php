<?php 
require_once __DIR__ . '/../vendor/autoload.php';

use Pleb\ui\WebUser;
use Pleb\ui\WebUserData;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Routing\RouteCollectorProxy;

require_once __DIR__ . '/config.php';

$app->get('/home', function (Request $request, Response $response, $args) {
    $response->getBody()->write(file_get_contents("ui/resources/main.html"));
    return $response;
});

$app->post('/login', WebUser::class.":login");

$app->group( '/userdata', function (RouteCollectorProxy $group) {
    $group->get('/{infoType}', WebUserData::class.":fetch");
    $group->post('/{infoType}', WebUserData::class.":save");
});
    
//$container->get(LoggerInterface::class)->info("Logging enabled");

$app->addErrorMiddleware(false, true, true, $container->get(LoggerInterface::class));
    
$app->run();
