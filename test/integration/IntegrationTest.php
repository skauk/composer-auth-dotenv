<?php

declare(strict_types=1);

namespace rcknr\ComposerAuthDotenv\Test;

use donatj\MockWebServer\MockWebServer;
use donatj\MockWebServer\Response;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

use function base64_encode;
use function file_get_contents;
use function file_put_contents;
use function join;
use function json_encode;
use function mkdir;
use function str_replace;
use function sys_get_temp_dir;

use const PHP_EOL;

class IntegrationTest extends TestCase
{
    /** @var string|null */
    protected $pwd;

    protected function setUp(): void
    {
        // Choose tmp working directory to run test case in
        $this->pwd = sys_get_temp_dir() . '/composer-auth-dotenv';

        // Create working directory
        @mkdir($this->pwd);

        $this->server = new MockWebServer();
        $this->server->start();
        $this->server->setDefaultResponse(
            new Response('{}', ['Content-Type' => 'application/json'])
        );
    }

    protected function tearDown(): void
    {
        // Remove working directory
        $process = new Process(['rm', '-r', $this->pwd]);
        $process->mustRun();
        $this->pwd = null;
        $this->server->stop();
    }

    public function testWordPressComposerIntegration()
    {
        $repositoryHost = "{$this->server->getHost()}:{$this->server->getPort()}";
        $credentials    = [
            'username' => 'username',
            'password' => 'password',
        ];
        $composerAuth   = json_encode([
            'http-basic' => [
                $repositoryHost => $credentials,
            ],
        ]);

        file_put_contents(
            $this->pwd . '/.env',
            "COMPOSER_AUTH={$composerAuth}" . PHP_EOL
        );
        $composerJson = file_get_contents(__DIR__ . '/../stubs/composer-test.json');
        file_put_contents(
            $this->pwd . '/composer.json',
            str_replace('{TEST_URL}', "http://{$repositoryHost}", $composerJson)
        );

        $install = new Process(
            [
                __DIR__ . '/../../vendor/composer/composer/bin/composer',
                'install',
                '--no-interaction',
            ],
            $this->pwd,
        );
        $install->setTimeout(60);
        $install->mustRun();

        $this->assertTrue($install->isSuccessful());

        $update = new Process(
            [
                __DIR__ . '/../../vendor/composer/composer/bin/composer',
                'update',
                '--no-interaction',
            ],
            $this->pwd,
        );
        $update->setTimeout(60);
        $update->mustRun();
        $this->assertTrue($update->isSuccessful());

        $this->assertArrayHasKey('Authorization', $this->server->getLastRequest()?->getHeaders());
        $this->assertSame(
            'Basic ' . base64_encode(join(':', $credentials)),
            $this->server->getLastRequest()->getHeaders()['Authorization']
        );
    }
}
