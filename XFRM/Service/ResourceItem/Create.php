<?php
/**
 * @license
 * Copyright 2018 TruongLuu. All Rights Reserved.
 */

namespace Truonglv\ThreadResource\XFRM\Service\ResourceItem;

use XF\Entity\Thread;

/**
 * Class Create
 * @package Truonglv\ThreadResource\XFRM\Service\ResourceItem
 * @inheritdoc
 */
class Create extends XFCP_Create
{
    /**
     * @var null|Thread
     */
    protected $threadResource_thread = null;

    public function setThreadResourceThread(Thread $thread)
    {
        $this->threadResource_thread = $thread;

        return $this;
    }

    protected function finalSetup()
    {
        parent::finalSetup();

        if ($this->threadResource_thread && $this->category->thread_node_id && $this->category->ThreadForum) {
            $this->resource->discussion_thread_id = $this->threadResource_thread->thread_id;
        }
    }

    protected function setupResourceThreadCreation(\XF\Entity\Forum $forum)
    {
        if ($this->threadResource_thread && $this->category->thread_node_id && $this->category->ThreadForum) {
            return null;
        }

        return parent::setupResourceThreadCreation($forum);
    }
}
