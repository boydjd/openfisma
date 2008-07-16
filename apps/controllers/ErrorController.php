<?PHP
/**
 * @file ErrorController.php
 *
 * @description Error Controller
 *
 * @author     Jim <jimc@reyosoft.com>
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * @version $Id$
*/

require_once 'Zend/Controller/Action.php' ;

class ErrorController extends Zend_Controller_Action 
{
    /**
     * This action handles
     *    - Application errors
     *    - Errors in the controller chain arising from missing
     *     controller classes and/or action methods
     */
    public function errorAction ()
    {
        $content = null;
        $errors = $this->_getParam ('error_handler') ;
        $this->_helper->layout->setLayout('error');
        switch ($errors->type) {
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER :
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION :
                // 404 error -- controller or action not found
                $this->getResponse ()->setRawHeader ( 'HTTP/1.1 404 Not Found' ) ;
                // ... get some output to display...
                $content .= "<h1>404 Page not found!</h1>" . PHP_EOL;
                $content .= "<p>The page you requested was not found.</p>";
                break ;
            default :
                $content .= "<h1>Error!</h1>" . PHP_EOL;
                $content .= "<p>An unexpected error occurred with your request. Please try again later.</p>";
                // Log the exception
                break ;
        }
        $this->getResponse()->clearBody();
        $this->view->content = $content . '<p>' . $errors->exception->getMessage().'</p>';
        $this->render();
    }
}



?>
