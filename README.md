# OpenRpc (Laravel/Lumen)

## Описание
Пакет для автоматической генерации документации JsonRpc-сервера по стандарту OpenRpc (https://spec.open-rpc.org/).
Совместим с пакетом `tochka-developers/jsonrpc`>=v4.0

## Установка
Установка через composer:
```shell script
composer require tochka-developers/openrpc
```
### Laravel
Для Laravel есть возможность опубликовать конфигурацию для всех пакетов:
```shell script
php artisan vendor:publish
```

Для того, чтобы опубликовать только конфигурацию данного пакета, можно воспользоваться опцией tag
```shell script
php artisan vendor:publish --tag="openrpc-config"
```

### Lumen
В Lumen отсутствует команда _vendor:publish_, поэтому делается это вручную.
Если в проекте еще нет директории для конфигураций - создайте ее:
```shell script
mkdir config
```
Скопируйте в нее конфигрурацию openrpc:
```shell script
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

## Настройка точки входа
Укажите в конфигурации `openrpc.endpoint` точку входа для получения схемы OpenRpc:
```php
'endpoint' => '/api/openrpc.json'
```
В дальнейшем эта точка входа будет использована для Service Discovery Method.

## Кеширование
OpenRpc может кешировать схему с описанием JsonRpc, чтобы не собирать ее каждый раз при вызове endpoint.
Для создания кеша используйте команду artisan:
```bash
php artisan openrpc:cache
```
После выполнения этой команды будет выполнена сборка схемы и сохранена в файл кеша. При каждом следующем обращении к 
OpenRpc будет использован именно этот файл, повторной пересборки происходить не будет. 

Если файла с кешем нет - схема каждый раз будет собираться заново.

Чтобы очистить кеш - используйте команду artisan:
```bash
php artisan openrpc:clear
```
Рекомендуется запускать команду кеширования сразу после деплоя перед запуском приложения.

## Как использовать

### Аннотации и атрибуты
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

### Расширенное описание
Во многих объектах спецификации OpenRpc есть поле description, которое позволяет использовать MarkDown разметку.
Для более удобной организации есть возможность выносить эти описания в отдельные файлы с расширением .md, и затем в
описании ссылаться на них:
```php
'description' => '$views/docs/description.md'
```
В данном случае вместо указанного текста в поле подставится содержимое файла `resources/views/docs/description.md`.
Будьте внимательны! Документы должны находиться в папке resources, так как путь к файлу строится относительно этой 
директории.

### Основная информация
Вся основная информация о вашем приложении заполняется в конфигурации `openrpc.php`. В дефолтной конфигурации
описаны все поля, а также приведены примеры

### Информация о серверах (ендпойнты)
Ваше приложение может иметь несколько точек входа с разным списком методов. Все возможные конфигурации точек входа
JsonRpc-сервера описываются в конфигурации `jsonrpc.php`. Для корректного вывода информации о точках входа в OpenRpc
необходимо добавить еще несколько полей в конфигурацию каждого сервера JsonRpc:

```php
/** Адрес endpoint для текущего сервера */
'url' => '/api/v1/public/jsonrpc',
/** Базовая информация о сервере/точки входа */
'summary' => 'Основная точка входа',
/** Расширенное описание сервера/точки входа */
'description' => 'Основная точка входа',
```

### Информация о методах
OpenRpc собирает информацию о доступных методах во всех доступных классах в namespace, указанном в качестве 
основного для конкретной точки входа (в параметре `jsonrpc.SERVER.namespace`). 
OpenRpc считает, что все публичные методы в найденных классах - являются доступными JsonRpc методами. Для того, чтобы 
скрыть некоторые методы/классы от вывода в описание используйте аннотации/атрибуты:
- `ApiIgnore` 
    * если указан для класса - в описание не будут включены все методы этого класса. 
    * eсли указан для метода - в описание не будет включен только этот метод
- `ApiIgnoreMethod(name: 'methodName')` (указывается для класса) - в описание не будет включен указанный метод

Примеры:
```php
use Tochka\JsonRpc\Annotations\ApiIgnore;
use Tochka\JsonRpc\Annotations\ApiIgnoreMethod;

#[ApiIgnore] // игнорирование всего класса
#[ApiIgnoreMethod(name: 'fooMethod')] // игнорирование метода fooMethod
class TestController
{
    #[ApiIgnore] // игнорирование конкретного метода
    public function someMethod()
    {
        // ...
    }
    
    public function fooMethod()
    {
        // ...
    }
}
```

### Информация о формате запроса
Для получения информации о формате запроса OpenRpc ищет среди аргументов метода класс, реализующий интерфейс 
`Tochka\JsonRpc\Contracts\JsonRpcRequestInterface`. Все остальные аргументы метода игнорируются, так как считается,
что весь запрос будет представлен и описан именно этим классом.

В качестве аргументов метода выбираются все публичные поля класса. Кроме того, учитываются поля, описанные в phpDoc 
(помеченные атрибутом `@property`). 

В качестве типа аргумента всегда выбирается тип поля. Поле считается обязательным, если у него нет дефолтного значения.

Если в качестве типа поля указан другой класс - OpenRpc также попытается описать вложенные поля.

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

### Примеры запросов и ответов
*В разработке...*

### Ошибки
*В разработке...*
