<?php
/**
 * helpers.class.php file for MyComponent Extra
 *
 * @author Bob Ray
 * Copyright 2012 by Bob Ray <http://bobsguides.com>
 *
 * MyComponent is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * MyComponent is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * MyComponent; if not, write to the Free Software Foundation, Inc., 59 Temple
 * Place, Suite 330, Boston, MA 02111-1307 USA
 *
 * @package MyComponent
 */

/** Description:
 * -------------
 * Methods used by helpers class in MyComponent Extra
 */

class Helpers

{
    /* @var $modx modX - $modx object */
    public $modx;
    /* @var $props array - $scriptProperties array */
    public $props;
    /* @var $replaceFields array */
    protected $replaceFields;
    /* @var $tplPath string - path to MyComponent tpl directory */
    protected $tplPath;
    /* @var $source string - path to root of MyComponent */
    protected $source;
    /* @var $dirPermission - permission for new directories (from config file) */
    protected $dirPermission;
    /* @var $filePermission - permission for new files (from config file) */
    protected $filePermission;
    /* @var $files array - files collected by dirWalk() */
    protected $files = array();
    

    function  __construct(&$modx, &$props = array()) {
        $this->modx =& $modx;
        $this->props =& $props;
    }
    public function init() {
        $this->tplPath = $this->props['mycomponentCore'] . 'elements/chunks/';
        if (substr($this->tplPath, -1) != "/") {
            $this->tplPath .= "/";
        }
        $this->dirPermission = $this->props['dirPermission'];
        $this->filePermission = $this->props['filePermission'];

        $this->replaceFields = array(
            '[[+packageName]]' => $this->props['packageName'],
            '[[+packageNameLower]]' => $this->props['packageNameLower'],
            '[[+packageDescription]]' => $this->props['packageDescription'],
            '[[+author]]' => $this->props['author'],
            '[[+email]]' => $this->props['email'],
            '[[+copyright]]' => $this->props['copyright'],
            '[[+createdon]]' => $this->props['createdon'],
            '[[+authorSiteName]]' => $this->props['authorSiteName'],
            '[[+authorUrl]]' => $this->props['authorUrl'],
            '[[+packageUrl]]' => $this->props['packageDocumentationUrl'],
            '[[+gitHubUsername]]' => $this->props['gitHubUsername'],
            '[[+gitHubRepository]]' => $this->props['gitHubRepository'],

        );
        $license = $this->getTpl('license');
        if (!empty($license)) {
            $license = $this->strReplaceAssoc($this->replaceFields, $license);
            $this->replaceFields['[[+license]]'] = $license;
        }
        unset($license);
    }
    public function getReplaceFields() {
        return $this->replaceFields;
    }
    public function replaceTags($text, $replaceFields = array()) {
        $replaceFields = empty ($replaceFields)? $this->replaceFields : $replaceFields;
        return $this->strReplaceAssoc($replaceFields, $text);
    }

    /**
     * Get tpl file contents from MyComponent build tpl directory
     *
     * @param $name string  - Name of tpl file
     * @return string - Content of tpl file or '' if it doesn't exist
     */
    public function getTpl($name)
    {
        $name = strtolower($name);
        /* add .tpl if there's no .php */
        $name = strstr($name, '.php')? $name : $name . '.tpl';
        /* search for tpl in this order:
            my . name chunk
            my . name file
            name chunk
            name file
        */
        $text = $this->modx->getChunk('my' . $name);
        if (empty($text)) {
            if (file_exists($this->tplPath . 'my' . $name)) {
                $text = file_get_contents($this->tplPath . 'my' . $name);
            }
        }
        if (empty($text)) {
            $text = $this->modx->getChunk($name);
        }
        if (empty($text)) {
            if (file_exists($this->tplPath . $name)) {
                $text = file_get_contents($this->tplPath . $name);
            }
        }

        if (strstr($name, '.php') && !empty($text)) {
            /* make sure the header made it and do alerts if not */
            if (empty($text)) {
                $this->sendLog(MODX_LOG_LEVEL_ERROR, '    Problem loading Tpl file (text is empty) ' . $name  );
                $text = "<?php\n/* empty header */\n\n";
            } elseif (strpos($text, '<' . '?' . 'php') === false) {
                $this->sendLog(MODX_LOG_LEVEL_ERROR, '    Problem loading Tpl file (text has no PHP tag) ' . $name);
                $text = "<?php\n /* inserted PHP tag */\n\n" . $text;
            }
        }
        return empty($text) ? '' : $text;
    }

    /**
     * Returns the correct filename for a given file
     *
     * @param $name - string name of object (mixed case OK)
     * @param $elementType string - 'modChunk', 'modResource', etc.
     * @param string $fileType string - Type of file to be created (code, properties, transport)
     * @return string
     *
     * Example returns for MyObject plugin-type object
     *    code:  myobject.plugin.php
     *    transport: transport.plugins.php
     *    properties: properties.myobject.plugin.php
     */
    public function getFileName($name, $elementType, $fileType = 'code') {
        /* $elementType is in the form 'modSnippet', 'modChunk', etc.
         * set default suffix to 'chunk', 'snippet', etc. */
        /* ToDo: Get suffix from config file */
        /* @var $elementObj modElement */
        $name = strtolower($name);
        $extension = 'php'; /* default extension */
        $suffix = substr(strtolower($elementType), 3); /* modPlugin -> plugin, etc.; default suffix */
        if ($suffix == 'templatevar') {
            $suffix = 'tv';
        }
        $output = '';

        if ($fileType == 'code') {
            switch ($elementType) {
                case 'modResource':
                    $suffix = 'content';
                case 'modTemplate':
                case 'modChunk':
                    $extension = 'html';
                case 'modSnippet':
                case 'modPlugin':
                    $output = $name .'.'. $suffix . '.' . $extension;
                    break;
                case 'modClass':
                    $output = $name . '.' . 'class.' . $extension;
                    break;
                default:  /* all other elements get no code file */
                    $output = '';
                    break;

            }

        } elseif ($fileType == 'transport') {
            $output = 'transport.' . $suffix . 's.php';
        } elseif ($fileType = 'properties') {
            $output = 'properties.' . $name . '.' . $suffix . '.php';
        }
        /* replace any spaces with underscore */
        $output = str_replace(' ', '_', $output);
        return $output;
    }

    /**
     * @param $targetCore string - path to core directory in build
     * @param $type string - modSnippet, modChunk, etc.
     * @return string - full path for element code file (without filename or trailing slash)
     */
    public function getCodeDir ($targetCore, $type) {
        $dir = $targetCore . 'elements/';
        $type = $type == 'modTemplateVar' ? 'modTv' : $type;
        return $dir . strtolower(substr($type, 3)) . 's';
    }
    /**
     * @param $elementType string - 'modChunk', 'modSnippet', etc.
     * @return string - The name of the 'name' field for the object (name, pagetitle, etc.)
     */
    public function getNameAlias($elementType) {
        switch ($elementType) {
            case 'modTemplate':
                $nameAlias = 'templatename';
                break;
            case 'modCategory':
                $nameAlias = 'category';
                break;
            case 'modResource':
                $nameAlias = 'pagetitle';
                break;
            default:
                $nameAlias = 'name';
                break;
        }
        return $nameAlias;

    }


    /**
     * Write a file to disk - non-destructive -- will not overwrite existing files
     * Creates dir if necessary
     *
     * @param $dir string - directory for file (should not have trailing slash!)
     * @param $fileName string - file name
     * @param $content - file content
     * @param $dryRun boolean - if true, writes to stdout instead of file.
     */
    public function writeFile ($dir, $fileName, $content, $dryRun = false) {

        if (!is_dir($dir)) {
            mkdir($dir, $this->dirPermission, true);
        }
        /* add trailing slash if not there */
        if (substr($dir, -1) != "/") {
            $dir .= "/";
        }
        /* write to stdout if dryRun is true */

        $file = $dryRun? 'php://output' : $dir . $fileName;

        $fp = fopen($file, 'w');
        if ($fp) {
            if ( ! $dryRun) {
                $this->sendLog(MODX::LOG_LEVEL_INFO, '    Creating ' . $file);
                if (empty($content)) {
                    $this->sendLog(MODX::LOG_LEVEL_INFO, ' (empty)', true);
                }

            }
            fwrite($fp, $content);
            fclose($fp);
            if (! $dryRun) {
                chmod($file, $this->filePermission);
            }
        } else {
            $this->sendLog(MODX::LOG_LEVEL_INFO, '    Could not write file ' . $file);
        }


    }

    /**
     * Replaces all strings in $subject based on $replace associative array
     *
     * @param $replace array - associative array of key => value pairs
     * @param $subject string - text to do replacement in
     * @return string - altered text
     */
    public function strReplaceAssoc(array $replace, $subject)
    {
        return str_replace(array_keys($replace), array_values($replace), $subject);
    }

    /**
     * Recursive function copies an entire directory and its all descendants
     *
     * @param $source string - source directory
     * @param $destination string - target directory
     * @return bool - used only to control recursion
     */

    public function copyDir($source, $destination)
    {
        //echo "SOURCE: " . $source . "\nDESTINATION: " . $destination . "\n";
        if (is_dir($source)) {
            if (!is_dir($destination)) {
                mkdir($destination, $this->dirPermission, true);
            }
            $objects = scandir($source);
            if (sizeof($objects) > 0) {
                foreach ($objects as $file) {
                    if ($file == "." || $file == ".." || $file == '.git' || $file == '.svn') {
                        continue;
                    }

                    if (is_dir($source . '/' . $file)) {
                        $this->copyDir($source . '/' . $file, $destination . '/' . $file);
                    } else {
                        if ($file == 'build.config.php') continue;
                        if (strstr($file, 'config.php') && $file != $this->props['packageNameLower'] . '.config.php') continue ;
                        copy($source . '/' . $file, $destination . '/' . $file);
                    }
                }
            }
            return true;
        } elseif (is_file($source)) {
            return copy($source, $destination);
        } else {
            return false;
        }
    }

    /**
     * @param $intersectType string (modTemplateVarTemplate, modPluginEvent, etc.)
     * @param $intersects array  array of intersect objects */

    public function createIntersects($intersectType, $intersects) {
        $mainObjectType = 'missing';
        $mainObjectName = 'missing';
        $subsidiaryObjectType = 'missing';
        $subsidiaryObjectName = 'missing';
        $this->sendLog(MODX::LOG_LEVEL_INFO, 'Creating ' . $intersectType . ' objects');
        foreach ($intersects as $values) {

            $mainIdField = 'id';
            $subIdField = 'id';
            switch($intersectType) {
                case 'modTemplateVarTemplate':
                    $mainObjectType = 'modTemplate';
                    $subsidiaryObjectType = 'modTemplateVar';
                    $mainObjectName = $values['templateid'];
                    $subsidiaryObjectName = $values['tmplvarid'];
                    break;
                case 'modPluginEvent':
                    $subIdField = 'name';
                    $mainObjectType = 'modPlugin';
                    $subsidiaryObjectType = 'modEvent';
                    $mainObjectName = $values['plugin'];
                    $subsidiaryObjectName = $values['event'];
                    if (isset($values['propertyset'])) {
                        $ps = $this->modx->getObject('modPropertySet', array('name' => $values['propertyset']));
                        if ($ps) {
                            $values['propertyset'] = $ps->get('id');
                        } else {
                            $this->sendLog(MODX_LOG_LEVEL_ERROR, 'Could not find Property Set: ' .
                                $values['propertyset']);
                        }
                    }
                    break;
                case 'modElementPropertySet':
                    $subIdField = 'id';
                    $mainObjectType = $values['element_class'];
                    $mainObjectName = $values['element'];
                    $subsidiaryObjectType = 'modPropertySet';
                    $subsidiaryObjectName = $values['property_set'];

                    break;
                default:
                    $this->sendLog(MODX_LOG_LEVEL_ERROR, 'Asked for unknown intersect type');
                    break;


            }
            $alias = $this->getNameAlias($mainObjectType);
            $searchFields = array($alias => $mainObjectName);
            $mainObject = $this->modx->getObject($mainObjectType, $searchFields);

            if (!$mainObject) {
                $this->sendLog(MODX::LOG_LEVEL_ERROR, '    Error creating intersect ' .
                        $intersectType . ': Could not get main object ' . $mainObjectName .
                        "\n    " . implode(',', $searchFields));
                return false;
            }

            $alias = $this->getNameAlias($subsidiaryObjectType);
            $searchFields = array($alias => $subsidiaryObjectName);
            $subsidiaryObject = $this->modx->getObject($subsidiaryObjectType, $searchFields);
            if (! $subsidiaryObject) {
                $this->sendLog(MODX::LOG_LEVEL_ERROR, '    Error creating intersect ' .
                        $intersectType . ': could not get subsidiary object ' .
                        $subsidiaryObjectName ."\n    " . implode(', ', $searchFields));
                return false;
            }


            switch($intersectType) {
                case 'modTemplateVarTemplate':
                    $searchFields = array(
                        'templateid' => $mainObject->get($mainIdField),
                        'tmplvarid' => $subsidiaryObject->get($subIdField),
                    );
                    break;
                case 'modPluginEvent':
                    $searchFields = array(
                        'pluginid' => $mainObject->get($mainIdField),
                        'event' => $subsidiaryObject->get($subIdField),
                    );
                    break;
                case 'modElementPropertySet':
                    $searchFields = array(
                        'element' => $mainObject->get($mainIdField),
                        'element_class' => $mainObjectType,
                        'property_set' => $subsidiaryObject->get($subIdField),
                    );

                    break;
                default:
                    break;
            }

            $intersectObj = $this->modx->getObject($intersectType, $searchFields);

            if ($intersectObj) {
                $this->sendLog(MODX_LOG_LEVEL_INFO, '    Intersect already exists for ' . $mainObjectName . ' => ' . $subsidiaryObjectName);

            } else {
                $intersectObj = $this->modx->newObject($intersectType);
                if ($intersectObj) {
                    /* add any extra fields */
                    if ($intersectType != 'modElementPropertySet') {
                        $extraValues = array_slice($values, 2);
                        $values = array_merge($searchFields, $extraValues);
                    } else {
                        $values = $searchFields;
                    }

                    foreach ($values as $k => $v) {
                        /* make sure no fields are null */
                        if (empty($v)) {
                            $v = '';
                        }
                        /* set the values */
                        $intersectObj->set($k, $v);
                    }
                    if ($intersectObj->save()) {
                        $this->sendLog(MODX_LOG_LEVEL_INFO, '    Created Intersect ' . $mainObjectName . ' => ' . $subsidiaryObjectName);

                    }
                } else {
                    $this->sendLog(MODX_LOG_LEVEL_ERROR, '    Could not create intersect for ' . $mainObjectName . ' => ' . $subsidiaryObjectName);
                }
            }

        }

    return true;
    }

    /**
     * Recursively search directories for certain file types
     * Adapted from boen dot robot at gmail dot com's code on the scandir man page
     * @param $dir - dir to search (no trailing slash)
     * @param mixed $types - null for all files, or a comma-separated list of strings
     *                       the filename must include (e.g., '.php,.class')
     * @param bool $recursive - if false, only searches $dir, not it's descendants
     * @param string $baseDir - used internally -- do not send
     */
    public function dirWalk($dir, $types = null, $recursive = false, $baseDir = '') {

        if ($dh = opendir($dir)) {
            while (($file = readdir($dh)) !== false) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                // $this->output .= "\n" , $dir;
                //$this->output .= "\n", $file;
                if (is_file($dir . '/' . $file)) {
                    if ($types !== null) {
                        $found = false;
                        $typeArray = explode(',', $types);
                        foreach($typeArray as $type) {
                            if (strstr($file, $type)) {
                                $found = true;
                            }
                        }
                        if (! $found) continue;
                    }
                    // $this->{$callback}($dir, $file);
                    $this->addFile($dir, $file);
                } elseif ($recursive && is_dir($dir . '/' . $file)) {
                    $this->dirWalk($dir . '/' . $file, $types, $recursive, $baseDir . '/' . $file);
                }
            }
            closedir($dh);
        }
    }
    public function addFile($dir, $file) {
        $this->files[$file] = $dir;
    }
    public function resetFiles() {
        $this->files = array();
    }
    public function getFiles() {
        return $this->files;
    }

    public function strip_comments($source) {
        $tokens = token_get_all($source);
        $ret = "";
        foreach ($tokens as $token) {
            if (is_string($token)) {
                $ret .= $token;
            }
            else {
                list($id, $text) = $token;

                switch ($id) {
                    case T_COMMENT:
                    case T_ML_COMMENT: // we've defined this
                    case T_DOC_COMMENT: // and this
                        break;

                    default:
                        $ret .= $text;
                        break;
                }
            }
        }
        return trim(str_replace(array(
                 '<?',
                 '?>'
            ), array(
                    '',
                    ''
               ), $ret));
    }

    public function sendLog($level, $message, $suppressReturn = false) {
        $msg = '';
        if (!$suppressReturn) {
            $msg .= "\n";
        }
        if ($level == MODX::LOG_LEVEL_ERROR) {
            $msg .= 'ERROR -- ';
        }
        $msg .= $message;
        echo $msg;
    }

   /* public function serialize_array(&$array, $root = '$root', $depth = 0) {
        $items = array();

        foreach ($array as $key => &$value) {
            if (is_array($value)) {
                serialize_array($value, $root . '[\'' . $key . '\']', $depth + 1);
            }
            else {
                $items[$key] = $value;
            }
        }

        if (count($items) > 0) {
            echo $root . ' = array(';

            $prefix = '';
            foreach ($items as $key => &$value) {
                echo $prefix . '\'' . $key . '\' => \'' . addslashes($value) . '\'';
                $prefix = ', ';
            }

            echo ');' . "\n";
        }
    }*/
}
