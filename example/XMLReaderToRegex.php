<?php
require_once __DIR__ . '/../vendor/autoload.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "XMLReaderReg.php";

$input_file = "Data/simpleTest1.xml";
$reader = new XMLReaderReg();
$reader->open($input_file);

$reader->setMatch(['(.*/b(\[\d*\])?)' => function (SimpleXMLElement $data, $path): void {
        echo "Value for ".$path." is ".PHP_EOL.
            $data->asXML().PHP_EOL;
    },
    '(.*/c(\[\d*\])?)' => function (DOMElement $data, $path): void {
        echo "Value for ".$path." is ".PHP_EOL.
            $data->ownerDocument->saveXML($data).PHP_EOL;
    },
        '/a/c/firstname' => function ($data): void {
        echo "Value for /a/c/firstname is ". $data.PHP_EOL;
    }
    ]);

$reader->process();
