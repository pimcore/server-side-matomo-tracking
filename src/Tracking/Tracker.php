<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\ServerSideMatomoTrackingBundle\Tracking;

use Symfony\Component\HttpFoundation\RequestStack;

class Tracker extends \PiwikTracker
{
    protected $pimcoreSiteId = 'default';
    protected $requestUserAgent = '';
    protected $requestLanguage = '';

    public function __construct($idSite, $apiUrl = '', $pimcoreSiteId = 'default', RequestStack $requestStack)
    {
        parent::__construct($idSite, $apiUrl);
        $this->pimcoreSiteId = $pimcoreSiteId;

        //set a few values based on request data
        $currentRequest = $requestStack->getMasterRequest();
        $this->setIp($currentRequest->getClientIp());
        $this->requestLanguage = $currentRequest->getPreferredLanguage();
        $this->requestUserAgent = $currentRequest->headers->get('user-agent');
    }

    public function getPimcoreSiteId()
    {
        return $this->pimcoreSiteId;
    }

    protected function getCookieName($cookieName)
    {
        // NOTE: If the cookie name is changed, we must also update the method in piwik.js with the same name.
        $hash = substr(
            sha1(
                ($this->configCookieDomain == '' ? self::getCurrentHost() : $this->configCookieDomain) . $this->configCookiePath
            ),
            0,
            4
        );

        return '_pct_' . $cookieName . '.' . $this->idSite . '.' . $hash;
    }

    protected function sendRequest($url, $method = 'GET', $data = null, $force = false)
    {
        $content = parent::sendRequest($url, $method, $data, $force);

        //reset useragent and language when doing bulk requests
        if($this->doBulkRequests && !$force) {
            $this->setUserAgent($this->requestUserAgent);
            $this->setBrowserLanguage($this->requestLanguage);
        }

        return $content;
    }
}
