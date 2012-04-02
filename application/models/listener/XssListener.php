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
 * A special listener which performs sanitization on user-provided inputs to protect against XSS attacks.
 * 
 * This listener works by introspecting the model and looking for an extra property named "purify", which can have
 * the values "html" or "plaintext". In HTML mode, this listener invokes the HtmlPurifier library to clean up
 * invalid and/or malicious markup while preserving valid user-generated markup. In plain text mode, the listener
 * uses htmlspecialchars() to escape any characters which may interrupt normal rendering of plain text.
 *
 * This listener should be attached to any class which puts external data (e.g., user-provided) 
 * 
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Listener
 */
class XssListener extends Fisma_Doctrine_Record_Listener
{
    /**
     * The HTMLPurifier instance used by the listener
     * 
     * @var HTMLPurifier
     */
    private static $_purifier;
    
    /**
     * Purify any fields which have been marked in the schema as needing purification
     * 
     * @param Doctrine_Event $event The listened doctrine event to process
     * @return void
     */
    public function preSave(Doctrine_Event $event) 
    {
        if (!self::$_listenerEnabled) {
            return;
        }

        $invoker = $event->getInvoker();
        $modified = $invoker->getModified();
        $table = $invoker->getTable();
        
        // Step through each modified value, and see if it needs to have any purification applied
        foreach ($modified as $field => $value) {
            $fieldDefinition = $table->getDefinitionOf($field);

            if (isset($fieldDefinition['extra'])
                && isset ($fieldDefinition['extra']['purify'])) {
                $purifyType = $fieldDefinition['extra']['purify'];
                switch ($purifyType) {
                    case 'html':
                        $invoker[$field] = $this->getPurifier()->purify($value);
                        break;
                    default:
                        throw new Fisma_Zend_Exception("Undefined purification type '$purifyType' on field "
                                                . "'$field' on table '{$table->getTableName()}'");
                }
            }
        }
    }
    
    /**
     * Return the purifier instance for this class, initializing it first if necessary
     * 
     * @return HTMLPurifier Initialized instance of HTMLPurifier
     * @see http://htmlpurifier.org/live/configdoc/plain.htm
     */
    public function getPurifier() 
    {
        if (!isset(self::$_purifier)) {
            require_once('HTMLPurifier/Bootstrap.php');

            $config = HTMLPurifier_Config::createDefault();

            // Whenever the configuration is modified, the definition rev needs to be incremented.
            // This prevents HTML Purifier from using a stale cach definition
            $config->set('Cache.DefinitionImpl', null); // remove this later
            $config->set('HTML.Doctype', 'HTML 4.01 Strict'); /** @todo put the purifier into the registry */

            // Make sure to keep the following line in sync with Tiny MCE so users aren't surprised when their
            // data looks different before storage and after retreival.
            $config->set(
                'HTML.Allowed',
                'a[href],p[style],br,b,i,strong,em,span[style],ul,li,ol,table[summary],tr,th[abbr],td[abbr]'
                . ',h1,h2,h3,h4,h5,h6'
            );
            
            // Conform user submitted HTML to our doctype
            $config->set('HTML.TidyLevel', 'medium'); 

            // Turn text URLS into <a> links
            $config->set('AutoFormat.Linkify', true); 

            // Remove tags which do not contain semantic information
            $config->set('AutoFormat.RemoveEmpty', true); 

            // Do not add HTML comments for browsers that don't understand scripts
            $config->set('Output.CommentScriptContents', false); 
            
            // Restrict what types of links users can create
            $config->set('URI.AllowedSchemes', array('http','https','mailto')); 

            // Force links to use the OpenFISMA URL redirector
            $config->set('URI.Munge', '/redirect/redirect/?url=%s'); 

            self::$_purifier = new HTMLPurifier($config);
        } 
        
        return self::$_purifier;
    }
}
