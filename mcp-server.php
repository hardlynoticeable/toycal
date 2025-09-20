<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/vendor/autoload.php';

use App\Database;
use PhpMcp\Server\Http\Request;
use PhpMcp\Server\Server;
use PhpMcp\Server\Defaults\ArrayConfigurationRepository;

// It's good practice to set a default timezone.
date_default_timezone_set('UTC');

// A real server would have more robust logging and error handling.
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(function (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]
    ]);
});

// Ensure this script is accessed via POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    header('Allow: POST');
    echo 'This endpoint only accepts POST requests.';
    exit;
}

// Build the server configuration
$configRepo = new ArrayConfigurationRepository([
    'mcp' => [
        'server' => [
            'name' => 'Toy Cal Server',
            'version' => '1.0.0'
        ],
    ],
]);

$server = Server::make()
    ->withConfig($configRepo)
    ->withBasePath(__DIR__)
    ->withScanDirectories(['src']);

// Discover MCP elements via attributes
$server->discover();

// Create a request object from the incoming HTTP request
$request = new Request(
    headers: getallheaders(),
    body: file_get_contents('php://input')
);

// Handle the request and get the response
$response = $server->handle($request);

// Send the response back to the client
http_response_code($response->status);
foreach ($response->headers as $name => $value) {
    header("$name: $value");
}
echo $response->body;
