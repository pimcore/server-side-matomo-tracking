<?php
/**
 * Created by PhpStorm.
 * User: cfasching
 * Date: 11.06.2018
 * Time: 13:56
 */
declare(strict_types=1);

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

use Symfony\Component\HttpFoundation\Request;

/**
 * @method TrackingFacadeInterface doTrackContentImpressionForAllTrackers($contentName, $contentPiece = 'Unknown', $contentTarget = false): TrackingFacadeInterface
 */
interface TrackingFacadeInterface
{
    /**
     * @return Tracker[]
     */
    public function getCurrentTrackers(): array;

    /**
     * @param Request $request
     *
     * @return TrackingFacadeInterface
     */
    public function updateSite(Request $request): TrackingFacadeInterface;

    /**
     * @return TrackingFacadeInterface
     *
     * @throws \Exception
     */
    public function doBulkTrackForAllTrackers(): TrackingFacadeInterface;

    /**
     * @param Request $request
     *
     * @return $this
     */
    public function doTrackPageViewForAllTrackers(Request $request): TrackingFacadeInterface;

    /**
     * @param Tracker $tracker
     *
     * @return TrackingFacadeInterface
     */
    public function addTracker(Tracker $tracker): TrackingFacadeInterface;

    /**
     * @param int $id
     * @param string $name
     * @param string $value
     * @param string $scope
     * @return TrackingFacadeInterface
     */
    public function doSetCustomVariableForAllTrackers(int $id, string $name, string $value, string $scope = 'visit'): TrackingFacadeInterface;

    /**
     * @param string $userId
     * @return TrackingFacadeInterface
     */
    public function doSetUserIdForAllTrackers(string $userId): TrackingFacadeInterface;
}
