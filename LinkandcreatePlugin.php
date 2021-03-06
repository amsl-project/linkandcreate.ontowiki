<?php
/**
 * This file is part of the {@link http://amsl.technology amsl} project.
 *
 * @author Norman Radtke
 * @copyright Copyright (c) 2015, {@link http://ub.uni-leipzig.de Leipzig University Library}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

/**
 * The main class for the linkandcreate plugin.
 *
 * @category   OntoWiki
 * @package    Extensions_Linkandcreate
 */
class LinkandcreatePlugin extends OntoWiki_Plugin
{

    private $_model  = null;
    private $_ranges = array();
    /*
     * our event method
     */
    public function onResourceShowRanges($event)
    {
        $this->showRanges($event);
    }

    /*
     * this methods analyzes properties and tries to find ranges for object properties
     */
    private function showRanges($event)
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
                && $result['oneOf'] === ''
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

        $event->data = $data;
        return;
    }

    /**
     * @param $preUri the URI of the predecessor
     * @return the result is written to $this->_ranges (array())
     * This methods searches for members of a collection
     * The method should be called with the first member in $preUri
     */
    private function _getCollection($preUri) {
        $query = 'SELECT ?first ?rest WHERE ' . PHP_EOL;
        $query.= ' { ' . PHP_EOL;
        $query.= ' <' . $preUri . '> <' . EF_RDF_FIRST . '> ?first . ' . PHP_EOL;
        $query.= ' <' . $preUri . '> <' . EF_RDF_REST . '> ?rest . ' . PHP_EOL;
        $query.= ' } ' . PHP_EOL;

        $result = $this->_model->sparqlQuery($query);
        unset($query);

        if (count($result) > 0) {
            $this->_ranges[] = $result[0]['first'];
            if ($result[0]['rest'] !== EF_RDF_NIL) {
                $nextUri = $result[0]['rest'];
                unset($result);
                $this->_getCollection($nextUri);
            } else {
                return;
            }
        }
    }
}
