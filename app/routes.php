<?php
declare(strict_types=1);

use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use Dmtx\Reader;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

function isBase64Encoded($data)
{
    if (preg_match('%^[a-zA-Z0-9/+]*={0,2}$%', $data)) {
        return true;
    } else {
        return false;
    }
};

function generateName() {
    $length = 10;
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    $charactersLength = strlen($characters);
    $randomString = '';

    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }

    return $randomString;
}

return function (App $app) {
    $app->post('/decode', function(Request $request, Response $response) {
        $response = $response->withHeader('Content-Type', 'application/json');
        $contentType = $request->getHeaderLine('Content-Type');

        if (strstr($contentType, 'application/json')) {
            $contents = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $request = $request->withParsedBody($contents);
            }
        }

        $label = $request->getParsedBody();

        if(!array_key_exists('label', $label)) {
            $response->withStatus(400)->getBody()->write(json_encode(array(
                'message' => 'Label is null'
            )));
            return $response;
        }

        $label = $label['label'];

        if(!isBase64Encoded($label)) {
            $response->withStatus(400)->getBody()->write(json_encode(array(
                'message' => 'Label is not base64 encoded'
            )));
            return $response;
        }

        $zpl = base64_decode($label, true);

        $labelary = new Labelary\Client();

        try {
            $label = $labelary->printers->labels([
                'zpl' => $zpl,
            ]);
        } catch(Exception $e) {
            $response->withStatus(400)->getBody()->write(json_encode(array(
                'message' => 'Error generating labels. Please check the ZPL is valid and try again'
            )));
            return $response;
        }

        $labelResponse = json_decode($label);

        if(!isset($labelResponse->label)) {
            $response->withStatus(500)->getBody()->write(json_encode(array(
                'message' => 'Something went wrong with the image conversion. Please try again later'
            )));
            return $response;
        }

        $imageDecoded = $labelResponse->label;
        $imageDecoded = base64_decode($imageDecoded);

        $imageName = generateName();
        $imageFileName = $imageName . '.png';
        $imagePath = '/tmp/' . $imageFileName;

        if(!file_put_contents($imagePath, $imageDecoded)) {
            $response->withStatus(500)->getBody()->write(json_encode(array(
                'message' => 'Something went wrong with saving the image. Please try again later'
            )));
            return $response;
        }

        $reader = new Reader();

        try {
            $labelData = $reader->decodeFile($imagePath);
        } catch (Exception $e) {
            $response->withStatus(500)->getBody()->write(json_encode(array(
                'message' => 'Something went wrong with decoding the label. Please try again later'
            )));
            return $response;
        }

        $response->withStatus(200)->getBody()->write(json_encode(array(
            'message' => 'Returned label data',
            'data' => $labelData
        )));

        unlink($imagePath);

        return $response;
    });

    $app->group('/users', function (Group $group) {
        $group->get('', ListUsersAction::class);
        $group->get('/{id}', ViewUserAction::class);
    });
};
