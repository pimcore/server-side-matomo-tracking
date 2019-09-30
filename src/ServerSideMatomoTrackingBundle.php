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

namespace Pimcore\Bundle\ServerSideMatomoTrackingBundle;

use Pimcore\Bundle\ServerSideMatomoTrackingBundle\DependencyInjection\Compiler\TrackerRegistrationCompilerPass;
use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ServerSideMatomoTrackingBundle extends AbstractPimcoreBundle
{
    use PackageVersionTrait;

    protected function getComposerPackageName()
    {
        return 'pimcore/server-side-matomo-tracking';
    }

    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new TrackerRegistrationCompilerPass());
    }
}
