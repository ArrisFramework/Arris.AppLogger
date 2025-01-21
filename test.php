<?php

use Arris\AppLogger;
use Arris\AppLogger\Monolog\Handler\StreamHandler;
use Arris\AppLogger\Monolog\Handler\SyslogHandler;
use Arris\AppLogger\Monolog\Logger;

require_once __DIR__ . '/vendor/autoload.php';

\Arris\AppLogger::init('AppLogger', '', [
    'default_logfile_path'  =>  __DIR__,
]);

/*AppLogger::addScope('console', [
    [ 'php://stdout', Logger::INFO, [ 'handler' => function() {
        $formatter = new \Arris\Formatter\LineFormatterColored("[%datetime%]: %message% %context% %extra%\n", "Y-m-d H:i:s", true, true);
        $handler = new StreamHandler('php://stdout', Logger::INFO);
        $handler->setFormatter($formatter);
        return $handler;
    } ]
    ]
]);*/

AppLogger::addScope('handler', [
    [ 'debug.log', Logger::DEBUG, [ /*'handler' => AppLogger\Monolog\Handler\StreamHandler::class*/ ] ],
    [ 'info.log', Logger::INFO, [ /*'handler' => AppLogger\Monolog\Handler\StreamHandler::class*/ ] ]
]);


AppLogger::addScopeLevel('syslog', 'syslog', Logger::DEBUG, true, false, handler: function (){
    return new SyslogHandler(AppLogger::$application, LOG_USER, Logger::DEBUG, false);
});

AppLogger::addScopeLevel('syslog', 'syslog', Logger::INFO, true, false, handler: function (){
    return new SyslogHandler(AppLogger::$application, LOG_USER, Logger::INFO, false);
});


/*
AppLogger::addScopeLevel('xxx', 'info.log', Logger::INFO);
AppLogger::scope('xxx')->info('Message XXX');
*/


// AppLogger::addScopeLevel('syslog', 'syslog', Logger::INFO, handler: SyslogHandler::class); // так лучше не делать

// AppLogger::addScopeLevel('syslog', 'syslog', Logger::INFO, true, false, SyslogHandler::class); // так лучше не делать

AppLogger::scope('handler')->debug('Message');

AppLogger::scope('handler')->info('Message');

// AppLogger::addScopeLevel('xxx', 'info.log',Logger::DEBUG, handler: [ AppLogger\Monolog\Handler\SyslogHandler::class ]);

AppLogger::scope('syslog')->debug('Debug message from AppLogger');
AppLogger::scope('syslog')->info('Info message from AppLogger');

// \Arris\AppLogger::scope('console')->info("Test message with <font color='blue'>blue</font> color");

// \Arris\AppLogger::scope('console')->info("Test message with <br><hr color='blue' width='5'><br>color");