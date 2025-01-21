# Init

Инициализирует класс логгера:

```php
AppLogger::init($application, $instance, $options = []):void
```

* `$application` - Имя приложения
* `$instance` - код инстанса приложения (рекомендуется генерировать его при старте приложения и с помощью этого кода отличать логи, записанные разными инстансами приложения параллельно)
* `$options` опции приложения:
  * `bubbling` - [FALSE] - всплывает ли логгируемое сообщение?
  * `default_log_level` - `[Monolog::DEBUG]` - уровень логгирования по умолчанию
  * `default_logfile_path` - `''` - путь к файлам логов по умолчанию
  * `default_logfile_prefix` - `''` - префикс файла лога по умолчанию
  * `default_log_file` - `'_.log'` - имя файла лога по умолчанию, применяется если для имени файла передан NULL
  * `default_handler` - или хэндлер, реализующий \Monolog\Handler\HandlerInterface как логгер по умолчанию для этого скоупа
  * `add_scope_to_log` - [FALSE] - добавлять ли имя скоупа к имени логгера в файле лога?
  * `deferred_scope_creation` - [TRUE] - разрешать ли отложенную инициализацию скоупов
  * `deferred_scope_separate_files` - [TRUE] - использовать ли разные файлы для deferred-скоупов (на основе имени скоупа)

# Add Scope (multiply levels)

```php
AppLogger::addScope($scope = null, $scope_levels = [], $scope_logging_enabled = true, $is_deferred_scope = false):void
```

Добавляет скоуп (логгер) с параметрами

* `$scope` - имя скоупа
* `$scope_levels` - массив кортежей с опциями уровней логгирования.
* `$scope_logging_enabled` - включено ли логгирование для этого скоупа. Это глобальная настройка для скоупа, если логгирование отключено - никакие опции, переданные в `$scope_levels` его не включат. Если этим параметром логгирование отключено (false), но скоуп создается, но для всех уровней логгирования хэндлер ставится `NullLogger`.
* `$is_deferred_scope` - служебный аргумент, его никогда не следует указывать напрямую (он задает создание логгера как deferred)

Настройки логгеров по уровням (логгирования) передаются в массиве `$scope_levels`. Может быть передан пустой массив - тогда поставятся опции "по умолчанию" (на основе глобальных опций), а скоуп будет создан как 'deferred' (отложенная инициализация).

Пример:
```php
AppLogger::addScope('mysql', 
[
    [ '__mysql.100-debug.log', Logger::DEBUG, 'enable' => true],
    [ '__mysql.250-notice.log', Logger::NOTICE,  'enable' => true],
    [ '__mysql.300-warning.log', Logger::WARNING,  'enable' => true],
    [ '__mysql.400-error.log', Logger::ERROR,  'enable' => true],
], getenv('IS_MYSQL_LOGGER_ENABLED'));
```

Параметры уровня логгирования (элементы кортежа опций):
* Первый элемент: `filename` - имя файла (в случае отсутствия будет применено имя по умолчанию из глобальных опций)
* Второй элемент:`logging_level` - уровень логгирования, используются числа или (что удобнее), константы `\Monolog\Logger::DEBUG` и другие из того же неймспейса.

Остальные параметры передаются через ключи ассоциативного массива:
* `enable` - [TRUE], разрешен ли этот уровень логгирования. Применяется тот же механизм, что и для глобальной опции `$scope_logging_enabled` для скоупа;
* `bubbling` - [FALSE], всплывает ли сообщение логгирования на следующий (более низкий) уровень?
* `handler` - [NULL] либо хэндлер, реализующий интерфейс `Monolog\Handler\HandlerInterface`. Если указан NULL - будет использован хэндлер по умолчанию: StreamHandler, записывающий лог в файл.

*NB:* Следует отметить, что если используется необъявленный в скоупе логгер, например:

```php
AppLogger::scope('mysql')->emergency('MYSQL EMERGENCY');
```
Monolog проспамит этим сообщением по всем объявленным уровням.

# Scope 

Вызов `AppLogger::scope($scope_name)` возвращает инстанс `\Monolog\Logger`, к которому можно применить штатные методы логгирования:

```
debug, notice, warn, error, emergency и так далее
```

Пример:

```php
AppLogger::scope('mysql')->debug("mysql::Debug", [ ['x'], ['y']]);
AppLogger::scope('mysql')->notice('mysql::Notice', ['x', 'y']);
```

# Deferred Scope

Есть возможность использовать скоупы с отложенной инициализацией и параметрами по умолчанию. Этот механизм называется Deferred Scope.

Вызов аналогичен предварительно инициализированному логгеру:

```php
AppLogger::scope('usage')->emergency('EMERGENCY USAGE');
```

В этом случае будет создан скоуп `usage` со всеми уровнями логгирования и параметрами по умолчанию (но реальный вызов логгера произойдет только для уровня `emergency`).

*NB:* Если при инициализации обычного скоупа методом `addScope()` передан пустой массив опций логгеров - будет применен механизм инициализации deferred-скоупа.

# Hints 

## Один файл для нескольких уровней логгирования

Указываем наименьший используемый уровень логгирования (`Logger::NOTICE`)

```php
AppLogger::addScope('log.selectel', [ 
    [ '_selectel_upload.log', Logger::NOTICE ]  
]);
```

Теперь вот эти два вызова запишут в файл 2 строчки
```php
AppLogger::scope('log.selectel')->error('Error');
AppLogger::scope('log.selectel')->notice('Notice');
```

# Custom handler - хэндлер, отличный от стандартного StreamHandler

Дефолтное определение хэндлера, выводящего данные в stdout, таково:
```php
AppLogger::addScope('console', [
        [ 'php://stdout', Logger::INFO, [ 'handler' => StreamHandler::class ]]
    ], $options['verbose']);
```

Добавляем кастомный форматтер и хэндлер логгирования:
```php
  AppLogger::addScope('console', [
        [ 'php://stdout', Logger::INFO, [ 'handler' => static function()
          {
              $formatter = new \Arris\Formatter\LineFormatterColored("[%datetime%]: %message%\n", "Y-m-d H:i:s", false, true);
              $handler = new StreamHandler('php://stdout', Logger::INFO);
              $handler->setFormatter($formatter);
              return $handler;
          }
    ], $options['verbose']);
```

Смотри: https://stackoverflow.com/questions/70875746/laravel-monolog-lineformatter-datetime-pattern

# addScopeLevel()

Метод используется для описания конкретного уровня логгирования и логгера. Рекомендуется использовать в PHP8+:

## "Обычное" логгирование в файл:

```php
AppLogger::addScopeLevel('xxx', 'info.log', Logger::INFO); // Handler не указан, что означает, по умолчанию, StreamHandler 
AppLogger::scope('xxx')->info('Message XXX');
```

## Передача хэндлера коллбэком

```php
AppLogger::addScopeLevel('syslog', 'syslog', Logger::DEBUG, true, false, function (){
    return new SyslogHandler(AppLogger::$application, LOG_USER, Logger::DEBUG, false);
});

AppLogger::addScopeLevel('syslog', 'syslog', Logger::INFO, true, false, function (){
    return new SyslogHandler(AppLogger::$application, LOG_USER, Logger::INFO, false);
});

AppLogger::scope('syslog')->debug('Debug message from AppLogger');
AppLogger::scope('syslog')->info('Info message from AppLogger');
```
Так мы задаем кастомный хэндлер через коллбэк, указывая для него особые параметры.

Или, для PHP8, короче:

```php
AppLogger::addScopeLevel('syslog', 'syslog', Logger::DEBUG, handler: function (){
    return new SyslogHandler(AppLogger::$application, LOG_USER, Logger::DEBUG, false);
});

AppLogger::addScopeLevel('syslog', 'syslog', Logger::INFO, handler: function (){
    return new SyslogHandler(AppLogger::$application, LOG_USER, Logger::INFO, false);
});
```

## Передача хэндлера строкой (не рекомендуется в версии 1.*)

```php
AppLogger::addScopeLevel('syslog', 'syslog', Logger::INFO, handler: SyslogHandler::class); 
```
Проблема: конструктор примет значения по-умолчанию, в том числе `$bubble = true`, что вызывает странные
эффекты. Например:

```php
AppLogger::addScopeLevel('syslog', 'syslog', Logger::INFO, handler: SyslogHandler::class);
AppLogger::scope('syslog')->debug('Debug message from AppLogger');
AppLogger::scope('syslog')->info('Info message from AppLogger');
```
Выдаст 2 записи для Debug и 2 для Info. Причина - "bubbling". 








