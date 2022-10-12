<?php

namespace App\Http\Controllers;

use Dmtx\Reader;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Labelary\Client;
use Laravel\Lumen\Routing\Controller as BaseController;

class DecodeController extends BaseController
{
    public function isBase64Encoded($data): bool
    {
        if (preg_match('%^[a-zA-Z0-9/+]*={0,2}$%', $data)) {
            return true;
        } else {
            return false;
        }
    }

    public function generateName(): string
    {
        $length = 10;
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    public function decode(Request $request): JsonResponse
    {
        $label = $request->all();

        if(!array_key_exists('label', $label)) {
            return response()->json([
                'message' => 'label is required'
            ], 400);
        }

        $label = $label['label'];

        if(!$this->isBase64Encoded($label)) {
            return response()->json([
                'message' => 'label is not base64 encoded'
            ], 400);
        }

        $zpl = base64_decode($label, true);

        $labelary = new Client();

        try {
            $label = $labelary->printers->labels([
                'zpl' => $zpl,
            ]);
        } catch(Exception $e) {
            return response()->json([
                'message' => 'Error generating labels. Please check the ZPL is valid and try again'
            ], 400);
        }

        $labelResponse = json_decode($label);

        if(!isset($labelResponse->label)) {
            return response()->json([
                'message' => 'Something went wrong with the image conversion. Please try again later'
            ], 500);
        }

        $imageDecoded = $labelResponse->label;
        $imageDecoded = base64_decode($imageDecoded);

        $imageName = $this->generateName();
        $imageFileName = $imageName . '.png';
        $imagePath = '/tmp/' . $imageFileName;

        if(!file_put_contents($imagePath, $imageDecoded)) {
            return response()->json([
                'message' => 'Something went wrong with saving the image. Please try again later'
            ], 500);
        }

        $reader = new Reader();

        try {
            $labelData = $reader->decodeFile($imagePath);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Something went wrong with decoding the label. Please try again later'
            ], 500);
        }

        unlink($imagePath);

        return response()->json([
            'message' => 'Label decoded',
            'data' => $labelData
        ], 200);

    }
}
