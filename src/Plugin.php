<?php

declare(strict_types=1);

namespace rcknr\ComposerAuthDotenv;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Json\JsonValidationException;
use Composer\Plugin\PluginInterface;
use Dotenv\Dotenv;
use Dotenv\Repository\Adapter\ArrayAdapter;
use Dotenv\Repository\Adapter\ServerConstAdapter;
use Dotenv\Repository\RepositoryBuilder;
use Dotenv\Repository\RepositoryInterface;

use function array_merge;
use function getcwd;
use function is_array;
use function json_decode;
use function json_last_error;

use const JSON_ERROR_NONE;

class Plugin implements PluginInterface
{
    /** @var Composer|null */
    protected $composer;

    /** @var array|null */
    protected $config;

    /** @var IOInterface|null */
    protected $io;

    /** @var string */
    protected $name = 'composer-auth-dotenv';

    /** @var RepositoryInterface|null */
    protected $repository;

    /**
     * Return the composer instance.
     */
    public function getComposer(): ?Composer
    {
        return $this->composer;
    }

    /**
     * Return the IO interface object.
     */
    public function getIO(): ?IOInterface
    {
        return $this->io;
    }

    public function getRepository(): ?RepositoryInterface
    {
        return $this->repository;
    }

    /**
     * Return the config value for the given key.
     *
     * Returns the entire root config array, if key is set to null.
     *
     * @return mixed
     */
    public function getConfig(?string $key): ?string
    {
        if ($this->config === null) {
            $this->config = [
                'dotenv-path' => getcwd(),
                'dotenv-name' => null,
            ];

            $rootPackage  = $this->composer->getPackage();
            $extra        = $rootPackage->getExtra();
            $config       = $extra[$this->name] ?? [];
            $this->config = array_merge($this->config, $config);
        }

        return $key !== null ? ($this->config[$key] ?? null) : $this->config;
    }

    /**
     * @inheritDoc
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io       = $io;

        $this->initRepository();

        if ($this->repository->has('COMPOSER_AUTH')) {
            $this->io->write('Loading COMPOSER_AUTH from .env file');
            $data = $this->validateAuth($this->repository->get('COMPOSER_AUTH'));
//            $io->write('Contents: ' . json_encode($data));
            $this->loadAuth($data);
        }
    }

    public function initRepository()
    {
        $this->repository = RepositoryBuilder::createWithNoAdapters()
            ->addReader(ServerConstAdapter::class)
            ->addAdapter(ArrayAdapter::class)
            ->immutable()
            ->make();

        Dotenv::create(
            $this->repository,
            $this->getConfig('dotenv-path'),
            $this->getConfig('dotenv-name')
        )->safeLoad();
    }

    public function validateAuth(string $jsonString): array
    {
        $data = json_decode($jsonString, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($data)) {
            throw new JsonValidationException('Invalid JSON format for COMPOSER_AUTH');
        }

        return $data;
    }

    public function loadAuth(array $authData): void
    {
        $config = $this->composer->getConfig();
        $config->merge(['config' => $authData], 'COMPOSER_AUTH');
        $this->io->loadConfiguration($config);
    }

    /**
     * @inheritDoc
     */
    public function deactivate(Composer $composer, IOInterface $io)
    {
        $this->composer = null;
        $this->io       = null;
    }

    /**
     * @inheritDoc
     */
    public function uninstall(Composer $composer, IOInterface $io)
    {
    }
}
