<?php

use Firebase\JWT\JWT;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

if (PHP_SAPI == 'cli-server') {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file
    $url  = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    if (is_file($file)) {
        return false;
    }
}

require __DIR__ . '/../vendor/autoload.php';

session_start();

// Instantiate the app
$settings = require __DIR__ . '/../src/settings.php';
$app = new \Slim\App($settings);

// Set up dependencies
$dependencies = require __DIR__ . '/../src/dependencies.php';
$dependencies($app);

// Register middleware
$middleware = require __DIR__ . '/../src/middleware.php';
$middleware($app);

// Register routes
$routes = require __DIR__ . '/../src/routes.php';
$routes($app);

//import
require __DIR__. '/../src/common.php';

// Run app
$app->run();

function encode(Request $request, Response $response)
{
    $data = $request->getParsedBody();
    $header = $request->getHeaders();

    $key = "mangetesmorts";

    $playload = array(
        "iss" => "Lafont_Bukin_babadia",
        "iat" => time(),
        "exp" => time() + (10000),
        "context" => [
            "user" => [
                "username" => $header['username'],
                "user_id" => $header['id']
            ]
        ]
    );

    try {
        $jwt = JWT::encode($playload, $key);
    } catch (Exception $e) {
        echo json_encode($e);
    }

    if (jwt == $header['jwt']) {
        $httpcode = 200;
        //TODO ENCODE
    } else {
        $httpcode = 403;
        displayErrorJSON("Forbidden");
    }
    return $response->withHeader('Content-Type', 'application/json')
        ->withStatus($httpcode);
}
