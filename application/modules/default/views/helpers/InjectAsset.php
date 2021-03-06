<?php
/**
 * Copyright (c) 2009 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OpenFISMA is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OpenFISMA.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Josh Boyd <joshua.boyd@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2009 (http://www.endeavorsystems.com)
 * @license   http://openfisma.org/content/license
 * @package   View_Helper
 */

/**
 * Helper for injecting assets into layouts
 *
 * @copyright (c) Endeavor Systems, Inc. 2009 (http://www.endeavorsystems.com)
 * @license http://openfisma.org/content/license
 * @package View_Helper
 * @var Zend_View_Interface $view
 * @var array $_depMap An array of dependencies for Fisma specific assets
 */
class View_Helper_InjectAsset
{
    public $view;
    private static $_depMap = array(
                                '/javascripts/combined.js' =>
                                array('/javascripts/php.js',
                                      '/javascripts/jstz.js',
                                      '/javascripts/tiny_mce_config.js',
                                      '/javascripts/groupeddatatable.js',
                                      '/javascripts/jquery.js',
                                      '/javascripts/jquery-ui.js',
                                      '/javascripts/bootstrap.js',
                                      '/javascripts/jquery-tinysort.js',
                                      '/javascripts/jquery-timeentry.js',
                                      '/javascripts/jquery-json.js',
                                      '/javascripts/jquery-datatables.js',
                                      '/javascripts/bootstrap-datatables.js',
                                      '/javascripts/moment.js',
                                      '/javascripts/Fisma.js',
                                      '/javascripts/jqPlot/core/jquery-jqplot.js',
                                      '/javascripts/jqPlot/plugins/jqplot-canvasTextRenderer.js',
                                      '/javascripts/jqPlot/plugins/jqplot-canvasAxisLabelRenderer.js',
                                      '/javascripts/jqPlot/plugins/jqplot-canvasAxisTickRenderer.js',
                                      '/javascripts/jqPlot/plugins/jqplot-categoryAxisRenderer.js',
                                      '/javascripts/jqPlot/plugins/jqplot-highlighter.js',
                                      '/javascripts/jqPlot/renderers/jqplot-barRenderer.js',
                                      '/javascripts/jqPlot/renderers/jqplot-pointLabels.js',
                                      '/javascripts/jqPlot/renderers/jqplot-pieRenderer.js',
                                      '/javascripts/Fisma/Storage.js',
                                      '/javascripts/Fisma/PersistentStorage.js',
                                      '/javascripts/Fisma/TreeTable.js',
                                      '/javascripts/Fisma/Editable.js',
                                      '/javascripts/Fisma/AttachArtifacts.js',
                                      '/javascripts/Fisma/Asset.js',
                                      '/javascripts/Fisma/AutoComplete.js',
                                      '/javascripts/Fisma/Blinker.js',
                                      '/javascripts/Fisma/Calendar.js',
                                      '/javascripts/Fisma/Chart.js',
                                      '/javascripts/Fisma/CheckboxTree.js',
                                      '/javascripts/Fisma/Commentable.js',
                                      '/javascripts/Fisma/Email.js',
                                      '/javascripts/Fisma/Finding.js',
                                      '/javascripts/Fisma/FindingSummary.js',
                                      '/javascripts/Fisma/Highlighter.js',
                                      '/javascripts/Fisma/HtmlPanel.js',
                                      '/javascripts/Fisma/Icon.js',
                                      '/javascripts/Fisma/ImagePicker.js',
                                      '/javascripts/Fisma/Incident.js',
                                      '/javascripts/Fisma/InteractiveOrderedListItem.js',
                                      '/javascripts/Fisma/IncidentWorkflow.js',
                                      '/javascripts/Fisma/Ldap.js',
                                      '/javascripts/Fisma/Menu.js',
                                      '/javascripts/Fisma/MessageBox.js',
                                      '/javascripts/Fisma/MessageBoxStack.js',
                                      '/javascripts/Fisma/Module.js',
                                      '/javascripts/Fisma/Organization.js',
                                      '/javascripts/Fisma/OrganizationTreeView.js',
                                      '/javascripts/Fisma/PocTreeView.js',
                                      '/javascripts/Fisma/Registry.js',
                                      '/javascripts/Fisma/Remediation.js',
                                      '/javascripts/Fisma/Role.js',
                                      '/javascripts/Fisma/RowsPerPageInputBox.js',
                                      '/javascripts/Fisma/Sa.js',
                                      '/javascripts/Fisma/Search.js',
                                      '/javascripts/Fisma/Search/Criterion.js',
                                      '/javascripts/Fisma/Search/Criteria.js',
                                      '/javascripts/Fisma/Search/CriteriaDefinition.js',
                                      '/javascripts/Fisma/Search/CriteriaQuery.js',
                                      '/javascripts/Fisma/Search/CriteriaRenderer.js',
                                      '/javascripts/Fisma/Search/Panel.js',
                                      '/javascripts/Fisma/SessionManager.js',
                                      '/javascripts/Fisma/Spinner.js',
                                      '/javascripts/Fisma/Search/TablePreferences.js',
                                      '/javascripts/Fisma/Search/QueryState.js',
                                      '/javascripts/Fisma/System.js',
                                      '/javascripts/Fisma/SystemAggregationView.js',
                                      '/javascripts/Fisma/SwitchButton.js',
                                      '/javascripts/Fisma/TableFormat.js',
                                      '/javascripts/Fisma/TabView.js',
                                      '/javascripts/Fisma/TabView/Roles.js',
                                      '/javascripts/Fisma/Tag.js',
                                      '/javascripts/Fisma/TinyMCE.js',
                                      '/javascripts/Fisma/TreeNodeDragBehavior.js',
                                      '/javascripts/Fisma/UrlPanel.js',
                                      '/javascripts/Fisma/User.js',
                                      '/javascripts/Fisma/Util.js',
                                      '/javascripts/Fisma/ViewAs.js',
                                      '/javascripts/Fisma/Vulnerability.js',
                                      '/javascripts/Fisma/Workflow.js'
                                 ),
                                '/stylesheets/combined.css' =>
                                array('/stylesheets/main.css',
                                      '/stylesheets/AutoComplete.css',
                                      '/stylesheets/AttachArtifacts.css',
                                      '/stylesheets/chart.css',
                                      '/stylesheets/Dashboard.css',
                                      '/stylesheets/Finding.css',
                                      '/stylesheets/ImagePicker.css',
                                      '/stylesheets/Incident.css',
                                      '/stylesheets/InteractiveOrderedListItem.css',
                                      '/stylesheets/jquery-ui.css',
                                      '/stylesheets/jquery-timeentry.css',
                                      '/stylesheets/jquery-jqplot.css',
                                      '/stylesheets/jquery-datatables.css',
                                      '/stylesheets/MessageBox.css',
                                      '/stylesheets/Modules.css',
                                      '/stylesheets/Search.css',
                                      '/stylesheets/SwitchButton.css',
                                      '/stylesheets/TreeNodeDragBehavior.css',
                                      '/stylesheets/TreeTable.css',
                                      '/stylesheets/Toolbar.css',
                                      '/stylesheets/User.css',
                                      '/stylesheets/datatablegrouper.css'
                                )
                            );

    /**
     * setView - Gives access to the current Zend_View object
     *
     * @param Zend_View_Interface $view
     * @access public
     * @return void
     */
    public function setView(Zend_View_Interface $view)
    {
        $this->view = $view;
    }

    /**
     * injectAsset - Manages the injection of CSS or JS assets into a layout or view. Takes into account
     * rollups/combined files, and the currently running debug level.
     *
     * @param string $asset Path to the asset
     * @param string $type Type of asset
     * @param boolean $combo Whether the asset is a combo package or not
     * @param string $media Media settings for CSS files
     * @param string $conditional Conditional settings for CSS/JS files
     * @access public
     * @return void
     */
    public function injectAsset($asset, $type, $combo = FALSE,
        $media = 'screen', $conditional = FALSE)
    {
        /**
         * This asset is a Combo, and the application is in debug mode, so we need to output
         * each of the individual pieces of the combo.
         */
        if ($combo && Fisma::debug()) {
            $assets = self::$_depMap[$asset];
        } else {
            /**
             * This is just a single asset, throw it into an array for easier processing
             */
            $assets = array($asset);
        }

        /**
         * If we're not in debug mode, then insert the application version and -min into
         * the path.
         */
        $versions = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getOption('versions');
        if (!Fisma::debug()) {
            $appVersion = $versions['application'];

            foreach ($assets as &$asset) {
                $asset = str_replace(
                    ".$type", "-min." . $appVersion . ".$type", $asset
                );
            }
        }

        switch ($type) {
            case 'js':
                foreach ($assets as $asset) {
                    $this->view->headScript()->appendFile(
                        $asset, 'text/javascript', array('conditional' => $conditional)
                    );
                }

                break;

            case 'css':
                foreach ($assets as $asset) {
                    $this->view->headLink()->appendStylesheet($asset, $media, $conditional);
                }

                break;

            default:
                break;
        }
    }
}
