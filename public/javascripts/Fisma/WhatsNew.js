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
 *
 * @fileoverview Utility functions
 *
 * @author    Mark Ma  <mark.ma@reyosoft.com>
 * @copyright (c) Endeavor Systems, Inc. 2012 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/content/license
 */

Fisma.WhatsNew = {
    /**
     * Make WhatsNew panel serial scroll.
     */
    panelScroll : function () {
        var panels = $('#slider .scrollContainer > div');
        var container = $('#slider .scrollContainer'); 
        $('#whats-new-wrapper').css('overflow', 'hidden');
        panels.css({
            'float' : 'left',
            'position' : 'relative'
        });

        var scroll = $('#slider .scroll').css('overflow', 'hidden');

        var scrollOptions = {
            target: scroll,
            items: panels,
            prev: 'a.previous', 
            next: 'a.next',
            axis: 'xy',        
            duration: 400    
        };

        $('#slider').serialScroll(scrollOptions);
        $.localScroll(scrollOptions);

        $('.panel:last').addClass('last-panel');
        $('.arrow:last').css('display','none');
                
        $('.next').click(function(){
            $("object[id^=newFeature]").each(function() {
                var videoEle = document.getElementById($(this).attr('id'));
                if (videoEle) {
                    videoEle.pauseVideo();
                }
            });
        });    

        $('.previous').click(function(){
            $("object[id^=newFeature]").each(function() {
            var videoEle = document.getElementById($(this).attr('id'));
                if (videoEle) {
                    videoEle.pauseVideo();
                }
            });
        });
    }
};
