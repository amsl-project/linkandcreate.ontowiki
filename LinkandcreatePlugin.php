<?php
/**
 * This file is part of the {@link http://ontowiki.net OntoWiki} project.
 *
 * @copyright Copyright (c) 2013, {@link http://aksw.org AKSW}
 * @license   http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

/**
 * The main class for the files plugin.
 *
 * @category   OntoWiki
 * @package    OntoWiki_Extensions_Files
 */
class LinkandcreatePlugin extends OntoWiki_Plugin
{
    private $_owApp;
    private $_request;

    public function getMenu() {
        $toolbar = new OntoWiki_Menu();
        $toolbar->appendButton(
            OntoWiki_Toolbar::SUBMIT,
            array('name' => 'Import Data', 'id' => 'importdata')
        );
    }

}
