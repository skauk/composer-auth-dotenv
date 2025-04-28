<?php

declare(strict_types=1);

namespace rcknr\ComposerAuthDotenv\Test;

use Composer\Composer;
use Composer\Config;
use Composer\IO\IOInterface;
use Composer\Json\JsonValidationException;
use Composer\Package\RootPackageInterface;
use PHPUnit\Framework\TestCase;
use rcknr\ComposerAuthDotenv\Plugin;

use function file_exists;
use function file_put_contents;
use function getcwd;
use function unlink;

use const DIRECTORY_SEPARATOR;
use const PHP_EOL;

class PluginTest extends TestCase
{
    private Plugin $plugin;
    private Composer $composerMock;
    private IOInterface $ioMock;

    protected function setUp(): void
    {
        $this->plugin = new Plugin();

        $this->composerMock = $this->createMock(Composer::class);
        $this->ioMock       = $this->createMock(IOInterface::class);
    }

    public function tearDown(): void
    {
        unset($_SERVER['COMPOSER_AUTH']);

        $dotenvPath = getcwd() . DIRECTORY_SEPARATOR . '.env';
        if (file_exists($dotenvPath)) {
            unlink($dotenvPath);
        }
    }

    public function testGetComposerAndIO(): void
    {
        $this->plugin->activate($this->composerMock, $this->ioMock);

        $this->assertSame($this->composerMock, $this->plugin->getComposer());
        $this->assertSame($this->ioMock, $this->plugin->getIO());
    }

    public function testGetConfigMergesWithExtra(): void
    {
        $rootPackage = $this->createMock(RootPackageInterface::class);
        $rootPackage->method('getExtra')
            ->willReturn([
                'composer-auth-dotenv' => [
                    'dotenv-path' => '/custom/path',
                    'dotenv-name' => '.custom.env',
                ],
            ]);

        $this->composerMock->method('getPackage')->willReturn($rootPackage);
        $this->plugin->activate($this->composerMock, $this->ioMock);

        $this->assertSame('/custom/path', $this->plugin->getConfig('dotenv-path'));
        $this->assertSame('.custom.env', $this->plugin->getConfig('dotenv-name'));
    }

    public function testValidateAuthReturnsValidArray(): void
    {
        $json = '{"http-basic":{"example.com":{"username":"user","password":"pass"}}}';

        // We mock static method validateJsonSchema by overriding it via reflection or separate class,
        // but for now let's just test the decoding part
        $result = $this->plugin->validateAuth($json);

        $this->assertIsArray($result);
        $this->assertEquals('user', $result['http-basic']['example.com']['username']);
    }

    public function testValidateAuthThrowsException(): void
    {
        $json = '{TEST}';

        $this->expectException(JsonValidationException::class);

        $this->plugin->validateAuth($json);
    }

    public function testReloadAuthMergesAndLoads(): void
    {
        $authData = ['http-basic' => ['example.com' => ['username' => 'u', 'password' => 'p']]];

        $configMock = $this->createMock(Config::class);
        $configMock->expects($this->once())
            ->method('merge')
            ->with(['config' => $authData], 'COMPOSER_AUTH');

        $this->composerMock->method('getConfig')->willReturn($configMock);
        $this->ioMock->expects($this->once())->method('loadConfiguration')->with($configMock);

        $this->plugin->activate($this->composerMock, $this->ioMock);
        $this->plugin->loadAuth($authData);
    }

    public function testDeactivateResetsComposerAndIO(): void
    {
        $this->plugin->activate($this->composerMock, $this->ioMock);
        $this->plugin->deactivate($this->composerMock, $this->ioMock);

        $this->assertNull($this->plugin->getComposer());
        $this->assertNull($this->plugin->getIO());
    }

    public function testDotenvFile()
    {
        $authData = '{"test":true}';

        file_put_contents(
            getcwd() . DIRECTORY_SEPARATOR . '.env',
            "COMPOSER_AUTH={$authData}" . PHP_EOL
        );

        $this->plugin->activate($this->composerMock, $this->ioMock);
        $value = $this->plugin->getRepository()->get('COMPOSER_AUTH');
        $this->assertEquals($authData, $value);
    }

    public function testPrefersVariableFromEnv()
    {
        $_SERVER['COMPOSER_AUTH'] = '{"test":false}';
        $authData                 = '{"test":true}';

        file_put_contents(
            getcwd() . DIRECTORY_SEPARATOR . '.env',
            "COMPOSER_AUTH={$authData}" . PHP_EOL
        );

        $this->plugin->activate($this->composerMock, $this->ioMock);
        $value = $this->plugin->getRepository()->get('COMPOSER_AUTH');
        $this->assertEquals($_SERVER['COMPOSER_AUTH'], $value);
    }

    public function testSideEffectFreeDotenvLoading()
    {
        $authData = '{"test":true}';

        file_put_contents(
            getcwd() . DIRECTORY_SEPARATOR . '.env',
            "COMPOSER_AUTH={$authData}" . PHP_EOL
        );

        $this->plugin->activate($this->composerMock, $this->ioMock);
        $this->assertArrayNotHasKey('COMPOSER_AUTH', $_SERVER);
    }
}
