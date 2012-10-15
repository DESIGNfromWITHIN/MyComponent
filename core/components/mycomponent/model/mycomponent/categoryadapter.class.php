<?php


class CategoryAdapter extends ObjectAdapter {
    /* These will never change. */
    protected $dbClass = 'modCategory';
    protected $dbClassIDKey = 'id';
    protected $dbClassNameKey = 'category';
    protected $dbClassParentKey = 'parent';
    /* ************** */

    protected $myFields;
    protected $name;
    protected $createProcessor = 'element/category/create';
    protected $updateProcessor = 'element/category/update';
    protected $myId;
    protected $objectArray = array();
    protected $targetBase;
    /* @var $modx modX */
    public $modx;
    /* @var $helpers Helpers */
    public $helpers;


  
    final public function __construct(&$modx, &$helpers, $fields) {
        /* @var $modx modX */
        $this->modx =& $modx;
        $this->helpers =& $helpers;
        $this->objectArray =& $objectArray;
        $this->targetRoot = $this->helpers->props['targetRoot'];
        $fields = is_array($fields) ? $fields : array();

        $this->name = $fields['category'];

        if (isset($fields['parent'])) {
            $pn = $fields['parent'];
            if (!is_numeric($fields['parent'])) {
                $p = $modx->getObject('modCategory', array('category' => $pn));
                if ($p) {
                    $fields['parent'] = $p->get('id');
                }
            }
        }
        if (is_array($fields)) {
            $this->myFields = $fields;
        }
        parent::__construct($modx, $helpers);

    }


/* *****************************************************************************
   Bootstrap and Support Functions (in MODxObjectAdapter)
***************************************************************************** */

    public function addToMODx($overwrite = false) {
        /* create category if necessary */
        parent::addToModx($overwrite);
    }

    public function addChildren($fields, $createCodeFiles = false) {
        if (is_array($fields) && !empty($fields)) {
            $elements = $fields;
            foreach ($elements as $element => $objects) {
                if ($element == 'category') {
                    /* don't recreate myself */
                    continue;
                }
                $this->helpers->sendLog(MODX_LOG_LEVEL_INFO, 'Creating ' . $element);
                foreach ($objects as $object => $fields) {

                    $fields['category'] = $this->myId;

                    /* @var $o ObjectAdapter */
                    $adapter = substr(ucfirst($element), 0, -1) . 'Adapter';
                    $o = new $adapter($this->modx, $this->helpers, $fields);
                    $o->addToModx();
                    if ($createCodeFiles) {
                        $o->createCodeFile();
                    }
                }

            }
        }
    }


    public function createTemplateVarTemplateResolver() {
        $templateVarTemplates = $this->modx->getOption('templateVarTemplates', $this->objectArray, array());

        /* Create Resolver */
        if (!empty($templateVarTemplates)) {
            $this->helpers->sendLog(MODX::LOG_LEVEL_INFO, 'Creating tv resolver');
            $tpl = $this->helpers->getTpl('tvresolver.php');
            $tpl = $this->helpers->replaceTags($tpl);
            if (empty($tpl)) {
                $this->helpers->sendLog(MODX::LOG_LEVEL_ERROR, 'tvresolver tpl is empty');
            }
            $dir = $this->targetBase . '_build/resolvers';
            $fileName = 'tv.resolver.php';

            if (!file_exists($dir . '/' . $fileName)) {
                $code = '';
                $codeTpl = $this->helpers->getTpl('tvresolvercode.php');
                $codeTpl = str_replace('<?php', '', $codeTpl);

                foreach ($templateVarTemplates as $template => $tvs) {
                    $tempCodeTpl = str_replace('[[+template]]', $template, $codeTpl);
                    $tempCodeTpl = str_replace('[[+tvs]]', $tvs, $tempCodeTpl);
                    $code .= "\n" . $tempCodeTpl;
                }

                $tpl = str_replace('/* [[+code]] */', $code, $tpl);

                $this->helpers->writeFile($dir, $fileName, $tpl);
            }
            else {
                $this->helpers->sendLog(MODX::LOG_LEVEL_INFO, '    ' . $fileName . ' already exists');
            }
        }
    }

    public static function createResolver($dir, $intersects, $helpers) {

        /* Create resource.resolver.php resolver */
        /* @var $helpers Helpers */
        if (!empty($dir) && !empty($intersects)) {
            $helpers->sendLog(MODX::LOG_LEVEL_INFO, 'Creating Category resolver');
            $tpl = $helpers->getTpl('categoryresolver.php');
            $tpl = $helpers->replaceTags($tpl);
            if (empty($tpl)) {
                $helpers->sendLog(MODX::LOG_LEVEL_ERROR, 'categoryresolver tpl is empty');
                return false;
            }

            $fileName = 'category.resolver.php';

            if (!file_exists($dir . '/' . $fileName)) {
                $intersectArray = $helpers->beautify($intersects);
                $tpl = str_replace("'[[+intersects]]'", $intersectArray, $tpl);

                $helpers->writeFile($dir, $fileName, $tpl);
            }
            else {
                $helpers->sendLog(MODX::LOG_LEVEL_INFO, '    ' . $fileName . ' already exists');
            }
        }
        return true;
    }
/* *****************************************************************************
   Export Objects and Support Functions (in MODxObjectAdapter)
***************************************************************************** */


/* *****************************************************************************
   Build Vehicle and Support Functions 
***************************************************************************** */

    final public function buildVehicle()
    {//For Quick Access
        $mc = $this->myComponent;
        $attr = static::dbTransportAttributes;
        $title = $this->properties['pagetitle'];
        
    // Localize builder
        $builder = $mc->builder;
        if (!empty($this->properties)
        ||  is_array($this->properties))
        {   $mc->sendLog(modX::LOG_LEVEL_ERROR, 'Resource has no Properties');
            return false;
        }
    // We must have Attributes in order to Package
        if (empty($attr) ||  is_array($attr)) {
            $mc->sendLog(modX::LOG_LEVEL_ERROR, 'Could not package Category: '.$title);
            return false;
        }
    // Add to the Transport Package
        if (parent::buildVehicle()) {//Return Success
            $mc->sendLog(modX::LOG_LEVEL_INFO, 'Packaged Category: '.$title);
            return true;
        } else {
            return false;
        }
        
        /* create category  The category is required and will automatically
         * have the name of your package
         */
        /* @var $category modCategory */
        $category= $modx->newObject('modCategory');
        $category->set('id',1);
        $category->set('category',PKG_CATEGORY);
        
        /* add snippets */
        if ($hasSnippets) {
            $mc->sendLog(modX::LOG_LEVEL_INFO,'Adding in Snippets.');
            $snippets = include $sources['data'].'transport.snippets.php';
            /* note: Snippets' default properties are set in transport.snippets.php */
            if (is_array($snippets)) {
                $category->addMany($snippets, 'Snippets');
            } 
            else { $mc->sendLog(modX::LOG_LEVEL_FATAL,'Adding Snippets failed.'); }
        }
        /* ToDo: Implement Property Sets */
        if ($hasPropertySets) {
            $mc->sendLog(modX::LOG_LEVEL_INFO,'Adding in Property Sets.');
            $propertySets = include $sources['data'].'transport.propertysets.php';
            //  note: property set' properties are set in transport.propertysets.php
            if (is_array($propertySets)) {
                $category->addMany($propertySets, 'PropertySets');
            } 
            else { $mc->sendLog(modX::LOG_LEVEL_FATAL,'Adding Property Sets failed.'); }
        }
        if ($hasChunks) { /* add chunks  */
            $mc->sendLog(modX::LOG_LEVEL_INFO,'Adding in Chunks.');
            /* note: Chunks' default properties are set in transport.chunks.php */    
            $chunks = include $sources['data'].'transport.chunks.php';
            if (is_array($chunks)) {
                $category->addMany($chunks, 'Chunks');
            } else { $mc->sendLog(modX::LOG_LEVEL_FATAL,'Adding Chunks failed.'); }
        }
        
        
        if ($hasTemplates) { /* add templates  */
            $modx->log(modX::LOG_LEVEL_INFO,'Adding in Templates.');
            /* note: Templates' default properties are set in transport.templates.php */
            $templates = include $sources['data'].'transport.templates.php';
            if (is_array($templates)) {
                if (! $category->addMany($templates,'Templates')) {
                    $mc->sendLog(modX::LOG_LEVEL_INFO,'addMany failed with templates.');
                };
            } else { $mc->sendLog(modX::LOG_LEVEL_FATAL,'Adding Templates failed.'); }
        }
        
        if ($hasTemplateVariables) { /* add template variables  */
            $modx->log(modX::LOG_LEVEL_INFO,'Adding in Template Variables.');
            /* note: Template Variables' default properties are set in transport.tvs.php */
            $tvs = include $sources['data'].'transport.tvs.php';
            if (is_array($tvs)) {
                $category->addMany($tvs, 'TemplateVars');
            } else { $mc->sendLog(modX::LOG_LEVEL_FATAL,'Adding Template Variables failed.'); }
        }
        
        
        if ($hasPlugins) {
            $mc->sendLog(modX::LOG_LEVEL_INFO,'Adding in Plugins.');
            $plugins = include $sources['data'] . 'transport.plugins.php';
             if (is_array($plugins)) {
                $category->addMany($plugins);
             } else {
                 $mc->sendLog(modX::LOG_LEVEL_FATAL, 'Adding Plugins failed.');
             }
        }
        
        /* Create Category attributes array dynamically
         * based on which elements are present
         */
        
        if ($hasValidator) {
              $attr[xPDOTransport::ABORT_INSTALL_ON_VEHICLE_FAIL] = true;
        }
        
        if ($hasSnippets) {
            $attr[xPDOTransport::RELATED_OBJECT_ATTRIBUTES]['Snippets'] = array(
                    xPDOTransport::PRESERVE_KEYS => false,
                    xPDOTransport::UPDATE_OBJECT => true,
                    xPDOTransport::UNIQUE_KEY => 'name',
                );
        }
        if ($validator == 'default') {
        }

        if ($hasPropertySets) {
            $attr[xPDOTransport::RELATED_OBJECT_ATTRIBUTES]['PropertySets'] = array(
                    xPDOTransport::PRESERVE_KEYS => false,
                    xPDOTransport::UPDATE_OBJECT => true,
                    xPDOTransport::UNIQUE_KEY => 'name',
                );
        }
        
        if ($hasChunks) {
            $attr[xPDOTransport::RELATED_OBJECT_ATTRIBUTES]['Chunks'] = array(
                    xPDOTransport::PRESERVE_KEYS => false,
                    xPDOTransport::UPDATE_OBJECT => true,
                    xPDOTransport::UNIQUE_KEY => 'name',
                );
        }
        
        if ($hasPlugins) {
            $attr[xPDOTransport::RELATED_OBJECT_ATTRIBUTES]['Plugins'] = array(
                xPDOTransport::PRESERVE_KEYS => false,
                xPDOTransport::UPDATE_OBJECT => true,
                xPDOTransport::UNIQUE_KEY => 'name',
            );
        }
        
        if ($hasTemplates) {
            $attr[xPDOTransport::RELATED_OBJECT_ATTRIBUTES]['Templates'] = array(
                xPDOTransport::PRESERVE_KEYS => false,
                xPDOTransport::UPDATE_OBJECT => true,
                xPDOTransport::UNIQUE_KEY => 'templatename',
            );
        }
        
        if ($hasTemplateVariables) {
            $attr[xPDOTransport::RELATED_OBJECT_ATTRIBUTES]['TemplateVars'] = array(
                xPDOTransport::PRESERVE_KEYS => false,
                xPDOTransport::UPDATE_OBJECT => true,
                xPDOTransport::UNIQUE_KEY => 'name',
            );
        }
        
        parent::buildVehicle();
        
       
        /* Package in script resolvers, if any */
        
        $resolvers = empty($props['resolvers'])? array() : explode(',', $props['resolvers']);
        $resolvers = array_merge($resolvers, array('plugin','tv','resource','propertyset'));
        
        /* This section transfers every file in the local
         mycomponents/mycomponent/assets directory to the
         target site's assets/mycomponent directory on install.
         If the assets dir. has been renamed or moved, they will still
         go to the right place.
         */
        
        if ($hasCore) {
            $vehicle->resolve('file', array(
                    'source' => $sources['source_core'],
                    'target' => "return MODX_CORE_PATH . 'components/';",
                ));
        }
        
        /* This section transfers every file in the local 
         mycomponents/mycomponent/core directory to the
         target site's core/mycomponent directory on install.
         If the core has been renamed or moved, they will still
         go to the right place.
         */
        
            if ($hasAssets) {
                $vehicle->resolve('file',array(
                    'source' => $sources['source_assets'],
                    'target' => "return MODX_ASSETS_PATH . 'components/';",
                ));
            }
        
        /* Add subpackages */
        /* The transport.zip files will be copied to core/packages
         * but will have to be installed manually with "Add New Package and
         *  "Search Locally for Packages" in Package Manager
         */
        
        if ($hasSubPackages) {
            $mc->sendLog(modX::LOG_LEVEL_INFO, 'Adding in subpackages.');
             $vehicle->resolve('file',array(
                'source' => $sources['packages'],
                'target' => "return MODX_CORE_PATH;",
                ));
        }
        return '';
 }
}