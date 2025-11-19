<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace CommentsBundle\Interfaces;

interface CommentInterface
{
    /**
     * add comment
     *
     * @param $postdata
     * @return
     */
    public function createComment($postdata);
}
