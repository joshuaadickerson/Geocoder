<?php

/**
 * This file is part of the Geocoder package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Geocoder\Provider;

use Geocoder\Exception\InvalidCredentialsException;
use Geocoder\Exception\ChainNoResultException;

/**
 * @author Markus Bachmann <markus.bachmann@bachi.biz>
 */
class ChainProvider implements ProviderInterface
{
    /**
     * @var ProviderInterface[]
     */
    private $providers = array();

	/**
     * @var string
     */
    private $lastResultProvider = 'chain';

    /**
     * Constructor
     *
     * @param ProviderInterface[] $providers
     */
    public function __construct(array $providers = array())
    {
        $this->providers = $providers;
    }

    /**
     * Add a provider
     *
     * @param ProviderInterface $provider
     */
    public function addProvider(ProviderInterface $provider)
    {
        $this->providers[] = $provider;
    }

    /**
     * {@inheritDoc}
     */
    public function getGeocodedData($address)
    {
        $exceptions = array();

        foreach ($this->providers as $provider) {
            try {
                $result = $provider->getGeocodedData($address);
                $this->setLastResultProvider($provider->getLastResultProvider());

                return $result;
            } catch (InvalidCredentialsException $e) {
                throw $e;
            } catch (\Exception $e) {
                $exceptions[] = $e;
            }
        }

        throw new ChainNoResultException(sprintf('No provider could provide the address "%s"', $address), $exceptions);
    }

    /**
     * {@inheritDoc}
     */
    public function getReversedData(array $coordinates)
    {
        $exceptions = array();

        foreach ($this->providers as $provider) {
            try {
                $result = $provider->getReversedData($coordinates);
                $this->setLastResultProvider($provider->getLastResultProvider());

                return $result;
            } catch (InvalidCredentialsException $e) {
                throw $e;
            } catch (\Exception $e) {
                $exceptions[] = $e;
            }
        }

        throw new ChainNoResultException(sprintf('No provider could provide the coordinated %s', json_encode($coordinates)), $exceptions);
    }

    /**
     * {@inheritDoc}
     */
    public function setMaxResults($limit)
    {
        foreach ($this->providers as $provider) {
            $provider->setMaxResults($limit);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'chain';
    }

    /**
     * Set the last successful result provider
	 * 
	 * @param string $lastResultProvider
	 * @return ProviderInterface
     */
    public function setLastResultProvider($lastResultProvider)
    {
        $this->lastResultProvider = $lastResultProvider;

		return $this;
    }

    /**
     * Get the last successful result provider
     * 
     * @return string
     */
    public function getLastResultProvider()
    {
        return $this->lastResultProvider;
    }
}
