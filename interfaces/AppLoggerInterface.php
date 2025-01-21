<?php

namespace Arris;

use Arris\AppLogger\Monolog\Logger;

interface AppLoggerInterface
{
    public static function init(string $application = '', string $instance = '', array $options = []);


    public static function addScope($scope = null, array $scope_levels = [], bool $scope_logging_enabled = true);

    public static function scope($scope = null):Logger;

    public static function addNullLogger();

    /**
     * @param string|null $scope
     * @param string|null $target
     * @param int $log_level
     * @param bool $enable
     * @param bool $bubble
     * @param callable|null $handler
     * @return mixed
     *@todo: for PHP8
     *
     */
    public static function addScopeLevel(?string $scope = null, ?string $target = '', int $log_level = Logger::DEBUG, bool $enable = true, bool $bubble = false, $handler = null);
}