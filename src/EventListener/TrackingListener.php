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
use Pimcore\Bundle\CoreBundle\EventListener\Traits\ResponseInjectionTrait;
use Pimcore\Bundle\ServerSideMatomoTrackingBundle\Tracking\TrackingFacadeInterface;
use Pimcore\Http\Request\Resolver\EditmodeResolver;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Http\RequestHelper;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;

class TrackingListener
{
    use PimcoreContextAwareTrait;
    use ResponseInjectionTrait;

    /**
     * @var TrackingFacadeInterface
     */
    protected $trackingFacade;

    /**
     * @var EditmodeResolver
     */
    protected $editmodeResolver;

    /**
     * @var RequestHelper
     */
    protected $requestHelper;

    public function __construct(TrackingFacadeInterface $trackingFacade, EditmodeResolver $editmodeResolver, RequestHelper $requestHelper)
    {
        $this->trackingFacade = $trackingFacade;
        $this->editmodeResolver = $editmodeResolver;
        $this->requestHelper = $requestHelper;
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

        if($this->editmodeResolver->isEditmode($request)) {
            return false;
        }

        if ($this->requestHelper->isFrontendRequestByAdmin($request)) {
            return false;
        }

        // it's standard industry practice to exclude tracking if the request includes
        // the header 'X-Purpose:preview'
        if ($request->server->get('HTTP_X_PURPOSE') === 'preview') {
            return false;
        }

        if ($request->server->get('HTTP_PURPOSE') === 'preview') {
            return false;
        }

        return true;
    }

    protected function checkIfApplicableResponse(PostResponseEvent $event) {
        if(!$this->checkIfApplicable($event)) {
            return false;
        }

        $response = $event->getResponse();

        if($response instanceof RedirectResponse) {
            return false;
        }

        if(!$this->isHtmlResponse($event->getResponse())) {
            return false;
        }

        return true;
    }

    public function onTerminate(PostResponseEvent $event)
    {
        if (!$this->checkIfApplicableResponse($event)) {
            return;
        }

        $this->trackingFacade->doBulkTrackForAllTrackers();
    }

    public function onResponse(FilterResponseEvent $event)
    {
        if (!$this->checkIfApplicable($event)) {
            return;
        }

        $this->trackingFacade->updateSite($event->getRequest());
        $this->trackingFacade->doTrackPageViewForAllTrackers($event->getRequest());
    }
}
