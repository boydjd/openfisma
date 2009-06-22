<?php

class Fisma_Format_Section {
    /** @yui document this class */
    static function startSection($title, $editableTarget = null) {
        if (isset($editableTarget)) {
            print "<div class='sectionHeader'><span class='editable' target='$editableTarget'>$title</span></div>\n"
                . "<div class='section'>";
        } else {
            print "<div class='sectionHeader'>$title</div>\n<div class='section'>";
        } 
    }
    
    static function stopSection() {
        print "<div class='clear'></div></div>\n";
    }
}