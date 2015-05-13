<?php
/**
 * This file is part of the {@link http://ontowiki.net OntoWiki} project.
 *
 * @copyright Copyright (c) 2011, {@link http://aksw.org AKSW}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

/**
 * Helper for the OntoWiki Files Extension
 *
 * @category OntoWiki
 * @package  OntoWiki_extensions_files
 * @author   Norman Radtke <norman.radtke@gmail.com>
 */

class LinkandcreateHelper extends OntoWiki_Component_Helper
{

    public $view = null;

    public function init()
    {
        $owApp = OntoWiki::getInstance();

        if (null === $this->view) {
            $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');

            if (null === $viewRenderer->view) {
                $viewRenderer->initView();
            }

            $this->view = clone $viewRenderer->view;
            $this->view->clearVars();
        }

        // if a model has been selected
        if ($owApp->selectedModel != null) {
            // register with extras menu
            //$translate  = $owApp->translate;
            //$url        = new OntoWiki_Url(array('controller' => 'linkandcreate', 'action' => 'start'));
        }

        $this->view->headScript()->appendFile($this->_config->urlBase . 'extensions/linkandcreate/templates/linkandcreate/js/linkandcreate.js');
    }
}
