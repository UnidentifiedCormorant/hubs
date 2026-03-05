# Laravel Hubs

План (поменять местами, если надо)

Оглавление
1. [Вступление](#вступление)
2. [Установка](#установка)
3. [Quick Start](#quick-start)
   1. [Подготовка объекта](#подготовка-объекта)
   2. [Подготовка хаба](#подготовка-хаба)
   3. [Подготовка пайпов](#подготовка-пайпов)
   4. [Запуск хаба](#запуск-хаба)
4. [Хабы](#хабы)
   1. Основные концепты
      1. PipeObjectable
      2. Pipelineable
      3. Несколько слов о структуре директорий
   2. setObject
   3. getResult
   4. Запуск хабов
   5. Хабы и транзакции
5. Пайпы
   1. Что может быть пайпом
   2. Создание пайпов для хабов
7. Консольные команды
   1. Создание хабов
      1. Что умеет команда и как она ведёт себя с разными параметрами
      2. Примеры
   2. Создание пайпов
      1. Что умеет команда и как она ведёт себя с разными параметрами
      2. Примеры
8. Рецепты
   1. Полиморфизм в хабах
   2. Переиспользуемость пайпов в разных хабах

## Вступление
Когда бизнес-процесс, который реализует наше приложение постоянно расширяется, его кейсы множатся, а код продолжает писаться "колбасой", поддерживать эту радость жизни становится, мягко говоря, болезненно.
Хабы предлагают альтернативный способ организации кода для выразительного и простого описания бизенс-процессов в рамках фреймворка Laravel.

Хаб - средоточие логики, по работе с частями бизнес-процесса. 
Любая задача, так или иначе, должна быть декомпозирована и разбита на этапы реализации. 
Хабы позволяют явно выделить эти этапы и сложить их в отдельные классы, которые благодаря контейнеру служб Laravel, можно будет использовать практически в любой части вашего приложения.

Технически, хабы представляют собой обёртку над пакетом `illuminate/pipeline`, где в пайпах описывается логика, которая в дальнейшем запускается в пайплайнах. Связать всё это вместе помогает объект, который кладётся в пайплайн и так или иначе, попадает в каждый пайп. 

> В Laravel по этому принципу работают, например, `middleware` 

Хабы в свою очередь, предоставляют способы различные способы наполнения, запуска, а также манипулирования пайпами.

## Установка
Для работы с пакетом необходимо выполнить два шага:
1. Установить его
```
composer require yourcormorant/laravel-hubs
```
2. Добавить провайдер пакета в файл `providers.php`
```diff
return [
    App\Providers\AppServiceProvider::class,
    +\Yourcormorant\LaravelHubs\Providers\HubServiceProvider::class,
];
```

## Quick start
Кратко рассмотрим важные функции пакета и то, как им пользоваться.

Сначала пакет необходимо [установить](#установка).

В рамках краткого руководства реализуем бизнес-процесс "создание заказа пользователя". Наш бизнес-процесс состоит из следующих этапов:
1. Получение товаров из корзины, которая работает на базе сессии. В сессии хранятся только id товаров
2. Создание записи о заказе в базе данных. У наших гипотетических заказов есть адрес и комментарий 
3. Создание записей о товарах, участвующих в заказе в базе данных

> Примечание: пример утрирован для демонстрации возможностей хабов

Напомним, что пайплайны, которые используются в хабах, состоят из трёх частей - объект, пайпы и пайплайн. Уделим внимание каждой из них.

### Подготовка объекта
Для начала подготовим наш объект.
```php
namespace App\Domain\DTO;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Spatie\LaravelData\Data;
use App\Models\Order\Order;
use App\Models\Order\OrderItem;
use App\Models\Product;
use Yourcormorant\LaravelHubs\Abstracts\PipeObjectable;

class OrderData extends Data
{
    public function __construct()(
        public string $address,
        public string $comment,
    ){}

    public Order $order;

    /** @var EloquentCollection<int, Product> */
    public EloquentCollection $products;

    /** @var EloquentCollection<int, OrderItem> */
    public EloquentCollection $orderItems;
}
```

> В нашем примере используется пакет [spatie/laravel-data](https://github.com/spatie/laravel-data) для дальнейшей интеграции полей из FormRequest в наш объект

В объект для хаба можно помещать любые поля, которые пригодятся вам по ходу работы с бизнес-процессом. Так, поля в методе `__construct()` будут использоваться для создания объекта, в `$order` мы сохраним заказ, созданный в одном из пайпов, в `$products` модели товаров, полученные с помощью сессии, а в `$orderItems` - товары, участвовавшие в заказе.

> Если полей из FormRequest слишком много, можно использовать отдельный объект для обособленной работы с ними

Заключительным шагом при подготовке объекта будет добавление к нему реализации интерфейса PipeObjectable, который явно сообщает, что объект используется в хабе.
```diff
...
use App\Models\Order\OrderItem;
+use Yourcormorant\LaravelHubs\Abstracts\PipeObjectable;

-class OrderData extends Data
+class OrderData extends Data implements PipeObjectable
...
```
Интерфейс является пустотелым, поэтому добавлять реализации каких-либо методов не нужно.

Наш объект готов.

### Подготовка хаба
Теперь создадим хаб. Используем artisan-команду для генерации базового файла хаба, а так же создания структуры директорий для дальнейшей работы с ним.

```bash
php artisan make:hub CreateOrderHub --object="App\Domain\DTO\OrderData"

   INFO  [app/Hubs/Order/OrderHub.php] created successfully.

 Создать интерфейс для будущих пайпов этого хаба? (yes/no) [no]:
 > yes

   INFO  [app/Hubs/Order/Abstracts/OrderPipelineable.php] created successfully.
```

При вызове команды явно указываем ссылку на объект, который будет использоваться в хабе. Также соглашаемся на генерацию интерфейса, что будет использоваться во всех будущих пайпах в этом хабе.

В результате работы команды, в `app` должна появиться директория `Hubs`, со следующей структурой:
```
Hubs/
└── Order
    ├── Abstracts
    │   └── OrderPipelineable.php
    └── OrderHub.php
```

Кроме того, был автоматически сгенерирован класс `OrderHub`, где в методе `setObject()` с помощью объединения типов явно указан наш объект, с которым будет работать хаб.
```php
namespace App\Hubs\Order;

use App\Domain\DTO\OrderData;
use Yourcormorant\LaravelHubs\Abstracts\AbstractHub;
use Yourcormorant\LaravelHubs\Abstracts\PipeObjectable;

class OrderHub extends AbstractHub
{
    protected array $pipes = [

    ];

    public function setObject(OrderData|PipeObjectable $object): AbstractHub
    {
        return parent::setObject($object);
    }
}
```

Также, сгенерирован интерфейс `OrderPipelineable`, где обозначена сигнатура метода `handle()` для будущих пайпов с явным указанием нашего объекта.
```php
namespace App\Hubs\Order\Abstracts;

use Yourcormorant\LaravelHubs\Abstracts\PipeObjectable;
use Yourcormorant\LaravelHubs\Abstracts\Pipelineable;
use App\Domain\DTO\OrderData;
use Closure;

interface OrderPipelineable extends Pipelineable
{
    public function handle(OrderData|PipeObjectable $data, Closure $next): Closure|PipeObjectable;
}
```

Хаб готов.

### Подготовка пайпов
Теперь добавим реализацию создания заказа в соответствии с тремя этапами, описанными выше.

Для генерации пайплайна вновь используем artisan-команду:
```bash
php artisan make:pipe GetOrderItems OrderHub

   INFO  [app/Hubs/Order/Pipes/GetOrderItems.php] created successfully.
```
Вторым аргументом указывается хаб, в рамках которого будет использоваться новоиспечённый пайп. По итогу в директории app/Hubs/Order должна была появиться следующая структура:
```
Pipes
└── GetOrderItems.php
```
Займёмся классом GetOrderItems.
```php
namespace App\Hubs\Order\Pipes;

use App\Domain\DTO\OrderData;
use App\Hubs\Order\Abstracts\OrderPipelineable;
use Closure;
use Yourcormorant\LaravelHubs\Abstracts\PipeObjectable;

class GetOrderItems implements OrderPipelineable
{
    public function handle(OrderData|PipeObjectable $data, Closure $next): Closure|PipeObjectable
    {
        //

        return $next($data);
    }
}
```
Логика, которую выполняет пайп, описываетс в методе `handle()`. В него попадает наш объект и замыкание, ссылающееся на следующий пайп в пайплайне.

> Обратите внимание на то, что строка `return $next($data);` явно указывает на запуск следующего пайпа. Без неё обработка прервётся, поэтому автор настоятельно рекомендует не забывать о ней!

Добавим логику получения товаров из сессии в нашем пайпе.
```php
...
use App\Services\CartSessionService;
use App\Models\Product;

class GetOrderItems implements OrderPipelineable
{
    public function __construct()(
        private readonly CartSessionService $cartSessionService,
    ) {}

    public function handle(OrderData|PipeObjectable $data, Closure $next): Closure|PipeObjectable
    {
        $data->products = Product::whereIn(
            'id',
            $this->cartSessionServic->getItemsIds(),
        );

        return $next($data);
    }
}
```
Обратите внимание на метод `__construct()`. Хабы используют контейнер служб Laravel, поэтому в них допускается внедрение зависимостей через `__construct()`.
Полученные из базы данных модели товаров сохраняем 

> Данный шаг можно было бы пропустить, т.к. логики получения товаров из сессии не то что бы много, однако не забывайте, что это пример. 
> В реальных случаях логика может быть гораздо больше и сложнее, и тогда использование этого пайпа будет оправдано. 
> Старайтесь делить логику на участки, которые впоследствии можно будет расширить без вреда для других пайпов и хабов (этот пайп может использоваться не только в этом хабе).

По аналогии создадим ещё два пайпа для оставшихся этапов бизнес-процесса - `CreateOrder` и `CreateOrderItems`.

Заключающим действием будет добавление наших пайпов в хаб.

```php
...
use App\Hubs\Order\Pipes\GetOrderItems;
use App\Hubs\Order\Pipes\CreateOrder;
use App\Hubs\Order\Pipes\CreateOrderItems;

class OrderHub extends AbstractHub
{
    protected array $pipes = [
        GetOrderItems::class,
        CreateOrder::class,
        CreateOrderItems::class,
    ];
...
```

Пакет предоставляет несколько способов добавления пайпов в хаб, а также манипулирования порядком их выполнения.
В данном случае, пайпы явно указываются прямо в хабе и будут выполнены ровно в том порядке, в каком расставлены в массиве `$pipes`. Этот способ отлично подходит для бизнес-процессов, логика в которых всегда одинаковая и порядок её шагов не зависит от внешних факторов.

Пайпы готовы.

### Запуск хаба
Наконец, запустим хаб.
Обязательно добавляем его в ServiceProvider, например с помощью массива `$singletons`, чтобы использовать преимущества контейнера служб.
```php
...
class AppServiceProvider extends ServiceProvider
{
    public $singletons = [
        OrderHub::class,
    ];
...
```
Хаб можно запустить как функцию, т.к. в `AbstractHub`, который по умолчанию наследуют все создаваемые командой хабы, реализован магический метод `__invoke()`.
Также, его можно передать в контроллер или в метод __construct() в рамках контейнера служб.
```php
class OrderController extends Controller
{
    public function store(StoreOrderRequest $request, OrderHub $orderHub)
    {
        $data = OrderData::from($request);
        
        /** @var OrderData $result */
        $result = $orderHub($data); //Запускаем хаб и сразу получаем результат
        
        return OrderResource::make($result);
    }
}
```
По умолчанию вызов хаба как функции возвращает тот же объект, что был передан в него, однако при необходимости это поведение можно переопределить (см раздел TODO).
В связи с этим, код можно сократить код метода `store()`:

```php
public function store(StoreOrderRequest $request, OrderHub $orderHub)
{
    return OrderResource::make(
       $orderHub(OrderData::from($request))
    );
}
```

Таким образом мы успешно описали логику бизнес-процесса с использованием хаба.

## Хабы
В этом разделе более подробно рассмотрим возможности хабов.

### Основные концепты
В предыдущем разделе вы могли столкнуться с такими структурами как PipeObjectable и OrderPipelineable. Давайте подробнее рассмотрим для чего они нужны и как используются в пакете.

#### PipeObjectable
Пустотелый интерфейс, явно указывающий на то, что текущий класс может использоваться в хабе. Пока что не содержит дополнительных методов.
Используется с расчётом на то, что в будущем, объектам для хабов могут понадобиться дополнительные методы и расширение данного интерфейса вызовет необходимость доработать логику всех объектов без риска что-то упустить. 

#### Pipelineable
Базовый интерфейс, который обязан реализовывать каждый пайп библиотеки. Использует PipeObjectable в сигнатуре для метода handle(), который вызывается по умолчанию для пайпов в хабе.
При работе с хабами рекомендуется создать дополнительный интерфейс исключительно для данного хаба, либо наборов хабов, который наследует интерфейс Pipelineable и расширяет сигнатуру метода handle().
Давайте ещё раз взглянем на интерфейс, производный от Pipelineable:
```php
interface OrderPipelineable extends Pipelineable
{
    public function handle(OrderData|PipeObjectable $data, Closure $next): Closure|PipeObjectable;
}
```
Можно заметить, что в нём явно указывается объект, с которым работает хаб. Бонусом, благодаря объединению типов, IDE сможет подсказать поля объекта.
Команда для создания хаба может без особых проблем генерировать подобные интерфейсы, а пайпы, создаваемые уже другой командой для этого хаба будут, цеплять его самостоятельно.

#### Несколько слов о структуре директорий
Давайте ещё раз вглянем на структуру директорий после созданий хаба:
```
Hubs/
└── Order
    ├── Abstracts
    │   └── OrderPipelineable.php
    └── OrderHub.php
```
Возможно, ваше внимание могла привлечь директория `Order`.
Причина её генерация следующая - несколько хабов могут использовать одни и те же пайпы, и поэтому их можно будет сложить в эту директорию. 
Если она не указана явно при создании хаба - её название будет получено из названия хаба.
Если вам необходимо самостоятельно указать её название - сделать это можно следующим образом:
```bash
php artisan make:hub Test/SuperHub

 Введите пространство имён + название класса объекта, с которым будет работать хаб (по умолчанию null). Пример: App\Entities\EntityClassName:
 >

   INFO  [app/Hubs/Test/SuperHub.php] created successfully.
```
Результат:
```
Hubs/
└── Test
    └── SuperHub.php
```

### setObject
Служит исключительно для того, чтобы явно обозначить с каким объектом работает хаб. 
Для рекомендуется минимально переопределить его в хабе, чтобы по одному взгляду на класс можно было понять, что за объект поедет через пайпы.
```php
...
class OrderHub extends AbstractHub
...
    public function setObject(OrderData|PipeObjectable $object): AbstractHub
    {
        return parent::setObject($object);
    }
```

### getResult
Как было сказано выше, в результате вызова хаба как функции будет получен объект, отправленный в хаб.
Тем не менее, это поведение по умолчанию можно переопределить, добавив в хаб реализацию метода getResult().
Например, сделаем так, чтобы в результате работы хаба мы получали модель заказа, а не DTO:
```php
...
class OrderHub extends AbstractHub
...
    /**
    * @returns Order
    */
    public function getResult(): mixed
    {
        return parent::getResult()->order;
    }
```

### Запуск хабов
Помимо запуска хаба как функции, можно использовать метод `init()`, который принимет те же аргументы, что и `__invoke()`.
`init()` может пригодится, если будет необходим подобавлять пайпы, либо ещё как-то настроить хаб перед запуском.
```php
public function store(StoreOrderRequest $request, OrderHub $orderHub)
{
    return OrderResource::make(
       $orderHub->init(OrderData::from($request))
    );
}
```
Кроме этого, хаб можно запустить вызвав по цепочке его основные методы:
```php
public function store(StoreOrderRequest $request, OrderHub $orderHub)
{
    return OrderResource::make(
       $orderHub
           ->setObject(OrderData::from($request))
           ->preparePipeline()
           ->getResult()
    );
}
```

### Хабы и транзакции
И в `__invoke()` и в `init()` можно передать дополнительный bool-аргумент. Если передано `true`, хаб выполнится в рамках функции `Illuminate\Support\Facades\DB::transaction()`. 
Любое исключение, выброшенное в любом из пайпов выполнит стандартный откат транзакции, а значит и всех изменений, что затронули базу данных пока выполнялись пайпы.

> Имейте в виду, что запуск хаба вызовом методов по цепочке не предоставляет возможности покрыть хаб транзакцией.

### Создание хабов
Рекомендуемый способ создания хабов - с помощью команды. Команда позволяет создать хаб без привязки к объекту (т.е. явно не обозначать его в методе setObject()) и без интерфейса для хабов.
