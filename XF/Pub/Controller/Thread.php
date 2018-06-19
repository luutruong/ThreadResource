<?php
/**
 * @license
 * Copyright 2018 TruongLuu. All Rights Reserved.
 */

namespace Truonglv\ThreadResource\XF\Pub\Controller;

use XF\Mvc\ParameterBag;
use Truonglv\ThreadResource\Listener;

/**
 * Class Thread
 * @package Truonglv\ThreadResource\XF\Pub\Controller
 * @inheritdoc
 */
class Thread extends XFCP_Thread
{
    public function actionTRConvert(ParameterBag $params)
    {
        $thread = $this->assertViewableThread($params->thread_id);

        if (!Listener::canConvertThread($thread)) {
            return $this->noPermission();
        }

        Listener::assertXFRMActive();

        /** @var \XFRM\Repository\Category $categoryRepo */
        $categoryRepo = $this->repository('XFRM:Category');
        $categoryTree = $categoryRepo->createCategoryTree($categoryRepo->getViewableCategories());

        if ($this->isPost()) {
            $input = $this->filter([
                'resource_title_type' => 'str',
                'resource_title' => 'str',
                'category_id' => 'uint',
                'version' => 'str',
                'creator_type' => 'str',
                'username' => 'str',
                'tag_line' => 'str'
            ]);

            /** @var \Truonglv\ThreadResource\Service\Convert $convert */
            $convert = $this->service('Truonglv\ThreadResource:Convert', $thread);

            $convert->prepareOptions($input);
            $resource = $convert->toResource();

            return $this->redirect($this->buildLink('resources', $resource));
        }

        $viewParams = [
            'thread' => $thread,
            'categoryTree' => $categoryTree
        ];

        return $this->view(
            'Truonglv\ThreadResource:Thread\Resource',
            'tl_thread_resource_convert',
            $viewParams
        );
    }
}
