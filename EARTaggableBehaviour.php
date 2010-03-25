<?php
/**
 * Author: 5е-1
 * Date: 09.03.2010
 * Time: 12:08:54
 */

class EARTaggableBehaviour extends ETaggableBehaviour {
    /**
     * Tags model name
     */
    public $tagModel = 'Tag';

    protected function createTag($title) {
        $class = $this->tagModel;
        $tag = new $class();
        $tag->{$this->tagTableName} = $title;
        $tag->save();
    }
}
