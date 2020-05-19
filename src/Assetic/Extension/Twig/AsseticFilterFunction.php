<?php namespace Assetic\Extension\Twig;

use Twig\TwigFunction;

class AsseticFilterFunction
{
    public static function make(AsseticExtension $extension, $name, $options = [])
    {
        return new TwigFunction($name, null, array_merge($options, [
            'node_class' => '\Assetic\Extension\Twig\AsseticFilterNode',
        ]));
    }
}
