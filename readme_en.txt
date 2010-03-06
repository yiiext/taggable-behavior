Taggable Behaviour
==================

Allows active record model to manage tags.

Installation and configuration
------------------------------
Create a table where you want to store tags and cross-table to store tag-model connections.
You can use sample SQL from `schema.sql` file.

In your ActiveRecord model define `behaviors()` method:
~~~
[php]
function behaviors() {
    return array(
        'tags' => array(
            'class' => 'ext.yiiext.behaviors.model.taggable.ETaggableBehaviour',
            // Table where tags are stored
            'tagTable' => 'Tag',
            // Cross-table that stores tag-model connections.
            // By default it's your_model_tableTag
            'tagBindingTable' => 'PostTag',
            // Foreign key in cross-table.
            // By default it's your_model_tableId
            'modelTableFk' => 'postId',
            // Tag binding table tag ID
            'tagBindingTableTagId' => 'tagId',
            // Caching component ID.
            // false by default.
            'cacheID' => 'cache',
            
            // Save nonexisting tags.
            // When false, throws exception when saving nonexisting tag.
            'createTagsAutomatically' => true,
        )
    );
}
~~~

Methods
-------
### setTags($tags)
Replace model tags with new tags set.

~~~
[php]
$post = new Post();
$post->setTags('tag1, tag2, tag3')->save();
~~~


### addTags($tags) or addTag($tags)
Add one or more tags to existing set.

~~~
[php]
$post->addTags('new1, new2')->save();
~~~


### removeTags($tags) or removeTag($tags)
Remove tags specified (if they do exist).

~~~
[php]
$post->removeTags('new1')->save();
~~~

### removeAllTags()
Remove all tags from the model.

~~~
[php]
$post->removeAllTags()->save();
~~~

### getTags()
Get array of model's tags.

~~~
[php]
$tags = $post->getTags();
foreach($tags as $tag){
  echo $tag;
}
~~~

### hasTag($tags) или hasTags($tags)
Returns true if all tags specified are assigned to current model and false otherwise.

~~~
[php]
$post = Post::model()->findByPk(1);
if($post->hasTags("yii, php")){
    //…
}
~~~

### getAllTags()
Get all possible tags for this model class.

~~~
[php]
$tags = Post::model()->getAllTags();
foreach($tags as $tag){
  echo $tag;
}
~~~

### getAllTagsWithModelsCount()
Get all possible tags with models count for each for this model class.
~~~
[php]
$tags = Post::model()->getAllTagsWithModelsCount();
foreach($tags as $tag){
  echo $tag['name']." (".$tag['count'].")";
}
~~~

### taggedWith($tags) или withTags($tags)
Limits AR query to records with all tags specified.

~~~
[php]
$posts = Post::model()->taggedWith('php, yii')->findAll();
$postCount = Post::model()->taggedWith('php, yii')->count();
~~~

### resetAllTagsCache() and resetAllTagsWithModelsCountCache()
could be used to reset getAllTags() or getAllTagsWithModelsCount() cache.

Bonus features
--------------
You can print comma separated tags following way:
~~~
[php]
$post->addTags('new1, new2')->save();
echo $post->tags;
~~~

Using multiple tag groups
-------------------------
You can use multiple tag groups for a single model. For example, we will create
OS and Category tag groups for Software model.

First we need to create DB tables. Two for each group:

~~~
[sql]
/* Tag table */
CREATE TABLE `Os` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `Os_name` (`name`)
);

/* Tag binding table */
CREATE TABLE `PostOs` (
  `postId` INT(10) UNSIGNED NOT NULL,
  `osId` INT(10) UNSIGNED NOT NULL,
  PRIMARY KEY  (`postId`,`osId`)
);

/* Tag table */
CREATE TABLE `Category` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `Category_name` (`name`)
);

/* Tag binding table */
CREATE TABLE `PostCategory` (
  `postId` INT(10) UNSIGNED NOT NULL,
  `categoryId` INT(10) UNSIGNED NOT NULL,
  PRIMARY KEY  (`postId`,`categoryId`)
);
~~~

Then we are attaching behaviours:

~~~
[php]
return array(
    'categories' => array(
        'class' => 'ext.CTaggableBehaviour.CTaggableBehaviour',
        'tagTable' => 'Category',
        'tagBindingTable' => 'PostCategory',
        'tagBindingTableTagId' => 'categoryId',
    ),
    'os' => array(
        'class' => 'ext.CTaggableBehaviour.CTaggableBehaviour',
        'tagTable' => 'Os',
        'tagBindingTable' => 'PostOs',
        'tagBindingTableTagId' => 'osId',
    ),
);
~~~

That's it. Now we can use it:

~~~
[php]
$soft = Software::model()->findByPk(1);
// fist attached taggable behaviour is used by default
// so we can use short syntax instead of $soft->categories->addTag("Antivirus"):
$soft->addTag("Antivirus");
$soft->os->addTag("Windows");
$soft->save();
~~~
