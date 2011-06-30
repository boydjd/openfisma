<?php
// @codingStandardsIgnoreFile
/**
 * Copyright (c) 2010 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify it under the terms of the GNU General Public 
 * License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later
 * version.
 *
 * OpenFISMA is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied 
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more 
 * details.
 *
 * You should have received a copy of the GNU General Public License along with OpenFISMA.  If not, see 
 * {@link http://www.gnu.org/licenses/}.
 */

/**
 * Add configuration fields for Search
 *
 * @package Migration
 * @copyright (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @author Mark E. Haase <mhaase@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Version81 extends Doctrine_Migration_Base
{
    public function up()
    {
		$this->addColumn('configuration', 'search_backend', 'enum', '', array(
             'values' => 
             array(
              0 => 'solr',
              1 => 'zend_search_lucene',
             ),
             'notblank' => '1',
             'comment' => 'The backend for the search engine.',
             'default' => 'zend_search_lucene',
             ));
		$this->addColumn('configuration', 'search_solr_host', 'string', '255', array(
             'comment' => 'The hostname or IP address that Solr is listening on.',
             ));
		$this->addColumn('configuration', 'search_solr_port', 'integer', '5', array(
             'comment' => 'The IP port that Solr is listening on.',
             ));
		$this->addColumn('configuration', 'search_solr_path', 'string', '255', array(
             'comment' => 'The path that the Solr service is running within its container. (Usually /solr)',
             ));
    }

    public function down()
    {
		$this->removeColumn('configuration', 'search_backend');
		$this->removeColumn('configuration', 'search_solr_host');
		$this->removeColumn('configuration', 'search_solr_port');
		$this->removeColumn('configuration', 'search_solr_path');
    }
}
