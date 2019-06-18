<?php

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise;
use Psr\Http\Message\ResponseInterface;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;

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


session_start();
require __DIR__ . '/../vendor/autoload.php';

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
function authenticateJwt($path)
{
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
        return $jwt;
    } catch (Exception $e) {
        echo json_encode($e);
        return "OUuups";
    }
}

function encode(Request $request, Response $response)
{
    $jwtApi = $request->getHeaderLine('JWT');
    $path = $request->getHeaderLine('PATH');
    $source = $request->getHeaderLine('SOURCE');
    $id_video = $request->getHeaderLine('ID_VIDEO');
    $jwt = authenticateJwt($path);

    if ($jwt == $jwtApi) {
        $httpcode = 201;
        encoding($path, $source, $id_video);


        echo(json_encode("c bon"));
        //TODO envoyer notif
    } else {
        $httpcode = 403;
        displayErrorJSON("Forbidden");
    }
    return $response->withHeader('Content-Type', 'application/json')
        ->withStatus($httpcode);

}


function encoding($path, $source, $id)
{
    $sizers = array(1080 => 1920, 720 => 1280, 480 => 720, 360 => 480, 240 => 352);
    $keys = array_keys($sizers);
    $values = array_Values($sizers);
    $ffmpeg = FFMpeg\FFMpeg::create();
    $video = $ffmpeg->open($path);
    $directory = '/home/raphael/Desktop/';
    $cmd = shell_exec('ffprobe -v error -select_streams v:0 -show_entries stream=width,height -of default=nw=1:nk=1 ' . $path);
    $tableau = preg_split('/[\n]+/', $cmd);
    $height = $tableau[1];
    $index = array_search($height, array_keys($sizers));

    $client = new Client();
    for ($i = $index; $i < sizeof($sizers); $i++) {
        $mp4Format = new X264();
        $mp4Format->setAudioCodec("aac");
        $video
            ->filters()
            ->resize(new FFMpeg\Coordinate\Dimension($values[$i], $keys[$i]))
            ->synchronize();
        $video
            ->frame(FFMpeg\Coordinate\TimeCode::fromSeconds(10))
            ->save('frame.jpg');
        $video
            ->save($mp4Format, $directory . preg_split("/[.]+/",$source)[0] . '_' . $keys[$i] . '.mp4');
        $path = $directory . preg_split("/[.]+/",$source)[0]. '_' . $keys[$i] . '.mp4';
        $jwt = authenticateJwt($path);

        try {
            $client->request('GET', '192.168.197.133:8080/api/updateVideoFormat', [
                'headers' => [
                    'PATH' => $directory . preg_split("/[.]+/",$source)[0]. '_' . $keys[$i] . '.mp4',
                    "ID_VIDEO" => $id,
                    'FORMAT' => $keys[$i],
                    'JWT' => $jwt,
                ],

            ]);
        } catch (GuzzleException $e) {
            error_log($e);
        }
    }

}
