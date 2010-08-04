<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
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
 * Fisma_Zend_Form_Manager_Abstract 
 * 
 * @package Fisma_Zend_Form_Manager
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
abstract class Fisma_Zend_Form_Manager_Abstract
{
    /**
     * _form 
     * 
     * @var Fisma_Zend_Form 
     * @access protected
     */
    protected $_form;

    /**
     * _acl 
     * 
     * @var Fisma_Zend_Acl 
     * @access protected
     */
    protected $_acl;

    /**
     * _me 
     * 
     * @var User 
     * @access protected
     */
    protected $_me;

    /**
     * _view
     * 
     * @var Fisma_Zend_View 
     * @access protected
     */
    protected $_view;

    /**
     * _request 
     * 
     * @var Zend_Controller_Request_Http 
     * @access protected
     */
    protected $_request;

    abstract public function prepareForm(); 

    /**
     * __construct 
     * 
     * @param Fisma_Zend_View $view 
     * @param Zend_Controller_Request_Http $request 
     * @param Fisma_Zend_Acl $acl 
     * @param User $user 
     * @return void
     */
    final public function __construct(
        Fisma_Zend_View $view, 
        Zend_Controller_Request_Http $request, 
        Fisma_Zend_Acl $acl, 
        User $user
    )
    {
        $this->_acl = $acl;
        $this->_me = $user;
        $this->_view = $view;
        $this->_request = $request;
    }

    /**
     * setForm 
     * 
     * @param Fisma_Zend_Form $form 
     * @return void
     */
    final public function setForm(Fisma_Zend_Form $form)
    {
        $this->_form = $form;
    }

    /**
     * getForm 
     * 
     * @return Fisma_Zend_Form 
     */
    final public function getForm()
    {
        return $this->_form;
    }
}
