
// Defaults for global chart settings definition:
var globalSettingsDefaults = {
    fadingEnabled:      false,
    barShadows:         false,
    barShadowDepth:     3,
    dropShadows:        false,
    gridLines:          false,
    pointLabels:        false,
    pointLabelsOutline: false,
    showDataTable: false
}

// Remember all chart paramiter objects which are drawn on the DOM within global var chartsOnDom
var chartsOnDOM = {};

// Is this client/browser Internet Explorer?
isIE = (window.ActiveXObject) ? true : false;

/**
 * Creates a chart within a div by the name of chartParamsObj['uniqueid'].
 * All paramiters needed to create the chart are expected to be within the chartParamsObj object.
 * This function may return before the actual creation of a chart if there is an external source.
 * Returns true on success, false on failure, and the integer 3 when on external source
 *
 * @return boolean/integer
 */
function createJQChart(chartParamsObj)
{

    // load in default values for paramiters, and replace it with any given params
    var defaultParams = {
        concatXLabel: false,
        nobackground: true,
        drawGridLines: false,
        pointLabelStyle: 'color: black; font-size: 12pt; font-weight: bold',
        pointLabelAdjustX: -3,
        pointLabelAdjustY: -7,
        AxisLabelX: '',
        AxisLabelY: '',
        DataTextAngle: -30
    };
    chartParamsObj = jQuery.extend(true, defaultParams, chartParamsObj);

    // param validation
    if (document.getElementById(chartParamsObj['uniqueid']) == false) {
        throw 'createJQChart Error - The target div/uniqueid does not exists' + chartParamsObj['uniqueid'];
        return false;
    }

    // set chart width to chartParamsObj['width']
    setChartWidthAttribs(chartParamsObj);

    // Ensure the load spinner is visible
    makeElementVisible(chartParamsObj['uniqueid'] + 'loader');

    // is the data being loaded from an external source? (Or is it all in the chartParamsObj obj?)
    if (chartParamsObj['externalSource']) {
        
        /*
         * If it is being loaded from an external source
         *   setup a json request
         *   have the json request return to createJQChart_asynchReturn
         *   exit this function as createJQChart_asynchReturn will call this function again with the same chartParamsObj object with chartParamsObj['externalSource'] taken out
        */

        document.getElementById(chartParamsObj['uniqueid']).innerHTML = 'Loading chart data...';

        // note externalSource, and remove/relocate it from its place in chartParamsObj[] so it dosnt retain and cause us to loop 
        var externalSource = chartParamsObj['externalSource'];
        if (!chartParamsObj['oldExternalSource']) {
            chartParamsObj['oldExternalSource'] = chartParamsObj['externalSource'];
        }
        chartParamsObj['externalSource'] = undefined;
        
        // Send data from widgets to external data source if needed7 (will load from cookies and defaults if widgets are not drawn yet)
        chartParamsObj = buildExternalSourceParams(chartParamsObj);
        externalSource += String(chartParamsObj['externalSourceParams']).replace(/ /g,'%20');
        chartParamsObj['lastURLpull'] = externalSource;

        // Are we debugging the external source?
        if (chartParamsObj['externalSourceDebug']) {
            var doNav = confirm ('Now pulling from external source: ' + externalSource + '\n\nWould you like to navigate here?')
            if (doNav) {
                document.location = externalSource;
            }
        }

        var myDataSource = new YAHOO.util.DataSource(externalSource);
        myDataSource.responseType = YAHOO.util.DataSource.TYPE_JSON;
        myDataSource.responseSchema = {resultsList: "chart"};

        var callBackFunct = new Function ("requestNumber", "value", "exception", "createJQChart_asynchReturn(requestNumber, value, exception, " + YAHOO.lang.JSON.stringify(chartParamsObj) + ");");

        var callback1 = {
            success : callBackFunct,
            failure : callBackFunct
        };
        myDataSource.sendRequest("", callback1);

        return 3;
    }

    // clear the chart area
    document.getElementById(chartParamsObj['uniqueid']).innerHTML = '';
    document.getElementById(chartParamsObj['uniqueid']).className = '';
    document.getElementById(chartParamsObj['uniqueid'] + 'toplegend').innerHTML = '';

    // handel aliases and short-cut vars
    if (typeof chartParamsObj['barMargin'] != 'undefined') {
        chartParamsObj = jQuery.extend(true, chartParamsObj, {'seriesDefaults': {'rendererOptions': {'barMargin': chartParamsObj['barMargin']}}});
        chartParamsObj['barMargin'] = undefined;
    }
    if (typeof chartParamsObj['legendLocation'] != 'undefined') {
        chartParamsObj = jQuery.extend(true, chartParamsObj, {'legend': {'location': chartParamsObj['legendLocation'] }});
        chartParamsObj['legendLocation'] = undefined;
    }
    if (typeof chartParamsObj['legendRowCount'] != 'undefined') {
        chartParamsObj = jQuery.extend(true, chartParamsObj, {'legend': {'rendererOptions': {'numberRows': chartParamsObj['legendRowCount']}}});
        chartParamsObj['legendRowCount'] = undefined;
    }
        
    // make sure the numbers to be plotted in chartParamsObj['chartData'] are infact numbers and not an array of strings of numbers
    chartParamsObj['chartData'] = forceIntegerArray(chartParamsObj['chartData']);

    // hide the loading spinner and show the canvas target
    document.getElementById(chartParamsObj['uniqueid'] + 'holder').style.display = '';
    makeElementInvisible(chartParamsObj['uniqueid'] + 'holder');
    document.getElementById(chartParamsObj['uniqueid'] + 'loader').style.position = 'absolute';
    document.getElementById(chartParamsObj['uniqueid'] + 'loader').finnishFadeCallback = new Function ("fadeIn('" + chartParamsObj['uniqueid'] + "holder', 500);");
    fadeOut(chartParamsObj['uniqueid'] + 'loader', 500);

    // now that we have the chartParamsObj['chartData'], do we need to make the chart larger and scrollable?
    setChartWidthAttribs(chartParamsObj);

    // Store this charts paramiter object into the global variable chartsOnDOM, so it can be redrawn
    // This must be done before the next switch block that translates some data within the chartParamsObj object for jqPlot
    chartsOnDOM[chartParamsObj['uniqueid']] = jQuery.extend(true, {}, chartParamsObj);
    
    // call the correct function based on chartType, or state there will be no chart created
    if (!chartIsEmpty(chartParamsObj)) {
    
        switch(chartParamsObj['chartType'])
        {
            case 'stackedbar':
                chartParamsObj['varyBarColor'] = false;
                            if (typeof chartParamsObj['showlegend'] == 'undefined') { chartParamsObj['showlegend'] = true; }
                var rtn = createJQChart_StackedBar(chartParamsObj);
                break;
            case 'bar':

                // Is this a simple-bar chart (not-stacked-bar) with multiple series?
                if (typeof chartParamsObj['chartData'][0] =='object') {

                    // the chartData is already a multi dimensional array, and the chartType is bar, not stacked bar. So we assume it is a simple-bar chart with multi series
                    // thus we will leave the chartData array as is (as opposed to forcing it to a 2 dim array, and claming it to be a stacked bar chart with no other layers of bars (a lazy but functional of creating a regular bar charts from the stacked-bar chart renderer)

                    chartParamsObj['varyBarColor'] = false;
                    chartParamsObj['showlegend'] = true;

                } else {
                    chartParamsObj['chartData'] = [chartParamsObj['chartData']];  // force to 2 dimensional array
                    chartParamsObj['links'] = [chartParamsObj['links']];
                    chartParamsObj['varyBarColor'] = true;
                    chartParamsObj['showlegend'] = false;
                }

                chartParamsObj['stackSeries'] = false;
                var rtn = createJQChart_StackedBar(chartParamsObj);
                break;

            case 'line':
                var rtn = createChartJQStackedLine(chartParamsObj);
                break;
            case 'stackedline':
                var rtn = createChartJQStackedLine(chartParamsObj);
                break;
            case 'pie':
                chartParamsObj['links'] = [chartParamsObj['links']];
                var rtn = createChartJQPie(chartParamsObj);
                break;
            default:
                throw 'createJQChart Error - chartType is invalid (' + chartParamsObj['chartType'] + ')';
                return false;
        }
    }

    // chart tweeking external to the jqPlot library
    removeOverlappingPointLabels(chartParamsObj);
    applyChartBackground(chartParamsObj);
    applyChartWidgets(chartParamsObj);
    createChartThreatLegend(chartParamsObj);
    applyChartBorders(chartParamsObj);
    globalSettingRefreashUI(chartParamsObj);
    showMsgOnEmptyChart(chartParamsObj);
    getTableFromChartData(chartParamsObj);

    return rtn;
}


/**
 * When an external source is needed, this function should handel the returned JSON request
 * The chartParamsObj object that went into createJQChart(obj) would be the chartParamsObj here, and
 * the "value" parameter should be the returned JSON request.
 * the chartParamsObj and value objects are merged togeather based in inheritance mode and 
 * returns the return value of createJQChart(), or false on external source failure.
 *
 * @return boolean/integer
 */
function createJQChart_asynchReturn(requestNumber, value, exception, chartParamsObj)
{
    // If anything (json) was returned at all...
    if (value) {
        
        // YAHOO.util.DataSource puts its JSON responce within value['results'][0]
        if (value['results'][0]) {
        
            chartParamsObj = mergeExtrnIntoParamObjectByInheritance(chartParamsObj, value)
            
        } else {
            if (confirm('Error - Chart creation failed due to data source error.\nIf you continuously see this message, please click Ok to navigate to data source, and copy-and-pase the text&data from there into email to Endeavor Systems.\n\nNavigate to the error-source?')) {
                document.location = chartParamsObj['lastURLpull'];
            }
        }

        if (typeof chartParamsObj['chartData'] == 'undefined') {
            throw 'Chart Error - The remote data source for chart "' + chartParamsObj['uniqueid'] + '" located at ' + chartParamsObj['lastURLpull'] + ' did not return data to plot on a chart';
            return;
        }

        // call the createJQChart() with the chartParamsObj-object initally given to createJQChart() and the merged responce object
        return createJQChart(chartParamsObj);
        
    } else {
        if (confirm('Error - Chart creation failed due to data source error.\nIf you continuously see this message, please click Ok to navigate to data source, and copy-and-pase the text&data from there into email to Endeavor Systems.\n\nNavigate to the error-source?')) {
            document.location = chartParamsObj['lastURLpull'];
        }
    }
    
    return false;
}

/**
 * Takes a chartParamsObj and merges content of 
 * ExternResponce-object into it based in the inheritance mode
 * set in ExternResponce.
 * Expects: A (chart-)object generated from Fisma_Chart->export('array')
 *
 * @param object
 * @return void
 * 
*/
function mergeExtrnIntoParamObjectByInheritance(chartParamsObj, ExternResponce)
{
    var joinedParam = {};

    // Is there an inheritance mode? 
    if (ExternResponce['results'][0]['inheritCtl']) {
        if (ExternResponce['results'][0]['inheritCtl'] == 'minimal') {
            // Inheritance mode set to minimal, retain certain attribs and merge
            var joinedParam = ExternResponce['results'][0];
            joinedParam['width'] = chartParamsObj['width'];
            joinedParam['height'] = chartParamsObj['height'];
            joinedParam['uniqueid'] = chartParamsObj['uniqueid'];
            joinedParam['externalSource'] = chartParamsObj['externalSource'];
            joinedParam['oldExternalSource'] = chartParamsObj['oldExternalSource'];
            joinedParam['widgets'] = chartParamsObj['widgets'];
        } else if (ExternResponce['results'][0]['inheritCtl'] == 'none') {
            // Inheritance mode set to none, replace the joinedParam object
            var joinedParam = ExternResponce['results'][0];
        } else {
            throw 'Error - Unknown chart inheritance mode';
            return;
        }
    } else {
        // No inheritance mode, by default, merge everything
        var joinedParam = jQuery.extend(true, chartParamsObj, ExternResponce['results'][0],true);
    }

    return joinedParam;
}

 /**
  * Fires the jqPlot library, and creates a pie chart
  * based on input chart object
  *
  * Expects: A (chart-)object generated from Fisma_Chart->export('array')
  *
  * @param object
  * @return void
 */
function createChartJQPie(chartParamsObj)
{
    usedLabelsPie = chartParamsObj['chartDataText'];

    var dataSet = [];

    for (var x = 0; x < chartParamsObj['chartData'].length; x++) {
        chartParamsObj['chartDataText'][x] += ' (' + chartParamsObj['chartData'][x]  + ')';
        dataSet[dataSet.length] = [chartParamsObj['chartDataText'][x], chartParamsObj['chartData'][x]];
    }
    

    var jPlotParamObj = {
        title: chartParamsObj['title'],
        seriesColors: chartParamsObj['colors'],
        grid: {
            drawBorder: false,
            drawGridlines: false,
            shadow: false
        },
        axes: {
            xaxis:{
                tickOptions: {
                    angle: chartParamsObj['DataTextAngle'],
                    fontSize: '10pt',
                    formatString: '%.0f'
                }
            },
            yaxis:{
                tickOptions: {
                    formatString: '%.0f'
                }
            }

        },
        seriesDefaults:{
            renderer:$.jqplot.PieRenderer,
            rendererOptions: {
                sliceMargin: 0,
                showDataLabels: true,
                shadowAlpha: 0.15,
                shadowOffset: 0,
                lineLabels: true,
                lineLabelsLineColor: '#777',
                diameter: chartParamsObj['height'] * 0.55
            }
        },
        legend: {
            location: 's',
            show: false,
            rendererOptions: {
                numberRows: 1
            }
        }
    }
    
    jPlotParamObj.seriesDefaults.renderer.prototype.startAngle = 0;

    // bug killer (for IE7) - state the height for the container div for emulated excanvas
    $("[id="+chartParamsObj['uniqueid']+"]").css('height', chartParamsObj['height']);

    // merge any jqPlot direct chartParamsObj-arguments into jPlotParamObj from chartParamsObj
    jPlotParamObj = jQuery.extend(true, jPlotParamObj, chartParamsObj);

    plot1 = $.jqplot(chartParamsObj['uniqueid'], [dataSet], jPlotParamObj);

    // create an event handeling function that calls chartClickEvent while preserving the parm object
    var EvntHandler = new Function ("ev", "seriesIndex", "pointIndex", "data", "var thisChartParamObj = " + YAHOO.lang.JSON.stringify(chartParamsObj) + "; chartClickEvent(ev, seriesIndex, pointIndex, data, thisChartParamObj);" );
    
    // use the created function as the click-event-handeler
    $('#' + chartParamsObj['uniqueid']).bind('jqplotDataClick', EvntHandler);

}

 /**
  * Fires the jqPlot library, and creates a stacked
  * bar chart based on input chart object
  *
  * Expects: A (chart-)object generated from Fisma_Chart->export('array')
  *
  * @param object
  * @return void
 */
function createJQChart_StackedBar(chartParamsObj)
{
    var dataSet = [];
    var thisSum = 0;
    var maxSumOfAll = 0;
    var chartCeilingValue = 0;

    for (var x = 0; x < chartParamsObj['chartDataText'].length; x++) {
    
        thisSum = 0;
        
        for (var y = 0; y < chartParamsObj['chartData'].length; y++) {
            thisSum += chartParamsObj['chartData'][y][x];
        }
        
        if (thisSum > maxSumOfAll) { maxSumOfAll = thisSum; }

        if (chartParamsObj['concatXLabel'] == true) {
            chartParamsObj['chartDataText'][x] += ' (' + thisSum  + ')';
        }
        
    }

    var seriesParam = [];
    if (chartParamsObj['chartLayerText']) {
        for (x = 0; x < chartParamsObj['chartLayerText'].length; x++) {
            seriesParam[x] = {label: chartParamsObj['chartLayerText'][x]};
        }
    }

    // Make sure the Y-axis (row labels) are not offset by the formatter string rounding their values...
    // (make the top most row label divisible by 5)
    chartCeilingValue = getNextNumberDivisibleBy5(maxSumOfAll);
    
    // Force Y-axis row labels to be divisible by 5
    yAxisTicks = [];
    yAxisTicks[0] = 0;
    yAxisTicks[1] = (chartCeilingValue/5) * 1;
    yAxisTicks[2] = (chartCeilingValue/5) * 2;
    yAxisTicks[3] = (chartCeilingValue/5) * 3;
    yAxisTicks[4] = (chartCeilingValue/5) * 4;
    yAxisTicks[5] = (chartCeilingValue/5) * 5;
    

    $.jqplot.config.enablePlugins = true

    var jPlotParamObj = {
        title: chartParamsObj['title'],
        seriesColors: chartParamsObj['colors'],
        stackSeries: true,
        series: seriesParam,
        seriesDefaults:{
            renderer: $.jqplot.BarRenderer,
            rendererOptions:{
                barWidth: 35,
                showDataLabels: true,
                varyBarColor: chartParamsObj['varyBarColor'],
                shadowAlpha: 0.15,
                shadowOffset: 0
            },
            pointLabels:{show: false, location: 's'}
        },
        axesDefaults: {
            tickRenderer: $.jqplot.CanvasAxisTickRenderer,
            borderWidth: 0,
            labelOptions: {
                enableFontSupport: true,
                fontFamily: 'arial, helvetica, clean, sans-serif',
                fontSize: '12pt',
                textColor: '#555555'
            }
        },
        axes: {
            xaxis:{
                label: chartParamsObj['AxisLabelX'],
                labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
                renderer: $.jqplot.CategoryAxisRenderer,
                ticks: chartParamsObj['chartDataText'],
                tickOptions: {
                    angle: chartParamsObj['DataTextAngle'],
                    fontFamily: 'arial, helvetica, clean, sans-serif',
                    fontSize: '10pt',
                    textColor: '#555555'
                }
            },
            yaxis:{
                label: chartParamsObj['AxisLabelY'],
                labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
                min: 0,
                max: chartCeilingValue,
                autoscale: true,
                ticks: yAxisTicks,
                tickOptions: {
                    formatString: '%.0f',
                    fontFamily: 'arial, helvetica, clean, sans-serif',
                    fontSize: '10pt',
                    textColor: '#555555'
                }
            }

        },
        highlighter: { 
            show: false 
            },
        grid: {
            gridLineWidth: 0,
            shadow: false,
            borderWidth: 1,
            gridLineColor: '#FFFFFF',
            background: 'transparent',
            drawGridLines: chartParamsObj['drawGridLines'],
            show: chartParamsObj['drawGridLines']
            },
        legend: {
                    show: chartParamsObj['showlegend'],
                    rendererOptions: {
                        numberRows: 1
                    },
                    location: 'nw'
                }
    };
    
    // bug killer - The canvas object for IE does not understand what transparency is...
    if (isIE) {
        jPlotParamObj.grid.background = '#FFFFFF';
    }
    
    // bug killer (for IE7) - state the height for the container div for emulated excanvas
    $("[id="+chartParamsObj['uniqueid']+"]").css('height', chartParamsObj['height']);
    
    // merge any jqPlot direct chartParamsObj-arguments into jPlotParamObj from chartParamsObj
    jPlotParamObj = jQuery.extend(true, jPlotParamObj, chartParamsObj);
    
    // override any jqPlot direct chartParamsObj-arguments based on globals setting from cookies (set by user)
    jPlotParamObj = alterChartByGlobals(jPlotParamObj);

    plot1 = $.jqplot(chartParamsObj['uniqueid'], chartParamsObj['chartData'], jPlotParamObj);

    
    var EvntHandler = new Function ("ev", "seriesIndex", "pointIndex", "data", "var thisChartParamObj = " + YAHOO.lang.JSON.stringify(chartParamsObj) + "; chartClickEvent(ev, seriesIndex, pointIndex, data, thisChartParamObj);" );
    $('#' + chartParamsObj['uniqueid']).bind('jqplotDataClick', EvntHandler);

    removeDecFromPointLabels(chartParamsObj);

}

function createChartJQStackedLine(chartParamsObj)
{
    var dataSet = [];
    var thisSum = 0;

    for (var x = 0; x < chartParamsObj['chartDataText'].length; x++) {
    
        thisSum = 0;
        
        for (var y = 0; y < ['chartData'].length; y++) {
            thisSum += ['chartData'][y][x];
        }
        
        chartParamsObj['chartDataText'][x] += ' (' + thisSum  + ')';
    }
        
    plot1 = $.jqplot(chartParamsObj['uniqueid'], chartParamsObj['chartData'], {
        title: chartParamsObj['title'],
        seriesColors: ["#F4FA58", "#FAAC58","#FA5858"],
        series: [{label: 'Open Findings', lineWidth:4, markerOptions:{style:'square'}}, {label: 'Closed Findings', lineWidth:4, markerOptions:{style:'square'}}, {lineWidth:4, markerOptions:{style:'square'}}],
        seriesDefaults:{
            fill:false,
            showMarker: true,
            showLine: true
        },
        axes: {
            xaxis:{
                renderer:$.jqplot.CategoryAxisRenderer,
                ticks:chartParamsObj['chartDataText']
            },
            yaxis:{
                min: 0
            }
        },
        highlighter: { show: false },
        legend: {
                    show: true,
                    rendererOptions: {
                        numberRows: 1
                    },
                    location: 'nw'
                }
    });

}

/**
 * Creates the red-orange-yello threat-legend that shows above charts
 * The generated HTML code should go into the div with the id of the
 * chart's uniqueId + "toplegend"
 *
 * @return boolean/integer
 */
function createChartThreatLegend(chartParamsObj)
{
    /*
        Creates a red-orange-yellow legent above the chart
    */

    if (chartParamsObj['showThreatLegend'] && !chartIsEmpty(chartParamsObj)) {
        if (chartParamsObj['showThreatLegend'] == true) {

            // Is a width given for the width of the legend? OR should we assume 100%?
            var tLegWidth = '100%';
            if (chartParamsObj['threatLegendWidth']) {
                tLegWidth = chartParamsObj['threatLegendWidth'];
            }

            var injectHTML = '<table style="font-size: 12px; color: #555555;" width="' + tLegWidth + '">  <tr>    <td style="text-align: center;" width="40%">Threat Level</td>    <td width="20%">    <table>      <tr>        <td bgcolor="#FF0000" width="1px">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>        <td>&nbsp;High</td>      </tr>    </table>    </td>    <td width="20%">    <table>      <tr>        <td bgcolor="#FF6600" width="1px">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>        <td>&nbsp;Moderate&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>      </tr>    </table>    </td>    <td width="20%">    <table>      <tr>        <td bgcolor="#FFC000" width="1px">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>        <td>&nbsp;Low</td>      </tr>    </table>    </td>  </tr></table>';
            var thisChartId = chartParamsObj['uniqueid'];
            var topLegendOnDOM = document.getElementById(thisChartId + 'toplegend');

            topLegendOnDOM.innerHTML = injectHTML;
        }
    }        
}

function chartClickEvent(ev, seriesIndex, pointIndex, data, paramObj)
{
    
    var theLink = false;
    if (paramObj['links']) {
        if (typeof paramObj['links'] == 'string') {
            theLink = paramObj['links'];
        } else {
            if (paramObj['links'][seriesIndex]) {
                if (typeof paramObj['links'][seriesIndex] == "object") {
                    theLink = paramObj['links'][seriesIndex][pointIndex];
                } else {
                    theLink = paramObj['links'][seriesIndex];
                }
            }
        }
    }
    
    // unescape
    theLink = unescape(theLink);
    
    // Does the link contain a variable?
    if (theLink != false) {
        theLink = String(theLink).replace('#ColumnLabel#', paramObj['chartDataText'][pointIndex]);
    }
    
    if (paramObj['linksdebug'] == true) {
        var msg = "You clicked on layer " + seriesIndex + ", in column " + pointIndex + ", which has the data of " + data[1] + "\n";
        msg += "The link information for this element should be stored as a string in chartParamData['links'], or as a string in chartParamData['links'][" + seriesIndex + "][" + pointIndex + "]\n";
        if (theLink != false) { msg += "The link with this element is " + theLink; }
        alert(msg);
    } else {
    
        // We are not in link-debug mode, navigate if there is a link
        if (theLink != false && String(theLink) != 'null') {
            document.location = theLink;
        }
        
    }
}

/**
 * Converts an array from strings to integers, for example;
 * ["1", 2, "3", 4] would become [1, 2, 3, 4]
 * This is a bug killer for external source plotting data as
 * the jqPlot lib expects integers, and JSON may not always 
 * be encoded that way
 *
 * @return array
 */
function forceIntegerArray(inptArray)
{
    for (var x = 0; x < inptArray.length; x++) {
        if (typeof inptArray[x] == 'object') {
            inptArray[x] = forceIntegerArray(inptArray[x]);
        } else {
            inptArray[x] = parseInt(inptArray[x]);    // make sure this is an int, and not a string of a number
        }
    }

    return inptArray;
}

/**
 * Manually draws borders onto the shadow canvas
 * This function is nessesary as jqPlot's API does not allow 
 * you to choose which borders are drawn and which are not.
 * If "L" exists within chartParamsObj['borders'], the left border is
 * drawn, if "R" does (too), then the right is drawn and so on.
 *
 * @return void
 */
function applyChartBorders(chartParamsObj)
{

    // What borders should be drawn? (L = left, B = bottom, R = right, T = top)
    if (typeof chartParamsObj['borders'] == 'undefined') {
        if (chartParamsObj['chartType'] == 'bar' || chartParamsObj['chartType'] == 'stackedbar') {
            // default for bar and stacked bar charts are bottom-left (BL)
            chartParamsObj['borders'] = 'BL';
        } else {
            // assume no default for other chart types
            return;
        }
    }

    // Get the area of our containing divs
    var targDiv = document.getElementById(chartParamsObj['uniqueid']);
    var children = targDiv.childNodes;
    
    for (var x = children.length - 1; x > 0; x++) {
        // search for a canvs
        if (typeof children[x].nodeName != 'undefined') {
            if (String(children[x].nodeName).toLowerCase() == 'canvas') {

                // search for a canvas that is the shadow canvas
                if (children[x].className = 'jqplot-series-shadowCanvas') {

                    // this is the canvas we want to draw on
                    var targCanv = children[x];
                    var context = targCanv.getContext('2d');

                    var h = children[x].height;
                    var w = children[x].width;

                    context.strokeStyle = '#777777'
                    context.lineWidth = 3;
                    context.beginPath();

                    // Draw left border?
                    if (chartParamsObj['borders'].indexOf('L') != -1) {
                        context.moveTo(0,0);
                        context.lineTo(0, h);
                        context.stroke();
                    }               

                    // Draw bottom border?
                    if (chartParamsObj['borders'].indexOf('B') != -1) {
                        context.moveTo(0, h);
                        context.lineTo(w, h);
                        context.stroke();
                    }

                    // Draw right border?
                    if (chartParamsObj['borders'].indexOf('R') != -1) {
                        context.moveTo(w, 0);
                        context.lineTo(w, h);
                        context.stroke();
                    }

                    // Draw top border?
                    if (chartParamsObj['borders'].indexOf('T') != -1) {
                        context.moveTo(0, 0);
                        context.lineTo(w, 0);
                        context.stroke();
                    }

                        return;
                }
            }
        }
    }
    
}

function applyChartBackground(chartParamsObj)
{

    var targDiv = document.getElementById(chartParamsObj['uniqueid']);

    // Dont display a background? Defined in either nobackground or background.nobackground
    if (chartParamsObj['nobackground']) {
        if (chartParamsObj['nobackground'] == true) { return; }
    }
    if (chartParamsObj['background']) {
        if (chartParamsObj['background']['nobackground']) {
            if (chartParamsObj['background']['nobackground'] == true) { return; }
        }
    }
    
    // What is the HTML we should inject?
    var backURL = '/images/logoShark.png'; // default location
    if (chartParamsObj['background']) { if (chartParamsObj['background']['URL']) { backURL = chartParamsObj['background']['URL']; } }
    var injectHTML = '<img height="100%" src="' + backURL + '" style="opacity:0.15;filter:alpha(opacity=15);opacity:0.15" />';

    // But wait, is there an override issued for the HTML of the background to inject?
    if (chartParamsObj['background']) {
        if (chartParamsObj['background']['overrideHTML']) {
            backURL = chartParamsObj['background']['overrideHTML'];
        }
    }

    // Where do we inject the background in the DOM? (different for differnt chart rederers)
    if (chartParamsObj['chartType'] == 'pie') {
        var cpy = targDiv.childNodes[3];
        var insertBeforeChild = targDiv.childNodes[4];
    } else {    
        var cpy = targDiv.childNodes[6];
        var insertBeforeChild = targDiv.childNodes[5];
    }

    var cpyStyl = cpy.style;

    injectedBackgroundImg = document.createElement('span');
    injectedBackgroundImg.setAttribute('align', 'center');
    injectedBackgroundImg.setAttribute('style' , 'position: absolute; left: ' + cpyStyl.left + '; top: ' + cpyStyl.top + '; width: ' + cpy.width + 'px; height: ' + cpy.height + 'px;');

    var inserted = targDiv.insertBefore(injectedBackgroundImg, insertBeforeChild);
    inserted.innerHTML = injectHTML;
}

/**
 * Creates the chart widgets/options (regular options, not global-settings).
 * The generated HTML for these "widgets" as placed in a div by the id of the
 * chart's uniqueId + "WidgetSpace"
 *
 * @return void
 */
function applyChartWidgets(chartParamsObj)
{

    var wigSpace = document.getElementById(chartParamsObj['uniqueid'] + 'WidgetSpace');

    // Are there widgets for this chart?
    if (typeof chartParamsObj['widgets'] == 'undefined') {
        wigSpace.innerHTML = '<br/><i>There are no parameters for this chart.</i><br/><br/>';
        return;
    } else if (chartParamsObj['widgets'].length == 0) {
        wigSpace.innerHTML = '<br/><i>There are no parameters for this chart.</i><br/><br/>';
        return;
    }

    if (chartParamsObj['widgets']) {

        var addHTML = '';

        for (var x = 0; x < chartParamsObj['widgets'].length; x++) {

            var thisWidget = chartParamsObj['widgets'][x];
            
            // create a widget id if one is not explicitly given
            if (!thisWidget['uniqueid']) {
                thisWidget['uniqueid'] = chartParamsObj['uniqueid'] + '_widget' + x;
                chartParamsObj['widgets'][x]['uniqueid'] = thisWidget['uniqueid'];
            }

            // print the label text to be displayed to the left of the widget if one is given
            addHTML += '<tr><td nowrap align=left>' + thisWidget['label'] + ' </td><td><td nowrap width="10"></td><td width="99%" align=left>';

            switch(thisWidget['type']) {
                case 'combo':

                    addHTML += '<select id="' + thisWidget['uniqueid'] + '" onChange="widgetEvent(' + YAHOO.lang.JSON.stringify(chartParamsObj).replace(/"/g, "'") + ');">';
                                        // " // ( comment double quote to fix syntax highlight errors with /"/g on previus line )

                    for (var y = 0; y < thisWidget['options'].length; y++) {
                        addHTML += '<option value="' + thisWidget['options'][y] + '">' + thisWidget['options'][y] + '</option><br/>';
                    }
                    
                    addHTML += '</select>';

                    break;

                case 'text':
    
                    addHTML += '<input onKeyDown="if(event.keyCode==13){widgetEvent(' + YAHOO.lang.JSON.stringify(chartParamsObj).replace(/"/g, "'") + ');};" type="textbox" id="' + thisWidget['uniqueid'] + '" />';
                                        // " // ( comment double quote to fix syntax highlight errors with /"/g on previus line )
                    break;

                default:
                    throw 'Error - Widget ' + x + "'s type (" + thisWidget['type'] + ') is not a known widget type';
                    return false;
            }

            
            addHTML += '</td></tr>';
            
        }

        // add this widget HTML to the DOM
        wigSpace.innerHTML = '<table>' + addHTML + '</table>';
        
    }

    applyChartWidgetSettings(chartParamsObj);
}

/**
 * Looks at chartParamsObj["widget"], or for every chart-options/widget, loads the
 * values for this opt/widget into the user-interface object for this option.
 * This value may be loaded froma saved cookie, fallback to a default, or
 * be foreced to a certain value every time if the PHP wrapper demands it.
 *
 * @return void
 */
function applyChartWidgetSettings(chartParamsObj)
{

    if (chartParamsObj['widgets']) {

        for (var x = 0; x < chartParamsObj['widgets'].length; x++) {

            var thisWidget = chartParamsObj['widgets'][x];
            
            // load the value for widgets
            var thisWigInDOM = document.getElementById(thisWidget['uniqueid']);
            if (thisWidget['forcevalue']) {
                // this widget value is forced to a certain value upon every load/reload
                thisWigInDOM.value = thisWidget['forcevalue'];
                thisWigInDOM.text = thisWidget['forcevalue'];
            } else {
                var thisWigCookieValue = getCookie(chartParamsObj['uniqueid'] + '_' + thisWidget['uniqueid']);
                if (thisWigCookieValue != '') {
                    // the value has been coosen in the past and is stored as a cookie
                    thisWigCookieValue = thisWigCookieValue.replace(/%20/g, ' ');
                    thisWigInDOM.value = thisWigCookieValue;
                    thisWigInDOM.text = thisWigCookieValue;
                } else {
                    // no saved value/cookie. Is there a default given in the chartParamsObj object
                    if (thisWidget['defaultvalue']) {
                        thisWigInDOM.value = thisWidget['defaultvalue'];
                        thisWigInDOM.text = thisWidget['defaultvalue'];
                    }
                }
            }
        }
    }

}

/**
 * When an external source is queried (JSON query), all chart parameters/options/widgets
 * are placed into the query URL. This function builds the trailing query to be appended
 * to the static external source URL.
 * Returns the chartParamsObj object given to this function with chartParamsObj['externalSourceParams'] altered.
 *
 * @return Array
 */
function buildExternalSourceParams(chartParamsObj)
{

    // build arguments to send to the remote data source

    var thisWidgetValue = '';
    chartParamsObj['externalSourceParams'] = '';

    if (chartParamsObj['widgets']) {
        for (var x = 0; x < chartParamsObj['widgets'].length; x++) {

            var thisWidget = chartParamsObj['widgets'][x];
            var thisWidgetName = thisWidget['uniqueid'];
            var thisWidgetOnDOM = document.getElementById(thisWidgetName);

            // is this widget actully on the DOM? Or should we load the cookie?         
            if (thisWidgetOnDOM) {
                // widget is on the DOM
                thisWidgetValue = thisWidgetOnDOM.value;
            } else {
                // not on DOM, is there a cookie?
                var thisWigCookieValue = getCookie(chartParamsObj['uniqueid'] + '_' + thisWidget['uniqueid']);
                if (thisWigCookieValue != '') {
                    // there is a cookie value, us it
                    thisWidgetValue = thisWigCookieValue;
                } else {
                    // there is no cookie, is there a default value?
                    if (thisWidget['defaultvalue']) {
                        thisWidgetValue = thisWidget['defaultvalue'];
                    }
                }
            }

            chartParamsObj['externalSourceParams'] += '/' + thisWidgetName + '/' + thisWidgetValue 
        }
    }

    return chartParamsObj;
}

 /**
  * Event handeler for when a user changes combo-boxes or textboxes 
  * of chart settings.
  *
  * Expects: A (chart-)object generated from Fisma_Chart->export('array')
  *
  * @param object
  * @return void
 */
function widgetEvent(chartParamsObj)
{

    // first, save the widget values (as cookies) so they can be retained later when the widgets get redrawn
    if (chartParamsObj['widgets']) {
        for (var x = 0; x < chartParamsObj['widgets'].length; x++) {
            var thisWidgetName = chartParamsObj['widgets'][x]['uniqueid'];
            var thisWidgetValue = document.getElementById(thisWidgetName).value;
            setCookie(chartParamsObj['uniqueid'] + '_' + thisWidgetName,thisWidgetValue,400);
        }
    }

    // build arguments to send to the remote data source
    chartParamsObj = buildExternalSourceParams(chartParamsObj);

    // restore externalSource so a json request is fired when calling createJQPChart
    chartParamsObj['externalSource'] = chartParamsObj['oldExternalSource'];
    chartParamsObj['oldExternalSource'] = undefined;

    chartParamsObj['chartData'] = undefined;
    chartParamsObj['chartDataText'] = undefined;

    // re-create chart entirly
    document.getElementById(chartParamsObj['uniqueid'] + 'holder').finnishFadeCallback = new Function ("makeElementVisible('" + chartParamsObj['uniqueid'] + "loader'); createJQChart(" + YAHOO.lang.JSON.stringify(chartParamsObj) + "); this.finnishFadeCallback = '';");
    fadeOut(chartParamsObj['uniqueid'] + 'holder', 300);

}

function makeElementVisible(eleId)
{
    var ele = document.getElementById(eleId);
    ele.style.opacity = '1';
    ele.style.filter = "alpha(opacity = '100')";
}

function makeElementInvisible(eleId)
{
    var ele = document.getElementById(eleId);
    ele.style.opacity = '0';
    ele.style.filter = "alpha(opacity = '0')";
}

function fadeIn(eid, TimeToFade)
{

    var element = document.getElementById(eid);
    if (element == null) return;
    
    
    var fadingEnabled = getGlobalSetting('fadingEnabled');
    if (fadingEnabled == 'false') {
        makeElementVisible(eid);
        if (element.finnishFadeCallback) {
            element.finnishFadeCallback();
            element.finnishFadeCallback = undefined;
        }
        return;
    }
    
    if (typeof element.isFadingNow != 'undefined') {
        if (element.isFadingNow == true) {
            return;
        }
    }
    element.isFadingNow = true;

    element.FadeState = null;
    element.FadeTimeLeft = undefined;

    makeElementInvisible(eid);
    element.style.opacity = '0';
    element.style.filter = "alpha(opacity = '0')";

    fade(eid, TimeToFade);
}

function fadeOut(eid, TimeToFade)
{

    var element = document.getElementById(eid);
    if (element == null) return;

    var fadingEnabled = getGlobalSetting('fadingEnabled');
    if (fadingEnabled == 'false') {
        makeElementInvisible(eid);
        if (element.finnishFadeCallback) {
            element.finnishFadeCallback();
            element.finnishFadeCallback = undefined;
        }
        return;
    }

    if (typeof element.isFadingNow != 'undefined') {
        if (element.isFadingNow == true) {
            return;
        }
    }
    element.isFadingNow = true;

    element.FadeState = null;
    element.FadeTimeLeft = undefined;

    makeElementVisible(eid);
    element.style.opacity = '1';
    element.style.filter = "alpha(opacity = '100')";

    fade(eid, TimeToFade);
}

function fade(eid, TimeToFade)
{

    var element = document.getElementById(eid);
    if (element == null) return;

//  element.style = '';

    if(element.FadeState == null)
    {
        if(element.style.opacity == null || element.style.opacity == '' || element.style.opacity == '1')
        {
            element.FadeState = 2;
        } else {
            element.FadeState = -2;
        }
    }

    if (element.FadeState == 1 || element.FadeState == -1) {
        element.FadeState = element.FadeState == 1 ? -1 : 1;
        element.FadeTimeLeft = TimeToFade - element.FadeTimeLeft;
    } else {
        element.FadeState = element.FadeState == 2 ? -1 : 1;
        element.FadeTimeLeft = TimeToFade;
        setTimeout("animateFade(" + new Date().getTime() + ",'" + eid + "'," + TimeToFade + ")", 33);
    }  
}

function animateFade(lastTick, eid, TimeToFade)
{  
    var curTick = new Date().getTime();
    var elapsedTicks = curTick - lastTick;

    var element = document.getElementById(eid);

    if(element.FadeTimeLeft <= elapsedTicks)
    {
        if (element.FadeState == 1) {
            element.style.filter = 'alpha(opacity = 100)';
            element.style.opacity = '1';
        } else {
            element.style.filter = 'alpha(opacity = 0)';
            element.style.opacity = '0';
        }
        element.isFadingNow = false;
        element.FadeState = element.FadeState == 1 ? 2 : -2;
        
        if (element.finnishFadeCallback) {
            element.finnishFadeCallback();
            element.finnishFadeCallback = '';
        }
        return;
    }

    element.FadeTimeLeft -= elapsedTicks;
    var newOpVal = element.FadeTimeLeft/TimeToFade;
    if(element.FadeState == 1) newOpVal = 1 - newOpVal;

    element.style.opacity = newOpVal;
    element.style.filter = 'alpha(opacity = "' + (newOpVal*100) + '")';

    setTimeout("animateFade(" + curTick + ",'" + eid + "'," + TimeToFade + ")", 33);
}

/**
 * This function controles how width and scrolling is handeled with the chart's canvase's
 * parent div. If autoWidth (or in PHP Fisma_Chart->widthAuto(true);) is set, the parent
 * div will always be scrollable. If not, it may still be automatically set scrollable if
 * the with in chartParamsObj['width'] is less than the minimum with required by the chart (calculated
 * in this function).
 *
 * NOTE: This function does not actully look at the DOM. It assumes the author to used
 *       Fisma_Chart->setWidth() knew what he was doing and set it correctly.
 *       The static width given to charts is considered a minimum width.
 *
 * @return void
 */
function setChartWidthAttribs(chartParamsObj)
{

    var makeScrollable = false;

    // Determin if we need to make this chart scrollable...
    // Do we really have the chart data to plot?
    if (chartParamsObj['chartData']) {
        // Is this a bar chart?
        if (chartParamsObj['chartType'] == 'bar' || chartParamsObj['chartType'] == 'stackedbar') {

            // How many bars does it have?
            if (chartParamsObj['chartType'] == 'stackedbar') {
                var barCount = chartParamsObj['chartData'][0].length;
            } else if (chartParamsObj['chartType'] == 'bar') {
                var barCount = chartParamsObj['chartData'].length;
            }

            // Assuming each bar margin is 10px, And each bar has a minimum width of 35px, how much space is needed total (minimum).
            var minSpaceRequired = (barCount * 10) + (barCount * 35) + 40;

            // Do we not have enough space for a non-scrolling chart?
            if (chartParamsObj['width'] < minSpaceRequired) {
                
                // We need to make this chart scrollable
                makeScrollable = true;
            }
        }
    }

    // Is auto-width enabeled? (set width to 100% and make scrollable)
    if (typeof chartParamsObj['autoWidth'] != 'undefined') {
        if (chartParamsObj['autoWidth'] == true) {
            makeScrollable = true;
        }
    }

    if (makeScrollable == true) {

        document.getElementById(chartParamsObj['uniqueid'] + 'loader').style.width = '100%';
        document.getElementById(chartParamsObj['uniqueid'] + 'holder').style.width = '100%';
        document.getElementById(chartParamsObj['uniqueid'] + 'holder').style.overflow = 'auto';
        document.getElementById(chartParamsObj['uniqueid']).style.width = minSpaceRequired + 'px';
        document.getElementById(chartParamsObj['uniqueid']  + 'toplegend').style.width = minSpaceRequired + 'px';

        // handel alignment
        if (chartParamsObj['align'] == 'center') {
            document.getElementById(chartParamsObj['uniqueid']).style.marginLeft = 'auto';
            document.getElementById(chartParamsObj['uniqueid']).style.marginRight = 'auto';
            document.getElementById(chartParamsObj['uniqueid'] + 'toplegend').style.marginLeft = 'auto';
            document.getElementById(chartParamsObj['uniqueid'] + 'toplegend').style.marginRight = 'auto';
        }
        
    } else {

        document.getElementById(chartParamsObj['uniqueid'] + 'loader').style.width = '100%';
        document.getElementById(chartParamsObj['uniqueid'] + 'holder').style.width = chartParamsObj['width'] + 'px';
        document.getElementById(chartParamsObj['uniqueid'] + 'holder').style.overflow = '';
        document.getElementById(chartParamsObj['uniqueid']).style.width = chartParamsObj['width'] + 'px';
        document.getElementById(chartParamsObj['uniqueid'] + 'toplegend').width = chartParamsObj['width'] + 'px';
    }
    
}

/**
 * Builds a table based on the data to plot on the chart for screen readers.
 * The generated HTML should generally be placed in a div by the Id of the
 * chart's uniqueId + "table"
 *
 * @param object
 * @return String
 */
function getTableFromChartData(chartParamsObj)
{
    if (chartIsEmpty(chartParamsObj)) {
        return;
    }

    var dataTableObj = document.getElementById(chartParamsObj['uniqueid'] + 'table');
    dataTableObj.innerHTML = '';
    
    if (getGlobalSetting('showDataTable') === 'true') {
    
        if (chartParamsObj['chartType'] === 'pie') {
            getTableFromCharPieChart(chartParamsObj);
        } else {
            getTableFromBarChart(chartParamsObj);
        }

        // Show the table generated based on chart data
        dataTableObj.style.display = '';
        // Hide, erase, and collapse the container of the chart divs
        document.getElementById(chartParamsObj['uniqueid']).innerHTML = '';
        document.getElementById(chartParamsObj['uniqueid']).style.width = 0;
        document.getElementById(chartParamsObj['uniqueid']).style.height = 0;
        // Ensure the threat-level-legend is hidden
        document.getElementById(chartParamsObj['uniqueid'] + 'toplegend').style.display = 'none';

    } else {
        dataTableObj.style.display = 'none';
    }
}

function getTableFromCharPieChart(chartParamsObj)
{
    var tbl     = document.createElement("table");
    var tblBody = document.createElement("tbody");

    // row of slice-labels
    var row = document.createElement("tr");
    for (var x = 0; x < chartParamsObj['chartDataText'].length; x++) {
        var cell = document.createElement("th");
        var cellText = document.createTextNode(chartParamsObj['chartDataText'][x]);
        cell.setAttribute("style", "font-style: bold;");
        cell.appendChild(cellText);
        row.appendChild(cell);
    }
    tblBody.appendChild(row);

    // row of data
    var row = document.createElement("tr");
    for (var x = 0; x < chartParamsObj['chartData'].length; x++) {
        var cell = document.createElement("td");
        var cellText = document.createTextNode(chartParamsObj['chartData'][x]);
        cell.appendChild(cellText);
        row.appendChild(cell);
    }
    tblBody.appendChild(row);

    tbl.appendChild(tblBody);
    tbl.setAttribute("border", "1");
    tbl.setAttribute("width", "100%");
    
    document.getElementById(chartParamsObj['uniqueid'] + 'table').appendChild(tbl);
}

function getTableFromBarChart(chartParamsObj)
{
    var tbl     = document.createElement("table");
    var tblBody = document.createElement("tbody");
    var row = document.createElement("tr");
    
    // add a column for layer names if this is a stacked chart
    if (typeof chartParamsObj['chartLayerText'] != 'undefined') {
        var cell = document.createElement("td");
        var cellText = document.createTextNode(" ");
        cell.appendChild(cellText);
        row.appendChild(cell);
    }
    
    for (var x = 0; x < chartParamsObj['chartDataText'].length; x++) {
        var cell = document.createElement("th");
        var cellText = document.createTextNode(chartParamsObj['chartDataText'][x]);
        cell.setAttribute("style", "font-style: bold;");
        cell.appendChild(cellText);
        row.appendChild(cell);
    }
    tblBody.appendChild(row);
    

    for (var x = 0; x < chartParamsObj['chartData'].length; x++) {

        var thisEle = chartParamsObj['chartData'][x];
        var row = document.createElement("tr");
        
        // each layer label
        if (typeof chartParamsObj['chartLayerText'] != 'undefined') {
            var cell = document.createElement("th");
            var cellText = document.createTextNode(chartParamsObj['chartLayerText'][x]);
            cell.setAttribute("style", "font-style: bold;");
            cell.appendChild(cellText);
            row.appendChild(cell);
        }
        
        if (typeof(thisEle) == 'object') {

            for (var y = 0; y < thisEle.length; y++) {
                var cell = document.createElement("td");
                var cellText = document.createTextNode(thisEle[y]);
                cell.setAttribute("style", "font-style: bold;");
                cell.appendChild(cellText);
                row.appendChild(cell);
            }
            
        } else {

            var cell = document.createElement("td");
            var cellText = document.createTextNode(thisEle);
            cell.appendChild(cellText);
            row.appendChild(cell);
        }

        tblBody.appendChild(row);

    }

    tbl.appendChild(tblBody);
    tbl.setAttribute("border", "1");
    tbl.setAttribute("width", "100%");
    
    document.getElementById(chartParamsObj['uniqueid'] + 'table').appendChild(tbl);
}

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
function removeDecFromPointLabels(chartParamsObj)
{
        var outlineStyle = '';
        var chartOnDOM = document.getElementById(chartParamsObj['uniqueid']);
    
        for (var x = 0; x < chartOnDOM.childNodes.length; x++) {
                
                var thisChld = chartOnDOM.childNodes[x];
                
                // IE Support - IE does not support .classList, manually make this
                if (typeof thisChld.classList == 'undefined') {
                    thisChld.classList = String(thisChld.className).split(' ');
                }
                
                if (thisChld.classList) {
                    if (thisChld.classList[0] == 'jqplot-point-label') {

                            // convert this from a string to a number to a string again (removes decimal if its needless)
                            thisLabelValue = parseInt(thisChld.innerHTML);
                            thisChld.innerHTML = thisLabelValue;
                            thisChld.value = thisLabelValue;

                            // if this number is 0, hide it (0s overlap with other numbers on bar charts)
                            if (parseInt(thisChld.innerHTM) == 0) {
                                thisChld.innerHTML = '';
                            }

                            // add outline to this point label so it is easily visible on dark color backgrounds (outlines are done through white-shadows)
                            if (getGlobalSetting('pointLabelsOutline') == 'true') {

                                outlineStyle = 'text-shadow: ';
                                outlineStyle += '#FFFFFF 0px -1px 0px, ';
                                outlineStyle += '#FFFFFF 0px 1px 0px, ';
                                outlineStyle += '#FFFFFF 1px 0px 0px, ';
                                outlineStyle += '#FFFFFF -1px 1px 0px, ';
                                outlineStyle += '#FFFFFF -1px -1px 0px, ';
                                outlineStyle += '#FFFFFF 1px 1px 0px; ';
                                
                                thisChld.innerHTML = '<span style="' + outlineStyle + chartParamsObj['pointLabelStyle'] + '">' + thisChld.innerHTML + '</span>';
                                thisChld.style.textShadow = 'text-shadow: #FFFFFF 0px -1px 0px, #FFFFFF 0px 1px 0px, #FFFFFF 1px 0px 0px, #FFFFFF -1px 1px 0px, #FFFFFF -1px -1px 0px, #FFFFFF 1px 1px 0px;';
                                
                            } else {
                                thisChld.innerHTML = '<span style="' + chartParamsObj['pointLabelStyle'] + '">' + thisChld.innerHTML + '</span>';
                            }

                            // adjust the label to the a little bit since with the decemal trimmed, it may seem off-centered
                            var thisLeftNbrValue = parseInt(String(thisChld.style.left).replace('px', ''));       // remove "px" from string, and conver to number
                            var thisTopNbrValue = parseInt(String(thisChld.style.top).replace('px', ''));       // remove "px" from string, and conver to number
                            thisLeftNbrValue += chartParamsObj['pointLabelAdjustX'];
                            thisTopNbrValue += chartParamsObj['pointLabelAdjustY'];
                            if (thisLabelValue >= 100) { thisLeftNbrValue -= 2; }
                            if (thisLabelValue >= 1000) { thisLeftNbrValue -= 3; }
                            thisChld.style.left = thisLeftNbrValue + 'px';
                            thisChld.style.top = thisTopNbrValue + 'px';

                            // force color to black
                            thisChld.style.color = 'black';

                    }
                }
        }
        
}

function removeOverlappingPointLabels(chartParamsObj)
{

        // This function will deal with removing point labels that collie with eachother
        // There is no need for this unless this is a stacked-bar or stacked-line chart
        if (chartParamsObj['chartType'] != 'stackedbar' && chartParamsObj['chartType'] != 'stackedline') {
            return;
        }

        var chartOnDOM = document.getElementById(chartParamsObj['uniqueid']);

        var pointLabels_info = {};
        var pointLabels_indexes = [];
        var thisLabelValue = 0;
        var d = 0;

        for (var x = 0; x < chartOnDOM.childNodes.length; x++) {

            var thisChld = chartOnDOM.childNodes[x];
            
            // IE support - IE dosnt supply .classList array, just a className string. Manually build this....
            if (typeof thisChld.classList == 'undefined') {
                thisChld.classList = String(thisChld.className).split(' ');
            }
            
            if (thisChld.classList[0] == 'jqplot-point-label') {

                var chldIsRemoved = false;

                if (typeof thisChld.isRemoved != 'undefined') {
                    chldIsRemoved = thisChld.isRemoved;
                }

                if (chldIsRemoved == false) {
                    // index this point labels position

                    var thisLeftNbrValue = parseInt(String(thisChld.style.left).replace('px', '')); // remove "px" from string, and conver to number
                    var thisTopNbrValue = parseInt(String(thisChld.style.top).replace('px', '')); // remove "px" from string, and conver to number
                    thisLabelValue = thisChld.value; // the value property should be given to this element form removeDecFromPointLabels

                    var thisIndex = 'left_' + thisLeftNbrValue;
                    if (typeof pointLabels_info[thisIndex] == 'undefined') {
                        pointLabels_info[thisIndex] = [];
                        pointLabels_indexes.push(thisIndex);
                    }

                    var thispLabelInfo = {
                        left: thisLeftNbrValue, 
                        top: thisTopNbrValue, 
                        value: thisLabelValue, 
                        obj: thisChld
                    };

                    pointLabels_info[thisIndex].push(thispLabelInfo);
                }
            }
        }
        
        // Ensure point labels do not collide with others
        for (var x = 0; x < pointLabels_indexes.length; x++) {
            
            var thisIndex = pointLabels_indexes[x];
            
            for (var y = 0; y < pointLabels_info[thisIndex].length; y++) {
                
                /* now determin the distance between this point label, and all
                   point labels within this column. pointLabels_info[thisIndex]
                   holds all point labels within this column. */
                
                var thisPointLabel = pointLabels_info[thisIndex][y];
                
                for (var c = 0; c < pointLabels_info[thisIndex].length; c++) {
                
                    var checkAgainst = pointLabels_info[thisIndex][c];
                    
                    // get the distance from thisPointLabel to checkAgainst point label
                    d = Math.abs(checkAgainst['top'] - thisPointLabel['top']);
                    
                    if (d < 12 && d != 0) {
                        
                        // remove whichever label has the lower number
                        
                        if (checkAgainst['value'] < thisPointLabel['value']) {
                            checkAgainst['obj'].innerHTML = '';
                            checkAgainst['obj'].isRemoved = true;
                        } else {
                            thisPointLabel['obj'].innerHTML = '';
                            checkAgainst['obj'].isRemoved = true;
                        }
                        
                        // We jave just removed a point label, so this function will need to be run again
                        // as the labels will need to be reindexed.
                        
                        removeOverlappingPointLabels(chartParamsObj)
                        return;
                    }
                }
            }
            
        }
        
}

function hideButtonClick(scope, chartParamsObj, obj)
{
    setChartSettingsVisibility(chartParamsObj , false);
}

/**
 * Controles if the YUI-tab-view of the settings for a given drawn chart on the DOM
 * is visible or not.
 *
 * Expects: A (chart-)object generated from Fisma_Chart->export('array')
 *
 * @param object
 * @return void
 */
function setChartSettingsVisibility(chartId, boolVisible)
{
    var menuHolderId = chartId + 'WidgetSpaceHolder';
    var menuObj = document.getElementById(menuHolderId);
    
    if (boolVisible == 'toggle') {
        if (menuObj.style.display == 'none') {
            boolVisible = true;
        } else {
            boolVisible = false;
        }
    }
    
    if (boolVisible == true) {
        menuObj.style.display = '';
    } else {
        menuObj.style.display = 'none';
    }
}

/**
 * Will take values from checkboxes/textboxes within the Global Settings tab of
 * a chart and save each settings into cookies, and then trigger redrawAllCharts()
 *
 * Expects: A (chart-)object generated from Fisma_Chart->export('array')
 *
 * @param object
 * @return void
 */
function globalSettingUpdate(chartUniqueId)
{
    // get this chart's GlobSettings menue
    var settingsMenue = document.getElementById(chartUniqueId + 'GlobSettings');
    
    // get all elements of this chart's GlobSettings menue
    var settingOpts = settingsMenue.childNodes;
    
    for (var x = 0; x < settingOpts.length; x++) {
        var thisOpt = settingOpts[x];
        if (thisOpt.nodeName == 'INPUT') {
            if (thisOpt.type == 'checkbox') {
                setGlobalSetting(thisOpt.id, thisOpt.checked);
            } else {
                setGlobalSetting(thisOpt.id, thisOpt.value);
            }
        }
    }
    
    redrawAllCharts();
}

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
function globalSettingRefreashUI(chartParamsObj)
{
    /*
        Every input-element (setting UI) has an id equal to the cookie name 
        to which its value is stored. So wee we have to do is look for a
        cookie based on the id for each input element
    */
    
    // get this chart's GlobSettings menue
    var settingsMenue = document.getElementById(chartParamsObj['uniqueid'] + 'GlobSettings');
    
    // get all elements of this chart's GlobSettings menue
    var settingOpts = settingsMenue.childNodes;
    
    for (var x = 0; x < settingOpts.length; x++) {
        var thisOpt = settingOpts[x];
        if (thisOpt.nodeName == 'INPUT') {
        
            // By this line (and in this block), we know we have found an input element on this GlobSettings menue
            
            if (thisOpt.type == 'checkbox') {
                thisOpt.checked = (getGlobalSetting(thisOpt.id)=='true') ? true : false;
            } else {
                thisOpt.value = getGlobalSetting(thisOpt.id);
                thisOpt.text = thisOpt.value;
            }
        }
    }
}

function showSetingMode(showBasic)
{
    if (showBasic == true) {
        var showThese = document.getElementsByName('chartSettingsBasic')
        var hideThese = document.getElementsByName('chartSettingsGlobal')
    } else {
        var hideThese = document.getElementsByName('chartSettingsBasic')
        var showThese = document.getElementsByName('chartSettingsGlobal')
    }
    
    for (var x = 0; x < hideThese.length; x++) {
        hideThese[x].style.display = 'none';
    }
    
    for (var x = 0; x < hideThese.length; x++) {
            showThese[x].style.display = '';
        }
    
}

function getGlobalSetting(settingName)
{

    var rtnValue = getCookie('chartGlobSetting_' + settingName, '-RETURN-DEFAULT-SETTING-');

    if (rtnValue != '-RETURN-DEFAULT-SETTING-') {
        return rtnValue;
    } else {
    
        if (typeof globalSettingsDefaults[settingName] == 'undefined') {
            throw 'You have referenced a global setting (' + settingName + '), but have not defined a default value for it! Please defined a def-value in the object called globalSettingsDefaults that is located within the global scope of jqplotWrapper.js';
        } else {
            return String(globalSettingsDefaults[settingName]);
        }
    }

}

function setGlobalSetting(settingName, newValue)
{
    setCookie('chartGlobSetting_' + settingName, newValue);
}

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
function alterChartByGlobals(chartParamObj)
{
    
    // Show bar shadows?
    if (getGlobalSetting('barShadows') == 'true') {
        chartParamObj.seriesDefaults.rendererOptions.shadowDepth = 3;
        chartParamObj.seriesDefaults.rendererOptions.shadowOffset = 3;
    }
    
    // Depth of bar shadows?
    if (getGlobalSetting('barShadowDepth') != 'no-setting' && getGlobalSetting('barShadows') == 'true') {
        chartParamObj.seriesDefaults.rendererOptions.shadowDepth = getGlobalSetting('barShadowDepth');
        chartParamObj.seriesDefaults.rendererOptions.shadowOffset = getGlobalSetting('barShadowDepth');
    }
    
    // grid-lines?
    if (getGlobalSetting('gridLines') == 'true') {
        chartParamObj.grid.gridLineWidth = 1;
        chartParamObj.grid.borderWidth = 0;
        chartParamObj.grid.gridLineColor = undefined;
        chartParamObj.grid.drawGridLines = true;
        chartParamObj.grid.show = true;
    }
    
    // grid-lines?
    if (getGlobalSetting('dropShadows') != 'false') {
        chartParamObj.grid.shadow = true;
    }   

    // point labels?
    if (getGlobalSetting('pointLabels') == 'true') {
        chartParamObj.seriesDefaults.pointLabels.show = true;
    }
    
    // point labels outline?
        /* no alterations to the chartParamObject needs to be done here, this is handeled by removeDecFromPointLabels() */  
    
    
    return chartParamObj;
}

function redrawAllCharts()
{

    for (var uniqueid in chartsOnDOM) {
    
        var thisParamObj = chartsOnDOM[uniqueid];
        
        // redraw chart
        createJQChart(thisParamObj);
        
        // refreash Global Settings UI
        globalSettingRefreashUI(thisParamObj);
    }

}

/**
 * Will insert a "No data to plot" message when there is no 
 * data to plot, or all plot data are 0s
 *
 * Expects: A (chart) object generated from Fisma_Chart->export('array')
 * @param object
 * @return void
 */
function showMsgOnEmptyChart(chartParamsObj)
{

    if (chartIsEmpty(chartParamsObj)) {
        var targDiv = document.getElementById(chartParamsObj['uniqueid']);
        var injectHTML = 'No data to plot.';
        var insertBeforeChild = targDiv.childNodes[1];
        var msgOnDom = document.createElement('div');
        msgOnDom.height = '100%';
        msgOnDom.style.align = 'center';
        msgOnDom.style.position = 'absolute';
        msgOnDom.style.width = chartParamsObj['width'] + 'px';
        msgOnDom.style.height = '100%';
        msgOnDom.style.textAlign = 'center';
        msgOnDom.style.verticalAlign = 'middle';
        var inserted = targDiv.insertBefore(msgOnDom, insertBeforeChild);
        inserted.innerHTML = injectHTML;
    }
}

/**
 * Returns true if there is no data to 
 * plot, or if all plot data are 0s
 *
 * Expects: A (chart) object generated from Fisma_Chart->export('array')
 * @param object
 * @return boolean
 */
function chartIsEmpty(chartParamsObj)
{

    // Is all data 0?
    var isAll0Data = true;
    for (var x = 0; x < chartParamsObj['chartData'].length; x++) {
    
        if (typeof chartParamsObj['chartData'][x] == 'object') {
            
            for (var y = 0; y < chartParamsObj['chartData'][x].length; y++) {
                if (parseInt(chartParamsObj['chartData'][x][y]) > 0) { isAll0Data = false; }
            }
            
        } else {
            if (parseInt(chartParamsObj['chartData'][x]) > 0)
                isAll0Data = false;
        }
    
    }
    
    return isAll0Data;
}

function getNextNumberDivisibleBy5(nbr)
{

    nbr = Math.round(nbr);

    for (var x = 0; x < 8; x++ ) {
    
        var dividedBy5 = (nbr / 5);

        // is this a whole number?
        if (dividedBy5 == Math.round(dividedBy5)) {
            return nbr;
        } else {
            // currently nbr is not divisible by 5, increment and keep searching
            nbr++;
        }
    }

}

function setCookie(c_name,value,expiredays)
{
    var exdate=new Date();
    exdate.setDate(exdate.getDate()+expiredays);
    document.cookie = c_name + "=" + escape(value)+((expiredays==null) ? "" : ";expires="+exdate.toUTCString());
}

function getCookie(c_name, defaultValue)
{
    if (document.cookie.length>0) {

        c_start=document.cookie.indexOf(c_name + "=");

        if (c_start!=-1) {
            c_start = c_start + c_name.length + 1;
            c_end = document.cookie.indexOf(";",c_start);
            if (c_end==-1) c_end=document.cookie.length;
            return unescape(document.cookie.substring(c_start,c_end));
        }
    }

    if (typeof defaultValue != 'undefined') {
        return defaultValue;
    } else {
        return '';
    }
}
