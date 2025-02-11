<?php

namespace Arris\AppLogger;

use Arris\AppLogger\Monolog\Formatter\LineFormatter;

class LineFormatterColored extends LineFormatter
{
    const FOREGROUND_COLORS = [
        'black'         => '0;30',
        'dark gray'     => '1;30',
        'dgray'         => '1;30',
        'blue'          => '0;34',
        'light blue'    => '1;34',
        'lblue'         => '1;34',
        'green'         => '0;32',
        'light green'   => '1;32',
        'lgreen'        => '1;32',
        'cyan'          => '0;36',
        'light cyan'    => '1;36',
        'lcyan'         => '1;36',
        'red'           => '0;31',
        'light red'     => '1;31',
        'lred'          => '1;31',
        'purple'        => '0;35',
        'light purple'  => '1;35',
        'lpurple'       => '1;35',
        'brown'         => '0;33',
        'yellow'        => '1;33',
        'light gray'    => '0;37',
        'lgray'         => '0;37',
        'white'         => '1;37'
    ];

    const BACKGROUND_COLORS = [
        'black'     => '40',
        'red'       => '41',
        'green'     => '42',
        'yellow'    => '43',
        'blue'      => '44',
        'magenta'   => '45',
        'cyan'      => '46',
        'light gray'=> '47'
    ];

    public function __construct(?string $format = null, ?string $dateFormat = null, bool $allowInlineLineBreaks = false, bool $ignoreEmptyContextAndExtra = false, bool $includeStacktraces = false)
    {
        parent::__construct($format, $dateFormat, $allowInlineLineBreaks, $ignoreEmptyContextAndExtra, $includeStacktraces);
    }

    public function format(array $record): string
    {
        return self::colorize( parent::format($record));
    }

    private function colorize(string $message):string
    {
        // replace <br>
        $pattern_br = '#(?<br>\<br\s?\/?\>)#U';
        $message = \preg_replace_callback($pattern_br, function ($matches) {
            return PHP_EOL;
        }, $message);

        // replace <hr>
        $pattern_hr = '#<\s*hr\s*(?:(?<attrName1>\w+)=[\\\"\'](?<attrValue1>\S*)[\\\"\'])?\s*(?:(?<attrName2>\w+)=[\\\"\'](?<attrValue2>\S*)[\\\"\'])?>#';
        $message = \preg_replace_callback($pattern_hr, function ($matches) {
            $color = $matches['attrName1'] == 'color' ? $matches['attrValue1'] : ($matches['attrName2'] == 'color' ? $matches['attrValue2'] : "white");
            $width = $matches['attrName1'] == 'width' ? $matches['attrValue1'] : ($matches['attrName2'] == 'width' ? $matches['attrValue2'] : 80);
            $width = \max((int)$width, 0);
            $line = \str_repeat('-', $width);
            $color = self::FOREGROUND_COLORS[$color] ?? self::FOREGROUND_COLORS['white'];
            return "\033[{$color}m{$line}\033[0m";
        }, $message);

        // replace <font>
        $pattern_font = '#(?<Full>\<font[\s]+color=[\\\'\"](?<Color>[\D]+)[\\\'\"]\>(?<Content>.*)\<\/font\>)#U';
        $message = preg_replace_callback($pattern_font, function ($matches) {
            $color = self::FOREGROUND_COLORS[$matches['Color']] ?? self::FOREGROUND_COLORS['white '];
            return "\033[{$color}m{$matches['Content']}\033[0m";
        }, $message);

        $pattern_strong = '#(?<Full>\<strong\>(?<Content>.*)\<\/strong\>)#U';
        $message = \preg_replace_callback($pattern_strong, function ($matches) {
            $color = self::FOREGROUND_COLORS['white'];
            return "\033[{$color}m{$matches['Content']}\033[0m";
        }, $message);

        $message = \strip_tags($message);

        return $message;
    }


}