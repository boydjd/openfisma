<?php
/**
 * ZIDS Action Interface. 
 * 
 * Implement this interface for your own actions. 
 *
 * @package    ZIDS
 * @author     Christian Koncilia
 * @copyright  Copyright (c) 2010 Christian Koncilia. (http://www.web-punk.com)
 * @license    New BSD License (see above)
 * @version    V.0.6 
 */

interface ZIDS_Plugin_ActionPlugin_Interface
{
    /**
     * If an impact triggers this action, this method will be called. 
     *
     * @param IDS_Report $ids_result The result of the PHP IDS scan
     * @param int $impact The impact. If zids.aggregate_in_session = true, $impact consists of the aggregated impact otherwise $impact is the non aggregated impact
     * @param string $levelname Name of the level that fires the action
     * @return void
     */
    public function fires(IDS_Report $ids_result, $impact, $levelname);

    /**
     * Each plugin has to return a unique identifier / name
     *
     * @return string
     */
    public function getIdentifier();
}