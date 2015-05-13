<?php
/**
 * This file is part of the {@link http://ontowiki.net OntoWiki} project.
 *
 * @copyright Copyright (c) 2012, {@link http://aksw.org AKSW}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

/**
 * Controller for the OntoWiki files extension
 *
 * @category OntoWiki
 * @package  OntoWiki_extensions_files
 * @author   Norman Radtke <norman.radtke@gmail.com>
 */
class LinkandcreateController extends OntoWiki_Controller_Component
{
    public function startAction()
    {
        return;
    }

    /*
     * This action can be used to add a predicate and object to the selected Resource.
     * It uses access control, adding triples to models one isn't allowed to won't be possible
     *
     */
    public function linktripleAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        if ($this->_request->isPost()) {
            $subject   = (string)$this->_owApp->selectedResource;
            $post      = $this->_request->getPost();

            if (isset($post['predicate'])
                && isset($post['object']))
            {
                // everything fine
            } else {
                // necessarry parameters are missing
                $this->_response->setHeader(http_response_code(500));
                return;
            }

            $predicate = urldecode($post['predicate']);
            $object    = urldecode($post['object']);

            if (Erfurt_Uri::check($predicate) && Erfurt_Uri::check($object)) {
                $this->_owApp->selectedModel->addStatement(
                    $subject,
                    $predicate,
                    array('value' => $object, 'type' => 'uri')
                );
                $this->_response->setHeader(http_response_code(200));
                return;
            } else {
                // data contains no valid URIs
                $this->_response->setHeader(http_response_code(500));
                return;
            }
        } else {
            // no post request
            $this->_response->setHeader(http_response_code(500));
            return;
        }
    }

    /*
     * checks the resource types agains the configured patterns
     */
    public static function getLinkCandidates($resourceObject)
    {
        $owApp       = OntoWiki::getInstance();
        $store       = $owApp->erfurt->getStore();
        $rModel      = $resourceObject->getMemoryModel();
        $titleHelper = new OntoWiki_Model_TitleHelper();

        $values = $rModel->getValues($resourceObject->getUri(),EF_RDF_TYPE);
        $data = array();

        if (count($values) === 1) {
            $class = $values[0]['value'];
        } else {
            return $data;
        }

        $query = "SELECT DISTINCT ?p ?range ?oneOf WHERE " . PHP_EOL;
        $query.= " " . PHP_EOL;
        $query.= "{ " . PHP_EOL;
        $query.= "  ?s ?p ?o . " . PHP_EOL;
        $query.= "  ?s a <" . $class . "> . " . PHP_EOL;
        $query.= "  ?p <" . EF_RDFS_RANGE . "> ?range . " . PHP_EOL;
        $query.= "  OPTIONAL " . PHP_EOL;
        $query.= "  { " . PHP_EOL;
        $query.= "    ?range <" . EF_OWL_ONEOF . "> ?oneOf . " . PHP_EOL;        $data = array();
        $query.= "  } " . PHP_EOL;
        $query.= "} " . PHP_EOL;

        $results = $store->sparqlQuery($query);

        if (count($results) === 0) {
            return $data;
        }

        foreach ($results as $result) {
            if (strpos($result['range'],'XMLSchema#') === false
                && $result['oneOf'] === ''
                && $result['p'] !== EF_RDF_TYPE
            ) {
                $data[] = array(
                    'property'      => $result['p'],
                    'propertyLabel' => $titleHelper->getTitle($result['p']),
                    'class'         => $result['range'],
                    'classLabel'    => $titleHelper->getTitle($result['range'])
                );
            }
        }

        return $data;
    }
}
