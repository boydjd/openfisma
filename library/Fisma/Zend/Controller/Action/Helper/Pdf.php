<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Controller
 * @subpackage Zend_Controller_Action_Helper
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */

/**
 * Simplify AJAX context switching based on requested format
 *
 * @uses       Zend_Controller_Action_Helper_Abstract
 * @category   Zend
 * @package    Zend_Controller
 * @subpackage Zend_Controller_Action_Helper
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Fisma_Zend_Controller_Action_Helper_Pdf extends Zend_Controller_Action_Helper_Abstract
{
    /**
     * Suppress exit when sendJson() called
     * @var boolean
     */
    public $suppressExit = false;

    /**
     * Encode JSON response and immediately send
     *
     * @param  mixed   $data
     * @param  boolean|array $keepLayouts
     * NOTE:   if boolean, establish $keepLayouts to true|false
     *         if array, admit params for Zend_Json::encode as enableJsonExprFinder=>true|false
     *         if $keepLayouts and parmas for Zend_Json::encode are required
     *         then, the array can contains a 'keepLayout'=>true|false
     *         that will not be passed to Zend_Json::encode method but will be passed
     *         to Zend_View_Helper_Json
     * @return string|void
     */
    public function sendPdf(Fisma_PDF $data)
    {
        $data = $data->render();
        $response = $this->getResponse();
        $response->setHeader('Content-Type', 'application/pdf', true);
        $response->setBody($data);

        if (!$this->suppressExit) {
            $response->sendResponse();
            exit;
        }

        return $data;
    }

    /**
     * Strategy pattern: call helper as helper broker method
     *
     * Allows encoding JSON. If $sendNow is true, immediately sends JSON
     * response.
     *
     * @param  mixed   $data
     * @param  boolean $sendNow
     * @param  boolean $keepLayouts
     * @return string|void
     */
    public function direct(Fisma_PDF $data)
    {
        return $this->sendPdf($data);
    }
}
