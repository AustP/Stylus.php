<?php

/*
 * Stylus.php
 * A Stylus parser for PHP
 * Version 1.0
 * By AustP
 * github.com/AustP/Stylus.php/
 *
 * Composer/PSR-2 compatible fork
 * by neemzy <tom.panier@free.fr>
 * http://www.zaibatsu.fr
 *
 * Stylus for nodejs
 * learnboost.github.com/stylus/
 */

namespace Stylus;

class Exception extends \Exception
{
    public static function report($message = null, $code = null)
    {
        throw new self($message, $code);
    }
}
