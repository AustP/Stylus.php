Stylus.php
==========

A Stylus parser for PHP
<hr>
###CSS needs a hero... again

When I first saw <a href="http://learnboost.github.com/stylus/">Stylus</a> I thought it was amazing and I implemented it
into my nodejs application. When I started my next project, which was a PHP project, I liked Stylus so much
that I wanted to implement it into my PHP project as well. Surprisingly, I couldn't find any Stylus parser for PHP.
So I did as any developer would do and created my own. And I want to share it.

##Current Features

+ Omit braces
+ Omit colons
+ Omit semi-colons
+ Custom functions
+ Importing other files
+ '&' parent reference
+ Mixins
+ Interpolation
+ Variables

##Using Stylus.php
Using Stylus.php is really easy! Just include the following code:

    require('Stylus.php');
  
    $stylus = new Stylus();
    $stylus->setReadDir('read');
    $stylus->setWriteDir('write');
    $stylus->setImportDir('import'); //if you import a file without setting this, it will import from the read directory
    $stylus->parseFiles();
  
And that's all there is to it! Now a quick note about the `parseFiles()` function. It has one parameter called
`overwite` which defaults to `false`. It is a flag indicating whether or not you want to overwrite your
already parsed Stylus files.

This means that you could include this code on every page and you won't be parsing your Stylus files every time.
But make sure that you set `overwrite` to `true` when you are developing or updating your Stylus files so the
changes will be reflected in your site.

###Parse a Single File
It is possible to only parse one file. Instead of calling `parseFiles()` you simply just call `parseFile('my_styl')`.
`parseFile()`'s second parameter is the `overwrite` flag. If you wanted to parse a file on every page load but didn't
want to parse every file you could use this to do so.

    $stylus->parseFile('my_file', true);
    $stylus->parseFiles();
 
##Assigning Variables
Assigning variables is done the same way as in regular Stylus. But you now have the option of adding variables
from PHP before parsing the stylus files by calling the `assign` function. Here is an example:

**PHP**

    $stylus->assign('px_size', '30px');
    $stylus->parseFiles();
    
**Stylus**

    div
     font-size px_size

**Yields**

    div {
        font-size: 30px;
    }
