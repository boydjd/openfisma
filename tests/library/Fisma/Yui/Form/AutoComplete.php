<?php
/**
 * Copyright (c) 2011 Endeavor Systems, Inc.
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

require_once(realpath(dirname(__FILE__) . '/../../../../Case/Unit.php'));

/**
 * test /library/Fisma/Yui/Form/AutoComplete.php
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Library
 */
class Test_Library_Fisma_Yui_Form_AutoComplete extends Test_Case_Unit
{
    /**
     * test renderSelf()
     *
     * please maintain spacing *strictly*
     * @return void
     */
    public function testRenderSelf()
    {
        $readOnly = true;
        $disabled = $readOnly ? 'disabled="true"' : '';
 
        $name = 'TestLink';
        $hiddenField = 'TestHidden';
        $hiddenValue = 'Test_AutoComplete_Hidden';
        $label = 'Test Link';
        $containerId = uniqid();
        
        $resultsList = 'Test_AutoComplete';
        $fields = '{id, content}';
        $xhr = '/test/autocomplete';
        $queryPrepend = '/keyword/';
        $setupCallback = 'autocomplete_callback';

        $render  = "<div>
                    <input type=\"text\" 
                           name=\"$name\" 
                           id=\"$name\" 
                           value=\"$hiddenValue\"
                           $disabled>
                    <img class='spinner'
                         id='{$containerId}Spinner' 
                         src='/images/spinners/small.gif'>
                    <div id=\"$containerId\"></div>                    
                    </div>
                    <script type='text/javascript'>
                        YAHOO.util.Event.onDOMReady(Fisma.AutoComplete.init,
                          { schema: [\"$resultsList\", \"$fields\"],
                            xhr : \"$xhr\",
                            fieldId : \"$name\",
                            containerId: \"$containerId\",
                            hiddenFieldId: \"$hiddenField\",
                            setupCallback : '$setupCallback',
                            queryPrepend: \"$queryPrepend\"
                          } );
                    </script>";

        $options = array(
            readOnly => $readOnly,
            hiddenField => $hiddenField,
            value => $hiddenValue,
            containerId => $containerId,
            resultsList => $resultsList,
            fields => $fields,
            xhr => $xhr,
            queryPrepend => $queryPrepend,
            setupCallback => $setupCallback
        );
        $button = new Fisma_Yui_Form_AutoComplete($name, $options);
        $button->setDisplayText($label);
        $this->assertEquals($render, $button->__toString());
    }
}

