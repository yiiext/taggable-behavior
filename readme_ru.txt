CTaggableBehaviour
==================

Позволяет модели работать с тегами.

Установка и настройка
---------------------
Создать таблицу для хранения тегов и кросс-таблицу для связи тегов с моделью.
Для конфигурации ниже можно воспользоваться SQL из файла `schema.sql`.

Определить в модели ActiveRecord метод `behaviors()`:
~~~
[php]
function behaviors() {
    return array(
        'tags' => array(
            'class' => 'ext.CTaggableBehaviour.CTaggableBehaviour',
            // Имя таблицы для хранения тегов 
            'tagTable' => 'Tag',
            // Имя кросс-таблицы, связывающей тег с моделью.
            // По умолчанию выставляется как Имя_таблицы_моделиTag
            'tagBindingTable' => 'PostTag',
            // Имя внешнего ключа модели в кроcc-таблице.
            // По умолчанию равно имя_таблицы_моделиId 
            'modelTableFk' => 'postId',
            // ID тега в таблице-связке
            'tagBindingTableTagId' => 'tagId',
            // ID компонента, реализующего кеширование.
            // По умолчанию ID равен false. 
            'cacheID' => 'cache',

            // Создавать несуществующие теги автоматически.
            // При значении false сохранение выкидывает исключение если добавляемый тег не существует.
            'createTagsAutomatically' => true,
        )
    );
}
~~~

Методы
------
### setTags($tags)
Задаёт новые теги для модели затирая старые.

~~~
[php]
$post = new Post();
$post->setTags('tag1, tag2, tag3')->save();
~~~


### addTags($tags) или addTag($tags)
Добавляет один или несколько тегов к уже существующим.

~~~
[php]
$post->addTags('new1, new2')->save();
~~~


### removeTags($tags) или removeTag($tags)
Удаляет указанные теги (если есть).

~~~
[php]
$post->removeTags('new1')->save();
~~~

### removeAllTags()
Удаляет все теги данной модели.

~~~
[php]
$post->removeAllTags()->save();
~~~

### getTags()
Отдаёт массив тегов.

~~~
[php]
$tags = $post->getTags();
foreach($tags as $tag){
  echo $tag;
}
~~~

### hasTag($tags) или hasTags($tags)
Назаначены ли модели указанные теги.

~~~
[php]
$post = Post::model()->findByPk(1);
if($post->hasTags("yii, php")){
    //…
}
~~~

### getAllTags()
Отдаёт все имеющиеся для этого класса моделей теги.

~~~
[php]
$tags = Post::model()->getAllTags();
foreach($tags as $tag){
  echo $tag;
}
~~~

### getAllTagsWithModelsCount()
Отдаёт все имеющиеся для этого класса модели теги с количеством моделей для каждого.
~~~
[php]
$tags = Post::model()->getAllTagsWithModelsCount();
foreach($tags as $tag){
  echo $tag['name']." (".$tag['count'].")";
}
~~~

### taggedWith($tags) или withTags($tags)
Позволяет ограничить запрос AR записями с указанными тегами.

~~~
[php]
$posts = Post::model()->taggedWith('php, yii')->findAll();
$postCount = Post::model()->taggedWith('php, yii')->count();
~~~

### resetAllTagsCache() и resetAllTagsWithModelsCountCache()
Используются для сборса кеша getAllTags() и getAllTagsWithModelsCount().



Приятные бонусы
---------------
Теги, разделённые запятой можно распечатать следующим образом:
~~~
[php]
$post->addTags('new1, new2')->save();
echo $post->tags;
~~~

Использование нескольких групп тегов
------------------------------------
Модели можно присвоить теги из нескольких групп. Например, для модели Software можно
задать теги групп OS и Category.

Для этого необходимо создать по две таблицы на каждую группу тегов:

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

Затем прописать для модели поведения:

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

Далее можно писать такой код:

~~~
[php]
$soft = Software::model()->findByPk(1);
// по умолчанию идут методы подключенного выше поведения,
// поэтому можно не писать $soft->categories->addTag("Antivirus"),
// а использовать краткую форму:
$soft->addTag("Antivirus");
$soft->os->addTag("Windows");
$soft->save();
~~~

Автодополнение для контроллера
------------------------------

~~~
[php]
public function actions(){
    return array(
        'autocomplete_tags' => array(
            'class' => 'application.extensions.CTaggableBehaviour.CTaggableAutocompleteAction',
            'x' => 'y',
        )
    );
}
~~~

