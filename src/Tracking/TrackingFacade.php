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

use Pimcore\Http\Request\Resolver\SiteResolver;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method TrackingFacadeInterface doTrackContentImpressionForAllTrackers($contentName, $contentPiece = 'Unknown', $contentTarget = false): TrackingFacadeInterface
 */
class TrackingFacade implements TrackingFacadeInterface
{
    /**
     * @var SiteResolver
     */
    protected $siteResolver;

    /**
     * @var Tracker[][]
     */
    protected $trackerCache;

    /**
     * @var Tracker[]
     */
    protected $allTrackers = [];

    /**
     * @var mixed
     */
    protected $currentSiteId = 'default';

    public function __construct(SiteResolver $siteResolver)
    {
        $this->siteResolver = $siteResolver;
    }

    /**
     * @param Request $request
     *
     * @return TrackingFacadeInterface
     */
    public function updateSite(Request $request): TrackingFacadeInterface
    {
        $pimcoreSite = $this->siteResolver->getSite($request);
        $this->currentSiteId = $pimcoreSite ? $pimcoreSite->getId() : 'default';

        return $this;
    }

    /**
     * @return Tracker[]
     */
    public function getCurrentTrackers(): array
    {
        if (!$this->trackerCache[$this->currentSiteId]) {
            $currentSiteTrackers = [];
            foreach ($this->allTrackers as $tracker) {
                if ($tracker->getPimcoreSiteId() == $this->currentSiteId) {
                    $currentSiteTrackers[] = $tracker;
                }
            }
            $this->trackerCache[$this->currentSiteId] = $currentSiteTrackers;
        }

        return $this->trackerCache[$this->currentSiteId] ? $this->trackerCache[$this->currentSiteId] : [];
    }

    /**
     * @return TrackingFacadeInterface
     *
     * @throws \Exception
     */
    public function doBulkTrackForAllTrackers(): TrackingFacadeInterface
    {
        foreach ($this->getCurrentTrackers() as $tracker) {
            $tracker->doBulkTrack();
        }

        return $this;
    }

    /**
     * @param Request $request
     *
     * @return TrackingFacadeInterface
     */
    public function doTrackPageViewForAllTrackers(Request $request): TrackingFacadeInterface
    {
        $pathPath = $request->getPathInfo();
        foreach ($this->getCurrentTrackers() as $tracker) {
            $tracker->doTrackPageView($pathPath);
        }

        return $this;
    }

    /**
     * @param int $id
     * @param string $name
     * @param string $value
     * @param string $scope
     * @return TrackingFacadeInterface
     * @throws \Exception
     */
    public function doSetCustomVariableForAllTrackers(int $id, string $name, string $value, string $scope = 'visit'): TrackingFacadeInterface
    {
        foreach ($this->getCurrentTrackers() as $tracker) {
            $tracker->setCustomVariable($id, $name, $value, $scope = 'visit');
        }

        return $this;
    }

    /**
     * @param string $userId
     * @return TrackingFacadeInterface
     * @throws \Exception
     */
    public function doSetUserIdForAllTrackers(string $userId): TrackingFacadeInterface
    {
        foreach ($this->getCurrentTrackers() as $tracker) {
            $tracker->setUserId($userId);
        }


        return $this;
    }


    public function __call($name, $arguments)
    {
        $methodName = str_replace("ForAllTrackers", "", $name);

        foreach ($this->getCurrentTrackers() as $tracker) {
            if (method_exists($tracker, $methodName)) {
                call_user_func_array([$tracker, $methodName], $arguments);
            }
        }

        return $this;
    }

    /**
     * @param Tracker $tracker
     *
     * @return TrackingFacadeInterface
     */
    public function addTracker(Tracker $tracker): TrackingFacadeInterface
    {
        $tracker->enableBulkTracking();

        $this->allTrackers[] = $tracker;
        $this->trackerCache = [];

        return $this;
    }
}
