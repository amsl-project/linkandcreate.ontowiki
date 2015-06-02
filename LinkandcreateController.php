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

}
