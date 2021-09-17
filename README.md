# OpenRpc (Laravel/Lumen)

# Описание
Пакет для автоматической генерации документации JsonRpc-сервера по стандарту OpenRpc (https://spec.open-rpc.org/).
Совместим с пакетом `tochka-developers/jsonrpc`>=v4.0

# Установка
Установка через composer:
```shell
composer require tochka-developers/openrpc
```
### Laravel
Для Laravel есть возможность опубликовать конфигурацию для всех пакетов:
```shell
php artisan vendor:publish
```

Для того, чтобы опубликовать только конфигурацию данного пакета, можно воспользоваться опцией tag
```shell
php artisan vendor:publish --tag="openrpc-config"
```

### Lumen
В Lumen отсутствует команда _vendor:publish_, поэтому делается это вручную.
Если в проекте еще нет директории для конфигураций - создайте ее:
```shell
mkdir config
```
Скопируйте в нее конфигурацию openrpc:
```shell
cp vendor/tochka-developers/openrpc/config/openrpc.php config/openrpc.php
```
Вместо _config/openrpc.php_ нужно указать любую другую директорию, где хранятся ваши конфиги и название будущего конфига.
Далее необходимо прописать скопированный конфиг в _bootstrap/app.php_
```php
$app->configure('openrpc');
```
Так же прописать провайдер:
```php
$app->register(\Tochka\OpenRpc\OpenRpcServiceProvider::class);
```
Где _jsonrpc_ - имя файла конфига

Для корректной работы так же необходимы фасады:
```php
$app->withFacades();
```

# Настройка точки входа
Укажите в конфигурации `openrpc.endpoint` точку входа для получения схемы OpenRpc:
```php
'endpoint' => '/api/openrpc.json'
```
В дальнейшем эта точка входа будет использована для Service Discovery Method.

# Кеширование
OpenRpc может кешировать схему с описанием JsonRpc, чтобы не собирать ее каждый раз при вызове endpoint.
Для создания кеша используйте команду artisan:
```shell
php artisan openrpc:cache
```
После выполнения этой команды будет выполнена сборка схемы и сохранена в файл кеша. При каждом следующем обращении к 
OpenRpc будет использован именно этот файл, повторной пересборки происходить не будет. 

Если файла с кешем нет - схема каждый раз будет собираться заново.

Чтобы очистить кеш - используйте команду artisan:
```shell
php artisan openrpc:clear
```
Рекомендуется запускать команду кеширования сразу после деплоя перед запуском приложения.

# Как использовать

## Аннотации и атрибуты
Некоторые пометки или уточнения для классов, методов и полей могут использовать аннотации 
(https://www.doctrine-project.org/projects/doctrine-annotations/en/1.10/index.html) 
или атрибуты (https://www.php.net/manual/ru/language.attributes.overview.php).
Все классы аннотаций/атрибутов обратно совместимы и могут работать и как аннотации (для версии PHP<8), и как атрибуты
(для версии PHP>=8). 
Примеры использования аннотаций:
```php
use Tochka\JsonRpc\Annotations\ApiIgnore;
use Tochka\JsonRpc\Annotations\ApiValueExample;
use Tochka\JsonRpc\Annotations\ApiArrayShape;

/**
* @ApiIgnore()
*/
class TestDTO
{
    /**
    * @ApiValueExample(examples={1, 5, 6})
    */
    public ?int $int;
    
    /**
     * @ApiArrayShape(shape={"test": "string", "foo": "int", "bar": "array", "object": FooObject::class})
     */
    public array $testShape;
}
```

Пример использования атрибутов:
```php
use Tochka\JsonRpc\Annotations\ApiIgnore;
use Tochka\JsonRpc\Annotations\ApiValueExample;
use Tochka\JsonRpc\Annotations\ApiArrayShape;

#[ApiIgnore]
class TestDTO
{
    #[ApiValueExample(examples: [1, 5, 6])]
    public ?int $int;

    #[ApiArrayShape(shape: ['test' => 'string', 'foo' => 'int', 'bar' => 'array', 'object' => FooObject::class])]
    public array $testShape;
}
```

## Расширенное описание
Во многих объектах спецификации OpenRpc есть поле description, которое позволяет использовать MarkDown разметку.
Для более удобной организации есть возможность выносить эти описания в отдельные файлы с расширением .md, и затем в
описании ссылаться на них:
```php
'description' => '$views/docs/description.md'
```
В данном случае вместо указанного текста в поле подставится содержимое файла `resources/views/docs/description.md`.
Будьте внимательны! Документы должны находиться в папке resources, так как путь к файлу строится относительно этой 
директории.

## Основная информация
Вся основная информация о вашем приложении заполняется в конфигурации `openrpc.php`. В дефолтной конфигурации
описаны все поля, а также приведены примеры

## Информация о серверах (ендпойнты)
Ваше приложение может иметь несколько точек входа с разным списком методов. Все возможные конфигурации точек входа
JsonRpc-сервера описываются в конфигурации `jsonrpc.php`. Для корректного вывода информации о точках входа в OpenRpc
необходимо добавить еще несколько полей в конфигурацию каждого сервера JsonRpc:

```php
/** Адрес endpoint для текущего сервера */
'endpoint' => '/api/v1/public/jsonrpc',
/** Базовая информация о сервере/точки входа */
'summary' => 'Основная точка входа',
/** Расширенное описание сервера/точки входа */
'description' => 'Основная точка входа',
```

## Информация о методах
OpenRpc собирает информацию о доступных методах, получая информацию о 
[маршрутах из JsonRpc-сервера](https://github.com/tochka-developers/jsonrpc#%D0%BC%D0%B0%D1%80%D1%88%D1%80%D1%83%D1%82%D0%B8%D0%B7%D0%B0%D1%86%D0%B8%D1%8F-%D1%80%D0%BE%D1%83%D1%82%D0%B8%D0%BD%D0%B3).

## Информация о формате запроса
Если в методе используется аннотация/атрибут 
[`ApiMapRequestToObject`](https://github.com/tochka-developers/jsonrpc#%D0%BC%D0%B0%D0%BF%D0%BF%D0%B8%D0%BD%D0%B3-%D0%BF%D0%B0%D1%80%D0%B0%D0%BC%D0%B5%D1%82%D1%80%D0%BE%D0%B2-%D0%B2-dto-%D0%B8-%D0%BF%D0%BE%D0%BB%D1%83%D1%87%D0%B5%D0%BD%D0%B8%D0%B5-%D0%BF%D0%BE%D0%BB%D0%BD%D0%BE%D0%B3%D0%BE-%D0%B7%D0%B0%D0%BF%D1%80%D0%BE%D1%81%D0%B0)
, то в качестве параметров метода будут указаны поля класса, в который JsonRpc будет маппить запрос.

Если используется стандартное прокидывание параметров запроса в параметры метода - то OpenRpc будет собирать информацию
об используемых параметрах из этих параметров метода. При этом учитывается типизация, а также дополнительной описание
параметров с помощью PhpDoc (тег `@param`).

Вы можете уточнять тип вложенных в массив элементов также с помощью тегов `@param` и `@return`:
```php
/**
 * @param array<MyType> $foo Описание параметра FOO
 * @param string[] $bar Описание параметра BAR
 * @return Result[]
 */
public function myMethod(array $foo, array $bar): array
{
    // ...
}
```
Если в качестве типа указан какой-либо класс - OpenRpc попытается описать внутреннюю структуру класса.

По умолчанию выбираются все публичные поля класса. Кроме того, учитываются поля, описанные в phpDoc 
(помеченные атрибутом `@property`). 

В качестве типа аргумента всегда выбирается тип поля. Поле считается обязательным, если у него нет дефолтного значения.

Если в качестве типа поля указан `array` - вы можете уточнить тип элементов массива с помощью phpDoc в атрибуте `@var`:
```php
class TestDTO
{
    /** @var array<int> */
    public array $field;
    
    /** @var MyObject[] */
    public array $objects;
}
```

Если в качестве типа указан экземпляр `BenSampo\Enum\Enum` - OpenRpc автоматически получит все варианты значений для поля,
и попытается вычислить тип. Информация о возможных значениях будет отражена в схеме.

Если в качестве типа указан экземпляр `Illuminate\Database\Eloquent\Model` - OpenRpc получит все возможные поля из phpDoc
(как указано выше), а затем попытается отфильтровать их в соответствии с правилами, указанными в hidden и visible
(https://laravel.com/docs/8.x/eloquent-serialization#hiding-attributes-from-json)

Для указания примеров значений для аргумента используйте аннотацию/атрибут `ApiValueExample`:
```php
use Tochka\JsonRpc\Annotations\ApiValueExample;

class TestDTO
{
    #[ApiValueExample(examples: [1, 3, 5])]
    public int $field;
    
    /**
     * @ApiValueExample(examples={"foo", "bar"}}
     */
    public string $foo;
}
```

Для указания вариантов возможных значений (если не используется Enum) - используйте аннотацию/атрибут `ApiExpectedValues`:
```php
use Tochka\JsonRpc\Annotations\ApiExpectedValues;

class TestDTO
{
    #[ApiExpectedValues(values: [1, 3, 5])]
    public int $field;
    
    /**
     * @ApiExpectedValues(values={"foo", "bar"}}
     */
    public string $foo;
}
```

Для указания формата объекта (если для него нет отдельного класса) - используйте аннотацию/атрибут `ApiArrayShape`:
```php
use Tochka\JsonRpc\Annotations\ApiArrayShape;

class TestDTO
{
    #[ApiArrayShape(shape: ['test' => 'string', 'foo' => 'int', 'bar' => 'array', 'object' => FooObject::class])]
    public int $field;
    
    /**
     * @ApiArrayShape(shape={"test": "string", "foo": "int", "bar": "array", "object": FooObject::class}}
     */
    public string $foo;
}
```

Первая строка описания поля в phpDoc считается summary - и отображается в кратком описании аргумента в схеме.
Вторая и следующие строки описания поля в phpDoc считаются description - и отображаются в полном описании аргумента в 
схеме. При этом разрешено ссылаться на MarkDown файл:
```php
class TestDTO
{
    /**
     * Это будет summary
     * А вот это будет description
     */
    public int $someField;
    
    /**
     * Это будет summary
     * $views/docs/description.md // содержимое этого файла будет description
     */
    public string $foo;
}
```

### Информация об ответе
OpenRpc забирает информацию о формате ответа из типа, указанного в качестве результата метода. При этом тип может быть 
указан также в phpDoc этого метода. При этом все возможности OpenRpc, описанные в предыдущем пункте для запросов - будут
также работать и для описания формата ответа.

Кроме того, дополнительно есть возможность описать формат ответа с помощью атрибута/аннотации `ApiArrayShape`:
```php
use Tochka\JsonRpc\Annotations\ApiArrayShape;

class TestController
{
    #[ApiArrayShape(shape: ['test' => 'string', 'foo' => 'int', 'bar' => 'array', 'object' => FooObject::class])]
    public function someMethod(): array
    {
        return [];
    }
    
    /**
     * @ApiArrayShape(shape={"test": "string", "foo": "int", "bar": "array", "object": FooObject::class}}
     */
    public function fooMethod(): array
    {
        return [];
    }
}
```

Также аннотацию/атрибут `ApiArrayShape` можно использовать для описания структуры класса:
```php
use Tochka\JsonRpc\Annotations\ApiArrayShape;

/**
 * @ApiArrayShape(shape={"test": "string", "foo": "int", "bar": "array", "object": FooObject::class})
 */
class MyResultClass
{
    //...
}
```

Также эту аннотацию можно использовать для переопределения структуры какого либо свойства класса:
```php
use Tochka\JsonRpc\Annotations\ApiArrayShape;

class MyResultClass
{
    #[ApiArrayShape(shape: ['test' => 'string', 'foo' => 'int', 'bar' => 'array', 'object' => FooObject::class])]
    public SomeClass $property;
}
```
### Примеры запросов и ответов
*В разработке...*

### Ошибки
*В разработке...*
