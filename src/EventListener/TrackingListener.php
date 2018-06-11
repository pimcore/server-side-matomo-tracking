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

namespace Pimcore\Bundle\ServerSideMatomoTrackingBundle\EventListener;

use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Bundle\ServerSideMatomoTrackingBundle\Tracking\TrackingFacadeInterface;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;

class TrackingListener
{
    use PimcoreContextAwareTrait;

    /**
     * @var TrackingFacadeInterface
     */
    protected $trackingFacade;

    public function __construct(TrackingFacadeInterface $trackingFacade)
    {
        $this->trackingFacade = $trackingFacade;
    }

    /**
     * @param KernelEvent $event
     *
     * @return bool
     */
    protected function checkIfApplicable(KernelEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return false;
        }

        $request = $event->getRequest();

        // only inject analytics code on non-admin requests
        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return false;
        }

        // it's standard industry practice to exclude tracking if the request includes
        // the header 'X-Purpose:preview'
        if ($request->server->get('HTTP_X_PURPOSE') === 'preview') {
            return false;
        }

        return true;
    }

    public function onTerminate(PostResponseEvent $event)
    {
        if (!$this->checkIfApplicable($event)) {
            return;
        }

        $this->trackingFacade->doBulkTrackForAllTrackers();
    }

    public function onRequest(GetResponseEvent $event)
    {
        if (!$this->checkIfApplicable($event)) {
            return;
        }

        $this->trackingFacade->updateSite($event->getRequest());
        $this->trackingFacade->doTrackPageViewForAllTrackers($event->getRequest());
    }
}
