<?php

use Firebase\JWT\JWT;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

use FFMpeg\Format\Video\X264;

if (PHP_SAPI == 'cli-server') {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file
    $url = parse_url($_SERVER['REQUEST_URI']);
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
require __DIR__ . '/../src/common.php';

// Run app
$app->run();

function encode(Request $request, Response $response)
{

    encoding($request);
    $header = $request->getHeaders();
    $jwtApi = $header['JWT'];
    $path = $header['PATH'];

    $key = "mangetesmorts";

    $playload = array(
        "iss" => "Lafont_Bukin_babadia",
        "iat" => time(),
        "exp" => time() + (10000),
        "context" => [
            "user" => [
                "path" => $path,
            ]
        ]
    );

    try {
        $jwt = JWT::encode($playload, $key);
    } catch (Exception $e) {
        echo json_encode($e);
    }

    if (jwt == $jwtApi) {
        $httpcode = 200;
        encoding($request);
        //TODO envoyer notif
    } else {
        $httpcode = 403;
        displayErrorJSON("Forbidden");
    }
    return $response->withHeader('Content-Type', 'application/json');

}

function encoding(Request $request)
{
    $path = $request->getAttribute("PATH");
    $ffmpeg = FFMpeg\FFMpeg::create();
    $video = $ffmpeg->open('/home/raphael/Desktop/test.mp4');
    $directory = '/home/raphael/Desktop/';
    $cmd = shell_exec('ffprobe -v error -select_streams v:0 -show_entries stream=width,height -of default=nw=1:nk=1' . $path);
    $tableau = preg_split('/[\n]+/', $cmd);
    $width = $tableau[0];
    $height = $tableau[1];
    $mp4Format = new X264();
    $mp4Format->setAudioCodec("aac");
    $video
        ->filters()
        ->resize(new FFMpeg\Coordinate\Dimension(320, 240))
        ->synchronize();
    $video
        ->frame(FFMpeg\Coordinate\TimeCode::fromSeconds(10))
        ->save('frame.jpg');
    $video
        ->save($mp4Format, $directory .'/video2.mp4');
}
