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
     * @var array
     */
    private $providersExceedingQuota = array();

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
        $this->providers[$provider->getName()] = $provider;

		return $this;
    }

    /**
     * Remove a provider
     *
     * @param string $provider The name of the provider
     */
    public function removeProvider($provider)
    {
		if (isset($this->providers[$provider])) {
			unset($this->providers[$provider]);
		}

		return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getGeocodedData($address)
    {
        $exceptions = array();

        foreach ($this->providers as $provider) {
			if ($provider->exceededQuota()) {
				continue;
			}

            try {
                return $provider->getGeocodedData($address);
            } catch (InvalidCredentialsException $e) {
                throw $e;
            } catch (QuotaExceededException $e) {
                $this->providersExceedingQuota[] = $provider->getName();
				$provider->setQuotaExceeded(true);
                $exceptions[] = $e;
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
			if ($provider->exceededQuota()) {
				continue;
			}

            try {
                return $provider->getReversedData($coordinates);
            } catch (InvalidCredentialsException $e) {
                throw $e;
			} catch (QuotaExceededException $e) {
                $this->providersExceedingQuota[] = $provider->getName();
				$provider->setQuotaExceeded(true);
                $exceptions[] = $e;
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
     * Get the providers which are exceeding their quota
     */
	public function getProvidersExceedingQuota()
	{
		return $this->providersExceedingQuota;
	}
}
