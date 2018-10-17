<?php
/**
 * Created by PhpStorm.
 * User: cfasching
 * Date: 17.10.2018
 * Time: 11:46
 */

namespace Pimcore\Bundle\ServerSideMatomoTrackingBundle\Tracking;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Templating\Helper\Helper;


/**
 * @method TrackingFacadeInterface doTrackContentImpressionForAllTrackers($contentName, $contentPiece = 'Unknown', $contentTarget = false): TrackingFacadeInterface
 * @method TrackingFacadeInterface doTrackPageViewForAllTrackers(Request $request): TrackingFacadeInterface
 */
class TemplatingHelper extends Helper
{

    /**
     * @var TrackingFacadeInterface
     */
    protected $trackingFacade;

    public function __construct(TrackingFacadeInterface $trackingFacade)
    {
        $this->trackingFacade = $trackingFacade;
    }

    /**
     * Returns the canonical name of this helper.
     *
     * @return string The canonical name
     */
    public function getName()
    {
        return "matomoServersideTracker";
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->trackingFacade, $name], $arguments);
    }

}