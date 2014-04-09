# Stylus.php

A Stylus parser for PHP

***

### CSS needs a hero... again

When I first saw [Stylus](http://learnboost.github.com/stylus/) I thought it was amazing and I implemented it
into my nodejs application. When I started my next project, which was a PHP project, I liked Stylus so much
that I wanted to implement it into my PHP project as well. Surprisingly, I couldn't find any Stylus parser for PHP.
So I did as any developer would do and created my own. And I want to share it.

## Current Features

+ Omit braces
+ Omit colons
+ Omit semi-colons
+ Custom functions
+ Importing other files
+ '&' parent reference
+ Mixins
+ Interpolation
+ Variables

## Using Stylus.php

Using Stylus.php is really easy! Just include the following code:

```php
require('Stylus.php');

$stylus = new Stylus();
$stylus->setReadDir('read');
$stylus->setWriteDir('write');
$stylus->setImportDir('import'); //if you import a file without setting this, it will import from the read directory
$stylus->parseFiles();
```

And that's all there is to it! Now a quick note about the `parseFiles()` function. It has one parameter called
`overwite` which defaults to `false`. It is a flag indicating whether or not you want to overwrite your
already parsed Stylus files.

This means that you could include this code on every page and you won't be parsing your Stylus files every time.
But make sure that you set `overwrite` to `true` when you are developing or updating your Stylus files so the
changes will be reflected in your site.

### Parse a single file or strings

Instead of compiling all the files in the read directory, you can choose exactly what to do using the following syntax.

```php
// From string to string
$css = $stylus->fromString("body\n  color black")->toString();

// From string to file
$stylus->fromString("body\n color black")->toFile("out.css");

// From file to string
$css = $stylus->fromFile("in.styl")->toString();

// From file to file
$stylus->fromFile("in.styl")->toFile("out.css");
```

`toFile($file, $overwrite)` takes two parameter, both of them optional.

+ `$file`: The filename to write to, if ommited or null it will take the input filename and change `.styl` to `.css`.
+ `$overwrite`: Specifies wheter or not to parse and write the file if a file with the same name is found.

## Assigning Variables

Assigning variables is done the same way as in regular Stylus. But you now have the option of adding variables
from PHP before parsing the stylus files by calling the `assign` function. Here is an example:

**PHP**
```php
$stylus->assign('px_size', '30px');
$stylus->parseFiles();
```

**Stylus**

```stylus
div
  font-size px_size
```

**Yields**

```css
div {
    font-size: 30px;
}
```
