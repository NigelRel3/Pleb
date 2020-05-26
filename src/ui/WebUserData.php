<?php
namespace Pleb\ui;

use Firebase\JWT\JWT;
use Pleb\dataaccess\Template;
use Pleb\dataaccess\UserData;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class WebUserData    {
    private $userData = null;
    private $template = null;
    
    public function __construct( UserData $userData, Template $template )    {
        $this->userData = $userData;
        $this->template = $template;
    }
    
    public function fetch ( Request $request, Response $response, $args )   {
        $secret = getenv("JWT_SECRET");
        try {
            $token = substr($request->getHeader('Authorization')[0], 7);
            $data = JWT::decode($token, $secret, ["HS256"]);
            $responseCode = 200;
            if ( $data = $this->userData->fetch($data->uuid, $args['infoType']) )  {
                $data = json_decode($data);
            }
            // If not found, check for a default template
            elseif ( $data = $this->template->fetchAuto($args['infoType']) )  {
                    $data = json_decode($data);
            }
            else    {
                $data = [ "Error" => "Not found:{$args['infoType']}"];
                $responseCode = 404;
            }
        }
        catch ( \Exception $e ) {
            $data = ["Exception" => $e];
        }
        $responseCode = 200;
        $response = $response->withHeader('Content-type', 'application/json')
            ->withStatus($responseCode);
        $body = $response->getBody();
        $body->write( json_encode($data, JSON_UNESCAPED_SLASHES) );
        return $response;
    }
    
    public function save ( Request $request, Response $response, $args )   {
        $secret = getenv("JWT_SECRET");
        $body = $request->getParsedBody();
        try {
            $token = substr($request->getHeader('Authorization')[0], 7);
            $data = JWT::decode($token, $secret, ["HS256"]);
            $newID = $this->userData->save($data->uuid, $args['infoType'], $body);
            $responseCode = 200;
            $data = [ "infoType" => $args['infoType'], "id" => $newID];
        }
        catch ( \Exception $e ) {
            $data = ["Exception" => $e ];
        }
        $responseCode = 200;
        $response = $response->withHeader('Content-type', 'application/json')
            ->withStatus($responseCode);
        $body = $response->getBody();
        $body->write( json_encode($data, JSON_UNESCAPED_SLASHES) );
        return $response;
    }
}