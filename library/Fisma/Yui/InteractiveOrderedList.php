<?php
/**
 * @phpdoc: short description.
 *
 * @phpdoc: long description.
 *
 */
class Fisma_Yui_InteractiveOrderedList
{
    protected $_id;
    protected $_items;
    protected $_enabled;
    protected $_jsHandlers;

    /**
     * @phpdoc: short description.
     *
     * @param string $id            The HTML id of the UL
     * @param array  $items         The array of items' innerHTML
     * @param bool   $enabled       Whether the list is editable
     * @param string $jsHandlers    The name of the extra JS function that handles the onDragDrop event
     */
    public function __construct($id, $items, $enabled, $jsHandlers)
    {
        $this->_id = $id;
        $this->_items = $items;
        $this->_enabled = $enabled;
        $this->_jsHandlers = $jsHandlers;
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

        $data = array(
            'id' => $this->_id,
            'items' => $this->_items,
            'enabled' => $this->_enabled,
            'jsHandlers' => $this->_jsHandlers
        );

        return $view->partial('yui/interactive-ordered-list.phtml', 'default', $data);
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
