<?php
/**
 * This file is part of the {@link http://amsl.technology amsl} project.
 *
 * @author Norman Radtke
 * @copyright Copyright (c) 2015, {@link http://ub.uni-leipzig.de Leipzig University Library}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

/**
 * Helper for the linkandcreate extension
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

        $this->view->headScript()->appendFile($this->_config->urlBase . 'extensions/linkandcreate/templates/linkandcreate/js/linkandcreate.js');
    }
}
