# Init

Инициализирует класс логгера:
```
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

# Add Scope

```
AppLogger::addScope($scope = null, $scope_levels = [], $scope_logging_enabled = true, $is_deferred_scope = false):void
```

Добавляет скоуп (логгер) с параметрами

* `$scope` - имя скоупа
* `$scope_levels` - массив кортежей с опциями уровней логгирования.
* `$scope_logging_enabled` - включено ли логгирование для этого скоупа. Это глобальная настройка для скоупа, если логгирование отключено - никакие опции, переданные в `$scope_levels` его не включат. Если этим параметром логгирование отключено (false), но скоуп создается, но для всех уровней логгирования хэндлер ставится `NullLogger`.
* `$is_deferred_scope` - служебный аргумент, его никогда не следует указывать напрямую (он задает создание логгера как deferred)

Настройки логгеров по уровням (логгирования) передаются в массиве `$scope_levels`. Может быть передан пустой массив - тогда поставятся опции "по умолчанию" (на основе глобальных опций), а скоуп будет создан как 'deferred' (отложенная инициализация).

Пример:
```
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
* `enabled` - [TRUE], разрешен ли этот уровень логгирования. Применяется тот же механизм, что и для глобальной опции `$scope_logging_enabled` для скоупа;
* `bubbling` - [FALSE], всплывает ли сообщение логгирования на следующий (более низкий) уровень?
* `handler` - [NULL] либо хэндлер, реализующий интерфейс `Monolog\Handler\HandlerInterface`. Если указан NULL - будет использован хэндлер по умолчанию: StreamHandler, записывающий лог в файл.

*NB:* Следует отметить, что если используется необъявленный в скоупе логгер, например:
```
AppLogger::scope('mysql')->emergency('MYSQL EMERGENCY');
```
Monolog проспамит этим сообщением по всем объявленным уровням.

# Usage

Вызов `AppLogger::scope($scope_name)` возвращает инстанс `\Monolog\Logger`, к которому можно применить штатные методы логгирования:
```
debug, notice, warn, error, emergency и так далее
```

Например:
```
AppLogger::scope('mysql')->debug("mysql::Debug", [ ['x'], ['y']]);
AppLogger::scope('mysql')->notice('mysql::Notice', ['x', 'y']);
```

# Deferred Scope

Есть возможность использовать скоупы с отложенной инициализацией и параметрами по умолчанию. Этот механизм называется Deferred Scope.

Вызов аналогичен предварительно инициализированному логгеру:
```
AppLogger::scope('usage')->emergency('EMERGENCY USAGE');
```

В этом случае будет создан скоуп `usage` со всеми уровнями логгирования и параметрами по умолчанию (но реальный вызов логгера произойдет только для уровня `emergency`).

*NB:* Если при инициализации обычного скоупа методом `addScope()` передан пустой массив опций логгеров - будет применен механизм инициализации deferred-скоупа.

# Примечания (usage hints)

## Один файл для нескольких уровней логгирования

Указываем наименьший используемый уровень логгирования (`Logger::NOTICE`)
```
AppLogger::addScope('log.selectel', [ [ '_selectel_upload.log', Logger::NOTICE ]  ]);
```

Теперь вот эти два вызова запишут в файл 2 строчки
```
AppLogger::scope('log.selectel')->error('Error');
AppLogger::scope('log.selectel')->notice('Notice');
```

# Custom handler

```php
AppLogger::addScope('console', [
        [ 'php://stdout', Logger::INFO, [ 'handler' => StreamHandler::class ]]
    ], $options['verbose']);
```
Добавляет стандартный StreamHandler (в stdout). При этом он используется только если `$options['verbose'] === true`.

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

Добавляет собственный хэндлер логгирования - и кастомный форматтер. 


https://stackoverflow.com/questions/70875746/laravel-monolog-lineformatter-datetime-pattern



