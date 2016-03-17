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

use Stylus\Exception as StylusException;

class Stylus {

    protected $read_dir;
    protected $write_dir;
    protected $import_dir;
    protected $file;
    protected $functions;
    protected $blocks;
    protected $vars;
    protected $input;

    /*
     * setReadDir - sets the directory to read from
     */
    public function setReadDir($dir) {
        if (is_dir($dir)) {
            $this->read_dir = $dir;
        } else {
            throw new StylusException($dir.' is not a directory.');
        }
    }

    /*
     * setWriteDir - sets the directory to write to
     */
    public function setWriteDir($dir) {
        if (is_dir($dir)) {
            $this->write_dir = $dir;
        } else {
            throw new StylusException($dir.' is not a directory.');
        }
    }

    /*
     * setImportDir - sets the directory to import from
     */
    public function setImportDir($dir) {
        if (is_dir($dir)) {
            $this->import_dir = $dir;
        } else {
            throw new StylusException($dir.' is not a directory.');
        }
    }

    /*
     * assign - assigns a variable to be used in the css
     */
    public function assign($name, $value) {
        $this->vars[$name] = $value;
    }

    /*
     * isIndented - sees if the line is indented
     */
    protected function isIndented($line) {
        return preg_match('~^\s~', $line);
    }

    /*
     * getIndent - returns the indent of the line
     */
    protected function getIndent($line) {
        if (preg_match('~^\s~', $line)) {
            return preg_replace('~^(\s+).*$~', '$1', $line);
        } else {
            return '';
        }
    }

    /*
     * isBlockDeclaration - sees if the line looks like a block declaration
     */
    protected function isBlockDeclaration($lines, $i, $indent = '') {
        $line = $lines[$i];

        return ((preg_match('~^[a-zA-Z0-9.#*][^(]+((?<=:not)|$)~', $line)) || (preg_match('~^'.$indent.'[a-zA-Z0-9.#*+&\[\]=\'">\~\^\$\-]+,?$~', $line)) || (preg_match('~^'.$indent.'[a-zA-Z0-9.#*+&\[\]=\'">\~\^\$\- ,]+,$~', $line)) || (preg_match('~^'.$indent.'&~', $line)) || (isset($lines[$i+1]) && $this->getIndent($lines[$i+1]) > $this->getIndent($line)) || (preg_match('~{~', $line)));
    }

    /*
     * isProperty - sees if the line looks like a property
     */
    protected function isProperty($line) {
        return preg_match('~\S[\s:]\S~', $line);
    }

    /*
     * isVariableDeclaration - sees if the line looks like a variable declaration
     */
    protected function isVariableDeclaration($lines, $i) {
        $line = $lines[$i];
        return (preg_match('~^[\$a-zA-Z0-9_-]+\s*=\s*\S~', $line) && isset($lines[$i+1]) && $this->getIndent($lines[$i+1]) === $this->getIndent($line));
    }

    /*
     * isFunctionDeclaration - sees if the line looks like a function declaration
     */
    protected function isFunctionDeclaration($line) {
        return preg_match('~^[\$a-zA-Z0-9_-]+\s*\(~', $line);
    }

    /*
     * isImport - sees if the line is importing a file
     */
    protected function isImport($line) {
        return preg_match('~^@import~', $line);
    }

    /*
     * insertVariables - inserts variables into the arguments or line if there are any
     */
    protected function insertVariables($args, $line = false) {
        if ($line) {
            preg_match('~^(\S+\s+)(.*)$~', $args, $matches);
            return $matches[1].$this->insertVariables($matches[2]);
        } else {
            if (preg_match('~[,\s]~', $args)) {
                preg_match_all('~(\$|\b)[\$a-zA-Z0-9_-]+(\$|\b)~', $args, $matches);

                foreach ($matches[0] as $arg) {
                    if (isset($this->vars[$arg])) {
                        $reg = preg_quote($arg);
                        $args = preg_replace('~((?<=^|[^\$a-zA-Z0-9_-])'.$reg.'(?=$|[^\$a-zA-Z0-9_-]))|(\{'.$reg.'\})~', $this->vars[$arg], $args);
                    }
                }
            } else if (isset($this->vars[$args])) {
                $args = $this->vars[$args];
            }

            return $args;
        }
    }

    /*
     * call - calls user defined function
     */
    protected function call($name, $arguments, $parent_args = null) {
        $function = $this->functions[$name];
        $output = '';
        foreach ($function['contents'] as $i => $line) {
            $line = $this->insertVariables($line, true);

            if (preg_match('~^([^:\s(]+):?\s*\(?\s*([^);]+)\)?;?\s*$~', $line, $matches)) {
                $prop = $matches[1];
                $args = $matches[2];

                if (isset($this->functions[$prop]) && $prop != $name) {
                    return $this->call($prop, $args, $arguments);
                }
            }

            if ($function['args']) {
                $user_args = preg_split('~,\s*~', $arguments);
                foreach ($user_args as $j => $args) {
                    $args = preg_replace('~^([\'"]?)([^\1]+)(\1)$~', '$2', $args);
                    $line = preg_replace('~(\b'.$function['args'][$j].'\b)|(\{'.$function['args'][$j].'\})~', $args, $line);
                }
            }

            $i && $output .= PHP_EOL."\t";

            if ($parent_args) {
                $output .= preg_replace('~^([^: ]+):? ([^;]+);?$~', '$1: $2;', preg_replace('~arguments~', $parent_args, $line));
            } else {
                $output .= preg_replace('~^([^: ]+):? ([^;]+);?$~', '$1: $2;', preg_replace('~arguments~', $arguments, $line));
            }
        }
        return $output;
    }

    /*
     * parseLine - parses line by calling function if it is or formatting it into CSS
     */
    protected function parseLine($line) {
        preg_match('~^\s*([^:\s\(]+)\s*:?\s*(.+);?\s*$~', $line, $matches);
        $name = $matches[1];
        $args = $matches[2];
        if (isset($this->functions[$name])) {
            $args = str_replace(array('(', ')'), '', $args);
            return $this->call($name, $args);
        } else {
            $args = $this->insertVariables($args);

            return $name.': '.$args.';';
        }
    }

    /*
     * addBlock - adds block of css code
     */
    protected function addBlock($lines, &$i, $indent = '', $parent_names = array()) {
        $position = count($this->blocks);
        $this->blocks[$position] = 'placeholder';
        $block = array('names'=>array(), 'contents'=>array());

        while (isset($lines[$i]) && $indent === $this->getIndent($lines[$i])) {
            $block['names'] = array_merge($block['names'], preg_split('~,\s?~', preg_replace('~\s*{\s*$~', '', trim($lines[$i])), null, PREG_SPLIT_NO_EMPTY));
            $i++;
        }

        if ($parent_names) {
            $names = array();

            foreach ($block['names'] as $block_name) {
                foreach ($parent_names as $parent_name) {
                    if (preg_match('~&~', $block_name)) {
                        $names[] = preg_replace('~&~', $parent_name, $block_name);
                    } else {
                        $names[] = $parent_name.' '.$block_name;
                    }
                }
            }

            $block['names'] = $names;
        }

        $indent = $this->getIndent($lines[$i]);

        while (isset($lines[$i]) && $this->getIndent($lines[$i]) === $indent) {
            $line = $lines[$i];
            if ($this->isBlockDeclaration($lines, $i, $indent)) {
                $this->addBlock($lines, $i, $indent, $block['names']);
            } else if ($this->isProperty($line)) {
                $block['contents'][] = $this->parseLine($line);
            } else {
                break;
            }

            $i++;
        }

        $i--;
        $this->blocks[$position] = $block;
    }

    /*
     * addFunction - adds user defined function
     */
    protected function addFunction($lines, &$i) {
        preg_match('~([^(]+)\(\s*([^)]*)\s*\)~', $lines[$i], $matches);
        $name = $matches[1];
        $function = array();
        $function['args'] = $matches[2]? preg_split('~,\s*~', $matches[2]): '';

        while (isset($lines[++$i]) && $this->isIndented($lines[$i])) {
            $function['contents'][] = trim($lines[$i]);
        }

        $i--;
        $this->functions[$name] = $function;
    }

    /*
     * addVariable - adds user defined variable
     */
    protected function addVariable($line) {
        preg_match('~^([\$a-zA-Z0-9_-]+)\s*=\s*([^;]+);?$~', $line, $matches);
        $name = $matches[1];
        $value = preg_replace('~(^[^=]+=\s*)|;~', '', $this->parseLine($line));
        $this->assign($name, $value);
    }

    /*
     * import - imports the specified file
     */
    protected function import(&$lines, &$i, $extension = '.styl') {
        $name = preg_replace('~@import\s*[\'"]([^\'"]+)[\'"].*$~', '$1', $lines[$i]);

        if (preg_match('~^(.+)(\..*)$~', $name, $matches)) {
            $name = $matches[1];
            $extension = $matches[2];
        }

        $dir = $this->import_dir ? $this->import_dir : $this->read_dir;
        $path = $dir . '/' . $name . $extension;
        $contents = file_get_contents($path);

        if ($contents === false) {
            StylusException::report('Could not read ' . $path);
        }

        if ($extension === '.styl') {
            unset($lines[$i]);
            $lines = array_merge(array_values(array_filter(preg_replace('~^\s*}\s*$~', '', preg_split('~\r\n|\n|\r~', $contents)), 'strlen')), $lines);
            $i--;
        } else {
            $this->file .= PHP_EOL . $contents . PHP_EOL;
        }

    }

    /*
     * convertBlocksToCSS - converts blocks of CSS to actual CSS
     */
    protected function convertBlocksToCSS() {
        foreach ($this->blocks as $block) {
            if (! isset($block['contents']) || ! $block['contents']) {
                continue;
            }

            foreach ($block['names'] as $i => $name) {
                $i && $this->file .= ', ';
                $this->file .= $name;
            }

            $this->file .= ' {'.PHP_EOL;

            foreach ($block['contents'] as $i => $content) {
                $i && $this->file .= PHP_EOL;
                $this->file .= "\t".$content;
            }

            $this->file .= PHP_EOL.'}'.PHP_EOL;
        }
    }

    protected function readInput() {

        switch ($this->input->type) {
            case "file":
                $path = $this->read_dir . '/' . $this->input->value;
                $contents = file_get_contents($path);
                if ($contents === false) { StylusException::report('Could not read from ' . $this->input->value); }
                return $contents;
            case "string":
                return $this->input->value;
        }

        return "";
    }

    protected function renderInput() {

        $input = $this->readInput();
        $lines = array_values(array_filter(preg_replace('~^\s*}\s*$~', '', preg_split('~\r\n|\n|\r~', $input)), 'strlen'));

        for ($i=0; $i<count($lines); $i++) {
            $line = $lines[$i];

            if ($this->isFunctionDeclaration($line)) {
                $this->addFunction($lines, $i);
            } else if ($this->isVariableDeclaration($lines, $i)) {
                $this->addVariable($line);
            } else if ($this->isBlockDeclaration($lines, $i)) {
                $this->addBlock($lines, $i);
            } else if ($this->isImport($line)) {
                $this->import($lines, $i);
            }
        }

        $this->convertBlocksToCSS();

        $output = ($this->file ? $this->file : '');

        $this->functions = array();
        $this->blocks = array();
        $this->vars = array();
        $this->file = '';

        return $output;
    }

    /*
     * fromFile - use specific stylus file as input
     */
    public function fromFile($file) {

        if (!$this->read_dir) {
            StylusException::report('No read directory specified');
        }

        $this->input = (object) array(
            "type" => "file",
            "value" => $file
        );

        return $this;
    }

    /*
     * fromString - use specific stylus string as input
     */
    public function fromString($string) {

        $this->input = (object) array(
            "type" => "string",
            "value" => $string
        );

        return $this;
    }

    /*
     * toFile - parses input and writes it as css
     */
    public function toFile($file = null, $overwrite = false) {

        if (!$this->write_dir) {
            StylusException::report('No write directory specified');
        }

        if ($file === null) {
            if ($this->input->type === "file") {
                $file = preg_replace('~\.styl$~', '.css', $this->input->value);
            } else {
                StylusException::report('No filename specified');
            }
        }

        $outpath = $this->write_dir . '/' . $file;

        if (file_exists($outpath) && !$overwrite) {
            return ;
        }

        $output = $this->renderInput();

        file_put_contents($outpath, $output) or StylusException::report('Could not write to ' . $outpath);

    }

    /*
     * toString - parses input and returns it as css
     */
    public function toString() {
        return $this->renderInput();
    }

    /*
     * parseFiles - reads .styl files, parses them, writes .css files
     */
    public function parseFiles($overwrite = false) {
        if (!$this->read_dir) {
            StylusException::report('No read directory specified');
        }

        if (!$this->write_dir) {
            StylusException::report('No write directory specified');
        }

        $dir_handle = opendir($this->read_dir) or StylusException::report('Could not open directory ' . $this->read_dir);

        while (($file = readdir($dir_handle)) !== false) {
            if (is_file($this->read_dir . '/' . $file)) {
                $this->fromFile($file)->toFile(null, $overwrite);
            }
        }

        closedir($dir_handle);
    }
}
