<?php

/**
 * User: Karel Wintersky
 *
 * Class AppLogger
 * Namespace: Arris
 *
 * Github: https://github.com/ArrisFramework/Arris.AppLogger
 * Packagist: https://packagist.org/packages/karelwintersky/arris.logger
 *
 */

namespace Arris;

use Arris\AppLogger\Monolog\Handler\StreamHandler;
use Arris\AppLogger\Monolog\Logger;

/**
 * Class AppLogger
 *
 * @package Arris.AppLogger
 */
class AppLogger implements AppLoggerInterface
{
    /**
     * Monolog API version
     *
     * This is only bumped when API breaks are done and should
     * follow the major version of the library
     *
     * @var int
     */
    const API = 2;

    const SCOPE_DELIMITER = '.';

    const DEFAULT_LOG_FILENAME = '_.log';

    /**
     * Порядок опций в параметре $options метода addScope()
     */
    const addScope_OPTION_FILENAME = 0;
    const addScope_OPTION_LOGLEVEL = 1;
    const addScope_OPTION_OPTIONS = 2;

    /**
     * Detailed debug information
     */
    const DEBUG = 100;

    /**
     * Interesting events
     *
     * Examples: User logs in, SQL logs.
     */
    const INFO = 200;

    /**
     * Uncommon events
     */
    const NOTICE = 250;

    /**
     * Exceptional occurrences that are not errors
     *
     * Examples: Use of deprecated APIs, poor use of an API,
     * undesirable things that are not necessarily wrong.
     */
    const WARNING = 300;

    /**
     * Runtime errors
     */
    const ERROR = 400;

    /**
     * Critical conditions
     *
     * Example: Application component unavailable, unexpected exception.
     */
    const CRITICAL = 500;

    /**
     * Action must be taken immediately
     *
     * Example: Entire website down, database unavailable, etc.
     * This should trigger the SMS alerts and wake you up.
     */
    const ALERT = 550;

    /**
     * Urgent alert.
     */
    const EMERGENCY = 600;

    const DEFAULT_SCOPE_OPTIONS = [
        [ '100-debug.log',      self::DEBUG,      'bubbling'  =>  false, 'enable'    =>  true, 'handler'   =>  StreamHandler::class],
        [ '200-info.log',       self::INFO,       'bubbling'  =>  false, 'enable'    =>  true, 'handler'   =>  StreamHandler::class],
        [ '250-notice.log',     self::NOTICE,     'bubbling'  =>  false, 'enable'    =>  true, 'handler'   =>  StreamHandler::class],
        [ '300-warning.log',    self::WARNING,    'bubbling'  =>  false, 'enable'    =>  true, 'handler'   =>  StreamHandler::class],
        [ '400-error.log',      self::ERROR,      'bubbling'  =>  false, 'enable'    =>  true, 'handler'   =>  StreamHandler::class],
        [ '500-critical.log',   self::CRITICAL,   'bubbling'  =>  false, 'enable'    =>  true, 'handler'   =>  StreamHandler::class],
        [ '550-alert.log',      self::ALERT,      'bubbling'  =>  false, 'enable'    =>  true, 'handler'   =>  StreamHandler::class],
        [ '600-emergency.log',  self::EMERGENCY,  'bubbling'  =>  false, 'enable'    =>  true, 'handler'   =>  StreamHandler::class]
    ];

    /**
     * @var array $_instances
     */
    public static array $_instances = [];

    /**
     * @var array
     */
    public static array $_declared_loggers = [];

    /**
     * @var array $_global_config
     */
    public static array $_global_config = [
        'bubbling'                      =>  false,
        'default_logfile_path'          =>  '',
        'default_log_level'             =>  Logger::DEBUG,
        'default_logfile_prefix'        =>  '',
        'default_log_file'              =>  self::DEFAULT_LOG_FILENAME,
        'default_handler'               =>  StreamHandler::class,
        'add_scope_to_log'              =>  false,
        'deferred_scope_creation'       =>  true,
        'deferred_scope_separate_files' =>  true
    ];

    /**
     * @var string
     */
    public static string $application;

    /**
     * @var string
     */
    private static string $instance;

    /**
     * @var array
     */
    private static array $_configs = [];

    /**
     * @inheritDoc
     */
    public static function init(string $application = '', string $instance = '', array $options = [])
    {
        self::$application = $application;
        self::$instance = $instance;

        // Всплывание лога
        self::$_global_config['bubbling']
            = self::setOption($options, 'bubbling', false);

        // Уровень логгирования по умолчанию
        self::$_global_config['default_log_level']
            = self::setOption($options, 'default_log_level', Logger::DEBUG);

        // дефолтные значения для всего AppLogger
        self::$_global_config['default_logfile_path']
            = self::setOption($options, 'default_logfile_path', '');

        if (!empty(self::$_global_config['default_logfile_path'])) {
            self::$_global_config['default_logfile_path']
                = \rtrim(self::$_global_config['default_logfile_path'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR ;
        }

        self::$_global_config['default_logfile_prefix']
            = self::setOption($options, 'default_logfile_prefix', '');

        self::$_global_config['default_log_file']
            = self::setOption($options, 'default_log_file', self::DEFAULT_LOG_FILENAME);

        self::$_global_config['default_handler']
            = self::setOption($options, 'handler', StreamHandler::class);

        // добавлять ли скоуп к имени логгера в файле лога
        self::$_global_config['add_scope_to_log']
            = self::setOption($options, 'add_scope_to_log', false);

        // опции Deferred-скоупов
        self::$_global_config['deferred_scope_creation']
            = self::setOption($options, 'deferred_scope_creation', true);

        self::$_global_config['deferred_scope_separate_files']
            = self::setOption($options, 'deferred_scope_separate_files', true);
    }

    /**
     * @inheritDoc
     */
    public static function addScopeLevel(?string $scope = null, ?string $target = '', int $log_level = Logger::DEBUG, bool $enable = true, bool $bubble = false, $handler = null):void
    {
        $is_deferred_scope = false;

        $logger_name = self::getLoggerName($scope);
        $internal_key = self::getScopeKey($scope);

        $logger
            = array_key_exists($internal_key, self::$_instances)
            ? self::$_instances[$internal_key]
            : new Logger($logger_name);

        $filename
            = $target === 'php://stdout'
            ? 'php://stdout'
            : self::createLoggerFilename($scope, $target, $is_deferred_scope);

        $options = [
            'enable'    =>  $enable,
            'bubbling'  =>  $bubble,
            'handler'   =>  is_null($handler) ?  StreamHandler::class : $handler
        ];

        self::$_declared_loggers[ $scope ][] = [
            'file'      =>  $filename,
            'level'     =>  $log_level,
            'options'   =>  $options
        ];

        if (false === $enable) {
            // Null handler if level not enabled
            $options['enable'] = false;
            $options['handler'] = \Arris\AppLogger\Monolog\Handler\NullHandler::class;
            $logger->pushHandler(new \Arris\AppLogger\Monolog\Handler\NullHandler($log_level));
        }
        elseif ( $handler == StreamHandler::class || $handler === null )
        {
            $logger->pushHandler( new StreamHandler($filename, $log_level, $bubble) );
        }
        elseif (is_string($handler) && (new \ReflectionClass($handler))->implementsInterface('Arris\AppLogger\Monolog\Handler\HandlerInterface'))
        {
            // string + implements interface by reflection
            $logger->pushHandler( new $handler($logger_name, level: $log_level, bubble: $bubble) );
        }
        elseif ( \is_callable($handler) )
        {
            // callable
            $logger->pushHandler( call_user_func_array($handler, []) );
        }
        else
        {
            $logger->pushHandler( new \Arris\AppLogger\Monolog\Handler\NullHandler($log_level) );
        }

        self::$_configs[ $internal_key ][ $log_level ] = $options;
        self::$_instances[ $internal_key ] = $logger;
    }

    /**
     * @inheritDoc
     */
    public static function addScope($scope = null, array $scope_levels = [], bool $scope_logging_enabled = true): void
    {
        $is_deferred_scope = false;

        if (empty($scope_levels)) {
            $scope_levels = self::DEFAULT_SCOPE_OPTIONS;
            $is_deferred_scope = true;
        }

        $logger_name = self::getLoggerName($scope);
        $internal_key = self::getScopeKey($scope);

        $logger
            = array_key_exists($internal_key, self::$_instances)
            ? self::$_instances[$internal_key]
            : new Logger($logger_name);

        // $level - параметры логгера для разных уровней
        foreach ($scope_levels as $level_definition) {
            $filename
                = $level_definition[ self::addScope_OPTION_FILENAME ] === 'php://stdout'
                ? 'php://stdout'
                : self::createLoggerFilename($scope, $level_definition[ self::addScope_OPTION_FILENAME ], $is_deferred_scope);

            $loglevel = $level_definition[ self::addScope_OPTION_LOGLEVEL ] ?? self::$_global_config['default_log_level'];

            $options = \array_key_exists(self::addScope_OPTION_OPTIONS, $level_definition) ? $level_definition[ self::addScope_OPTION_OPTIONS ] : [];

            $level_options = [
                'enable'    =>  \array_key_exists('enable', $options)   ? $options['enable']    : $scope_logging_enabled,
                'bubbling'  =>  \array_key_exists('bubbling', $options) ? $options['bubbling']  : self::$_global_config['bubbling'],
                'handler'   =>  \array_key_exists('handler', $options)  ? $options['handler']   : StreamHandler::class
            ];

            self::$_declared_loggers[ $scope ][] = [
                'file'      =>  $filename,
                'level'     =>  $loglevel,
                'options'   =>  $level_options
            ];

            if (($level_options['enable'] === false) || ($scope_logging_enabled === false))
            {
                // NULL Handler
                $options['enable'] = false;
                $level_options['handler'] = \Arris\AppLogger\Monolog\Handler\NullHandler::class;
                $logger->pushHandler( new \Arris\AppLogger\Monolog\Handler\NullHandler($loglevel) );
            }
            elseif ( $level_options['handler'] == StreamHandler::class || is_null($level_options['handler']) )
            {
                $logger->pushHandler( new StreamHandler($filename, $loglevel, $level_options['bubbling']) );
            }
            elseif (is_string($level_options['handler']) && (new \ReflectionClass($level_options['handler']))->implementsInterface('Arris\AppLogger\Monolog\Handler\HandlerInterface'))
            {
                // string + implements interface by reflection
                /**
                 * @param \Arris\AppLogger\Monolog\Handler\HandlerInterface $handler
                 */
                $handler = $level_options['handler'];
                $logger->pushHandler( new $handler($logger_name, bubble: $level_options['bubbling']) );
            }
            elseif ( \is_callable($level_options['handler']) )
            {
                // у коллбэка не будет параметров
                $logger->pushHandler( call_user_func_array($level_options['handler'], []) );
            }
            else
            {
                $logger->pushHandler( new \Arris\AppLogger\Monolog\Handler\NullHandler($loglevel) );
            }

            self::$_configs[ $internal_key ][ $loglevel ] = $level_options;

        } //foreach

        self::$_instances[ $internal_key ] = $logger;
    }

    /**
     * @inheritDoc
     */
    public static function addNullLogger(): Logger
    {
        return (new Logger('null'))->pushHandler(new \Arris\AppLogger\Monolog\Handler\NullHandler());
    }

    /**
     * @inheritDoc
     */
    public static function scope($scope = null):Logger
    {
        $internal_key = self::getScopeKey( $scope );

        if (!self::checkInstance($internal_key) and self::$_global_config['deferred_scope_creation']) {
            self::addDeferredScope($scope);
        }

        return self::$_instances[ $internal_key ];
    }

    /**
     * Возвращает глобальный конфиг
     *
     * @return array
     */
    public static function getAppLoggerConfig(): array
    {
        return self::$_global_config;
    }

    /**
     * Возвращает вычисленные параметры логгера по имени скоупа и уровню логгирования (int)
     *
     * @param null $scope
     * @param null $level
     * @return array
     */
    public static function getScopeConfig($scope = null, $level = null):array
    {
        $data = \is_null($scope)
                ? self::$_declared_loggers
                : (
                    \array_key_exists( $scope, self::$_declared_loggers) ? self::$_declared_loggers[$scope] : []
                );

        return \is_null($level)
            ? $data
            : \current(\array_filter($data, static function ($v) use ($level)
            {
                return $v['level'] == $level;
            }));
    }

    /**
     * Возвращает опции логгер-скоупа
     *
     * @param null $scope
     * @return mixed
     */
    public static function getLoggerConfig($scope = null)
    {
        return self::$_configs[ self::getScopeKey( $scope ) ];
    }

    /**
     * Поздняя инициализация скоупа со значениями по умолчанию.
     *
     * @param null $scope
     */
    private static function addDeferredScope($scope = null)
    {
        self::addScope($scope, self::DEFAULT_SCOPE_OPTIONS, true, true);
    }

    /**
     * Проверяет существование инстанса логгера ПО internal_key (!!!), не по имени скоупа!
     *
     * @param $key
     * @return bool
     */
    private static function checkInstance($key):bool
    {
        return ( \array_key_exists($key, self::$_instances) && self::$_instances[$key] !== null );
    }

    /**
     * Получает внутренний ключ логгера
     *
     * @param null $scope
     * @return string
     */
    private static function getScopeKey($scope = null): string
    {
        $scope = $scope ? (self::SCOPE_DELIMITER . (string)$scope) : '';
        return self::$application . self::$instance . $scope;
    }

    /**
     * Проверяет существование скоупа по имени
     *
     * @param null $scope
     * @return bool
     */
    private static function checkScopePresent($scope = null): bool
    {
        $key = self::getScopeKey($scope);
        return self::checkInstance($key);
    }

    /**
     * Генерирует имя логгера
     *
     * @param null $scope
     * @return string
     */
    private static function getLoggerName($scope = null): string
    {
        $name = self::$application;

        if (!empty(self::$instance)) {
            $name .= self::SCOPE_DELIMITER . self::$instance;
        }

        if (self::$_global_config['add_scope_to_log'] && $scope) {
            $name .= self::SCOPE_DELIMITER . (string)$scope;
        }

        return $name;
    }

    /**
     * Генерирует имя файла для логгера/скоупа
     *
     * @param $scope
     * @param string $name
     * @param bool $is_deferred
     * @return string
     */
    private static function createLoggerFilename($scope, string $name = '', bool $is_deferred = false): string
    {
        $filename
            = empty($name)
            ? self::$_global_config['default_log_file']
            : $name;

        $filepath = self::$_global_config['default_logfile_path'] . self::$_global_config['default_logfile_prefix'];

        // если мы генерим имя файла для DeferredScope - префикс имени файла = scope, иначе ''
        // определить это мы вызовом метода не можем, придется передавать параметром

        // вообще, проверим, пишется ли deferred-лог в разные файлы?
        $is_deferred = $is_deferred && self::$_global_config['deferred_scope_separate_files'];

        $file_prefix = $is_deferred ? $scope . self::SCOPE_DELIMITER : '';

        return $filepath . $file_prefix . $filename;
    }

    private static function setOption(array $options = [], $key = null, $default_value = null)
    {
        if (!\is_array($options)) {
            return $default_value;
        }

        if (\is_null($key)) {
            return $default_value;
        }

        return \array_key_exists($key, $options) ? $options[ $key ] : $default_value;
    }

}

# -eof- #
