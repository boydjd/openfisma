<?php
/**
 * Copyright (c) 2012 Endeavor Systems, Inc.
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
 * Mail
 *
 * @author     Ben Zheng <ben.zheng@reyosoft.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Model
 * @uses       BaseMail
 */
class Mail extends BaseMail
{
    /**
     * Override the Doctrine hook to initialize new mail objects
     * 
     * @return void
     */
    public function construct()
    {
        // Set default sender and sendName for new objects, not persistent objects
        $state = $this->state();
        if ($state == Doctrine_Record::STATE_TCLEAN || $state == Doctrine_Record::STATE_TDIRTY) {
            $this->sender     = Fisma::configuration()->getConfig('sender');
            $this->senderName = Fisma::configuration()->getConfig('system_name');
        }
    }

    /**
     * Customize body content based on the mail templates
     * 
     * @param string $template A mail view script name.
     * @param array $options An array with key => value type to pass view variables
     */
    public function mailTemplate($template, $options = array())
    {
        $view = $this->_getView();

        foreach ($options as $k => $v) {
            $view->$k = $v;
        }

        $this->body = $view->render("$template.phtml");
    }

    /**
     * Return a zend mail instance
     * 
     * @return Zend_Mail
     */
    public function toZendMail()
    {
        $mail = new Zend_Mail();

        $mail->setFrom($this->sender, $this->senderName);
        $mail->addTo($this->recipient, $this->recipientName);
        $mail->setSubject($this->subject);
        if ($this->format === 'html') {
            $mail->setBodyHtml($this->body);
        } else {
            $mail->setBodyText($this->body);
        }

        return $mail;
    }

    /**
     * Return a view object that can be used to render mail templates
     *
     * @return Fisma_Zend_View
     */
    private function _getView()
    {
        $view = new Fisma_Zend_View();

        $view->setScriptPath(Fisma::getPath('application') . '/common-views/mail/')
             ->setEncoding('utf-8');

        return $view;
    }
}