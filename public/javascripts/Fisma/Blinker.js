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
 * 
 * @fileoverview A simple class that implements blink behavior
 * 
 * Blink behavior is defined as a system with two states that oscillates at some fixed frequency, such as a blinking
 * light.
 * 
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2010 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/content/license
 */

/**
 * The constructor for a blink object
 * 
 * @param int interval The interval (in milliseconds) between state changes
 * @param int cycles The number of cycles (ON->OFF or OFF->ON) to repeat before stopping
 * @param function onFunction The function called when the state goes to ON
 * @param function offFunction The function called when the state goes to OFF
 */
Fisma.Blinker = function (interval, cycles, onFunction, offFunction) {
    this.interval = interval;

    this.cycles = cycles;
    this.cyclesRemaining = cycles;

    this.onFunction = onFunction;
    this.offFunction = offFunction;
    
    // State definition: 1 => ON, 0 => OFF. Defaults to OFF.
    this.state = 0;
};

/**
 * Start blinking
 */
Fisma.Blinker.prototype.start = function () {
    this.cycle();
};

/**
 * The state transition method
 */
Fisma.Blinker.prototype.cycle = function () {
    var that = this;
    
    if (1 === this.state) {
        this.offFunction();
    } else {
        this.onFunction();        
    }

    // Toggle state
    this.state = 1 - this.state;
    
    this.cyclesRemaining--;

    if (this.cyclesRemaining > 0) {
        setTimeout(
            function () {
                that.cycle.call(that);
            },
            this.interval);
    }
};
