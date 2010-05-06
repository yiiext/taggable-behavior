<?php
Yii::import('zii.widgets.CMenu');
/**
 * ETagListWidget
 */
class ETagListWidget extends CMenu {
	/**
	 * Model with ETaggableBehavior attached
	 */
	public $model;

	/**
	 * Behavior name
	 */
	public $field = 'tags';

	/**
	 * Show all tags
	 */
	public $all = false;

	/**
	 * Show counter for each tag
	 */
	public $count = false;

	/**
	 * Do not show tags with count below this value.
	 * Currently works only when count is shown.
	 */
	public $countLimit = 1;

	/**
	 * CSS class
	 */
	public $class = 'tags';

	public $url = '';
	public $urlParamName = 'tag';

	/**
	 * Criteria used to select tags
	 */
	public $criteria = null;

	function init(){
		if(!isset($this->htmlOptions['class'])) $this->htmlOptions['class'] = 'tags';

		$tags = array();

		$criteria = new CDbCriteria();
		$criteria->order = $this->model->{$this->field}->tagTableName;
		
		if($this->all){
			if($this->count){
				$criteria->having = 'count>='.(int)$this->countLimit;
				if($this->criteria)
					$criteria->mergeWith($this->criteria);
				$tags = $this->model->{$this->field}->getAllTagsWithModelsCount($criteria);
			}
			else {
				if($this->criteria)
					$criteria->mergeWith($this->criteria);

				$tags = $this->model->{$this->field}->getAllTags($criteria);
			}
		}
		else {
			if($this->count){
				$criteria->having = 'count>='.(int)$this->countLimit;
				if($this->criteria)
					$criteria->mergeWith($this->criteria);				
				$tags = $this->model->{$this->field}->getTagsWithModelsCount($criteria);
			}
			else {
				if($this->criteria)
					$criteria->mergeWith($this->criteria);

				$tags = $this->model->{$this->field}->getTags($criteria);
			}			
		}

		foreach($tags as $tag){
			if(is_array($tag)){				
				$this->items[] = array(
					'label' => CHtml::encode($tag['name']).' <span>'.$tag['count'].'</span>',
					'url' => array($this->url, $this->urlParamName => $tag['name']),
				);
			}
			else {				
				$this->items[] = array(
					'label' => CHtml::encode($tag),
					'url' => array($this->url, $this->urlParamName => $tag),

				);
			}
		}		

		parent::init();		
	}
	
	function run(){
		$this->renderMenu($this->items);		
	}	
}