<?php
namespace Pleb\ui;

use Firebase\JWT\JWT;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Pleb\dataaccess\User;

class WebUser    {
    private $user = null;
    
    public function __construct( User $user )    {
        $this->user = $user;
    }
    
    public function login ( Request $request, Response $response )   {
        $responseCode = 200;
        $parsedBody = $request->getParsedBody();
        $data = [];
        if ( $this->user->login($parsedBody['username'] ?? '', $parsedBody['password'] ?? '') ) {
             // Generate JWT token
            $now = new \DateTime();
            $future = new \DateTime("now +2 hours");
            $payload = [
                "iat" => $now->getTimeStamp(),
                "exp" => $future->getTimeStamp(),
                "uuid" => $this->user->getUUID()
            ];
            $secret = getenv("JWT_SECRET");
            $token = JWT::encode($payload, $secret, "HS256");
            
            $data = [ "token" => $token, "expires" => $future->getTimeStamp()];
        }
        else    {
            $responseCode = 401;
        }
        
        $response = $response->withHeader('Content-type', 'application/json')
            ->withStatus($responseCode);
        $body = $response->getBody();
        $body->write( json_encode($data, JSON_UNESCAPED_SLASHES) );
        return $response;
    }
}