<?php
/**
 * TaggableBehaviour
 *
 * Provides tagging ability for a model.
 *
 * @version 1.1.0
 * @author Alexander Makarov
 * @link http://code.google.com/p/yiiext/
 */
class ETaggableBehaviour extends CActiveRecordBehavior {
    /**
     * Tags table name
     */
    public $tagTable = 'Tag';

    /**
     * Tag to Model binding table name.
     * Defaults to `{model table name}Tag`.
     */
    public $tagBindingTable = null;

    /**
     * Binding table tagId name.
     */
    public $tagBindingTableTagId = 'tagId';

    /**
     * Tag table field.
     */
    public $tagTableName = 'name';

    /**
     * Tag table count field. If null don't uses database
     */
    public $tagTableCount = null;

    /**
     * Binding table model FK name.
     * Defaults to `{model table name with first lowercased letter}Id`.  
     */
    public $modelTableFk = null;

    /**
     * Create tags automatically or throw exception if tag does not exist
     */
    public $createTagsAutomatically = true;

    /**
     * Caching component Id
     */
    public $cacheID = '';

    private $tags = array();
    private $originalTags = array();
    private $_conn = null;

    /**
     * @var CCache
     */
    protected $cache = null;

    /**
     * @return CDbConnection
     */
    protected function getConnection(){
        if (!isset($this->_conn))
          $this->_conn = $this->getOwner()->dbConnection;
        return $this->_conn;
    }

    /**
     * @throws CException
     * @param CComponent $owner
     * @return void
     */
    public function attach($owner) {
        // Prepare cache component
        $this->cache = Yii::app()->getComponent($this->cacheID);
        if (!($this->cache instanceof ICache)) {
            // If not set cache component, use dummy cache.
            $this->cache = new CDummyCache;
        }
        
        parent::attach($owner);
    }

    /**
     * Allows to print object.
     *
     * @return string
     */
    function toString(){
        $this->loadTags();
        return implode(', ', $this->tags);
    }

    /**
     * Get tag binding table name
     *
     * @access private
     * @return string
     */
    private function getTagBindingTableName(){
        if($this->tagBindingTable === null){
            $this->tagBindingTable = $this->getOwner()->tableName().'Tag';
        }
        return $this->tagBindingTable;
    }

    /**
     * Get model table FK name
     *
     * @access private
     * @return string
     */
    private function getModelTableFkName(){
        if($this->modelTableFk === null){
            $tableName = $this->getOwner()->tableName();
            $tableName[0] = strtolower($tableName[0]);
            $this->modelTableFk = $tableName.'Id';
        }
        return $this->modelTableFk;
    }

    /**
     * Set one or more tags
     *
     * @param string|array $tags
     * @return void
     */
    function setTags($tags){
        $tags = $this->toTagsArray($tags);
        $this->tags = array_unique($tags);

        return $this->getOwner();
    }

    /**
     * Add one or more tags
     *
     * @param string|array $tags
     * @return void
     */
    function addTags($tags){
        $this->loadTags();

        $tags = $this->toTagsArray($tags);
        $this->tags = array_unique(array_merge($this->tags, $tags));

        return $this->getOwner();
    }

    /**
     * Alias of addTags()
     *
     * @param string|array $tags
     * @return void
     */
    function addTag($tags){
        return $this->addTags($tags);        
    }

    /**
     * Remove one or more tags
     * 
     * @param string|array $tags
     * @return void
     */
    function removeTags($tags){
        $this->loadTags();

        $tags = $this->toTagsArray($tags);
        $this->tags = array_diff($this->tags, $tags);

        return $this->getOwner();
    }

    /**
     * Remove one or more tags.
     * Alias of removeTags.
     *
     * @param string|array $tags
     * @return void
     */
    function removeTag($tags){
        return $this->removeTags($tags);
    }

    /**
     * Remove all tags
     *
     * @return void
     */
    function removeAllTags(){
        $this->loadTags();
        $this->tags = array();
        return $this->getOwner();
    }

    /**
     * Get tags
     *
     * @return array
     */
    function getTags(){
        $this->loadTags();
        return $this->tags;
    }

    /**
     * Get tags array from comma separated tags string 
     *
     * @access private
     * @param string|array $tags
     * @return array
     */
    protected function toTagsArray($tags){
        if(is_string($tags)) $tags = explode(',', $tags);
        array_walk($tags, array($this, 'trim'));
        return $tags;
    }

    /**
     * Used as a callback to trim tags
     *
     * @access private
     * @param string $item
     * @param string $key
     * @return string
     */
    private function trim(&$item, $key){
        $item = trim($item);            
    }

    /**
     * If we need to save tags               
     *
     * @access private
     * @return boolean
     */
    private function needToSave(){
        $diff = array_merge(
            array_diff($this->tags, $this->originalTags),
            array_diff($this->originalTags, $this->tags)
        );

        return !empty($diff);
    }

    /**
	 * Saves model tags on model save.
     * 
	 * @param CModelEvent $event
     * @throw Exception
	 */
    function afterSave($event){
        if($this->needToSave()){
            $conn = $this->getConnection();

            if(!$this->createTagsAutomatically){
                // checking if all of the tags are existing ones
                foreach($this->tags as $tag){
                    $tagId = $conn->createCommand(
                        sprintf(
                            "SELECT id
                             FROM `%s`
                             WHERE %s = %s",
                             $this->tagTable,
                             $this->tagTableName,
                             $conn->quoteValue($tag)
                        )
                    )->queryScalar();

                    if(!$tagId){
                        throw new Exception("Tag \"$tag\" does not exist. Please add it before assigning or enable createTagsAutomatically.");
                    }
                }
            }

            if(!$this->getOwner()->getIsNewRecord()){
                // delete all present tag bindings if record is existing one
                $this->deleteTags();
            }

            // add new tag bindings and tags if there are any
            if(!empty($this->tags)){
                foreach($this->tags as $tag){
                    if (empty($tag)) return;
                    // try to get existing tag
                    $tagId = $conn->createCommand(
                        sprintf(
                            "SELECT id
                             FROM `%s`
                             WHERE %s = %s",
                             $this->tagTable,
                             $this->tagTableName,
                             $conn->quoteValue($tag)
                        )
                    )->queryScalar();

                    // if there is no existing tag, create one
                    if(!$tagId){
                        $this->createTag($tag);

                        // reset all tags cache
                        $this->resetAllTagsCache();
                        $this->resetAllTagsWithModelsCountCache();

                        $tagId = $conn->getLastInsertID();
                    }

                    // bind tag to it's model
                    $conn->createCommand(
                        sprintf(
                            "INSERT
                             INTO `%s`(%s, %s)
                             VALUES (%d, %d)",
                             $this->getTagBindingTableName(),
                             $this->getModelTableFkName(),
                             $this->tagBindingTableTagId,
                             $this->getOwner()->primaryKey,
                             $tagId
                        )
                    )->execute();
                }
                $this->updateCount(+1);
            }


            $this->cache->set($this->getCacheKey(), $this->tags);
        }

        parent::afterSave($event);
    }

    /**
     * Reset cache used for getAllTags()
     * @return void
     */
    function resetAllTagsCache(){
        $this->cache->delete('Taggable'.$this->getOwner()->tableName().'All');
    }

    /**
     * Reset cache used for getAllTagsWithModelsCount() 
     * @return void
     */
    function resetAllTagsWithModelsCountCache(){
        $this->cache->delete('Taggable'.$this->getOwner()->tableName().'AllWithCount');
    }

    /**
     * Deletes tag bindings on model delete. 
     *
     * @param CModelEvent $event
     */
    function afterDelete($event){
        // delete all present tag bindings
        $this->deleteTags();

        $this->cache->delete($this->getCacheKey());
        $this->resetAllTagsWithModelsCountCache();

        parent::afterDelete($event);
    }

    /**
     * Load tags into model
     * 
     * @access protected
     * @return void
     */
    protected function loadTags(){
        if($this->tags!=null) return;
        if($this->getOwner()->getIsNewRecord()) return;

        if(!($tags = $this->cache->get($this->getCacheKey()))){
            // getting associated tags
            $conn = $this->getConnection();
            $tags = $conn->createCommand(
                sprintf(
                    "SELECT t.%s as name
                    FROM `%s` t
                    JOIN `%s` et ON t.id = et.%s
                    WHERE et.%s = %d",
                    $this->tagTableName,
                    $this->tagTable,
                    $this->getTagBindingTableName(),
                    $this->tagBindingTableTagId,
                    $this->getModelTableFkName(),
                    $this->getOwner()->primaryKey
                )
            )->queryColumn();

            $this->cache->set($this->getCacheKey(), $tags);
        }

        $this->originalTags = $this->tags = $tags;
    }

    /**
     * Returns key for caching model tags
     *
     * @access private
     * @return string
     */
    private function getCacheKey(){
        return 'Taggable'.
                $this->getOwner()->tableName().
                $this->tagTable.
                $this->tagBindingTable.
                $this->modelTableFk.
                $this->tagBindingTableTagId.
                $this->getOwner()->primaryKey;
    }

    /**
     * Get criteria to limit query by tags
     *
     * @access private
     * @param array $tags
     * @return CDbCriteria
     */
    protected function getFindByTagsCriteria($tags){
        $criteria = new CDbCriteria();

        $pk = $this->getOwner()->tableSchema->primaryKey;

        if(!empty($tags)){
            $conn = $this->getConnection();
            for($i=0, $count=count($tags); $i<$count; $i++){
                $tag = $conn->quoteValue($tags[$i]);
                $criteria->join.=
                    "JOIN {$this->getTagBindingTableName()} bt$i ON t.{$pk} = bt$i.{$this->getModelTableFkName()}
                     JOIN {$this->tagTable} tag$i ON tag$i.id = bt$i.{$this->tagBindingTableTagId} AND tag$i.`{$this->tagTableName}` = $tag";
            }
        }

        return $criteria;
    }

    /**
     * Get all possible tags for current model class
     *
     * @param CDbCriteria $criteria
     * @return array
     */
    public function getAllTags($criteria = null){
        if(!($tags = $this->cache->get('Taggable'.$this->getOwner()->tableName().'All'))){
            // getting associated tags
            $builder = $this->getOwner()->getCommandBuilder();
            $criteria = new CDbCriteria();
            $criteria->select = $this->tagTableName;
            $tags = $builder->createFindCommand($this->tagTable, $criteria)->queryColumn();

            $this->cache->set('Taggable'.$this->getOwner()->tableName().'All', $tags);
        }

        return $tags;
    }

    /**
     * Get all possible tags with models count for each for this model class.
     *
     * @param CDbCriteria $criteria
     * @return array
     */
    public function getAllTagsWithModelsCount($criteria = null){
        if(!($tags = $this->cache->get('Taggable'.$this->getOwner()->tableName().'AllWithCount'))){
            // getting associated tags
            $conn = $this->getConnection();
            if($this->tagTableCount !== null){
              $tags = $conn->createCommand(
                  sprintf(
                    "SELECT %s as name, %s as `count`
                      FROM `%s`",
                    $this->tagTableName,
                    $this->tagTableCount,
                    $this->tagTable
                  )
              )->queryAll();
            }
            else {
              $tags = $conn->createCommand(
                  sprintf(
                    "SELECT t.%s as name, count(*) as `count`
                      FROM `%s` t
                      JOIN `%s` et ON t.id = et.%s
                      GROUP BY t.id",
                    $this->tagTableName,
                    $this->tagTable,
                    $this->getTagBindingTableName(),
                    $this->tagBindingTableTagId
                  )
              )->queryAll();
            }

            $this->cache->set('Taggable'.$this->getOwner()->tableName().'AllWithCount', $tags);
        }

        return $tags;
    }

    /**
     * Finds out if model has all tags specified.
     *
     * @param string|array $tags
     * @return boolean
     */    
    function hasTags($tags){
        $this->loadTags();

        $tags = $this->toTagsArray($tags);
        foreach($tags as $tag){
            if(!in_array($tag, $this->tags)) return false;
        }
        return true;
    }

    /**
     * Alias of hasTags() 
     *
     * @param string|array $tags
     * @return boolean     
     */
    function hasTag($tags){
        return $this->hasTags($tags);
    }

    /**
     * Limit current AR query to have all tags specified
     *
     * @param string|array $tags
     * @return CActiveRecord
     */
    function taggedWith($tags){
        $tags = $this->toTagsArray($tags);
        
        if(!empty($tags)){
            $criteria = $this->getFindByTagsCriteria($tags);
            $this->getOwner()->getDbCriteria()->mergeWith($criteria);
        }

        return $this->getOwner();
    }

    /**
     * taggedWith() alias
     *
     * @param string|array $tags
     * @return CActiveRecord
     */
    function withTags($tags){
        return $this->taggedWith($tags);
    }

    /**
     * Delete all present tag bindings
     * 
     * @return void
     */
    protected function deleteTags() {
        $this->updateCount(-1);

        $conn = $this->getConnection();
        $conn->createCommand(
            sprintf(
                "DELETE
                 FROM `%s`
                 WHERE %s = %d",
                 $this->getTagBindingTableName(),
                 $this->getModelTableFkName(),
                 $this->getOwner()->primaryKey
            )
        )->execute();
    }

    /**
     * Creates a tag
     * Method is for future inheritance
     *
     * @param string $tag tag name
     * @return void
     */
    protected function createTag($tag) {
        $conn = $this->getConnection();
        $conn->createCommand(
            sprintf(
                "INSERT
                 INTO `%s`(%s)
                 VALUES (%s)",
                 $this->tagTable,
                 $this->tagTableName,
                 $conn->quoteValue($tag)
            )
        )->execute();
    }

    /**
     * Updates counter information in database
     * Used if tagTableCount is not null
     *
     * @param int $count incremental ("1") or decremental ("-1") value
     * @return void
     */
    protected function updateCount($count) {
        if ($this->tagTableCount !== null){
            $conn = $this->getConnection();
            $conn->createCommand(
                sprintf(
                    "UPDATE %s
                    SET %s = %s + %s
                    WHERE id in (SELECT %s FROM %s WHERE %s = %d)",
                    $this->tagTable,
                    $this->tagTableCount,
                    $this->tagTableCount,
                    $count,
                    $this->tagBindingTableTagId,
                    $this->getTagBindingTableName(),
                    $this->getModelTableFkName(),
                    $this->getOwner()->primaryKey
                )
            )->execute();
        }
    }
}