<?php
Yii::import('zii.widgets.CMenu');
/**
 * ETagListWidget
 */
class ETagListWidget extends CMenu {
	public $model;
	public $field = 'tags';

	public $all = false;
	public $count = false;

	public $class = 'tags';

	public $url = '';
	public $urlParamName = 'tag';	

	function init(){
		if(!isset($this->htmlOptions['class'])) $this->htmlOptions['class'] = 'tags';

		$tags = array();
		if($this->all){
			if($this->count){
				$criteria = new CDbCriteria();
				$criteria->order = $this->model->{$this->field}->tagTableName;
				$tags = $this->model->{$this->field}->getAllTagsWithModelsCount($criteria);
			}
			else {
				$tags = $this->model->{$this->field}->getAllTags();
			}
		}
		else {
			if($this->count){
				$tags = $this->model->{$this->field}->getTagsWithModelsCount();
			}
			else {
				$tags = $this->model->{$this->field}->getTags();
			}			
		}

		foreach($tags as $tag){
			if(is_array($tag)){				
				$this->items[] = array(
					'label' => $tag['name'].' <span>'.$tag['count'].'</span>',
					'url' => array($this->url, $this->urlParamName => $tag['name']),					
				);
			}
			else {
				$this->items[] = array(
					'label' => $tag,
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