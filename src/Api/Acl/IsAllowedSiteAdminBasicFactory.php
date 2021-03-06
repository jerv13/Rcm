<?php

namespace Rcm\Api\Acl;

use Interop\Container\ContainerInterface;
use Rcm\Acl\ResourceName;
use RcmUser\Api\Acl\IsAllowed;

/**
 * @author James Jervis - https://github.com/jerv13
 */
class IsAllowedSiteAdminBasicFactory
{
    /**
     * @param ContainerInterface $serviceContainer
     *
     * @return IsAllowedSiteAdminBasic
     */
    public function __invoke($serviceContainer)
    {
        return new IsAllowedSiteAdminBasic(
            $serviceContainer->get(ResourceName::class),
            $serviceContainer->get(IsAllowed::class)
        );
    }
}
