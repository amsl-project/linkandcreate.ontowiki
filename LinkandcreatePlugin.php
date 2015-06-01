<?php
/**
 * This file is part of the {@link http://ontowiki.net OntoWiki} project.
 *
 * @license   http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

/**
 * The main class for the linkandcreate plugin.
 *
 * @category   OntoWiki
 * @package    Extensions_Linkandcreate
 * @author     Norman Radtke <radtke@informatik.uni-leipzig.de>
 */
class LinkandcreatePlugin extends OntoWiki_Plugin
{

    private $_model = null;
    /*
     * our event method
     */
    public function onResourceShowRanges($event)
    {
        $this->showRanges($event);
    }

    /*
     * here we add new import actions
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
            $temp[$name['classUri']] = '';
        }
        $hideProperties = $temp;

        $values = $rModel->getValues($resourceObject->getUri(), EF_RDF_TYPE);
        $data = array();

        if (count($values) === 1) {
            $class = $values[0]['value'];
        } else {
            return $data;
        }

        $query = 'SELECT DISTINCT ?p ?range ?oneOf ?testCollection WHERE ' . PHP_EOL;
        $query.= ' ' . PHP_EOL;
        $query.= '{ ' . PHP_EOL;
        $query.= '  ?s a <' . $class . '> . ' . PHP_EOL;
        $query.= '  ?s ?p ?o . ' . PHP_EOL;
        $query.= '  ?p <' . EF_RDFS_RANGE . '> ?range . ' . PHP_EOL;
        $query.= '  OPTIONAL ' . PHP_EOL;
        $query.= '  { ' . PHP_EOL;
        $query.= '      ?range <' . EF_OWL_ONEOF . '> ?oneOf . ' . PHP_EOL;
        //$query.= '    ?range <' . EF_RDF_FIRST . '> ?testCollection . ' . PHP_EOL;
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
            $query = 'SELECT ?testCollection WHERE ';
            $query .= '{ ' . PHP_EOL;
            $query .= '  <' . $range['class'] . '> ?p ?testCollection . ' . PHP_EOL;
            $query .= '  ?testCollection <' . EF_RDF_FIRST . '> ?test . ' . PHP_EOL;
            $query .= '} ' . PHP_EOL;
            $ranges = $this->_model->sparqlQuery($query);

            if (count($ranges) >= 0) {
                $delete[] = $key;
                $rangeset = $this->_getCollection($ranges[0]['testCollection']);
                foreach ($rangeset as $foundRange) {
                    $data[] = array(
                        'property' => $range['property'],
                        'propertyLabel' => $titleHelper->getTitle($range['property']),
                        'class' => $foundRange,
                        'classLabel' => $titleHelper->getTitle($foundRange)
                    );
                }
                foreach ($delete as $key) {
                    unset($data[$key]);
                }
            }
        }

        $event->data = $data;
        return;
    }

    private function _getCollection($preUri) {
        $query = 'SELECT ?first ?rest WHERE ' . PHP_EOL;
        $query.= ' { ' . PHP_EOL;
        $query.= ' <' . $preUri . '> <' . EF_RDF_FIRST . '> ?first . ' . PHP_EOL;
        $query.= ' <' . $preUri . '> <' . EF_RDF_REST . '> ?rest . ' . PHP_EOL;
        $query.= ' } ' . PHP_EOL;

        $owApp = OntoWiki::getInstance();
        $result = $this->_model->sparqlQuery($query);

        if (count($result) >= 0) {
            $temp = array();
            $temp[] = $result[0]['first'];
            if ($result[0]['rest'] !== EF_RDF_NIL) {
                return array_merge_recursive($temp, $this->_getCollection($result[0]['rest']));
            } else {
                return $temp;
            }
        }
    }
}
