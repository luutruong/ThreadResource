<?php
/**
 * @license
 * Copyright 2018 TruongLuu. All Rights Reserved.
 */

namespace Truonglv\ThreadResource\Service;

use XF\Entity\User;
use XF\Entity\Thread;
use XFRM\Entity\Category;
use XF\PrintableException;
use XF\Service\AbstractService;
use XFRM\Service\ResourceItem\Create;

class Convert extends AbstractService
{
    protected $thread;

    protected $title;
    protected $tagLine;
    protected $version;

    /**
     * @var Create
     */
    protected $resourceCreate;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var Category;
     */
    protected $category;

    public function __construct(\XF\App $app, Thread $thread)
    {
        parent::__construct($app);

        $this->thread = $thread;
        $this->user = \XF::visitor();
    }

    /**
     * @return Category
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    public function setCategory(Category $category)
    {
        $this->resourceCreate = $this->service('XFRM:ResourceItem\Create', $category);
        $this->category = $category;

        return $this;
    }

    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    public function setTagLine($tagLine)
    {
        $this->tagLine = $tagLine;

        return $this;
    }

    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    public function prepareOptions(array $options)
    {
        $options = array_replace([
            'resource_title_type' => 'thread',
            'resource_title' => '',
            'category_id' => 0,
            'version' => '',
            'creator_type' => 'visitor',
            'username' => '',
            'tag_line' => $this->thread->title
        ], $options);

        if ($options['resource_title_type'] === 'new_title') {
            $this->setTitle($options['resource_title']);
        } else {
            $this->setTitle($this->thread->title);
        }

        $this->setVersion($options['version']);
        $this->setTagLine($options['tag_line']);

        if ($options['creator_type'] !== null) {
            if ($options['creator_type'] === 'thread') {
                $this->setUser($this->thread->User);
            } elseif ($options['creator_type'] === 'user') {
                $user = $this->em()->findOne('XF:User', ['username' => $options['username']]);
                if (!$user) {
                    throw new PrintableException(\XF::phrase('requested_user_not_found'));
                }

                $this->setUser($user);
            } else {
                $this->setUser(\XF::visitor());
            }
        }

        if ($options['category_id'] !== null) {
            $category = $this->em()->find('XFRM:Category', $options['category_id']);
            if ($category) {
                $this->setCategory($category);
            } else {
                throw new PrintableException(\XF::phrase('requested_category_not_found'));
            }
        }
    }

    public function toResource()
    {
        $this->finalizeSetup();

        $creator = $this->resourceCreate;
        if (!$creator->validate($errors)) {
            throw new PrintableException(reset($errors));
        }

        $resource = $creator->save();

        return $resource;
    }

    protected function finalizeSetup()
    {
        $thread = $this->thread;
        $creator = $this->resourceCreate;
        $user = $this->user;

        $error = null;
        $allowAdd = \XF::asVisitor($user, function () use (&$error) {
            return $this->category->canAddResource($error);
        });

        if (!$allowAdd) {
            throw new PrintableException($error ?: \XF::phrase('xfrm_category_not_allow_new_resources'));
        }

        $creator->setContent($this->title ?: $thread->title, $thread->FirstPost->message);

        $tags = array_column($thread->tags, 'tag');
        $creator->setTags(implode(',', $tags));

        $creator->setFileless();
        $creator->setVersionString($this->version ?: '', $this->version ? false : true);

        $bulkSet = [
            'user_id' => $user->user_id,
            'username' => $user->username
        ];

        if (empty($input['tag_line'])) {
            $tagLine = $thread->title;
        } else {
            $tagLine = $input['tag_line'];
        }
        $bulkSet['tag_line'] = $tagLine;

        $creator->getResource()->bulkSet($bulkSet);
    }
}
