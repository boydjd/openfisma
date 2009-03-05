<?php

class Format_Section {
    static function startSection($title) {
        print "<div class='sectionHeader'>$title</div>\n<div class='section'>"; 
    }
    
    static function stopSection() {
        print "</div>\n";
    }
}