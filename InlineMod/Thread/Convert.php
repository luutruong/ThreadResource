<?php
/**
 * @license
 * Copyright 2018 TruongLuu. All Rights Reserved.
 */

namespace Truonglv\ThreadResource\InlineMod\Thread;

use XF\Http\Request;
use XF\Entity\Thread;
use XF\Mvc\Entity\Entity;
use XF\InlineMod\AbstractAction;
use Truonglv\ThreadResource\Listener;
use XF\Mvc\Entity\AbstractCollection;

class Convert extends AbstractAction
{
    protected $asserted = false;
    protected $category = null;
    protected $user = null;

    protected function canApplyToEntity(Entity $entity, array $options, &$error = null)
    {
        /** @var Thread $entity */
        return Listener::canConvertThread($entity);
    }

    public function getTitle()
    {
        return \XF::phrase('tl_thread_resource.threads_to_resources');
    }

    public function renderForm(AbstractCollection $entities, \XF\Mvc\Controller $controller)
    {
        Listener::assertXFRMActive();

        /** @var \XFRM\Repository\Category $categoryRepo */
        $categoryRepo = \XF::repository('XFRM:Category');
        $categoryTree = $categoryRepo->createCategoryTree($categoryRepo->getViewableCategories());

        $params = [
            'categoryTree' => $categoryTree,
            'threads' => $entities,
            'total' => count($entities),
        ];

        return $controller->view(
            'Truonglv\ThreadResource:InlineMod\Thread\Convert',
            'tl_thread_resource_inline_mod_convert',
            $params
        );
    }

    public function getFormOptions(AbstractCollection $entities, Request $request)
    {
        return $request->filter([
            'category_id' => 'uint',
            'version' => 'str',
            'creator_type' => 'str',
            'username' => 'str'
        ]);
    }

    protected function applyToEntity(Entity $entity, array $options)
    {
        if (!$this->asserted) {
            Listener::assertXFRMActive();
            $this->asserted = true;
        }

        /** @var \Truonglv\ThreadResource\Service\Convert $convert */
        $convert = \XF::service('Truonglv\ThreadResource:Convert', $entity);

        if ($this->category) {
            $options['category_id'] = null;

            $convert->setCategory($this->category);
        }

        if ($this->user) {
            $options['creator_type'] = null;

            $convert->setUser($this->user);
        }

        $convert->prepareOptions($options);
        $convert->toResource();

        $this->category = $convert->getCategory();
        $this->user = $convert->getUser();
    }
}
