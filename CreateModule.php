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

        $data['linkData'] = $this->showRanges($event);

        require_once('LinkandcreateController.php');
        return $this->render('linkandcreate/linkandcreate', $data);
    }

    public function showRanges($event)
    {
        $owApp = OntoWiki::getInstance();
        $this->_model = $owApp->selectedModel;
        $resourceObject = $event->resource;
        $rModel = $resourceObject->getMemoryModel();
        $titleHelper = new OntoWiki_Model_TitleHelper();
        $hideProperties = $event->hideProperties;

        $temp = array();

        foreach ($hideProperties as $name) {
            $temp[$name['propertyUri']] = '';
        }

        $hideProperties = $temp;

        $values = $rModel->getValues($resourceObject->getUri(), EF_RDF_TYPE);
        $data = array();

        if (count($values) === 1) {
            $class = $values[0]['value'];
        } else {
            return $data;
        }

        $query = 'SELECT DISTINCT ?p ?range ?oneOf WHERE ' . PHP_EOL;
        $query.= ' ' . PHP_EOL;
        $query.= '{ ' . PHP_EOL;
        $query.= '  ?s a <' . $class . '> . ' . PHP_EOL;
        $query.= '  ?s ?p ?o . ' . PHP_EOL;
        $query.= '  ?p <' . EF_RDFS_RANGE . '> ?range . ' . PHP_EOL;
        $query.= '  OPTIONAL ' . PHP_EOL;
        $query.= '  { ' . PHP_EOL;
        $query.= '      ?range <' . EF_OWL_ONEOF . '> ?oneOf . ' . PHP_EOL;
        $query.= '  } ' . PHP_EOL;
        $query.= '} ' . PHP_EOL;

        $results = $this->_model->sparqlQuery($query);

        if (count($results) === 0) {
            return $data;
        }

        foreach ($results as $result) {

            if (strpos($result['range'], 'XMLSchema#') === false
                && (!isset($result['oneOf']) || $result['oneOf'] === '' || is_null($result['oneOf']))
                && $result['p'] !== EF_RDF_TYPE
                && !isset($hideProperties[$result['p']])
            ) {
                $data[] = array(
                    'property' => $result['p'],
                    'propertyLabel' => $titleHelper->getTitle($result['p']),
                    'class' => $result['range'],
                    'classLabel' => $titleHelper->getTitle($result['range'])
                );
            }
        }

        $delete = array();

        foreach($data as $key => $range) {
            $query  = 'SELECT ?testCollection WHERE ';
            $query .= '{ ' . PHP_EOL;
            $query .= '  <' . $range['class'] . '> ?p ?testCollection . ' . PHP_EOL;
            $query .= '  ?testCollection <' . EF_RDF_FIRST . '> ?test . ' . PHP_EOL;
            $query .= '} ' . PHP_EOL;
            $ranges = $this->_model->sparqlQuery($query);

            if (count($ranges) > 0) {
                $delete[] = $key;
                $this->_getCollection($ranges[0]['testCollection']);
                foreach ($this->_ranges as $foundRange) {
                    $data[] = array(
                        'property' => $range['property'],
                        'propertyLabel' => $titleHelper->getTitle($range['property']),
                        'class' => $foundRange,
                        'classLabel' => $titleHelper->getTitle($foundRange)
                    );
                }
            }
        }

        foreach ($delete as $key) {
            unset($data[$key]);
        }

        return $data;
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

