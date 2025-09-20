<?php

namespace Tests;

use PhpMcp\Server\Defaults\ArrayConfigurationRepository;
use PhpMcp\Server\JsonRpc\Notification;
use PhpMcp\Server\JsonRpc\Request;
use PhpMcp\Server\Server;
use PHPUnit\Framework\TestCase;

class ServerTest extends TestCase
{
    public function testListToolsIncludesDescriptions(): void
    {
        // 1. Build the server and discover tools
        $configRepo = new ArrayConfigurationRepository([
            'mcp' => [
                'server' => [
                    'name' => 'Test Server',
                    'version' => '1.0.0'
                ],
                'capabilities' => [
                    'tools' => ['enabled' => true],
                ],
            ],
        ]);

        $server = Server::make()
            ->withConfig($configRepo)
            ->withBasePath(dirname(__DIR__)) // Project root
            ->withScanDirectories(['src']);
        
        $server->discover(false); // Disable cache for testing

        $processor = $server->getProcessor();

        // 2. Send initialize request
        $initRequest = new Request('2.0', 'init-1', 'initialize', [
            'protocolVersion' => '2024-11-05',
            'clientInfo' => ['name' => 'test-client']
        ]);
        $initResponse = $processor->process($initRequest, 'test-client');
        $this->assertTrue($initResponse->isSuccess(), 'Initialize request failed');

        // 3. Send initialized notification
        $initializedNotification = new Notification('2.0', 'notifications/initialized', []);
        $processor->process($initializedNotification, 'test-client');

        // 4. Create a 'tools/list' request
        $request = new Request('2.0', 'test-1', 'tools/list', []);

        // 5. Handle the request
        $response = $processor->process($request, 'test-client');

        $this->assertNotNull($response);
        $this->assertTrue($response->isSuccess(), 'Response was not successful: ' . print_r($response->error, true));

        // 6. Verify all discovered tools
        $tools = $response->result->tools;

        $this->assertCount(10, $tools, 'Expected to discover 10 tools.');

        foreach ($tools as $tool) {
            $this->assertIsString($tool->toolName);
            $this->assertNotEmpty($tool->toolName);

            $this->assertIsString($tool->description);
            $this->assertNotEmpty($tool->description);
        }
    }
}
