/*******************************************************************************
 *
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
 *******************************************************************************
 *
 * Configuration file for tiny_mce, configuration options may be found at the
 * following website
 * http://wiki.moxiecode.com/index.php/TinyMCE:Configuration
 *
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id: $
 *
 *******************************************************************************
 */


tinyMCE.init({
	theme : "advanced",
	mode : "textareas",
	cleanup : false,
	element_format : "html",
	plugins : "spellchecker, searchreplace, insertdatetime, print, fullscreen",
	plugin_insertdate_dateFormat : "%Y-%m-%d",
	plugin_insertdate_timeFormat : "%H:%M:%S",
	browsers : "msie,gecko,safari,opera",
	theme_advanced_buttons1 : "bold, italic, underline, |, bullist, numlist, |, outdent, indent, |, cut, copy, paste, |, undo, redo, |, spellchecker, |, search, replace, |, insertdate, inserttime, link, unlink, |, print, fullscreen",
	theme_advanced_buttons2 : "",
	theme_advanced_buttons3 : "",
	theme_advanced_toolbar_location : "top",
	theme_advanced_toolbar_align : "left",
	theme_advanced_statusbar_location : "bottom",
	theme_advanced_resizing : true,
	spellchecker_rpc_url : '/javascripts/tiny_mce/plugins/spellchecker/rpc.php',
	spellchecker_languages : "+English=en"
});
