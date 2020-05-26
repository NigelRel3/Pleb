<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', "1");

require_once __DIR__ . '/../vendor/autoload.php';

use Pleb\ui\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\StreamInterface;
use Tuupola\Http\Factory\RequestFactory;
use Tuupola\Http\Factory\ResponseFactory;

// Temp
putenv("JWT_SECRET=1234abc");

$host = "172.17.0.4";
$user = "Pleb";
$password = "RNjjJBkgduyTx7zl";
$database = "Pleb";
$db = new PDO("mysql:host={$host};dbname={$database}",
$user, $password);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$user = new User($db);

$rf = new RequestFactory();

$request = $rf->createRequest("GET", "172.17.0.1/login/root/locked");
$response = (new ResponseFactory())->createResponse();

$user->login($request, $response, ["username" => "admin",
    "password" => "locked"
]);

echo $response->getBody();