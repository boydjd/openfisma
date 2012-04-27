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
 *
 * @fileoverview Client-side behavior related to the Finding module
 *
 * @author    Dale Frey <dale.frey@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2011 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/content/license
 */

Fisma.Chart = {

    // Constants
    CHART_CREATE_SUCCESS: 1,
    CHART_CREATE_FAILURE: 2,
    CHART_CREATE_EXTERNAL: 3,

    // Defaults for global chart settings definition:
    globalSettingsDefaults: {
        fadingEnabled:      false,
        usePatterns:        false,
        barShadows:         false,
        barShadowDepth:     3,
        dropShadows:        false,
        gridLines:          false,
        pointLabels:        false,
        pointLabelsOutline: false,
        showDataTable: false
    },

    // URLs to all available pattern images
    patternURLs: [
        '/images/pattern-horizontal.png',
        '/images/pattern-diamonds.png',
        '/images/pattern-backbg-whitedots.png',
        '/images/pattern-diagonal-45degree.png',
        '/images/pattern-bubbles.png',
        '/images/pattern-checkers.png',
        '/images/pattern-diagonal-135degree.png',
        '/images/pattern-diagonal-bricks.png'
    ],

    // Remember all chart paramiter objects which are drawn on the DOM within global var chartsOnDom
    chartsOnDOM: {},

    // Is this client-browser Internet Explorer?
    isIE: (window.ActiveXObject) ? true : false,

    // Class static variables
    hasHookedPostDrawSeries: false,

    /**
     * When an external source is needed, this function should handel the returned JSON request
     * The chartParamsObj object that went into Fisma.Chart.createJQChart(obj) would be the chartParamsObj here, and
     * the "value" parameter should be the returned JSON request.
     * the chartParamsObj and value objects are merged togeather based in inheritance mode and 
     * returns the return value of Fisma.Chart.createJQChart(), or false on external source failure.
     *
     * @return integer
     */
    createJQChart_asynchReturn : function (requestNumber, value, chartParamsObj) {
        // If anything (json) was returned at all...
        if (value) {

            // YAHOO.util.DataSource puts its JSON responce within value['results'][0]
            if (value.results[0]) {
                chartParamsObj = Fisma.Chart.mergeExtrnIntoParamObjectByInheritance(chartParamsObj, value);
            } else {
                Fisma.Chart.showMsgOnEmptyChart(chartParamsObj);
                throw 'Error - Chart creation failed due to data source error at ' + chartParamsObj.lastURLpull;
            }

            Fisma.Chart.showMsgOnEmptyChart(chartParamsObj);

            // validate that chart plotting data (numeric information) was returned
            if (typeof chartParamsObj.chartData === 'undefined') {
                Fisma.Chart.showMsgOnEmptyChart(chartParamsObj);
                var msg = 'Chart Error - The remote data source for chart "';
                msg += chartParamsObj.uniqueid + '" located at ' + chartParamsObj.lastURLpull;
                msg += ' did not return data to plot on a chart';
                throw msg;
            } else if (chartParamsObj.chartData.length === 0) {
                Fisma.Chart.showMsgOnEmptyChart(chartParamsObj);
            }

            // call the Fisma.Chart.createJQChart() with the chartParamsObj-object initally given to Fisma.Chart.createJQChart() and the merged responce object
            return Fisma.Chart.createJQChart(chartParamsObj);

        } else {
            Fisma.Chart.showMsgOnEmptyChart(chartParamsObj);
            throw 'Error - Chart creation failed due to data source error at ' + chartParamsObj.lastURLpull;
        }
    },

    /**
     * Creates a chart within a div by the name of chartParamsObj.uniqueid.
     * All paramiters needed to create the chart are expected to be within the chartParamsObj object.
     * This function may return before the actual creation of a chart if there is an external source.
     *
     * @return boolean
     */
    createJQChart : function (chartParamsObj) {

        // load in default values for paramiters, and replace it with any given params
        var defaultParams = {
            concatXLabel: false,
            nobackground: true,
            drawGridLines: false,
            pointLabelStyle: 'color: black; font-size: 12pt; font-weight: regular',
            pointLabelAdjustX: -3,
            pointLabelAdjustY: -7,
            AxisLabelX: '',
            AxisLabelY: '',
            DataTextAngle: -30
        };
        chartParamsObj = jQuery.extend(true, defaultParams, chartParamsObj);

        // param validation
        if (document.getElementById(chartParamsObj.uniqueid) === false) {
            throw 'createJQChart Error - The target div/uniqueid does not exists' + chartParamsObj.uniqueid;
        }

        // set chart width to chartParamsObj.width
        Fisma.Chart.setChartWidthAttribs(chartParamsObj);

        // Ensure the load spinner is visible
        Fisma.Chart.makeElementVisible(chartParamsObj.uniqueid + 'loader');

        // is the data being loaded from an external source? (Or is it all in the chartParamsObj obj?)
        if (chartParamsObj.externalSource) {

            /*
             * If it is being loaded from an external source
             *   setup a json request
             *   have the json request return to createJQChart_asynchReturn
             *   exit this function as createJQChart_asynchReturn will call this function again 
             *   with the same chartParamsObj object with chartParamsObj.externalSource taken out
            */

            document.getElementById(chartParamsObj.uniqueid).innerHTML = 'Loading chart data...';

            // note externalSource, and remove/relocate it from its place in chartParamsObj[] so 
            // it dosnt retain and cause us to loop 
            var externalSource = chartParamsObj.externalSource;
            if (!chartParamsObj.oldExternalSource) {
                chartParamsObj.oldExternalSource = chartParamsObj.externalSource;
            }
            chartParamsObj.externalSource = undefined;

            // Send data from widgets to external data source if needed7 (will load from cookies and defaults if widgets are not drawn yet)
            chartParamsObj = Fisma.Chart.buildExternalSourceParams(chartParamsObj);
            externalSource += String(chartParamsObj.externalSourceParams).replace(/ /g, '%20');
            chartParamsObj.lastURLpull = externalSource;

            var myDataSource = new YAHOO.util.DataSource(externalSource);
            myDataSource.responseType = YAHOO.util.DataSource.TYPE_JSON;
            myDataSource.responseSchema = {resultsList: "chart"};

            var callback1 = {
                success : Fisma.Chart.createJQChart_asynchReturn,
                failure : Fisma.Chart.createJQChart_asynchReturn,
                argument: chartParamsObj
            };
            myDataSource.sendRequest("", callback1);

            return Fisma.Chart.CHART_CREATE_EXTERNAL;
        }

        // clear the chart area
        document.getElementById(chartParamsObj.uniqueid).innerHTML = '';
        document.getElementById(chartParamsObj.uniqueid).className = '';
        document.getElementById(chartParamsObj.uniqueid + 'toplegend').innerHTML = '';

        // handel aliases and short-cut vars
        if (typeof chartParamsObj.barMargin !== 'undefined') {
            chartParamsObj = jQuery.extend(true, chartParamsObj, {
                'seriesDefaults' : {
                    'rendererOptions' : {
                        'barMargin' : chartParamsObj.barMargin
                    }
                }
            });
            chartParamsObj.barMargin = undefined;
        }
        if (typeof chartParamsObj.legendLocation !== 'undefined') {
            chartParamsObj = jQuery.extend(true, chartParamsObj, {
                'legend' : {
                    'location' : chartParamsObj.legendLocation
                }
            });
            chartParamsObj.legendLocation = undefined;
        }
        if (typeof chartParamsObj.legendRowCount !== 'undefined') {
            chartParamsObj = jQuery.extend(true, chartParamsObj, {
                'legend' : {
                    'rendererOptions' : {
                        'numberRows' : chartParamsObj.legendRowCount
                    }
                }
            });
            chartParamsObj.legendRowCount = undefined;
        }

        // make sure the numbers to be plotted in chartParamsObj.chartData are infact numbers and not an array of strings of numbers
        chartParamsObj.chartData = Fisma.Chart.forceIntegerArray(chartParamsObj.chartData);

        // hide the loading spinner and show the canvas target
        document.getElementById(chartParamsObj.uniqueid + 'holder').style.display = '';
        Fisma.Chart.makeElementInvisible(chartParamsObj.uniqueid + 'holder');
        document.getElementById(chartParamsObj.uniqueid + 'loader').style.position = 'absolute';
        document.getElementById(chartParamsObj.uniqueid + 'loader').finnishFadeCallback = new Function ("Fisma.Chart.fadeIn('" + chartParamsObj.uniqueid + "holder', 500);");
        Fisma.Chart.fadeOut(chartParamsObj.uniqueid + 'loader', 500);

        // now that we have the chartParamsObj.chartData, do we need to make the chart larger and scrollable?
        Fisma.Chart.setChartWidthAttribs(chartParamsObj);

        // Store this charts paramiter object into the global variable chartsOnDOM, so it can be redrawn
        // This must be done before the next switch block that translates some data within the chartParamsObj object for jqPlot
        Fisma.Chart.chartsOnDOM[chartParamsObj.uniqueid] = jQuery.extend(true, {}, chartParamsObj);

        // call the correct function based on chartType, or state there will be no chart created
        var rtn = Fisma.Chart.CHART_CREATE_FAILURE;
        if (!Fisma.Chart.chartIsEmpty(chartParamsObj)) {

            switch (chartParamsObj.chartType) {
            case 'stackedbar':
                chartParamsObj.varyBarColor = false;
                            if (typeof chartParamsObj.showlegend === 'undefined') { chartParamsObj.showlegend = true; }
                rtn = Fisma.Chart.createChartStackedBar(chartParamsObj);
                break;
            case 'bar':

                // Is this a simple-bar chart (not-stacked-bar) with multiple series?
                if (typeof chartParamsObj.chartData[0] === 'object') {

                    // the chartData is already a multi dimensional array, and the chartType is bar, not stacked bar. So we assume it is a simple-bar chart with multi series
                    // thus we will leave the chartData array as is (as opposed to forcing it to a 2 dim array, and claming it to be a stacked bar chart with no other layers of bars (a lazy but functional of creating a regular bar charts from the stacked-bar chart renderer)

                    chartParamsObj.varyBarColor = false;
                    chartParamsObj.showlegend = true;

                } else {
                    chartParamsObj.chartData = [chartParamsObj.chartData];  // force to 2 dimensional array
                    chartParamsObj.links = [chartParamsObj.links];
                    chartParamsObj.varyBarColor = true;
                    chartParamsObj.showlegend = false;
                }

                chartParamsObj.stackSeries = false;
                rtn = Fisma.Chart.createChartStackedBar(chartParamsObj);
                break;

            case 'line':
                rtn = Fisma.Chart.createChartStackedLine(chartParamsObj);
                break;
            case 'stackedline':
                rtn = Fisma.Chart.createChartStackedLine(chartParamsObj);
                break;
            case 'pie':
                chartParamsObj.links = [chartParamsObj.links];
                rtn = Fisma.Chart.createChartPie(chartParamsObj);
                break;
            default:
                throw 'createJQChart Error - chartType is invalid (' + chartParamsObj.chartType + ')';
            }
        }

        // chart tweeking external to the jqPlot library
        Fisma.Chart.removeOverlappingPointLabels(chartParamsObj);
        Fisma.Chart.applyChartBackground(chartParamsObj);
        Fisma.Chart.applyChartWidgets(chartParamsObj);
        Fisma.Chart.createChartThreatLegend(chartParamsObj);
        Fisma.Chart.applyChartBorders(chartParamsObj);
        Fisma.Chart.globalSettingRefreshUi(chartParamsObj);
        Fisma.Chart.showMsgOnEmptyChart(chartParamsObj);
        Fisma.Chart.getTableFromChartData(chartParamsObj);
        Fisma.Chart.setTitle(chartParamsObj);
        Fisma.Chart.placeCanvasesInDivs(chartParamsObj);
        
        return rtn;
    },

    /**
     * Takes a chartParamsObj and merges content of 
     * externResponse-object into it based in the inheritance mode
     * set in externResponse.
     * Expects: A (chart-)object generated from Fisma_Chart->export('array')
     *
     * @param object
     * @return void
     * 
    */
    mergeExtrnIntoParamObjectByInheritance : function (chartParamsObj, externResponse) {
        var joinedParam = {};

        // Is there an inheritance mode? 
        if (externResponse.results[0].inheritCtl) {
            if (externResponse.results[0].inheritCtl === 'minimal') {
                // Inheritance mode set to minimal, retain certain attribs and merge
                joinedParam = externResponse.results[0];
                joinedParam.width = chartParamsObj.width;
                joinedParam.height = chartParamsObj.height;
                joinedParam.uniqueid = chartParamsObj.uniqueid;
                joinedParam.externalSource = chartParamsObj.externalSource;
                joinedParam.oldExternalSource = chartParamsObj.oldExternalSource;
                joinedParam.widgets = chartParamsObj.widgets;
            } else if (externResponse.results[0].inheritCtl === 'none') {
                // Inheritance mode set to none, replace the joinedParam object
                joinedParam = externResponse.results[0];
            } else {
                throw 'Error - Unknown chart inheritance mode';
            }
        } else {
            // No inheritance mode, by default, merge everything
            joinedParam = jQuery.extend(true, chartParamsObj, externResponse.results[0],true);
        }

        return joinedParam;
    },

     /**
      * Fires the jqPlot library, and creates a pie chart
      * based on input chart object
      *
      * Expects: A (chart-)object generated from Fisma_Chart->export('array')
      *
      * @param object
      * @return void
     */
    createChartPie : function (chartParamsObj) {
        var x = 0;
        var dataSet = [];
        var usedLabelsPie = chartParamsObj.chartDataText;

        for (x = 0; x < chartParamsObj.chartData.length; x++) {
            chartParamsObj.chartDataText[x] += ' (' + chartParamsObj.chartData[x]  + ')';
            dataSet[dataSet.length] = [chartParamsObj.chartDataText[x], chartParamsObj.chartData[x]];
        }

        var jPlotParamObj = {
            seriesColors: chartParamsObj.colors,
            grid: {
                drawBorder: false,
                drawGridlines: false,
                shadow: false
            },
            axes: {
                xaxis: {
                    tickOptions: {
                        angle: chartParamsObj.DataTextAngle,
                        fontSize: '10pt',
                        formatString: '%.0f'
                    }
                },
                yaxis: {
                    tickOptions: {
                        formatString: '%.0f'
                    }
                }

            },
            seriesDefaults: {
                renderer:$.jqplot.PieRenderer,
                rendererOptions: {
                    sliceMargin: 0,
                    showDataLabels: true,
                    shadowAlpha: 0.15,
                    shadowOffset: 0,
                    lineLabels: true,
                    lineLabelsLineColor: '#777',
                    diameter: chartParamsObj.height * 0.55,
                    dataLabelFormatString: "%d%"
                }
            },
            legend: {
                location: 's',
                show: true,
                rendererOptions: {
                    numberRows: 2 
                }
            },
            highlighter: {
                show: false
            }
        };

        jPlotParamObj.seriesDefaults.renderer.prototype.startAngle = 0;

        // bug killer (for IE7) - state the height for the container div for emulated excanvas
        $("[id="+chartParamsObj.uniqueid+"]").css('height', chartParamsObj.height);

        // merge any jqPlot direct chartParamsObj-arguments into jPlotParamObj from chartParamsObj
        jPlotParamObj = jQuery.extend(true, jPlotParamObj, chartParamsObj);

        // dont show title on canvas, (it must be above the threat-level-legend if it exists)
        jPlotParamObj.title = null;

        var plot1 = $.jqplot(chartParamsObj.uniqueid, [dataSet], jPlotParamObj);

        // create an event handeling function that calls chartClickEvent while preserving the parm object
        var EvntHandler = new Function ("ev", "seriesIndex", "pointIndex", "data", "var thisChartParamObj = " + YAHOO.lang.JSON.stringify(chartParamsObj) + "; Fisma.Chart.chartClickEvent(ev, seriesIndex, pointIndex, data, thisChartParamObj);" );

        // hook highlight event for tooltips
        $('#' + chartParamsObj.uniqueid).bind('jqplotDataHighlight', 
            function (ev, seriesIndex, pointIndex, data) {
                Fisma.Chart.chartHighlightEvent(jPlotParamObj, ev, seriesIndex, pointIndex, data);
            }
        );

        // hook un-highlight event for tooltips
        $('#' + chartParamsObj.uniqueid).bind('jqplotDataUnhighlight', 
            function (ev, seriesIndex, pointIndex, data) {
                Fisma.Chart.hideAllChartTooltips();
            }
        );

        /* Hook the onMouseMove even on the foward most event canvas for the pie tooltip
           The reason for this is because the highlight event wont return the mouse X/Y location */
        // Get the container for canvases for this chart
        var chartCanvasContainer = YAHOO.util.Dom.get(chartParamsObj.uniqueid);
        // Find the foward most (event) canvas
        var canvases = $(chartCanvasContainer).find('canvas').filter(
            function() {
                return $(this)[0].className === 'jqplot-event-canvas';
            }
        );
        // Hook onMouseMove on it
        canvases[0].onmousemove = function (e) {
            Fisma.Chart.chartMouseMovePieEvent(chartParamsObj, e, canvases[0]);
        };
        
        // Hook onMouseOut of this canvas (to ensure tooltips are gone when so)
        canvases[0].onmouseout = function (e) {
            Fisma.Chart.hideAllChartTooltips();
        };

        // use the created function as the click-event-handeler
        $('#' + chartParamsObj.uniqueid).bind('jqplotDataClick', EvntHandler);

        return Fisma.Chart.CHART_CREATE_SUCCESS;
    },

     /**
      * Fires the jqPlot library, and creates a stacked
      * bar chart based on input chart object
      *
      * Expects: A (chart-)object generated from Fisma_Chart->export('array')
      *
      * @param object
      * @return CHART_CREATE_SUCCESS|CHART_CREATE_FAILURE|CHART_CREATE_EXTERNAL
     */
    createChartStackedBar : function (chartParamsObj) {
        var x = 0; var y = 0;
        var thisSum = 0;
        var maxSumOfAll = 0;
        var chartCeilingValue = 0;

        for (x = 0; x < chartParamsObj.chartDataText.length; x++) {

            thisSum = 0;

            for (y = 0; y < chartParamsObj.chartData.length; y++) {
                thisSum += chartParamsObj.chartData[y][x];
            }

            if (thisSum > maxSumOfAll) { maxSumOfAll = thisSum; }

            if (chartParamsObj.concatXLabel === true) {
                chartParamsObj.chartDataText[x] += ' (' + thisSum  + ')';
            }
        }

        var seriesParam = [];
        if (chartParamsObj.chartLayerText) {
            for (x = 0; x < chartParamsObj.chartLayerText.length; x++) {
                seriesParam[x] = {label: chartParamsObj.chartLayerText[x]};
            }
        }

        // Make sure the Y-axis (row labels) are not offset by the formatter string rounding their values...
        // (make the top most row label divisible by 5)
        chartCeilingValue = Math.ceil(maxSumOfAll / 5) * 5;

        // Force Y-axis row labels to be divisible by 5
        var yAxisTicks = [];
        yAxisTicks[0] = 0;
        yAxisTicks[1] = (chartCeilingValue/5);
        yAxisTicks[2] = (chartCeilingValue/5) * 2;
        yAxisTicks[3] = (chartCeilingValue/5) * 3;
        yAxisTicks[4] = (chartCeilingValue/5) * 4;
        yAxisTicks[5] = (chartCeilingValue/5) * 5;

        $.jqplot.config.enablePlugins = true;

        var jPlotParamObj = {
            seriesColors: chartParamsObj.colors,
            stackSeries: true,
            series: seriesParam,
            seriesDefaults: {
                renderer: $.jqplot.BarRenderer,
                rendererOptions: {
                    barWidth: 35,
                    showDataLabels: true,
                    varyBarColor: chartParamsObj.varyBarColor,
                    shadowAlpha: 0.15,
                    shadowOffset: 0
                },
                pointLabels: {
                    show: false,
                    location: 's',
                    hideZeros: true
                }
            },
            axesDefaults: {
                tickRenderer: $.jqplot.CanvasAxisTickRenderer,
                borderWidth: 0,
                labelOptions: {
                    enableFontSupport: true,
                    fontFamily: 'arial, helvetica, clean, sans-serif',
                    fontSize: '12pt',
                    textColor: '#000000'
                }
            },
            axes: {
                xaxis: {
                    label: chartParamsObj.AxisLabelX,
                    labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
                    renderer: $.jqplot.CategoryAxisRenderer,
                    ticks: chartParamsObj.chartDataText,
                    tickOptions: {
                        angle: chartParamsObj.DataTextAngle,
                        fontFamily: 'arial, helvetica, clean, sans-serif',
                        fontSize: '10pt',
                        textColor: '#000000'
                    }
                },
                yaxis: {
                    label: chartParamsObj.AxisLabelY,
                    labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
                    min: 0,
                    max: chartCeilingValue,
                    autoscale: true,
                    ticks: yAxisTicks,
                    tickOptions: {
                        formatString: '%.0f',
                        fontFamily: 'arial, helvetica, clean, sans-serif',
                        fontSize: '10pt',
                        textColor: '#000000'
                    }
                }

            },
            highlighter: {
                show: true,
                showMarker:false,
                showTooltip: true,
                tooltipAxes: 'xy',
                yvalues: 1,
                tooltipLocation: 'e',
                formatString: "-"
            },
            grid: {
                gridLineWidth: 0,
                shadow: false,
                borderWidth: 1,
                gridLineColor: '#FFFFFF',
                background: 'transparent',
                drawGridLines: chartParamsObj.drawGridLines,
                show: chartParamsObj.drawGridLines
                },
            legend: {
                show: chartParamsObj.showlegend,
                rendererOptions: {
                    numberRows: 2 
                },
                location: 'nw'
            }
        };

        // bug killer - The canvas object for IE does not understand what transparency is...
        if (Fisma.Chart.isIE) {
            jPlotParamObj.grid.background = '#FFFFFF';
        }

        // bug killer (for IE7) - state the height for the container div for emulated excanvas
        $("[id="+chartParamsObj.uniqueid+"]").css('height', chartParamsObj.height);

        // merge any jqPlot direct chartParamsObj-arguments into jPlotParamObj from chartParamsObj
        jPlotParamObj = jQuery.extend(true, jPlotParamObj, chartParamsObj);

        // override any jqPlot direct chartParamsObj-arguments based on globals setting from cookies (set by user)
        jPlotParamObj = Fisma.Chart.alterChartByGlobals(jPlotParamObj);

        // dont show title on canvas, (it must be above the threat-level-legend if it exists)
        jPlotParamObj.title = null;

        // implement hook nessesary for patterns
        Fisma.Chart.hookPostDrawSeriesHooks();

        // trigger jqPlot lib to draw the chart
        var plot1 = $.jqplot(chartParamsObj.uniqueid, chartParamsObj.chartData, jPlotParamObj);

        // hook click events for navigation/"drill-down"
        var EvntHandler = new Function ("ev", "seriesIndex", "pointIndex", "data", "var thisChartParamObj = " + YAHOO.lang.JSON.stringify(chartParamsObj) + "; Fisma.Chart.chartClickEvent(ev, seriesIndex, pointIndex, data, thisChartParamObj);" );
        $('#' + chartParamsObj.uniqueid).bind('jqplotDataClick', EvntHandler);

        // hook highlight event for tooltips
        $('#' + chartParamsObj.uniqueid).bind('jqplotDataHighlight', 
            function (ev, seriesIndex, pointIndex, data) {
                Fisma.Chart.chartHighlightEvent(jPlotParamObj, ev, seriesIndex, pointIndex, data);
            }
        );

        // Get the container for canvases for this chart
        var chartCanvasContainer = YAHOO.util.Dom.get(chartParamsObj.uniqueid);

        // Find the foward most (event) canvas
        var canvases = $(chartCanvasContainer).find('canvas').filter(
            function() {
                return $(this)[0].className === 'jqplot-event-canvas';
            }
        );

        // Hook onMouseOut of this canvas (to ensure tooltips are gone when so)
        canvases[0].onmouseout = function (e) {
            Fisma.Chart.hideAllChartTooltips();
        };

        // Hook the mouse events for column labels (make tooltips show on label hovering)
        var columnLabelObjs = Fisma.Chart.getElementsByClassWithinObj('jqplot-xaxis-tick', 'canvas', chartParamsObj.uniqueid);

        var columnLabelMouseOver = function (ev) {
                var forceStyle = {
                    'left': this.parentNode.offsetLeft + 'px',
                    'bottom': (this.parentNode.parentNode.offsetHeight + 10) + 'px',
                    'top': ''
                };
                Fisma.Chart.chartHighlightEvent(chartParamsObj, ev, 0, this.columnNumber, null, forceStyle);
        };

        var columnLabelMouseOut = function (ev) {
            Fisma.Chart.hideAllChartTooltips();
        };

        for (x = 0; x < columnLabelObjs.length; x++) {

            // note which column number this object represents
            columnLabelObjs[x].columnNumber = x;

            // hook onMouseOver to show tooltip
            columnLabelObjs[x].onmouseover = columnLabelMouseOver;

            // hook onMouseOut to hide tooltip
            columnLabelObjs[x].onmouseout = columnLabelMouseOut;

            // bring this label to front, otherwise onmouseover will never fire
            columnLabelObjs[x].style.zIndex = 1;
        }

        Fisma.Chart.removeDecFromPointLabels(chartParamsObj);

        return Fisma.Chart.CHART_CREATE_SUCCESS;
    },

    /**
     * Gets the tooltip div created for the chart 
     *
     * Expects: A (chart-)object generated from Fisma_Chart->export('array')
     *
     * @param object
     * @return object[]
     */
    getTooltipObjOfChart : function (chartParamsObj) {
        return Fisma.Chart.getElementsByClassWithinObj('jqplot-highlighter-tooltip', 'div', chartParamsObj.uniqueid)[0];
    },

    getElementsByClassWithinObj : function (className, objectType, withinDiv) {

        // Is WithinDiv given? If not, assume we are looking from the document.body and down
        if (withinDiv === null || withinDiv === '') {
            withinDiv = document.body;
        }

        // Is withinDiv an object, or object ID? - make it an object
        if (typeof withinDiv !== 'object') {
            withinDiv = document.getElementById(withinDiv);
        }

        // Find the div that has the jqplot-highlighter-tooltip class
        var objsFound = $(withinDiv).find(objectType).filter(
            function() {
                return $(this)[0].className.indexOf(className) !== -1;
            }
        );

        // Return results
        return objsFound;
    },

     /**
      * Fires the jqPlot library, and creates a stacked
      * line chart based on input chart object
      *
      * Expects: A (chart-)object generated from Fisma_Chart->export('array')
      *
      * @param object
      * @return CHART_CREATE_SUCCESS|CHART_CREATE_FAILURE|CHART_CREATE_EXTERNAL
     */
    createChartStackedLine : function (chartParamsObj) {
        var x = 0; var y = 0;
        var thisSum = 0;

        for (x = 0; x < chartParamsObj.chartDataText.length; x++) {
            thisSum = 0;

            for (y = 0; y < ['chartData'].length; y++) {
                thisSum += ['chartData'][y][x];
            }

            chartParamsObj.chartDataText[x] += ' (' + thisSum  + ')';
        }

        var plot1 = $.jqplot(chartParamsObj.uniqueid, chartParamsObj.chartData, {
            seriesColors: ["#F4FA58", "#FAAC58","#FA5858"],
            series: [{label: 'Open Findings', lineWidth:4, markerOptions: {style:'square'}}, {label: 'Closed Findings', lineWidth:4, markerOptions: {style:'square'}}, {lineWidth:4, markerOptions: {style:'square'}}],
            seriesDefaults: {
                fill:false,
                showMarker: true,
                showLine: true
            },
            axes: {
                xaxis: {
                    renderer:$.jqplot.CategoryAxisRenderer,
                    ticks:chartParamsObj.chartDataText
                },
                yaxis: {
                    min: 0
                }
            },
            highlighter: { show: false },
            legend: {
                        show: true,
                        rendererOptions: {
                            numberRows: 2 
                        },
                        location: 'nw'
                    }
        });

        return Fisma.Chart.CHART_CREATE_SUCCESS;
    },

    /**
     * Setup the jqPlot library to hook its draw event. Upon postDrawSeriesHooks, create chart patterns if desiered.
     * This function will return and do nothing if it was called before, this is to prevent multiple hooks being
     * placed into jqPlot.
     *
     * @return void
     */
    hookPostDrawSeriesHooks : function ()
    {
        if (Fisma.Chart.hasHookedPostDrawSeries !== false) {
            // we only push one hook
            return;
        }

        // Note that we have push this hook
        Fisma.Chart.hasHookedPostDrawSeries = true;

        // hook jqPlot's postDrawSeriesHook to draw patterns should the user want this options
        $.jqplot.postDrawSeriesHooks.push(function (canvasObj) {

                /* This is called after everytime a layer on a stacked bar chart is drawn (either the high,
                   moderate, or low), so each time this is called, it is being called for a different color
                   of chartParamsObj.colors */

                // Is the checkbox to use patterns in place of colors checked?
                var usePatterns = Fisma.Chart.getGlobalSetting('usePatterns');
                if (usePatterns === 'false') {
                    return;
                }

                // This code only works with bar charts
                if (this._barPoints === undefined) {
                    return;
                }

                // Because this is a nested function, chartParamsObj may or may not be the true related
                // instance to this trigger (it isnt in Firefox). Refreash this variable.
                var uniqueId;
                var chartParamsObj;
                if (this.canvas._elem.context.parentNode.id !== undefined) {
                    uniqueId = this.canvas._elem.context.parentNode.id;
                    chartParamsObj = Fisma.Chart.chartsOnDOM.uniqueId;
                }

                /* chartParamsObj.patternCounter will be used (incremented for each call of this function) to
                   keep track of which pattern should be used next (in Fisma.Chart.patternURLs). */
                // Instance this variable if needed
                if (Fisma.Chart.chartsOnDOM.uniqueId.patternCounter === undefined) {
                    Fisma.Chart.chartsOnDOM.uniqueId.patternCounter = 0;
                }

                // Decide which pattern to use in place of the color for this hooked layer/series, and increment for next hook
                var myPatternId = Fisma.Chart.chartsOnDOM.uniqueId.patternCounter;
                var myPatternURL = Fisma.Chart.patternURLs.myPatternId;
                Fisma.Chart.chartsOnDOM.uniqueId.patternCounter++;

                // For each bar drawn of this layer/series/color
                var bar;
                var imageOnloadEvent = function (image) {
                    // create pattern
                    var ptrn = canvasObj.createPattern(image, 'repeat');
                    canvasObj.fillStyle = ptrn;
                    canvasObj.fillRect(this.barRect.x, this.barRect.y, this.barRect.w, this.barRect.h);
                    canvasObj.restore();
                };

                for (bar = 0; bar < this._barPoints.length; bar++) {

                    var img = new Image();

                    // because img.onload will fire within this function, store the bar information on the object
                    img.barRect = {
                        'x': this._barPoints[bar][0][0],
                        'y': this._barPoints[bar][0][1],
                        'w': this._barPoints[bar][2][0] - this._barPoints[bar][0][0],   // width
                        'h': this._barPoints[bar][1][1] - this._barPoints[bar][3][1]    // height
                    };

                    img.onload = imageOnloadEvent(img);

                    // load pattern
                    img.src = myPatternURL;
                }

                return;
            }
        );

        return;
    },

    /**
     * Creates the red-orange-yellow threat-legend that shows above charts
     * The generated HTML code should go into the div with the id of the
     * chart's uniqueId + "toplegend"
     *
     * @return boolean/integer
     */
    createChartThreatLegend : function (chartParamsObj) {
        if (chartParamsObj.showThreatLegend && !Fisma.Chart.chartIsEmpty(chartParamsObj)) {
            if (chartParamsObj.showThreatLegend === true) {

                // Is a width given for the width of the legend? OR should we assume 100%?
                var threatLegendWidth = '100%';
                if (chartParamsObj.threatLegendWidth) {
                    threatLegendWidth = chartParamsObj.threatLegendWidth;
                }

                var cell;

                // Tabel to hold all colored boxes and labels
                var threatTable = document.createElement("table");
                threatTable.width = threatLegendWidth;
                threatTable.className = 'jqplot-threat-legend';
                var tblBody = document.createElement("tbody");
                var row = document.createElement("tr");

                cell = document.createElement("td");
                cell.style.textAlign = 'center';
                cell.style.fontWeight = 'bold';
                cell.width = '40%';
                var textLabel = document.createTextNode('');
                cell.appendChild(textLabel);
                row.appendChild(cell);

                var colorToUse;
                var usePatterns;
                var thisLayerText;
                var layerIndex;
                for (layerIndex in chartParamsObj.chartLayerText) {
                    cell = document.createElement("td");
                    cell.width = '20%';

                    // Are we using colors, or patterns?
                    usePatterns = Fisma.Chart.getGlobalSetting('usePatterns');
                    if (usePatterns === 'true') {
                        colorToUse = Fisma.Chart.patternURLs[layerIndex];
                    } else {
                        colorToUse = chartParamsObj.colors[layerIndex];
                        colorToUse = colorToUse.replace('#', '');
                    }

                    thisLayerText = chartParamsObj.chartLayerText[layerIndex];

                    cell.appendChild(Fisma.Chart.createThreatLegendSingleColor(colorToUse, thisLayerText));

                    row.appendChild(cell);
                }

                // close and post table on DOM
                tblBody.appendChild(row);
                threatTable.appendChild(tblBody);
                var thisChartId = chartParamsObj.uniqueid;
                var topLegendOnDOM = document.getElementById(thisChartId + 'toplegend');
                topLegendOnDOM.appendChild(threatTable);
                topLegendOnDOM.style.display = 'block';
            }
        }
    },

    /**
     * Creates a single color (i.e. red/orange/yellow) tabels to be added 
     * into the threat-legend that shows above charts
     *
     * @return table
     */
    createThreatLegendSingleColor : function (blockColor, textLabel) {

        var colorBlockTbl = document.createElement("table");
        var colorBody = document.createElement("tbody");
        var colorRow = document.createElement("tr");

        var colorCell;

        // Create the colored box or pattern
        colorCell = document.createElement("td");
        if (blockColor.indexOf('/') === -1) {                   // is this a URL, or color code?
            colorCell.style.backgroundColor = '#' + blockColor;  // its a color
        } else {                                                // its a URL
            colorCell.style.backgroundImage = 'url(' + blockColor + ')';
        }
        colorCell.width = '15px';
        colorRow.appendChild(colorCell);

        // Forced space between colored box and label
        colorCell = document.createElement("td");
        colorCell.width = '3px';
        colorRow.appendChild(colorCell);

        // Apply label
        colorCell = document.createElement("td");
        colorCell.style.fontSize = '12px';
        var textLabelObj = document.createTextNode('   ' + textLabel);
        colorCell.appendChild(textLabelObj);
        colorRow.appendChild(colorCell);

        colorBody.appendChild(colorRow);
        colorBlockTbl.appendChild(colorBody);
        return colorBlockTbl;    
    },

    /**
     * Event handler for the hilight event of charts. Upon hilighting is when the chart tooltip contents should
     * be updated, and shown if not already.
     *
     * @return void
     */
    chartHighlightEvent : function (chartParamsObj, ev, seriesIndex, pointIndex, data, forceTooltipStyle)
    {
        // Ensure all other tooltips are hidden 
        Fisma.Chart.hideAllChartTooltips();

        var toolTipObj = Fisma.Chart.getTooltipObjOfChart(chartParamsObj);
        var defaultTooltip;
        var customTooltip;

        /* The chartParamsObj.tooltip may have one of two structures -
           either an array (of columns)
           or an array (of layers) of arrays (of columns)   */
        if (typeof chartParamsObj.tooltip[0] !== 'object') {
            customTooltip = chartParamsObj.tooltip[pointIndex];
        } else {
            customTooltip = chartParamsObj.tooltip[seriesIndex][pointIndex];
        }

        // the same structure for the above applies to chartParamsObj.chartData
        if (typeof chartParamsObj.chartData[seriesIndex] !== 'object') {
            defaultTooltip = chartParamsObj.chartData[pointIndex];
        } else {
            defaultTooltip = chartParamsObj.chartData[seriesIndex][pointIndex];
        }

        // decide tooltip HTML
        var ttHtml = '<span class="chartToolTipText">';
        if (customTooltip !== '' && customTooltip !== undefined) {
            ttHtml += customTooltip;
        } else {
            ttHtml += defaultTooltip;
        }
        ttHtml += '</span>';

        // apply variables
        ttHtml = ttHtml.replace('#percent#', Fisma.Chart.getPercentage(chartParamsObj, seriesIndex, pointIndex));
        ttHtml = ttHtml.replace('#columnName#', chartParamsObj.chartDataText[pointIndex]);
        if (data !== undefined && data !== null) {
            ttHtml = ttHtml.replace('#count#', data[1]);
        }
        if (chartParamsObj.chartLayerText) {
            ttHtml = ttHtml.replace('#layerName#', chartParamsObj.chartLayerText[seriesIndex]);
        }
        if (ttHtml.indexOf('#columnReport#') !== -1) {
            ttHtml = ttHtml.replace('#columnReport#', Fisma.Chart.getColumnReport(chartParamsObj, seriesIndex, pointIndex));
        }

        // apply to tooltip
        toolTipObj.innerHTML = ttHtml;
        toolTipObj.style.display = 'block';

        // remove the .bottom and .right property - it should only exist if it is in forceTooltipStyle
        toolTipObj.style.bottom = '';
        toolTipObj.style.right = '';

        // IE7 (7 only) has a problem where it streatches the tooltip div
        if (YAHOO.env.ua.ie === 7) {
            toolTipObj.style.width = '80px';
        }

        // apply tooltip style if requested (this makes it possible to relocate the tooltip)
        if (forceTooltipStyle !== undefined && forceTooltipStyle !== null) {
            var key;
            for(key in forceTooltipStyle) {
                toolTipObj.style[key] = forceTooltipStyle[key];
            }
        }

        /* By this line, we are done for bar charts. The tooltip will auto show itself
           jqPlot Pie charts however, do not support the tooltip plugin, we need to make our own */
        if (chartParamsObj.chartType === 'pie') {

            toolTipObj.style.display = 'none';

            var pieTooltip = document.getElementById(chartParamsObj.uniqueid + 'pieTooltip');
            pieTooltip.style.display = 'block';
            pieTooltip.innerHTML = ttHtml;

            // IE7 (7 only) has a problem where it streatches the tooltip div
            if (YAHOO.env.ua.ie === 7) {
                pieTooltip.style.width = '200px';
            }
        }
    },

    /**
     * Returns HTML code to be injected into a tooltip. When rendered in a browser, is a human readable
     * report showing information about the given column.
     *
     * @return string
     */
    getColumnReport : function (chartParamsObj, layerIndex, columnIndex)
    {
        var report = '';
        var total = 0;
        var i;

        for (i = 0; i < chartParamsObj.chartLayerText.length; i++) {
            report += chartParamsObj.chartLayerText[i] + ": " + chartParamsObj.chartData[i][columnIndex] + '<br/>';
            total += chartParamsObj.chartData[i][columnIndex];
        }

        report += 'Total: ' + total;
        return report;
    },

    /**
     * For pie-slice (or bar) referenced by pointIndex (which column/slice), returns the
     * percentage that slice represents.
     *
     * @return int
     */
    getPercentage: function (chartParamsObj, seriesIndex, pointIndex)
    {
        if (typeof chartParamsObj.chartData[0] === 'object') {

            // then this is a stacked bar chart
            return 0;

        } else {

            // This is a basic-bar or pie chart

            var total = 0;
            var i;
            for (i = 0; i < chartParamsObj.chartData.length; i++) {
                total += chartParamsObj.chartData[i];
            }

            var percentage = chartParamsObj.chartData[pointIndex] / total;
            return Math.round(percentage * 100);
        }
    },

    /**
     * Hides all chart tooltips throughout the entire DOM for all charts
     *
     * @return void
     */
    hideAllChartTooltips : function ()
    {
        // Find all divs that are chart tooltips
        var tooltips = $(document.body).find('div').filter(
            function() {
                return $(this)[0].className.indexOf('jqplot-highlighter-tooltip') !== -1;
            }
        );

        // Hide them all
        var x;
        for (x = 0; x < tooltips.length; x++) {
            tooltips[x].style.display = 'none';
        }
    },

    /**
     * The event handler for a mouse movement on pie charts. This function should move the tooltip along 
     * with the mouse
     *
     * @return void
     */
    chartMouseMovePieEvent : function (chartParamsObj, e, eventCanvas) {
        var pieTooltip = document.getElementById(chartParamsObj.uniqueid + 'pieTooltip');

        var offsetX; var offsetY;
        if (window.event !== undefined) {
            // We are in IE
            offsetX = window.event.offsetX;
            offsetY = window.event.offsetY;
            offsetY += 65;
        } else if (e.offsetX !== undefined) {
            // We are not in IE nor FireFox
            offsetX = e.offsetX;
            offsetY = e.offsetY;
            offsetY += 45;
        } else if (e.layerX !== undefined) {
            // We are in Firefox
            offsetX = e.layerX;
            offsetY = e.layerY;
            offsetY += 45;
        } else {
            // We are in a browser that clearly dosnt like standards... oh wait, none of them do
            offsetX = 0;
            offsetY = 0;
        }

        pieTooltip.style.left = (offsetX + eventCanvas.offsetLeft) + 'px';
        pieTooltip.style.top = offsetY + 'px';
    },

    /**
     * The event handeler for a chart click. This function should determine if the user needs to be
     * navigated to another page or not
     *
     * @return void
     */
    chartClickEvent : function (ev, seriesIndex, pointIndex, data, paramObj)
    {
        var theLink = false;
        if (paramObj.links) {
            if (typeof paramObj.links === 'string') {
                theLink = paramObj.links;
            } else {
                if (paramObj.links[seriesIndex]) {
                    if (typeof paramObj.links[seriesIndex] === "object") {
                        theLink = paramObj.links[seriesIndex][pointIndex];
                    } else {
                        theLink = paramObj.links[seriesIndex];
                    }
                }
            }
        }

        // bail on blank link or undefine link
        if (theLink === '' || YAHOO.lang.isUndefined(theLink)) {
            return;
        }

        // Does the link contain a variable?
        if (theLink !== false) {
            theLink = String(theLink);
            if (theLink.indexOf('#ColumnLabel#') !== -1) {
                theLink = theLink.replace('#ColumnLabel#', encodeURIComponent(paramObj.chartDataText[pointIndex]));
            } else {
                theLink = window.escape(theLink);
                theLink = theLink.replace('%3F', '?');
                theLink = theLink.replace('%3D', '=');
            }
        }

        if (paramObj.linksdebug === true) {
            var msg = "You clicked on layer " + seriesIndex + ", in column " + pointIndex + ", which has the data of " + data[1] + "\n";
            msg += "The link information for this element should be stored as a string in chartParamData['links'], or as a string in chartParamData['links'][" + seriesIndex + "][" + pointIndex + "]\n";
            if (theLink !== false) { msg += "The link with this element is " + theLink; }
            Fisma.Util.showAlertDialog(msg);
        } else {

            // We are not in link-debug mode, navigate if there is a link
            if (theLink !== false && theLink !== 'false' && String(theLink) !== 'null') {
                document.location = theLink;
            }

        }
    },

    /**
     * Converts an array from strings to integers, for example;
     * ["1", 2, "3", 4] would become [1, 2, 3, 4]
     * This is a bug killer for external source plotting data as
     * the jqPlot lib expects integers, and JSON may not always 
     * be encoded that way
     *
     * @return array
     */
    forceIntegerArray : function (inptArray) {
        var x = 0;
        for (x = 0; x < inptArray.length; x++) {
            if (typeof inptArray[x] === 'object') {
                inptArray[x] = Fisma.Chart.forceIntegerArray(inptArray[x]);
            } else {
                inptArray[x] = parseInt(inptArray[x], 10);    // make sure this is an int, and not a string of a number
            }
        }

        return inptArray;
    },

    /**
     * Manually draws borders onto the shadow canvas
     * This function is nessesary as jqPlot's API does not allow 
     * you to choose which borders are drawn and which are not.
     * If "L" exists within chartParamsObj.borders, the left border is
     * drawn, if "R" does (too), then the right is drawn and so on.
     *
     * @return void
     */
    applyChartBorders : function (chartParamsObj) {
        var x = 0;

        // What borders should be drawn? (L = left, B = bottom, R = right, T = top)
        if (typeof chartParamsObj.borders === 'undefined') {
            if (chartParamsObj.chartType === 'bar' || chartParamsObj.chartType === 'stackedbar') {
                // default for bar and stacked bar charts are bottom-left (BL)
                chartParamsObj.borders = 'BL';
            } else {
                // assume no default for other chart types
                return;
            }
        }

        // Get the area of our containing divs
        var targDiv = document.getElementById(chartParamsObj.uniqueid);
        var children = targDiv.childNodes;

        for (x = children.length - 1; x > 0; x--) {
            // search for a canvs
            if (typeof children[x].nodeName !== 'undefined') {

                // search for a canvas that is the shadow canvas
                if (String(children[x].nodeName).toLowerCase() === 'canvas' && children[x].className === 'jqplot-series-shadowCanvas') {

                    // this is the canvas we want to draw on
                    var targCanv = children[x];
                    var context = targCanv.getContext('2d');

                    var h = children[x].height;
                    var w = children[x].width;

                    context.strokeStyle = '#777777';
                    context.lineWidth = 3;
                    context.beginPath();

                    // Draw left border?
                    if (chartParamsObj.borders.indexOf('L') !== -1) {
                        context.moveTo(0,0);
                        context.lineTo(0, h);
                        context.stroke();
                    }

                    // Draw bottom border?
                    if (chartParamsObj.borders.indexOf('B') !== -1) {
                        context.moveTo(0, h);
                        context.lineTo(w, h);
                        context.stroke();
                    }

                    // Draw right border?
                    if (chartParamsObj.borders.indexOf('R') !== -1) {
                        context.moveTo(w, 0);
                        context.lineTo(w, h);
                        context.stroke();
                    }

                    // Draw top border?
                    if (chartParamsObj.borders.indexOf('T') !== -1) {
                        context.moveTo(0, 0);
                        context.lineTo(w, 0);
                        context.stroke();
                    }

                    return;
                }
            }
        }
    },

    applyChartBackground : function (chartParamsObj) {

        var targDiv = document.getElementById(chartParamsObj.uniqueid);

        // Dont display a background? Defined in either nobackground or background.nobackground
        if (chartParamsObj.nobackground) {
            if (chartParamsObj.nobackground === true) { return; }
        }
        if (chartParamsObj.background) {
            if (chartParamsObj.background.nobackground) {
                if (chartParamsObj.background.nobackground === true) {
                    return;
                }
            }
        }

        // What is the HTML we should inject?
        var backURL = '/images/logoShark.png'; // default location
        if (chartParamsObj.background) {
            if (chartParamsObj.background.URL) {
                backURL = chartParamsObj.background.URL;
            }
        }
        var injectHTML = '<img height="100%" src="' + backURL + '" style="opacity:0.15;filter:alpha(opacity=15);opacity:0.15" />';

        // But wait, is there an override issued for the HTML of the background to inject?
        if (chartParamsObj.background) {
            if (chartParamsObj.background.overrideHTML) {
                backURL = chartParamsObj.background.overrideHTML;
            }
        }

        // Where do we inject the background in the DOM? (different for differnt chart rederers)
        var cpy;
        var insertBeforeChild;
        if (chartParamsObj.chartType === 'pie') {
            cpy = targDiv.childNodes[3];
            insertBeforeChild = targDiv.childNodes[4];
        } else {    
            cpy = targDiv.childNodes[6];
            insertBeforeChild = targDiv.childNodes[5];
        }

        var cpyStyl = cpy.style;

        var injectedBackgroundImg = document.createElement('span');
        injectedBackgroundImg.setAttribute('align', 'center');
        injectedBackgroundImg.setAttribute('style' , 'position: absolute; left: ' + cpyStyl.left + '; top: ' + cpyStyl.top + '; width: ' + cpy.width + 'px; height: ' + cpy.height + 'px;');

        var inserted = targDiv.insertBefore(injectedBackgroundImg, insertBeforeChild);
        inserted.innerHTML = injectHTML;
    },

    /**
     * Creates the chart widgets/options (regular options, not global-settings).
     * The generated HTML for these "widgets" as placed in a div by the id of the
     * chart's uniqueId + "WidgetSpace"
     *
     * @return void
     */
    applyChartWidgets : function (chartParamsObj) {
        var x = 0;
        var y = 0;

        var wigSpace = document.getElementById(chartParamsObj.uniqueid + 'WidgetSpace');
        wigSpace.innerHTML = '';

        // Are there widgets for this chart?
        var message = document.createElement("p");
        message.innerHTML = '<i>There are no parameters for this chart.</i>';
        if (typeof chartParamsObj.widgets === 'undefined') {
            wigSpace.appendChild(message);
            return;
        } else if (chartParamsObj.widgets.length === 0) {
            wigSpace.appendChild(message);
            return;
        }

        if (chartParamsObj.widgets) {
            var table = document.createElement("table");
            var tableBody = document.createElement("tbody");

            var keyEvent = function () {
                Fisma.Chart.widgetEvent(null, chartParamsObj);
            };


            for (x = 0; x < chartParamsObj.widgets.length; x++) {
                var row = document.createElement("tr");

                var thisWidget = chartParamsObj.widgets[x];

                // create a widget id if one is not explicitly given
                if (!thisWidget.uniqueid) {
                    thisWidget.uniqueid = chartParamsObj.uniqueid + '_widget' + x;
                    chartParamsObj.widgets[x].uniqueid = thisWidget.uniqueid;
                }

                // print the label text to be displayed to the left of the widget if one is given
                var firstCell = document.createElement("td");
                firstCell.noWrap = true;
                firstCell.align = "left";
                firstCell.innerHTML = thisWidget.label;
                row.appendChild(firstCell);

                var secondCell = document.createElement("td");
                secondCell.nowrap = "nowrap";
                secondCell.width = "90%";
                secondCell.align = "left";
                secondCell.style.paddingLeft = "10px";
                row.appendChild(secondCell);

                switch(thisWidget.type) {
                    case 'combo':
                        var select = document.createElement('select');
                        select.id = thisWidget.uniqueid;
                        var key;
                        for (key in thisWidget.options) {
                            var option = document.createElement('option');
                            option.innerHTML = $P.htmlspecialchars(thisWidget.options[key]);
                            if (thisWidget.setKeyValue) {
                                option.value = key;
                            } else {
                                option.value = thisWidget.options[key];
                            }

                            select.appendChild(option);
                        }
                        secondCell.appendChild(select);

                        YAHOO.util.Event.addListener(thisWidget.uniqueid,
                            "change",
                            Fisma.Chart.widgetEvent,
                            chartParamsObj
                        );
 
                        break;
                    case 'text':
                        var input = document.createElement('input');
                        input.type = "text";
                        input.id = thisWidget.uniqueid;
                        secondCell.appendChild(input);

                         var keyHandle = new YAHOO.util.KeyListener(input,
                                                {keys : YAHOO.util.KeyListener.KEY.ENTER},
                                                {fn : keyEvent});
                        keyHandle.enable();

                        break;
                    default:
                        throw 'Error - Widget ' + x + "'s type (" + thisWidget.type + ') is not a known widget type';
                }
                tableBody.appendChild(row);
            }

            table.appendChild(tableBody);
            wigSpace.appendChild(table);
        }

        Fisma.Chart.applyChartWidgetSettings(chartParamsObj);
    },

    /**
     * Looks at chartParamsObj["widget"], or for every chart-options/widget, loads the
     * values for this opt/widget into the user-interface object for this option.
     * This value may be loaded froma saved cookie, fallback to a default, or
     * be foreced to a certain value every time if the PHP wrapper demands it.
     *
     * @return void
     */
    applyChartWidgetSettings : function (chartParamsObj) {
        var x = 0;

        if (chartParamsObj.widgets) {

            for (x = 0; x < chartParamsObj.widgets.length; x++) {

                var thisWidget = chartParamsObj.widgets[x];

                // load the value for widgets
                var thisWigInDOM = document.getElementById(thisWidget.uniqueid);
                if (thisWidget.forcevalue) {
                    // this widget value is forced to a certain value upon every load/reload
                    thisWigInDOM.value = thisWidget.forcevalue;
                    thisWigInDOM.text = thisWidget.forcevalue;
                } else {
                    var thisWigCookieValue = YAHOO.util.Cookie.get(chartParamsObj.uniqueid + '_' + thisWidget.uniqueid);
                    if (thisWigCookieValue !== null) {
                        // the value has been coosen in the past and is stored as a cookie
                        thisWigCookieValue = thisWigCookieValue.replace(/%20/g, ' ');
                        thisWigInDOM.value = thisWigCookieValue;
                        thisWigInDOM.text = thisWigCookieValue;
                    } else {
                        // no saved value/cookie. Is there a default given in the chartParamsObj object
                        if (thisWidget.defaultvalue) {
                            thisWigInDOM.value = thisWidget.defaultvalue;
                            thisWigInDOM.text = thisWidget.defaultvalue;
                        }
                    }
                }
            }
        }
    },

    /**
     * When an external source is queried (JSON query), all chart parameters/options/widgets
     * are placed into the query URL. This function builds the trailing query to be appended
     * to the static external source URL.
     * Returns the chartParamsObj object given to this function with chartParamsObj.externalSourceParams altered.
     *
     * @return Array
     */
    buildExternalSourceParams : function (chartParamsObj) {

        // build arguments to send to the remote data source

        var x = 0;
        var thisWidgetValue = '';
        chartParamsObj.externalSourceParams = '';

        if (chartParamsObj.widgets) {
            for (x = 0; x < chartParamsObj.widgets.length; x++) {

                var thisWidget = chartParamsObj.widgets[x];
                var thisWidgetName = thisWidget.uniqueid;
                var thisWidgetOnDOM = document.getElementById(thisWidgetName);

                // is this widget actully on the DOM? Or should we load the cookie?
                if (thisWidgetOnDOM) {
                    // widget is on the DOM
                    thisWidgetValue = thisWidgetOnDOM.value;
                } else {
                    // not on DOM, is there a cookie?
                    var thisWigCookieValue = YAHOO.util.Cookie.get(chartParamsObj.uniqueid + '_' + thisWidget.uniqueid);
                    if (thisWigCookieValue !== null) {
                        // there is a cookie value, us it
                        thisWidgetValue = thisWigCookieValue;
                    } else {
                        // there is no cookie, is there a default value?
                        if (thisWidget.defaultvalue) {
                            thisWidgetValue = thisWidget.defaultvalue;
                        }
                    }
                }

                chartParamsObj.externalSourceParams += '/' + thisWidgetName + '/' + thisWidgetValue;
            }
        }

        return chartParamsObj;
    },

     /**
      * Event handeler for when a user changes combo-boxes or textboxes 
      * of chart settings.
      *
      * Expects: A (chart-)object generated from Fisma_Chart->export('array')
      *
      * @param object
      * @return void
     */
    widgetEvent : function (event, chartParamsObj) {
        var x = 0;

        // first, save the widget values (as cookies) so they can be retained later when the widgets get redrawn
        if (chartParamsObj.widgets) {
            for (x = 0; x < chartParamsObj.widgets.length; x++) {
                var thisWidgetName = chartParamsObj.widgets[x].uniqueid;
                var thisWidgetValue = document.getElementById(thisWidgetName).value;
                YAHOO.util.Cookie.set(chartParamsObj.uniqueid + '_' + thisWidgetName, thisWidgetValue, {path: "/"});
            }
        }

        // build arguments to send to the remote data source
        chartParamsObj = Fisma.Chart.buildExternalSourceParams(chartParamsObj);

        // restore externalSource so a json request is fired when calling createJQPChart
        chartParamsObj.externalSource = chartParamsObj.oldExternalSource;
        chartParamsObj.oldExternalSource = undefined;

        chartParamsObj.chartData = undefined;
        chartParamsObj.chartDataText = undefined;

        // re-create chart entirly
        document.getElementById(chartParamsObj.uniqueid + 'holder').finnishFadeCallback = new Function ("Fisma.Chart.makeElementVisible('" + chartParamsObj.uniqueid + "loader'); Fisma.Chart.createJQChart(" + YAHOO.lang.JSON.stringify(chartParamsObj) + "); Fisma.Chart.finnishFadeCallback = '';");
        Fisma.Chart.fadeOut(chartParamsObj.uniqueid + 'holder', 300);
    },

    makeElementVisible : function (eleId) {
        var ele = document.getElementById(eleId);
        ele.style.opacity = '1';
        ele.style.filter = "alpha(opacity = '100')";
    },

    makeElementInvisible : function (eleId) {
        var ele = document.getElementById(eleId);
        ele.style.opacity = '0';
        ele.style.filter = "alpha(opacity = '0')";
    },

    fadeIn : function (eid, TimeToFade) {

        var element = document.getElementById(eid);
        if (element === null) {
            return;
        }

        var fadingEnabled = ('fadingEnabled');
        if (fadingEnabled === 'false') {
            Fisma.Chart.makeElementVisible(eid);
            if (element.finnishFadeCallback) {
                element.finnishFadeCallback();
                element.finnishFadeCallback = undefined;
            }
            return;
        }

        if (typeof element.isFadingNow !== 'undefined') {
            if (element.isFadingNow === true) {
                return;
            }
        }
        element.isFadingNow = true;

        element.FadeState = null;
        element.FadeTimeLeft = undefined;

        Fisma.Chart.makeElementInvisible(eid);
        element.style.opacity = '0';
        element.style.filter = "alpha(opacity = '0')";

        Fisma.Chart.fade(eid, TimeToFade);
    },

    fadeOut : function (eid, TimeToFade) {

        var element = document.getElementById(eid);
        if (element === null) { return; }

        var fadingEnabled = Fisma.Chart.getGlobalSetting('fadingEnabled');
        if (fadingEnabled === 'false') {
            Fisma.Chart.makeElementInvisible(eid);
            if (element.finnishFadeCallback) {
                element.finnishFadeCallback();
                element.finnishFadeCallback = undefined;
            }
            return;
        }

        if (typeof element.isFadingNow !== 'undefined') {
            if (element.isFadingNow === true) {
                return;
            }
        }
        element.isFadingNow = true;

        element.FadeState = null;
        element.FadeTimeLeft = undefined;

        Fisma.Chart.makeElementVisible(eid);
        element.style.opacity = '1';
        element.style.filter = "alpha(opacity = '100')";

        Fisma.Chart.fade(eid, TimeToFade);
    },

    fade : function (eid, TimeToFade) {

        var element = document.getElementById(eid);
        if (element === null) { return; }

        //  element.style = '';

        if(element.FadeState === null)
        {
            if(element.style.opacity === null || element.style.opacity === '' || element.style.opacity === '1')
            {
                element.FadeState = 2;
            } else {
                element.FadeState = -2;
            }
        }

        if (element.FadeState === 1 || element.FadeState === -1) {
            element.FadeState = element.FadeState === 1 ? -1 : 1;
            element.FadeTimeLeft = TimeToFade - element.FadeTimeLeft;
        } else {
            element.FadeState = element.FadeState === 2 ? -1 : 1;
            element.FadeTimeLeft = TimeToFade;
            setTimeout("Fisma.Chart.animateFade(" + new Date().getTime() + ",'" + eid + "'," + TimeToFade + ")", 33);
        }  
    },

    animateFade : function (lastTick, eid, TimeToFade) {  
        var curTick = new Date().getTime();
        var elapsedTicks = curTick - lastTick;

        var element = document.getElementById(eid);

        if(element.FadeTimeLeft <= elapsedTicks)
        {
            if (element.FadeState === 1) {
                element.style.filter = 'alpha(opacity = 100)';
                element.style.opacity = '1';
            } else {
                element.style.filter = 'alpha(opacity = 0)';
                element.style.opacity = '0';
            }
            element.isFadingNow = false;
            element.FadeState = element.FadeState === 1 ? 2 : -2;

            if (element.finnishFadeCallback) {
                element.finnishFadeCallback();
                element.finnishFadeCallback = '';
            }
            return;
        }

        element.FadeTimeLeft -= elapsedTicks;
        var newOpVal = element.FadeTimeLeft/TimeToFade;
        if(element.FadeState === 1) {
            newOpVal = 1 - newOpVal;
        }

        element.style.opacity = newOpVal;
        element.style.filter = 'alpha(opacity = "' + (newOpVal*100) + '")';

        setTimeout("Fisma.Chart.animateFade(" + curTick + ",'" + eid + "'," + TimeToFade + ")", 33);
    },

    /**
     * This function controles how width and scrolling is handeled with the chart's canvase's
     * parent div. If autoWidth (or in PHP Fisma_Chart->widthAuto(true);) is set, the parent
     * div will always be scrollable. If not, it may still be automatically set scrollable if
     * the with in chartParamsObj.width is less than the minimum with required by the chart (calculated
     * in this function).
     *
     * NOTE: This function does not actully look at the DOM. It assumes the author to used
     *       Fisma_Chart->setWidth() knew what he was doing and set it correctly.
     *       The static width given to charts is considered a minimum width.
     *
     * @return void
     */
    setChartWidthAttribs : function (chartParamsObj) {

        var makeScrollable = false;
        var minSpaceRequired;

        // Determin if we need to make this chart scrollable...
        // Do we really have the chart data to plot?
        if (chartParamsObj.chartData) {
            // Is this a bar chart?
            if (chartParamsObj.chartType === 'bar' || chartParamsObj.chartType === 'stackedbar') {

                // How many bars does it have?
                var barCount;
                if (chartParamsObj.chartType === 'stackedbar') {
                    if (typeof chartParamsObj.chartData[0] === 'undefined') {
                        return;
                    } else {
                        barCount = chartParamsObj.chartData[0].length;
                    }
                } else if (chartParamsObj.chartType === 'bar') {
                    barCount = chartParamsObj.chartData.length;
                }

                // Assuming each bar margin is 10px, And each bar has a minimum width of 35px, how much space is needed total (minimum).
                minSpaceRequired = (barCount * 10) + (barCount * 35) + 40;

                // Do we not have enough space for a non-scrolling chart?
                if (chartParamsObj.width < minSpaceRequired) {

                    // We need to make this chart scrollable
                    makeScrollable = true;
                }
            }
        }

        // Is auto-width enabeled? (set width to 100% and make scrollable)
        if (typeof chartParamsObj.autoWidth !== 'undefined') {
            if (chartParamsObj.autoWidth === true) {
                makeScrollable = true;
            }
        }

        if (makeScrollable === true) {

            document.getElementById(chartParamsObj.uniqueid + 'loader').style.width = '100%';
            document.getElementById(chartParamsObj.uniqueid + 'holder').style.width = '100%';
            document.getElementById(chartParamsObj.uniqueid + 'holder').style.overflow = 'auto';
            document.getElementById(chartParamsObj.uniqueid).style.width = minSpaceRequired + 'px';
            document.getElementById(chartParamsObj.uniqueid  + 'toplegend').style.width = minSpaceRequired + 'px';

            // handel alignment
            if (chartParamsObj.align === 'center') {
                document.getElementById(chartParamsObj.uniqueid).style.marginLeft = 'auto';
                document.getElementById(chartParamsObj.uniqueid).style.marginRight = 'auto';
                document.getElementById(chartParamsObj.uniqueid + 'toplegend').style.marginLeft = 'auto';
                document.getElementById(chartParamsObj.uniqueid + 'toplegend').style.marginRight = 'auto';
            }

        } else {

            document.getElementById(chartParamsObj.uniqueid + 'loader').style.width = '100%';
            document.getElementById(chartParamsObj.uniqueid + 'holder').style.width = chartParamsObj.width + 'px';
            document.getElementById(chartParamsObj.uniqueid + 'holder').style.overflow = '';
            document.getElementById(chartParamsObj.uniqueid).style.width = chartParamsObj.width + 'px';
            document.getElementById(chartParamsObj.uniqueid + 'toplegend').width = chartParamsObj.width + 'px';
        }

    },

    /**
     * Builds a table based on the data to plot on the chart for screen readers.
     * The generated HTML should generally be placed in a div by the Id of the
     * chart's uniqueId + "table"
     *
     * @param object
     * @return String
     */
    getTableFromChartData : function (chartParamsObj) {
        if (Fisma.Chart.chartIsEmpty(chartParamsObj)) {
            return;
        }

        var dataTableObj = document.getElementById(chartParamsObj.uniqueid + 'table');
        dataTableObj.innerHTML = '';

        if (Fisma.Chart.getGlobalSetting('showDataTable') === 'true') {

            if (chartParamsObj.chartType === 'pie') {
                Fisma.Chart.getTableFromChartPieChart(chartParamsObj, dataTableObj);
            } else {
                Fisma.Chart.getTableFromBarChart(chartParamsObj, dataTableObj);
            }

            // Show the table generated based on chart data
            dataTableObj.style.display = '';
            // Hide, erase, and collapse the container of the chart divs
            document.getElementById(chartParamsObj.uniqueid).innerHTML = '';
            document.getElementById(chartParamsObj.uniqueid).style.width = 0;
            document.getElementById(chartParamsObj.uniqueid).style.height = 0;
            // Ensure the threat-level-legend is hidden
            document.getElementById(chartParamsObj.uniqueid + 'toplegend').style.display = 'none';

        } else {
            dataTableObj.style.display = 'none';
        }
    },

    /**
     * Creates a HTML table showing the data represented by the pie chart given. 
     *
     * Expects: A (chart-)object generated from Fisma_Chart->export('array')
     * Expects: An object that is either on, or about to be placed on the DOM, to which the 
     * HTML data-table should be appended to.
     *
     * @param object chartParamsObj
     * @param object dataTableObj
     * @return void
     */
    getTableFromChartPieChart : function (chartParamsObj, dataTableObj)
    {
        var tbl     = document.createElement("table");
        var tblBody = document.createElement("tbody");

        var x = 0;
        var cell;
        var cellText;
        var row;

        // row of slice-labels
        row = document.createElement("tr");
        for (x = 0; x < chartParamsObj.chartDataText.length; x++) {
            cell = document.createElement("th");
            cellText = document.createTextNode(chartParamsObj.chartDataText[x]);
            cell.setAttribute("style", "font-style: bold;");
            cell.appendChild(cellText);
            row.appendChild(cell);
        }
        tblBody.appendChild(row);

        // row of data
        row = document.createElement("tr");
        for (x = 0; x < chartParamsObj.chartData.length; x++) {
            cell = document.createElement("td");
            cellText = document.createTextNode(chartParamsObj.chartData[x]);
            cell.appendChild(cellText);
            row.appendChild(cell);
        }
        tblBody.appendChild(row);

        tbl.appendChild(tblBody);
        tbl.setAttribute("border", "1");
        tbl.setAttribute("width", "100%");

        dataTableObj.appendChild(tbl);
    },

    /**
     * Creates a HTML table showing the data represented by the bar chart given. 
     *
     * Expects: A (chart-)object generated from Fisma_Chart->export('array')
     * Expects: An object that is either on, or about to be placed on the DOM, to which the 
     * HTML data-table should be appended to.
     *
     * @param object chartParamsObj
     * @param object dataTableObj
     * @return void
     */
    getTableFromBarChart : function (chartParamsObj, dataTableObj)
    {
        var x = 0;
        var y = 0;
        var cell;
        var cellText;

        var tbl     = document.createElement("table");
        var tblBody = document.createElement("tbody");
        var row = document.createElement("tr");

        // add a column for layer names if this is a stacked chart
        if (typeof chartParamsObj.chartLayerText !== 'undefined') {
            cell = document.createElement("td");
            cellText = document.createTextNode(" ");
            cell.appendChild(cellText);
            row.appendChild(cell);
        }

        for (x = 0; x < chartParamsObj.chartDataText.length; x++) {
            cell = document.createElement("th");
            cellText = document.createTextNode(chartParamsObj.chartDataText[x]);
            cell.setAttribute("style", "font-style: bold;");
            cell.appendChild(cellText);
            row.appendChild(cell);
        }
        tblBody.appendChild(row);

        for (x = 0; x < chartParamsObj.chartData.length; x++) {

            var thisEle = chartParamsObj.chartData[x];
            row = document.createElement("tr");

            // each layer label
            if (typeof chartParamsObj.chartLayerText !== 'undefined') {
                cell = document.createElement("th");
                cellText = document.createTextNode(chartParamsObj.chartLayerText[x]);
                cell.setAttribute("style", "font-style: bold;");
                cell.appendChild(cellText);
                row.appendChild(cell);
            }

            if (typeof(thisEle) === 'object') {

                for (y = 0; y < thisEle.length; y++) {
                    cell = document.createElement("td");
                    cellText = document.createTextNode(thisEle[y]);
                    cell.setAttribute("style", "font-style: bold;");
                    cell.appendChild(cellText);
                    row.appendChild(cell);
                }

            } else {

                cell = document.createElement("td");
                cellText = document.createTextNode(thisEle);
                cell.appendChild(cellText);
                row.appendChild(cell);
            }

            tblBody.appendChild(row);

        }

        tbl.appendChild(tblBody);
        tbl.setAttribute("border", "1");
        tbl.setAttribute("width", "100%");

        dataTableObj.appendChild(tbl);
    },

    /**
     * Removes decimals from point labels, along with some other minor maintenance
     * - removes data/point-labels that are 0s
     * - Applies outlines if the globalSettings is set so
     * - forces color to black, and bolds the font
     *
     * Expects: A (chart-)object generated from Fisma_Chart->export('array')
     *
     * @param object
     * @return void
     */
    removeDecFromPointLabels : function (chartParamsObj) {
        var outlineStyle = '';
        var chartOnDOM = document.getElementById(chartParamsObj.uniqueid);

        var x = 0;
        for (x = 0; x < chartOnDOM.childNodes.length; x++) {

            var thisChld = chartOnDOM.childNodes[x];

            // IE Support - IE does not support .classList, manually make this
            if (typeof thisChld.classList === 'undefined') {
                thisChld.classList = String(thisChld.className).split(' ');
            }

            if (thisChld.classList) {
                if (thisChld.classList[0] === 'jqplot-point-label') {

                    // convert this from a string to a number to a string again (removes decimal if its needless)
                    var thisLabelValue = parseInt(thisChld.innerHTML, 10);
                    thisChld.innerHTML = thisLabelValue;
                    thisChld.value = thisLabelValue;

                    // if this number is 0, hide it (0s overlap with other numbers on bar charts)
                    if (parseInt(thisChld.innerHTML, 10) === 0 || isNaN(thisLabelValue)) {
                        thisChld.innerHTML = '';
                    }

                    // add outline to this point label so it is easily visible on dark color backgrounds (outlines are done through white-shadows)
                    if (Fisma.Chart.getGlobalSetting('pointLabelsOutline') === 'true') {

                        outlineStyle = 'text-shadow: ';
                        outlineStyle += '#FFFFFF 0px -1px 0px, ';
                        outlineStyle += '#FFFFFF 0px 1px 0px, ';
                        outlineStyle += '#FFFFFF 1px 0px 0px, ';
                        outlineStyle += '#FFFFFF -1px 1px 0px, ';
                        outlineStyle += '#FFFFFF -1px -1px 0px, ';
                        outlineStyle += '#FFFFFF 1px 1px 0px; ';

                        thisChld.innerHTML = '<span style="' + outlineStyle + chartParamsObj.pointLabelStyle + '">' + thisChld.innerHTML + '</span>';
                        thisChld.style.textShadow = 'text-shadow: #FFFFFF 0px -1px 0px, #FFFFFF 0px 1px 0px, #FFFFFF 1px 0px 0px, #FFFFFF -1px 1px 0px, #FFFFFF -1px -1px 0px, #FFFFFF 1px 1px 0px;';

                    } else {
                        thisChld.innerHTML = '<span style="' + chartParamsObj.pointLabelStyle + '">' + thisChld.innerHTML + '</span>';
                    }

                    // adjust the label to the a little bit since with the decemal trimmed, it may seem off-centered
                    var thisLeftNbrValue = parseInt(String(thisChld.style.left).replace('px', ''), 10);       // remove "px" from string, and conver to number
                    var thisTopNbrValue = parseInt(String(thisChld.style.top).replace('px', ''), 10);       // remove "px" from string, and conver to number
                    thisLeftNbrValue += chartParamsObj.pointLabelAdjustX;
                    thisTopNbrValue += chartParamsObj.pointLabelAdjustY;
                    if (thisLabelValue >= 100) { thisLeftNbrValue -= 2; }
                    if (thisLabelValue >= 1000) { thisLeftNbrValue -= 3; }
                    thisChld.style.left = thisLeftNbrValue + 'px';
                    thisChld.style.top = thisTopNbrValue + 'px';

                    // force color to black
                    thisChld.style.color = 'black';

                }
            }
        }
    },

    /**
     * Removes data-labels that are within a certain range of eachother. If two labels are close,
     * the data-label showing the lesser value is hidden.
     *
     * Expects: A (chart-)object generated from Fisma_Chart->export('array')
     * @param object
     * @return void
     */
    removeOverlappingPointLabels : function (chartParamsObj)
    {
        // This function will deal with removing point labels that collie with eachother
        // There is no need for this unless this is a stacked-bar or stacked-line chart
        if (chartParamsObj.chartType !== 'stackedbar' && chartParamsObj.chartType !== 'stackedline') {
            return;
        }

        var chartOnDOM = document.getElementById(chartParamsObj.uniqueid);

        var pointLabels_info = [];  //array of objects {left, top, value, obj}, one for each data label

        var pointLabelLeft;     // the x-offset of the data label
        var pointLabelTop;      // the y=offset of the data label
        var pointLabelValue;    // the numerical value the data label displays (casted as an integer)

        var x = 0;
        var y = 0;
        var d = 0;

        for (x = 0; x < chartOnDOM.childNodes.length; x++) {

            var thisChld = chartOnDOM.childNodes[x];

            // IE support - IE dosnt supply .classList array, just a className string. Manually build Fisma.Chart....
            if (typeof thisChld.classList === 'undefined') {
                thisChld.classList = String(thisChld.className).split(' ');
            }

            if (thisChld.classList[0] === 'jqplot-point-label') {

                var chldIsRemoved = false;

                if (typeof thisChld.isRemoved !== 'undefined') {
                    chldIsRemoved = thisChld.isRemoved;
                }

                if (chldIsRemoved === false) {
                    // index this point labels position

                    // remove "px" from string, and conver to number
                    pointLabelLeft = parseInt(String(thisChld.style.left).replace('px', ''), 10);
                    pointLabelTop = parseInt(String(thisChld.style.top).replace('px', ''), 10);
                    pointLabelValue = thisChld.value; // the value property should be given to this element form removeDecFromPointLabels

                    var thispLabelInfo = {
                        left: pointLabelLeft, 
                        top: pointLabelTop, 
                        value: pointLabelValue, 
                        obj: thisChld
                    };

                    pointLabels_info.push(thispLabelInfo);
                }
            }
        }

            // Ensure point labels do not collide with others
        $.each(pointLabels_info, function(index, thisPointLabel) {

            /* now determin the distance between this point label, and all
               point labels within this column. pointLabels_info[]
               holds all point labels within this column. */

            $.each(pointLabels_info, function(index, checkAgainst) {

                // get the distance from thisPointLabel to checkAgainst point label
                var deltaX = (thisPointLabel.left - checkAgainst.left);
                deltaX = deltaX * deltaX;
                var deltaY = (thisPointLabel.top - checkAgainst.top);
                deltaY = deltaY * deltaY;
                var d = Math.sqrt(deltaX + deltaY);

                if (d < 17 && d !== 0 && !isNaN(checkAgainst.value) && !isNaN(thisPointLabel.value)) {

                    // remove whichever label has the lower number

                    if (checkAgainst.value < thisPointLabel.value) {
                        checkAgainst.obj.innerHTML = '';
                        checkAgainst.obj.isRemoved = true;
                    } else {
                        thisPointLabel.obj.innerHTML = '';
                        thisPointLabel.obj.isRemoved = true;
                    }

                    // We jave just removed a point label, so this function will need to be run again
                    // as the labels will need to be reindexed.

                    Fisma.Chart.removeOverlappingPointLabels(chartParamsObj);
                    return;
                }
            });
        });
    },

    /**
     * The event listener for the Hide button shown in chart options
     *
     * @return void
     */
    hideButtonClick : function (scope, chartParamsObj, obj)
    {
        Fisma.Chart.setChartSettingsVisibility(chartParamsObj , false);
    },

    /**
     * Controles if the YUI-tab-view of the settings for a given drawn chart on the DOM
     * is visible or not.
     *
     * Expects: A (chart-)object generated from Fisma_Chart->export('array')
     *
     * @param object
     * @return void
     */
    setChartSettingsVisibility : function (chartId, boolVisible) {
        var menuHolderId = chartId + 'WidgetSpaceHolder';
        var menuObj = document.getElementById(menuHolderId);

        if (boolVisible === 'toggle') {
            if (menuObj.style.display === 'none') {
                boolVisible = true;
            } else {
                boolVisible = false;
            }
        }

        if (boolVisible === true) {
            menuObj.style.display = '';
        } else {
            menuObj.style.display = 'none';
        }
    },

    /**
     * Will take values from checkboxes/textboxes within the Global Settings tab of
     * a chart and save each settings into cookies, and then trigger Fisma.Chart.redrawAllCharts()
     *
     * @return void
     */
    globalSettingUpdate : function (mouseEvent, chartUniqueId) {
        // get this chart's GlobSettings menue
        var settingsMenue = document.getElementById(chartUniqueId + 'GlobSettings');

        // get all elements of this chart's GlobSettings menue
        var settingOpts = settingsMenue.childNodes;

        var x = 0;
        for (x = 0; x < settingOpts.length; x++) {
            var thisOpt = settingOpts[x];
            if (thisOpt.nodeName === 'INPUT') {
                if (thisOpt.type === 'checkbox') {
                    Fisma.Chart.setGlobalSetting(thisOpt.id, thisOpt.checked);
                } else {
                    Fisma.Chart.setGlobalSetting(thisOpt.id, thisOpt.value);
                }
            }
        }

        Fisma.Chart.redrawAllCharts();
    },

    /**
     * Will update checkboxes/textboxes within the Global Settings tab of
     * the chart to be equal to the current cookie state for each setting 
     * or the default stored in globalSettingsDefaults.
     *
     * Expects: A (chart-)object generated from Fisma_Chart->export('array')
     *
     * @param object
     * @return void
     */
    globalSettingRefreshUi : function (chartParamsObj) {
        /*
            Every input-element (setting UI) has an id equal to the cookie name 
            to which its value is stored. So wee we have to do is look for a
            cookie based on the id for each input element
        */

        // get this chart's GlobSettings menue
        var settingsMenue = document.getElementById(chartParamsObj.uniqueid + 'GlobSettings');

        // get all elements of this chart's GlobSettings menue
        var settingOpts = settingsMenue.childNodes;

        var x = 0;
        for (x = 0; x < settingOpts.length; x++) {
            var thisOpt = settingOpts[x];
            if (thisOpt.nodeName === 'INPUT') {

                // By this line (and in this block), we know we have found an input element on this GlobSettings menue

                if (thisOpt.type === 'checkbox') {
                    thisOpt.checked = (Fisma.Chart.getGlobalSetting(thisOpt.id) ==='true') ? true : false;
                } else {
                    thisOpt.value = Fisma.Chart.getGlobalSetting(thisOpt.id);
                    thisOpt.text = thisOpt.value;
                }
            }
        }
    },

    showSetingMode : function (showBasic) {
        var x = 0;
        var hideThese;
        var showThese;

        if (showBasic === true) {
            showThese = document.getElementsByName('chartSettingsBasic');
            hideThese = document.getElementsByName('chartSettingsGlobal');
        } else {
            hideThese = document.getElementsByName('chartSettingsBasic');
            showThese = document.getElementsByName('chartSettingsGlobal');
        }

        for (x = 0; x < hideThese.length; x++) {
            hideThese[x].style.display = 'none';
        }

        for (x = 0; x < hideThese.length; x++) {
            showThese[x].style.display = '';
        }
    },

    /**
     * Gets a setting previously saved by Fisma.Chart.setGlobalSetting()
     * If the setting being looked for has never been set, a value from Fisma.Chart.globalSettingsDefaults
     * will be returned.
     * If the setting being looked for has never beem set, and there is no default value, an 
     * exception is thown.
     *
     * @param string settingName
     * @return string
     */
    getGlobalSetting : function (settingName) {

        var rtnValue = YAHOO.util.Cookie.get('chartGlobSetting_' + settingName);

        if (rtnValue !== null) {
            return rtnValue;
        } else {

            if (typeof Fisma.Chart.globalSettingsDefaults[settingName] === 'undefined') {
                throw 'You have referenced a global setting (' + settingName + '), but have not defined a default value for it! Please defined a def-value in the object called globalSettingsDefaults that is located within the global scope of Chart.js';
            } else {
                return String(Fisma.Chart.globalSettingsDefaults[settingName]);
            }
        }
    },

    /**
     * Saves a setting with the that can be recalled later with Fisma.Chart.getGlobalSetting()
     *
     * @param string settingName
     * @param string newValue
     * @return void
     */
    setGlobalSetting : function (settingName, newValue)
    {
        YAHOO.util.Cookie.set('chartGlobSetting_' + settingName, newValue, {path: "/"});
    },

    /**
     * Will alter the input chart object based on 
     * settings(cookies) or defaults stored in globalSettingsDefaults.
     *
     * Expects: A (chart) object generated from Fisma_Chart->export('array')
     * Returns: The given object, which may or may not have alterations
     *
     * @param object
     * @return object
     */
    alterChartByGlobals : function (chartParamObj) {

        // Show bar shadows?
        if (Fisma.Chart.getGlobalSetting('barShadows') === 'true') {
            chartParamObj.seriesDefaults.rendererOptions.shadowDepth = 3;
            chartParamObj.seriesDefaults.rendererOptions.shadowOffset = 3;
        }

        // Depth of bar shadows?
        if (Fisma.Chart.getGlobalSetting('barShadowDepth') !== 'no-setting' && Fisma.Chart.getGlobalSetting('barShadows') === 'true') {
            chartParamObj.seriesDefaults.rendererOptions.shadowDepth = Fisma.Chart.getGlobalSetting('barShadowDepth');
            chartParamObj.seriesDefaults.rendererOptions.shadowOffset = Fisma.Chart.getGlobalSetting('barShadowDepth');
        }

        // grid-lines?
        if (Fisma.Chart.getGlobalSetting('gridLines') === 'true') {
            chartParamObj.grid.gridLineWidth = 1;
            chartParamObj.grid.borderWidth = 0;
            chartParamObj.grid.gridLineColor = undefined;
            chartParamObj.grid.drawGridLines = true;
            chartParamObj.grid.show = true;
        }

        // grid-lines?
        if (Fisma.Chart.getGlobalSetting('dropShadows') !== 'false') {
            chartParamObj.grid.shadow = true;
        }

        // point labels?
        if (Fisma.Chart.getGlobalSetting('pointLabels') === 'true') {
            chartParamObj.seriesDefaults.pointLabels.show = true;
        }

        // point labels outline?
            /* no alterations to the chartParamObject needs to be done here, this is handeled by Fisma.Chart.removeDecFromPointLabels() */  


        return chartParamObj;
    },


    /**
     * Redraws all charts and refreashes all options dialogs associated.
     *
     * If using IE, will post a loading message, and re-call this function
     * again with doRedrawNow=true based on a timer
     *
     * The reason for the use of the timer is to ensure the browser repaints
     * its content area, and the loading message is actully shown 
     * (and yes, this is nessesary).
     */
    redrawAllCharts : function (doRedrawNow) {
        var thisParamObj;
        var uniqueid;

        // First, show a loading message showing that the chart is loading
        for (uniqueid in Fisma.Chart.chartsOnDOM) {
            thisParamObj = Fisma.Chart.chartsOnDOM[uniqueid];
            Fisma.Chart.showChartLoadingMsg(thisParamObj);
        }

        // If we are running in IE, continue to redraw charts after a brief pause to ensure IE has repainted the screen
        if (Fisma.Chart.isIE === true) {
            if (doRedrawNow !== true || doRedrawNow === null) { 
                setTimeout(Fisma.Chart.redrawAllCharts(true), 300);
                return;
            }
        }

        // Now redraw and refreash charts and chart options
        for (uniqueid in Fisma.Chart.chartsOnDOM) {
            thisParamObj = Fisma.Chart.chartsOnDOM[uniqueid];

            // redraw chart
            Fisma.Chart.createJQChart(thisParamObj);

            // refreash Global Settings UI
            Fisma.Chart.globalSettingRefreshUi(thisParamObj);
        }
    },

    /**
     * Shows the loading spinner in the place of the given chart on the DOM.
     * If a chart has already been drawn, it will be destoryed.
     *
     * Expects: A (chart) object generated from Fisma_Chart->export('array')
     * @param object
     * @return void
     */
    showChartLoadingMsg : function (chartParamsObj)
    {
        // Ensure the threat-level-legend is hidden
        document.getElementById(chartParamsObj.uniqueid + 'toplegend').innerHTML = '';

        // Show spinner
        Fisma.Chart.makeElementVisible(chartParamsObj.uniqueid + 'loader');

        // Create text "Loading" message
        var chartContainer = document.getElementById(chartParamsObj.uniqueid);
        var loadChartDataMsg = document.createTextNode("\n\n\n\nLoading chart data...");
        var pTag = document.createElement('p');
        pTag.align = 'center';
        pTag.appendChild(loadChartDataMsg);

        // Show text "Loading" message
        chartContainer.innerHTML = '';      // clear the current chart container div
        chartContainer.appendChild(document.createElement('br'));
        chartContainer.appendChild(document.createElement('br'));
        chartContainer.appendChild(document.createElement('br'));
        chartContainer.appendChild(document.createElement('br'));
        chartContainer.appendChild(pTag);
    },

    /**
     * Does nothing. Used to set a title on a chart, but now, nothing.
     *
     * @deprecated
     * @param object
     * @return void
     */
    setTitle : function (chartParamsObj)
    {
    },

    /**
     * Will insert a "No data to plot" message when there is no 
     * data to plot, or all plot data are 0s
     *
     * Expects: A (chart) object generated from Fisma_Chart->export('array')
     * @param object
     * @return void
     */
    showMsgOnEmptyChart : function (chartParamsObj) {
        if (Fisma.Chart.chartIsEmpty(chartParamsObj)) {
            var targDiv = document.getElementById(chartParamsObj.uniqueid);

            // Place message on DOM
            var insertBeforeChild = targDiv.childNodes[1];
            var msgOnDom = document.createElement('div');
            msgOnDom.height = '100%';
            msgOnDom.style.align = 'center';
            msgOnDom.style.position = 'absolute';
            msgOnDom.style.width = chartParamsObj.width + 'px';
            msgOnDom.style.height = '100%';
            msgOnDom.style.textAlign = 'center';
            msgOnDom.style.verticalAlign = 'middle';
            msgOnDom.appendChild( document.createTextNode("No data to plot. ") );
            var changeParamsLink = document.createElement('a');
            changeParamsLink.href = '#';
            changeParamsLink.onclick = function() {
                Fisma.Chart.setChartSettingsVisibility(chartParamsObj.uniqueid, 'toggle');
            };

            changeParamsLink.appendChild( document.createTextNode('Change chart parameters?') );
            msgOnDom.appendChild(changeParamsLink);
            targDiv.appendChild(msgOnDom);

            // Make sure screen-reader-table is not showing
            var dataTableObj = document.getElementById(chartParamsObj.uniqueid + 'table');
            dataTableObj.style.display = 'none';
        }
    },

    /**
     * Returns true if there is no data to plot, or if all plot data is 0
     *
     * Expects: A (chart) object generated from Fisma_Chart->export('array')
     * @param object
     * @return boolean
     */
    chartIsEmpty : function (chartParamsObj) {
        var isChartEmpty = true;
        var x = 0; var y = 0;

        for (x in chartParamsObj.chartData) {
            if (typeof chartParamsObj.chartData[x] === 'object') {

                for (y in chartParamsObj.chartData[x]) {
                    if (parseInt(chartParamsObj.chartData[x][y], 10) > 0) {
                        isChartEmpty = false;
                    }
                }

            } else {
                if (parseInt(chartParamsObj.chartData[x], 10) > 0) {
                    isChartEmpty = false;
                }
            }
        }

        return isChartEmpty;
    },

    /**
     * Place canvases in divs with the appropriate style declairations. 
     * This is nessesary to force styles when printing.
     *
     * Expects: A (chart) object generated from Fisma_Chart->export('array')
     * @param object
     * @return void
     */
    placeCanvasesInDivs : function(chartParamsObj) {

        // Get the div that holds all canvases of this chart
        var chartCanvasContainer = YAHOO.util.Dom.get(chartParamsObj.uniqueid);

        // Get a list (obj-array) of all canvases for this chart that are absolute positioned
        var canvases = $(chartCanvasContainer).find('canvas').filter(
            function() {
                return $(this).css('position') === 'absolute';
            }
        );

        // Wrap each canvas in <div>~</div> blocks, and add certain style-declarations to the div
        canvases.wrap(
            function() {
                var div;
                var canvas = $(this);

                if (canvas.context.className === 'jqplot-yaxis-tick') {

                    // y-axis labels/ticks (labels for each row), must be placed to the farthest right of the parent
                    div = $('<div />').css(
                        {
                            position: 'absolute',
                            top: canvas.css('top'),
                            right: canvas.css('right')
                        }
                    );
                    canvas.css(
                        {
                            top: 0,
                            right: 0
                        }
                    );
                    
                    if (Fisma.Chart.isIE === false) {
                        div.className = 'chart-yaxis-tick';
                    } else {
                        div.className = 'chart-yaxis-tick-InIE';
                    }

                } else if (canvas.context.className === 'jqplot-xaxis-label') {

                    // X-Axis labels (label for the entire x-axis), must be centered on the bottom of the parent
                    div = $('<div />').css(
                        {
                            position: 'absolute',
                            bottom: '0px'
                        }
                    );

                } else {

                    // All other canvases elements are placed absolute and corectly, and need not to be moved for printing purposes
                    div = $('<div />').css(
                        {
                            position: 'absolute',
                            top: canvas.css('top'),
                            left: canvas.css('left')
                        }
                    );
                    canvas.css(
                        {
                            top: 0,
                            left: 0
                        }
                    );

                }

                return div;
            }
        );

        return this;
    }
};
