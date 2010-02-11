<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
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
 * @author    Nathan Harris <nathan.harris@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id: FindingController.php 2082 2009-07-29 21:27:37Z nathanrharris $
 * @package   Controller
 */

/**
 * The incident controller is used for searching, displaying, and updating
 * incidents.
 *
 * @package   Controller
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class IncidentController extends BaseController
{
    
    /**
     * The main name of the model.
     * 
     * This model is the main subject which the controller operates on.
     */
    protected $_modelName = 'Incident';

    /**
     * initialize the basic information, my orgSystems
     *
     */
    public function init()
    {
        parent::init();
    }
    
    
    /**
     * Returns the standard form for creating finding
     *
     * @return Zend_Form
     */
    public function getForm()
    {
        $form = Fisma_Form_Manager::loadForm('incident');

        /* setting up state dropdown */
        $form->getElement('reporter_state')->addMultiOptions(array(0 => '--select--'));
        foreach ($this->_getStates() as $state) {
            $form->getElement('reporter_state')
                 ->addMultiOptions(array($state => $state));
        }

        /* setting up timestamp and timezone dropdowns */
        $form->getElement('incident_hour')->addMultiOptions(array(0 => ' -- ')); 
        $form->getElement('incident_minute')->addMultiOptions(array(0 => ' -- ')); 
        $form->getElement('incident_ampm')->addMultiOptions(array(0 => ' -- ')); 
        $form->getElement('incident_tz')->addMultiOptions(array(0 => ' -- ')); 

        foreach($this->_getHours() as $hour) {
            $form->getElement('incident_hour')
                 ->addMultiOptions(array($hour => $hour));
        }
        
        foreach($this->_getMinutes() as $min) {
            $form->getElement('incident_minute')
                 ->addMultiOptions(array($min => $min));
        }
        
        foreach($this->_getAmpm() as $ampm) {
            $form->getElement('incident_ampm')
                 ->addMultiOptions(array($ampm => $ampm));
        }
        
        foreach($this->_getTz() as $tz) {
            $form->getElement('incident_tz')
                 ->addMultiOptions(array($tz => $tz));
        }

        foreach($this->_getOS() as $key => $os) {
            $form->getElement('host_os')
                 ->addMultiOptions(array($key => $os));
        }

        $form->getElement('pii_mobile_media_type')->addMultiOptions(array(0 => '--select--'));
        foreach($this->_getMobileMedia() as $key => $mm) {
            $form->getElement('pii_mobile_media_type')
                 ->addMultiOptions(array($key => $mm));
        }

        $form->getElement('classification')->addMultiOptions(array(0 => ' will be populated from category table ')); 
        
        $form->getElement('assessment_sensitivity')->addMultiOptions(array(   'low' => ' LOW ')); 
        $form->getElement('assessment_sensitivity')->addMultiOptions(array('medium' => ' MEDIUM ')); 
        $form->getElement('assessment_sensitivity')->addMultiOptions(array(  'high' => ' HIGN ')); 
       
        /* this method defined below adds yes/no values to all select elements passed in the 2nd argument */
        $this->_createBoolean(&$form,    array(  'assessment_critical', 
                                                'pii_involved', 
                                                'pii_mobile_media', 
                                                'pii_encrypted', 
                                                'pii_authorities_contacted', 
                                                'pii_police_report',
                                                'pii_individuals_notification',
                                                'pii_shipment',
                                                'pii_shipment_sender_contact'
                                        )
                            );

        $form->setDisplayGroupDecorators(array(
            new Zend_Form_Decorator_FormElements(),
            new Fisma_Form_CreateIncidentDecorator()
        ));

        $form->setElementDecorators(array(new Fisma_Form_CreateIncidentDecorator()));

        $timestamp = $form->getElement('incident_ts');
        $timestamp->clearDecorators();
        $timestamp->addDecorator('ViewScript', array('viewScript'=>'datepicker.phtml'));
        $timestamp->addDecorator(new Fisma_Form_CreateFindingDecorator());

        return $form;
    }

    /** 
     * Overriding Hooks
     *
     * @param Zend_Form $form
     * @param Doctrine_Record|null $subject
     */
    protected function saveValue($form, $subject=null)
    {
        if (is_null($subject)) {
            $subject = new $this->_modelName();
        } else {
            throw new Fisma_Exception('Invalid parameter expecting a Record model');
        }
        $values = $form->getValues();

        $values['source_ip'] = $_SERVER['REMOTE_ADDR'];

        $values['report_ts'] = date('Y-m-d G:i:s');
        $values['report_tz'] = date('T');

        if ($values['incident_hour'] && $values['incident_minute'] && $values['incident_ampm']) {
            if ($values['incident_ampm'] == 'PM') {
                $values['incident_hour'] += 12;
            }
            $values['incident_ts'] .= " {$values['incident_hour']}:{$values['incident_minute']}:00";
        }

        $subject->merge($values);
        $subject->save();
    }
    private function _getStates() {
        $states = array (
              'AL' => 'Alabama',
              'AK' => 'Alaska',
              'AZ' => 'Arizona',
              'AR' => 'Arkansas',
              'CA' => 'California',
              'CO' => 'Colorado',
              'CT' => 'Connecticut',
              'DE' => 'Delaware',
              'DC' => 'District of Columbia',
              'FL' => 'Florida',
              'GA' => 'Georgia',
              'HI' => 'Hawaii',
              'ID' => 'Idaho',
              'IL' => 'Illinois',
              'IN' => 'Indiana',
              'IA' => 'Iowa',
              'KS' => 'Kansas',
              'KY' => 'Kentucky',
              'LA' => 'Louisiana',
              'ME' => 'Maine',
              'MD' => 'Maryland',
              'MA' => 'Massachusetts',
              'MI' => 'Michigan',
              'MN' => 'Minnesota',
              'MS' => 'Mississippi',
              'MO' => 'Missouri',
              'MT' => 'Montana',
              'NE' => 'Nebraska',
              'NV' => 'Nevada',
              'NH' => 'New Hampshire',
              'NJ' => 'New Jersey',
              'NM' => 'New Mexico',
              'NY' => 'New York',
              'NC' => 'North Carolina',
              'ND' => 'North Dakota',
              'OH' => 'Ohio',
              'OK' => 'Oklahoma',
              'OR' => 'Oregon',
              'PW' => 'Palau',
              'PA' => 'Pennsylvania',
              'PR' => 'Puerto Rico',
              'RI' => 'Rhode Island',
              'SC' => 'South Carolina',
              'SD' => 'South Dakota',
              'TN' => 'Tennessee',
              'TX' => 'Texas',
              'UT' => 'Utah',
              'VT' => 'Vermont',
              'VI' => 'Virgin Island',
              'VA' => 'Virginia',
              'WA' => 'Washington',
              'WV' => 'West Virginia',
              'WI' => 'Wisconsin',
              'WY' => 'Wyoming'
        );

        return $states;
    }

    private function _getHours() {
        return array('1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12');
    }
    private function _getMinutes() {
        return array('00', '15', '30', '45');
    }
    private function _getAmpm() {
        return array('AM', 'PM');
    }
    private function _getTz() {
        return array('EST', 'CST', 'MTN', 'PST');
    }
    
    private function _getOS() {
        return array(    '2007' => 'Win 2007',
                        'vista' => 'Vista',
                           'xp' => 'XP',
                        'macos' => 'Mac OSX',
                        'linux' => 'Linux',
                         'unix' => 'Unix'
                    );
    }
    
    private function _getMobileMedia() {
        return array(    'laptop' => 'Laptop',
                           'disc' => 'CD/DVD',
                       'document' => 'Document',
                            'usb' => 'USB/Flash Drive',
                           'tape' => 'Magnetic Tape',
                          'other' => 'Other'
                    );
    }

    private function _createBoolean(&$form, $elements) {
        foreach($elements as $element) {
            $form->getElement($element)->addMultiOptions(array('0' => ' NO ')); 
            $form->getElement($element)->addMultiOptions(array('1' => ' YES ')); 
        }

        return 1;
    }
}
