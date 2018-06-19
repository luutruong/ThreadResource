<?php
/**
 * @license
 * Copyright 2018 TruongLuu. All Rights Reserved.
 */

namespace Truonglv\ThreadResource;

use XF\Entity\Thread;
use XF\Repository\AddOn;
use XF\PrintableException;

class Listener
{
    public static function onThreadInlineModActions(
        \XF\InlineMod\AbstractHandler $handler,
        \XF\Pub\App $app,
        array &$actions
    ) {
        $actions['tr_convertToResources'] = $handler->getActionHandler('Truonglv\ThreadResource:Thread\Convert');
    }

    public static function canConvertThread(Thread $thread)
    {
        return \XF::visitor()->hasNodePermission($thread->node_id, 'threadResource_convert');
    }

    public static function assertXFRMActive()
    {
        /** @var AddOn $addOnRepo */
        $addOnRepo = \XF::repository('XF:AddOn');

        $enabledAddOns = $addOnRepo->getEnabledAddOns();
        $disabledAddOns = $addOnRepo->getDisabledAddOnsCache();

        if (empty($enabledAddOns['XFRM']) && empty($disabledAddOns['XFRM'])) {
            // maybe not installed?
            throw new PrintableException(\XF::phrase('tl_thread_resource.you_may_install_xfrm_addon'));
        }

        if (empty($enabledAddOns['XFRM'])) {
            // require add-on in active?
            throw new PrintableException(\XF::phrase('tl_thread_resource.you_may_enable_xfrm_addon'));
        }
    }
}
