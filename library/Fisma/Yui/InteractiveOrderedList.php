<?php
/**
 * @phpdoc: short description.
 *
 * @phpdoc: long description.
 *
 */
class Fisma_Yui_InteractiveOrderedList
{
    /**
     * @phpdoc: short description.
     *
     * @param string    $id                 The HTML id of the UL
     * @param string    $contentModule      The module of the view script for each item in the list
     * @param string    $contentScript      The link to the view script for each item in the list
     * @param array     $dataList           The array of objects to pass to each item's view script
     * @param bool      $enabled            Whether the list is editable
     * @param string    $jsHandlers         The name of the extra JS function that handles the onDragDrop event
     */
    public function __construct($id, $contentModule, $contentScript, $dataList, $enabled, $jsHandlers)
    {
        $this->id = $id;
        $this->contentModule = $contentModule;
        $this->contentScript = $contentScript;
        $this->dataList = $dataList;
        $this->enabled = $enabled;
        $this->jsHandlers = $jsHandlers;
    }
    /**
     * Constructing the HTML markup of the whole list
     *
     * @param Zend_View_Layout $layout
     * @return string
     */
    public function render($layout = null)
    {
        $layout = (!isset($layout)) ? Zend_Layout::getMvcInstance() : $layout;
        $view = $layout->getView();

        /*$data = array(
            'id' => $this->_id,
            'items' => $this->_items,
            'enabled' => $this->_enabled,
            'jsHandlers' => $this->_jsHandlers
        );*/

        return $view->partial('yui/interactive-ordered-list.phtml', 'default', $this);
    }

    /**
     * Calls and returns render()
     *
     * @return void
     */
    public function __tostring()
    {
        return $this->render();
    }
}
