<?php
/**
 * This file is part of the {@link http://amsl.technology amsl} project.
 *
 * @author Norman Radtke
 * @copyright Copyright (c) 2015, {@link http://ub.uni-leipzig.de Leipzig University Library}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

/**
 * Create resource module for the linkandcreate extension
 *
 * @category OntoWiki
 * @package  OntoWiki_extensions_linkandcreate
 */
class CreateModule extends OntoWiki_Module
{
    /*
     * The module has two options:
     * 1. Enable the module according to the type of the selected resource or
     * 2. Enable the module without checking types (default)
     */

    private $_types = array();
    private $_hideProperties = array();
    private $_useWithoutTypeCheck = true;

    /**
     * Constructor
     */
    public function init()
    {
        $config = $this->_privateConfig;

        if (isset($config->useModuleWithoutTypeCheck)) {
            $this->_useWithoutTypeCheck = (boolean)$config->useModuleWithoutTypeCheck;
        }

        if ($this->_useWithoutTypeCheck === false  && isset($config->enableForTypes)) {
            $this->_types = $config->enableForTypes->toArray();
        }

        if (isset($config->hideProperties)) {
            $this->_hideProperties = $config->hideProperties->toArray();
        }

    }

    public function getTitle()
    {
        return $this->_owApp->translate->_('Link and Create a resource from here');
    }

    public function shouldShow()
    {
        // show only if type matches
        return $this->_checkClass();
    }

    public function getContents()
    {
        require_once('LinkandcreateController.php');
        $selectedResource = $this->_owApp->selectedResource;

        $data = array();
        $data['resourceUri'] = $selectedResource->getUri();

        $event = new Erfurt_Event('onResourceShowRanges');
        $event->resource = $selectedResource;
        $event->hideProperties = $this->_hideProperties;
        $event->trigger();

        $data['linkData'] = $event->data;

        require_once('LinkandcreateController.php');
        return $this->render('linkandcreate/linkandcreate', $data);
    }

    /*
     * checks the resource types agains the configured patterns
     */
    private function _checkClass()
    {
        if ($this->_useWithoutTypeCheck === true) {
            return true;
        }

        $resource = $this->_owApp->selectedResource;
        $rModel   = $resource->getMemoryModel();

        // search with each expression using the preg matchtype
        foreach ($this->_types as $type) {
            if (isset($type['classUri'])) {
                $classUri = $type['classUri'];
            } else {
                continue;
            }
            if (
                $rModel->hasSPvalue(
                    (string) $resource,
                    EF_RDF_TYPE,
                    $classUri
                )
            ) {
                return true;
            }
        }

        // type does not match to one of the expressions
        return false;
    }
}


