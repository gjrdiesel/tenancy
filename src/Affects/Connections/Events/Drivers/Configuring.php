<?php

declare(strict_types=1);

/*
 * This file is part of the tenancy/tenancy package.
 *
 * Copyright Tenancy for Laravel & Daniël Klabbers <daniel@klabbers.email>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @see https://tenancy.dev
 * @see https://github.com/tenancy
 */

namespace Tenancy\Affects\Connections\Events\Drivers;

use InvalidArgumentException;
use Tenancy\Affects\Connections\Contracts\ProvidesConfiguration;
use Tenancy\Identification\Contracts\Tenant;
use Tenancy\Support\Contracts\ProvidesPassword;

class Configuring
{
    /**
     * @var Tenant
     */
    public $tenant;

    /**
     * @var array
     */
    public $configuration;

    /**
     * @var ProvidesConfiguration
     */
    public $provider;

    public function __construct(Tenant $tenant, array &$configuration, ProvidesConfiguration $provider)
    {
        $configuration = $this->defaults($tenant, $configuration);

        $this->tenant = $tenant;
        $this->configuration = &$configuration;
        $this->provider = $provider;
    }

    public function useConnection(string $connection, array $override = [])
    {
        $this->configuration = array_merge(
            config("database.connections.$connection"),
            $override
        );

        return $this;
    }

    public function useConfig(string $path, array $override = [])
    {
        if (!file_exists($path)) {
            throw new InvalidArgumentException("Cannot set up tenant connection configuration, file $path does not exist.");
        }

        $this->configuration = array_merge(
            include $path,
            $override
        );

        return $this;
    }

    protected function defaults(Tenant $tenant, array &$configuration): array
    {
        if ($tenant->isDirty($tenant->getTenantKeyName())) {
            $configuration['oldUsername'] = $tenant->getOriginal($tenant->getTenantKeyName());
        }

        $configuration['username'] = $configuration['username'] ?? $tenant->getTenantKey();
        $configuration['database'] = $configuration['database'] ?? $configuration['username'];
        $configuration['password'] = $configuration['password'] ?? resolve(ProvidesPassword::class)->__invoke($tenant);

        return $configuration;
    }
}
