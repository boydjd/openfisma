/*
    bool chartObjData(obj)
    Creates a jgPlot chart based on the data in Obj

    Input
       Obj[...]
          Obj['uniqueid']       The name of the div for jqPlot create canvases inside of. The data within this div will be erased before chart plotting.
          Obj['externalSource'] An internal URL to load any of, or the rest of, the elements of this object. The content of the target URL given is expected to be a json responce. Any and all elements within the "chart" variable/object will be imported into this one.
          Obj['title']          The title to render above the chart
          Obj['chartType']      String that must be "bar", "stackedbar", "line", or "pie"
          Obj['chartData']      Array to pass to jqPlot as the data to plot (numbers).
          Obj['chartDataText']  Array of labels (strings) for each data set in chartData (x-axis of bar charts)
          Obj['concatXLabel']   Boolean that states if " (#)" should be concatinated at the end of each x-axis label (default=true)
          Obj['chartLayerText'] Array of labels (strings) for each different line/layer in a milti-line-char or stacked-bar-chart
          Obj['colors']         (optional) Array of colors for the chart to use across layers
          Obj['links']          (optional) Array of links of which the browser should navigate to when a given data element is clicked
          Obj['linksdebug']     (optional) Boolean, if set true, an alert box of what was clicked on will pop up instead of browser navigation based on Obj['links']

    Output
       returns true on success, false on failure, or nothing if the success of the chart creation cannot be determind at that time (asynchronous mode)
*/

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
 * Creates a chart within a div by the name of param['uniqueid'].
 * All paramiters needed to create the chart are expected to be within the param object.
 * This function may return before the actual creation of a chart if there is an external source.
 * Returns true on success, false on failure, and the integer 3 when on external source
 *
 * @return boolean/integer
 */
function createJQChart(param)
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
    param = jQuery.extend(true, defaultParams, param);

    // param validation
    if (document.getElementById(param['uniqueid']) == false) {
        alert('createJQChart Error - The target div/uniqueid does not exists');
        return false;
    }

    // set chart width to param['param']
    setChartWidthAttribs(param);

    // Ensure the load spinner is visible
    makeElementVisible(param['uniqueid'] + 'loader');

    // is the data being loaded from an external source? (Or is it all in the param obj?)
    if (param['externalSource']) {
        
        /*
          If it is being loaded from an external source
            setup a json request
            have the json request return to createJQChart_asynchReturn
            exit this function as createJQChart_asynchReturn will call this function again with the same param object with param['externalSource'] taken out
        */

        document.getElementById(param['uniqueid']).innerHTML = 'Loading chart data...';

        // note externalSource, and remove/relocate it from its place in param[] so it dosnt retain and cause us to loop 
        var externalSource = param['externalSource'];
        if (!param['oldExternalSource']) { param['oldExternalSource'] = param['externalSource']; }
        param['externalSource'] = undefined;
        
        // Send data from widgets to external data source if needed7 (will load from cookies and defaults if widgets are not drawn yet)
        param = buildExternalSourceParams(param);
        externalSource += String(param['externalSourceParams']).replace(/ /g,'%20');
        param['lastURLpull'] = externalSource;

        // Are we debugging the external source?
        if (param['externalSourceDebug']) {
            var doNav = confirm ('Now pulling from external source: ' + externalSource + '\n\nWould you like to navigate here?')
            if (doNav) {
                document.location = externalSource;
            }
        }

        var myDataSource = new YAHOO.util.DataSource(externalSource);
        myDataSource.responseType = YAHOO.util.DataSource.TYPE_JSON;
        myDataSource.responseSchema = {resultsList: "chart"};

        var callBackFunct = new Function ("requestNumber", "value", "exception", "createJQChart_asynchReturn(requestNumber, value, exception, " + YAHOO.lang.JSON.stringify(param) + ");");

        var callback1 = {
            success : callBackFunct,
            failure : callBackFunct
        };
        myDataSource.sendRequest("", callback1);

        return 3;
    }

    // clear the chart area
    document.getElementById(param['uniqueid']).innerHTML = '';
        document.getElementById(param['uniqueid']).className = '';
        document.getElementById(param['uniqueid'] + 'toplegend').innerHTML = '';

    // handel aliases and short-cut vars
    if (typeof param['barMargin'] != 'undefined') {
        param = jQuery.extend(true, param, {'seriesDefaults': {'rendererOptions': {'barMargin': param['barMargin']}}});
        param['barMargin'] = undefined;
    }
    if (typeof param['legendLocation'] != 'undefined') {
        param = jQuery.extend(true, param, {'legend': {'location': param['legendLocation'] }});
        param['legendLocation'] = undefined;
    }
    if (typeof param['legendRowCount'] != 'undefined') {
        param = jQuery.extend(true, param, {'legend': {'rendererOptions': {'numberRows': param['legendRowCount']}}});
        param['legendRowCount'] = undefined;
    }
    
    // make sure the numbers to be plotted in param['chartData'] are infact numbers and not an array of strings of numbers
    param['chartData'] = forceIntegerArray(param['chartData']);

    // hide the loading spinner and show the canvas target
    document.getElementById(param['uniqueid'] + 'holder').style.display = '';
    makeElementInvisible(param['uniqueid'] + 'holder');
    document.getElementById(param['uniqueid'] + 'loader').style.position = 'absolute';
    document.getElementById(param['uniqueid'] + 'loader').finnishFadeCallback = new Function ("fadeIn('" + param['uniqueid'] + "holder', 500);");
    fadeOut(param['uniqueid'] + 'loader', 500);

    // now that we have the param['chartData'], do we need to make the chart larger and scrollable?
    setChartWidthAttribs(param);

    // Store this charts paramiter object into the global variable chartsOnDOM, so it can be redrawn
    // This must be done before the next switch block that translates some data within the param object for jqPlot
    chartsOnDOM[param['uniqueid']] = jQuery.extend(true, {}, param);
    
    // call the correct function based on chartType
    switch(param['chartType'])
    {
        case 'stackedbar':
            param['varyBarColor'] = false;
                        if (typeof param['showlegend'] == 'undefined') { param['showlegend'] = true; }
            var rtn = createJQChart_StackedBar(param);
            break;
        case 'bar':

            // Is this a simple-bar chart (not-stacked-bar) with multiple series?
            if (typeof param['chartData'][0] =='object') {

                // the chartData is already a multi dimensional array, and the chartType is bar, not stacked bar. So we assume it is a simple-bar chart with multi series
                // thus we will leave the chartData array as is (as opposed to forcing it to a 2 dim array, and claming it to be a stacked bar chart with no other layers of bars (a lazy but functional of creating a regular bar charts from the stacked-bar chart renderer)

                param['varyBarColor'] = false;
                param['showlegend'] = true;

            } else {
                param['chartData'] = [param['chartData']];  // force to 2 dimensional array
                param['links'] = [param['links']];
                param['varyBarColor'] = true;
                param['showlegend'] = false;
            }
            
            param['stackSeries'] = false;
            var rtn = createJQChart_StackedBar(param);
            break;

        case 'line':
            var rtn = createChartJQStackedLine(param);
            break;
        case 'stackedline':
            var rtn = createChartJQStackedLine(param);
            break;
        case 'pie':
            param['links'] = [param['links']];
            var rtn = createChartJQPie(param);
            break;
        default:
            alert('createJQChart Error - chartType is invalid (' + param['chartType'] + ')');
            return false;
    }


    // chart tweeking external to the jqPlot library
    removeOverlappingPointLabels(param);
    applyChartBackground(param);
    applyChartWidgets(param);
    createChartThreatLegend(param);
    applyChartBorders(param);
    globalSettingRefreashUI(param);
    
    // Handel table for screen readers
    var dataTableObj = document.getElementById(param['uniqueid'] + 'table');
    dataTableObj.innerHTML = getTableFromChartData(param);
    if (getGlobalSetting('showDataTable') === 'true') {
        // Show the table generated based on chart data
        dataTableObj.style.display = '';
        // Hide, erase, and collapse the container of the chart divs
        document.getElementById(param['uniqueid']).innerHTML = '';
        document.getElementById(param['uniqueid']).style.width = 0;
        document.getElementById(param['uniqueid']).style.height = 0;
        // Ensure the threat-level-legend is hidden
        document.getElementById(param['uniqueid'] + 'toplegend').style.display = 'none';
    } else {
        dataTableObj.style.display = 'none';
    }

    return rtn;
}


/**
 * When an external source is needed, this function should handel the returned JSON request
 * The param object that went into createJQChart(obj) would be the parameter "param" here, and
 * the "value" parameter should be the returned JSON request.
 * the param and value objects are merged togeather based in inheritance controle and 
 * Returns the return value of createJQChart(), or false on external source failure.
 *
 * @return boolean/integer
 */
function createJQChart_asynchReturn(requestNumber, value, exception, param)
{

    if (value) {
        
        if (value['results'][0]) {
            if (value['results'][0]['inheritCtl']) {
                if (value['results'][0]['inheritCtl'] == 'minimal') {
                    var joinedParam = value['results'][0];
                    joinedParam['width'] = param['width'];
                    joinedParam['height'] = param['height'];
                    joinedParam['uniqueid'] = param['uniqueid'];
                    joinedParam['externalSource'] = param['externalSource'];
                    joinedParam['oldExternalSource'] = param['oldExternalSource'];
                    joinedParam['widgets'] = param['widgets'];
                } else if (value['results'][0]['inheritCtl'] == 'none') {
                    var joinedParam = value['results'][0];
                } else {
                    alert('Error - Unknown chart inheritance mode');
                }
            } else {
                var joinedParam = jQuery.extend(true, param, value['results'][0],true);
            }
        } else {
            if (confirm('Error - Chart creation failed due to data source error.\nIf you continuously see this message, please click Ok to navigate to data source, and copy-and-pase the text&data from there into email to Endeavor Systems.\n\nNavigate to the error-source?')) {
                document.location = param['lastURLpull'];
            }
        }

        if (!joinedParam['chartData']) {
            alert('Chart Error - The remote data source for chart "' + param['uniqueid'] + '" located at ' + param['lastURLpull'] + ' did not return data to plot on a chart');
        }

        // call the createJQChart() with the param-object initally given to createJQChart() and the merged responce object
        return createJQChart(joinedParam);
        
    } else {
        if (confirm('Error - Chart creation failed due to data source error.\nIf you continuously see this message, please click Ok to navigate to data source, and copy-and-pase the text&data from there into email to Endeavor Systems.\n\nNavigate to the error-source?')) {
            document.location = param['lastURLpull'];
        }
    }
    
    return false;
}

function createChartJQPie(param)
{
    usedLabelsPie = param['chartDataText'];

    var dataSet = [];

    for (var x = 0; x < param['chartData'].length; x++) {
        param['chartDataText'][x] += ' (' + param['chartData'][x]  + ')';
        dataSet[dataSet.length] = [param['chartDataText'][x], param['chartData'][x]];
    }
    

    var jPlotParamObj = {
        title: param['title'],
        seriesColors: param['colors'],
        grid: {
            drawBorder: false,
            drawGridlines: false,
            shadow: false
        },
        axes: {
            xaxis:{
                tickOptions: {
                    angle: param['DataTextAngle'],
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
                lineLabelsLineColor: '#777'
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
    $("[id="+param['uniqueid']+"]").css('height', param['height']);

    // merge any jqPlot direct param-arguments into jPlotParamObj from param
    jPlotParamObj = jQuery.extend(true, jPlotParamObj, param);

    plot1 = $.jqplot(param['uniqueid'], [dataSet], jPlotParamObj);

    // create an event handeling function that calls chartClickEvent while preserving the parm object
    var EvntHandler = new Function ("ev", "seriesIndex", "pointIndex", "data", "var thisChartParamObj = " + YAHOO.lang.JSON.stringify(param) + "; chartClickEvent(ev, seriesIndex, pointIndex, data, thisChartParamObj);" );
    
    // use the created function as the click-event-handeler
    $('#' + param['uniqueid']).bind('jqplotDataClick', EvntHandler);

}

function createJQChart_StackedBar(param)
{
    var dataSet = [];
    var thisSum = 0;
    var maxSumOfAll = 0;
    var chartCeilingValue = 0;

    for (var x = 0; x < param['chartDataText'].length; x++) {
    
        thisSum = 0;
        
        for (var y = 0; y < param['chartData'].length; y++) {
            thisSum += param['chartData'][y][x];
        }
        
        if (thisSum > maxSumOfAll) { maxSumOfAll = thisSum; }

        if (param['concatXLabel'] == true) {
            param['chartDataText'][x] += ' (' + thisSum  + ')';
        }
        
    }

    var seriesParam = [];
    if (param['chartLayerText']) {
        for (x = 0; x < param['chartLayerText'].length; x++) {
            seriesParam[x] = {label: param['chartLayerText'][x]};
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
        title: param['title'],
        seriesColors: param['colors'],
        stackSeries: true,
        series: seriesParam,
        seriesDefaults:{
            renderer: $.jqplot.BarRenderer,
            rendererOptions:{
                barWidth: 35,
                showDataLabels: true,
                varyBarColor: param['varyBarColor'],
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
                label: param['AxisLabelX'],
                labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
                renderer: $.jqplot.CategoryAxisRenderer,
                ticks: param['chartDataText'],
                tickOptions: {
                    angle: param['DataTextAngle'],
                    fontFamily: 'arial, helvetica, clean, sans-serif',
                    fontSize: '10pt',
                    textColor: '#555555'
                }
            },
            yaxis:{
                label: param['AxisLabelY'],
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
            drawGridLines: param['drawGridLines'],
            show: param['drawGridLines']
            },
        legend: {
                    show: param['showlegend'],
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
    $("[id="+param['uniqueid']+"]").css('height', param['height']);
    
    // merge any jqPlot direct param-arguments into jPlotParamObj from param
    jPlotParamObj = jQuery.extend(true, jPlotParamObj, param);
    
    // override any jqPlot direct param-arguments based on globals setting from cookies (set by user)
    jPlotParamObj = alterChartByGlobals(jPlotParamObj);

    plot1 = $.jqplot(param['uniqueid'], param['chartData'], jPlotParamObj);

    
    var EvntHandler = new Function ("ev", "seriesIndex", "pointIndex", "data", "var thisChartParamObj = " + YAHOO.lang.JSON.stringify(param) + "; chartClickEvent(ev, seriesIndex, pointIndex, data, thisChartParamObj);" );
    $('#' + param['uniqueid']).bind('jqplotDataClick', EvntHandler);

    removeDecFromPointLabels(param);

}

function createChartJQStackedLine(param)
{
    var dataSet = [];
    var thisSum = 0;

    for (var x = 0; x < param['chartDataText'].length; x++) {
    
        thisSum = 0;
        
        for (var y = 0; y < ['chartData'].length; y++) {
            thisSum += ['chartData'][y][x];
        }
        
        param['chartDataText'][x] += ' (' + thisSum  + ')';
    }
        
    plot1 = $.jqplot(param['uniqueid'], param['chartData'], {
        title: param['title'],
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
                ticks:param['chartDataText']
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

    $('#' + param['uniqueid']).bind('jqplotDataClick',
        function (ev, seriesIndex, pointIndex, data) {
            alert('You clicked on bar-level ' + seriesIndex + ' in column: ' + pointIndex);
        }
    );

}

/**
 * Creates the red-orange-yello threat-legend that shows above charts
 * The generated HTML code should go into the div with the id of the
 * chart's uniqueId + "toplegend"
 *
 * @return boolean/integer
 */
function createChartThreatLegend(param)
{
    /*
        Creates a red-orange-yellow legent above the chart
    */

    if (param['showThreatLegend']) {
        if (param['showThreatLegend'] == true) {

            // Is a width given for the width of the legend? OR should we assume 100%?
            var tLegWidth = '100%';
            if (param['threatLegendWidth']) {
                tLegWidth = param['threatLegendWidth'];
            }

            var injectHTML = '<table style="font-size: 12px; color: #555555;" width="' + tLegWidth + '">  <tr>    <td style="text-align: center;" width="40%">Threat Level</td>    <td width="20%">    <table>      <tr>        <td bgcolor="#FF0000" width="1px">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>        <td>&nbsp;High</td>      </tr>    </table>    </td>    <td width="20%">    <table>      <tr>        <td bgcolor="#FF6600" width="1px">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>        <td>&nbsp;Moderate&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>      </tr>    </table>    </td>    <td width="20%">    <table>      <tr>        <td bgcolor="#FFC000" width="1px">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>        <td>&nbsp;Low</td>      </tr>    </table>    </td>  </tr></table>';
            var thisChartId = param['uniqueid'];
            var topLegendOnDOM = document.getElementById(thisChartId + 'toplegend');

            topLegendOnDOM.innerHTML = injectHTML;
        }
    }        
}

function chartClickEvent(ev, seriesIndex, pointIndex, data, paramObj) {
    
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
function forceIntegerArray(inptArray) {
    for (var x = 0; x < inptArray.length; x++) {
        if (typeof inptArray[x] == 'object') {
            inptArray[x] = forceIntegerArray(inptArray[x]);
        } else {
            inptArray[x] = inptArray[x] * 1;    // make sure this is an int, and not a string of a number
        }
    }

    return inptArray;
}

/**
 * Manually draws borders onto the shadow canvas
 * This function is nessesary as jqPlot's API does not allow 
 * you to choose which borders are drawn and which are not.
 * If "L" exists within param['borders'], the left border is
 * drawn, if "R" does (too), then the right is drawn and so on.
 *
 * @return void
 */
function applyChartBorders(param) {

    // What borders should be drawn? (L = left, B = bottom, R = right, T = top)
    if (typeof param['borders'] == 'undefined') {
        if (param['chartType'] == 'bar' || param['chartType'] == 'stackedbar') {
            // default for bar and stacked bar charts are bottom-left (BL)
            param['borders'] = 'BL';
        } else {
            // assume no default for other chart types
            return;
        }
    }

    // Get the area of our containing divs
    var targDiv = document.getElementById(param['uniqueid']);
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
                    if (param['borders'].indexOf('L') != -1) {
                        context.moveTo(0,0);
                        context.lineTo(0, h);
                        context.stroke();
                    }               

                    // Draw bottom border?
                    if (param['borders'].indexOf('B') != -1) {
                        context.moveTo(0, h);
                        context.lineTo(w, h);
                        context.stroke();
                    }

                    // Draw right border?
                    if (param['borders'].indexOf('R') != -1) {
                        context.moveTo(w, 0);
                        context.lineTo(w, h);
                        context.stroke();
                    }

                    // Draw top border?
                    if (param['borders'].indexOf('T') != -1) {
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

function applyChartBackground(param) {

    var targDiv = document.getElementById(param['uniqueid']);

    // Dont display a background? Defined in either nobackground or background.nobackground
    if (param['nobackground']) {
        if (param['nobackground'] == true) { return; }
    }
    if (param['background']) {
        if (param['background']['nobackground']) {
            if (param['background']['nobackground'] == true) { return; }
        }
    }
    
    // What is the HTML we should inject?
    var backURL = '/images/logoShark.png'; // default location
    if (param['background']) { if (param['background']['URL']) { backURL = param['background']['URL']; } }
    var injectHTML = '<img height="100%" src="' + backURL + '" style="opacity:0.15;filter:alpha(opacity=15);opacity:0.15" />';

    // But wait, is there an override issued for the HTML of the background to inject?
    if (param['background']) {
        if (param['background']['overrideHTML']) {
            backURL = param['background']['overrideHTML'];
        }
    }

    // Where do we inject the background in the DOM? (different for differnt chart rederers)
    if (param['chartType'] == 'pie') {
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
function applyChartWidgets(param) {

    var wigSpace = document.getElementById(param['uniqueid'] + 'WidgetSpace');

    // Are there widgets for this chart?
    if (typeof param['widgets'] == 'undefined') {
        wigSpace.innerHTML = '<br/><i>There are no parameters for this chart.</i><br/><br/>';
        return;
    } else if (param['widgets'].length == 0) {
        wigSpace.innerHTML = '<br/><i>There are no parameters for this chart.</i><br/><br/>';
        return;
    }

    if (param['widgets']) {

        var addHTML = '';

        for (var x = 0; x < param['widgets'].length; x++) {

            var thisWidget = param['widgets'][x];
            
            // create a widget id if one is not explicitly given
            if (!thisWidget['uniqueid']) {
                thisWidget['uniqueid'] = param['uniqueid'] + '_widget' + x;
                param['widgets'][x]['uniqueid'] = thisWidget['uniqueid'];
            }

            // print the label text to be displayed to the left of the widget if one is given
            addHTML += '<tr><td nowrap align=left>' + thisWidget['label'] + ' </td><td><td nowrap width="10"></td><td width="99%" align=left>';

            switch(thisWidget['type']) {
                case 'combo':

                    addHTML += '<select id="' + thisWidget['uniqueid'] + '" onChange="widgetEvent(' + YAHOO.lang.JSON.stringify(param).replace(/"/g, "'") + ');">';
                                        // " // ( comment double quote to fix syntax highlight errors with /"/g on previus line )

                    for (var y = 0; y < thisWidget['options'].length; y++) {
                        addHTML += '<option value="' + thisWidget['options'][y] + '">' + thisWidget['options'][y] + '</option><br/>';
                    }
                    
                    addHTML += '</select>';

                    break;

                case 'text':
    
                    addHTML += '<input onKeyDown="if(event.keyCode==13){widgetEvent(' + YAHOO.lang.JSON.stringify(param).replace(/"/g, "'") + ');};" type="textbox" id="' + thisWidget['uniqueid'] + '" />';
                                        // " // ( comment double quote to fix syntax highlight errors with /"/g on previus line )
                    break;

                default:
                    alert('Error - Widget ' + x + "'s type (" + thisWidget['type'] + ') is not a known widget type');
                    return false;
            }

            
            addHTML += '</td></tr>';
            
        }

        // add this widget HTML to the DOM
        wigSpace.innerHTML = '<table>' + addHTML + '</table>';
        
    }

    applyChartWidgetSettings(param);
}

/**
 * Looks at param["widget"], or for every chart-options/widget, loads the
 * values for this opt/widget into the user-interface object for this option.
 * This value may be loaded froma saved cookie, fallback to a default, or
 * be foreced to a certain value every time if the PHP wrapper demands it.
 *
 * @return void
 */
function applyChartWidgetSettings(param) {

    if (param['widgets']) {

        for (var x = 0; x < param['widgets'].length; x++) {

            var thisWidget = param['widgets'][x];
            
            // load the value for widgets
            var thisWigInDOM = document.getElementById(thisWidget['uniqueid']);
            if (thisWidget['forcevalue']) {
                // this widget value is forced to a certain value upon every load/reload
                thisWigInDOM.value = thisWidget['forcevalue'];
                thisWigInDOM.text = thisWidget['forcevalue'];
            } else {
                var thisWigCookieValue = getCookie(param['uniqueid'] + '_' + thisWidget['uniqueid']);
                if (thisWigCookieValue != '') {
                    // the value has been coosen in the past and is stored as a cookie
                    thisWigCookieValue = thisWigCookieValue.replace(/%20/g, ' ');
                    thisWigInDOM.value = thisWigCookieValue;
                    thisWigInDOM.text = thisWigCookieValue;
                } else {
                    // no saved value/cookie. Is there a default given in the param object
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
 * Returns the param object given to this function with param['externalSourceParams'] altered.
 *
 * @return Array
 */
function buildExternalSourceParams(param) {

    // build arguments to send to the remote data source

    var thisWidgetValue = '';
    param['externalSourceParams'] = '';

    if (param['widgets']) {
        for (var x = 0; x < param['widgets'].length; x++) {

            var thisWidget = param['widgets'][x];
            var thisWidgetName = thisWidget['uniqueid'];
            var thisWidgetOnDOM = document.getElementById(thisWidgetName);

            // is this widget actully on the DOM? Or should we load the cookie?         
            if (thisWidgetOnDOM) {
                // widget is on the DOM
                thisWidgetValue = thisWidgetOnDOM.value;
            } else {
                // not on DOM, is there a cookie?
                var thisWigCookieValue = getCookie(param['uniqueid'] + '_' + thisWidget['uniqueid']);
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

            param['externalSourceParams'] += '/' + thisWidgetName + '/' + thisWidgetValue 
        }
    }

    return param;
}

function widgetEvent(param) {

    // first, save the widget values (as cookies) so they can be retained later when the widgets get redrawn
    if (param['widgets']) {
        for (var x = 0; x < param['widgets'].length; x++) {
            var thisWidgetName = param['widgets'][x]['uniqueid'];
            var thisWidgetValue = document.getElementById(thisWidgetName).value;
            setCookie(param['uniqueid'] + '_' + thisWidgetName,thisWidgetValue,400);
        }
    }

    // build arguments to send to the remote data source
    param = buildExternalSourceParams(param);

    // restore externalSource so a json request is fired when calling createJQPChart
    param['externalSource'] = param['oldExternalSource'];
    param['oldExternalSource'] = undefined;

    param['chartData'] = undefined;
    param['chartDataText'] = undefined;

    // re-create chart entirly
    document.getElementById(param['uniqueid'] + 'holder').finnishFadeCallback = new Function ("makeElementVisible('" + param['uniqueid'] + "loader'); createJQChart(" + YAHOO.lang.JSON.stringify(param) + "); this.finnishFadeCallback = '';");
    fadeOut(param['uniqueid'] + 'holder', 300);

}

function makeElementVisible(eleId) {
    var ele = document.getElementById(eleId);
    ele.style.opacity = '1';
    ele.style.filter = "alpha(opacity = '100')";
}

function makeElementInvisible(eleId) {
    var ele = document.getElementById(eleId);
    ele.style.opacity = '0';
    ele.style.filter = "alpha(opacity = '0')";
}

function fadeIn(eid, TimeToFade) {

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

function fadeOut(eid, TimeToFade) {

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

function fade(eid, TimeToFade) {

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
 * the with in param['width'] is less than the minimum with required by the chart (calculated
 * in this function).
 *
 * NOTE: This function does not actully look at the DOM. It assumes the author to used
 *       Fisma_Chart->setWidth() knew what he was doing and set it correctly.
 *       The static width given to charts is considered a minimum width.
 *
 * @return void
 */
function setChartWidthAttribs(param) {

    var makeScrollable = false;

    // Determin if we need to make this chart scrollable...
    // Do we really have the chart data to plot?
    if (param['chartData']) {
        // Is this a bar chart?
        if (param['chartType'] == 'bar' || param['chartType'] == 'stackedbar') {

            // How many bars does it have?
            if (param['chartType'] == 'stackedbar') {
                var barCount = param['chartData'][0].length;
            } else if (param['chartType'] == 'bar') {
                var barCount = param['chartData'].length;
            }

            // Assuming each bar margin is 10px, And each bar has a minimum width of 35px, how much space is needed total (minimum).
            var minSpaceRequired = (barCount * 10) + (barCount * 35) + 40;

            // Do we not have enough space for a non-scrolling chart?
            if (param['width'] < minSpaceRequired) {
                
                // We need to make this chart scrollable
                makeScrollable = true;
            }
        }
    }

    // Is auto-width enabeled? (set width to 100% and make scrollable)
    if (typeof param['autoWidth'] != 'undefined') {
        if (param['autoWidth'] == true) {
            makeScrollable = true;
        }
    }

    if (makeScrollable == true) {

        document.getElementById(param['uniqueid'] + 'loader').style.width = '100%';
        document.getElementById(param['uniqueid'] + 'holder').style.width = '100%';
        document.getElementById(param['uniqueid'] + 'holder').style.overflow = 'auto';
        document.getElementById(param['uniqueid']).style.width = minSpaceRequired + 'px';
        //document.getElementById(param['uniqueid']  + 'WidgetSpace').style.width = minSpaceRequired + 'px';
        document.getElementById(param['uniqueid']  + 'toplegend').style.width = minSpaceRequired + 'px';

        // handel alignment
        if (param['align'] == 'center') {
            document.getElementById(param['uniqueid']).style.marginLeft = 'auto';
            document.getElementById(param['uniqueid']).style.marginRight = 'auto';  
            //document.getElementById(param['uniqueid'] + 'WidgetSpace').style.marginLeft = 'auto';
            //document.getElementById(param['uniqueid'] + 'WidgetSpace').style.marginRight = 'auto';
            document.getElementById(param['uniqueid'] + 'toplegend').style.marginLeft = 'auto';
            document.getElementById(param['uniqueid'] + 'toplegend').style.marginRight = 'auto';
        }
        
    } else {

        document.getElementById(param['uniqueid'] + 'loader').style.width = '100%';
        document.getElementById(param['uniqueid'] + 'holder').style.width = param['width'] + 'px';
        document.getElementById(param['uniqueid'] + 'holder').style.overflow = '';
        document.getElementById(param['uniqueid']).style.width = param['width'] + 'px';
        document.getElementById(param['uniqueid'] + 'toplegend').width = param['width'] + 'px';
    }
    
}

/**
 * Builds a table based on the data to plot on the chart for screen readers.
 * The generated HTML should generally be placed in a div by the Id of the
 * chart's uniqueId + "table"
 *
 * @return String
 */
function getTableFromChartData(param)
{
    if (param['chartType'] === 'pie') {
        return getTableFromChartData_pieChart(param);
    } else {
        return getTableFromChartData_barChart(param);
    }
}

function getTableFromChartData_pieChart(param)
{
    var HTML = '<table width="100%" border=1><tr>';
    
    // row of slice-labels
    for (var x = 0; x < param['chartDataText'].length; x++) {
        HTML += '<th nowrap><b>' + param['chartDataText'][x] + '</b></th>';
    }
    HTML += '</tr><tr>';

    // row of data
    for (var x = 0; x < param['chartData'].length; x++) {

        HTML += '<td>' + param['chartData'][x] + '</td>';

    }

    HTML += '</tr></table>';

    return HTML;
}

function getTableFromChartData_barChart(param)
{
    var HTML = '<table width="100%" border=1>';
    
    // add a column for layer names if this is a stacked chart
    if (typeof param['chartLayerText'] != 'undefined') {
        HTML += '<tr><td></td>';
    }
    
    for (var x = 0; x < param['chartDataText'].length; x++) {
        HTML += '<th nowrap><b>' + param['chartDataText'][x] + '</b></th>';
    }
    HTML += '</tr>';

    for (var x = 0; x < param['chartData'].length; x++) {

        var thisEle = param['chartData'][x];
        HTML += '<tr>';
        
        // each layer label
        if (typeof param['chartLayerText'] != 'undefined') {
            HTML += '<th><b>' + param['chartLayerText'][x] + '</b></th>';
        }
        
        if (typeof(thisEle) == 'object') {

            for (var y = 0; y < thisEle.length; y++) {

                HTML += '<td>' + thisEle[y] + '</td>';
            }
            
        } else {

            HTML += '<td>' + thisEle + '</td>';
        }

        HTML += '</tr>';

    }

    HTML += '</table>';

    return HTML;
}

function removeDecFromPointLabels(param)
{
        var outlineStyle = '';
        var chartOnDOM = document.getElementById(param['uniqueid']);
    
        for (var x = 0; x < chartOnDOM.childNodes.length; x++) {
                
                var thisChld = chartOnDOM.childNodes[x];
                
                // IE Support - IE does not support .classList, manually make this
                if (typeof thisChld.classList == 'undefined') {
                    thisChld.classList = String(thisChld.className).split(' ');
                }
                
                if (thisChld.classList) {
                    if (thisChld.classList[0] == 'jqplot-point-label') {

                            // convert this from a string to a number to a string again (removes decimal if its needless)
                            thisLabelValue = thisChld.innerHTML * 1;
                            thisChld.innerHTML = thisLabelValue;
                            thisChld.value = thisLabelValue;

                            // if this number is 0, hide it (0s overlap with other numbers on bar charts)
                            if (thisChld.innerHTML * 1 == 0) {
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
                                
                                thisChld.innerHTML = '<span style="' + outlineStyle + param['pointLabelStyle'] + '">' + thisChld.innerHTML + '</span>';
                                thisChld.style.textShadow = 'text-shadow: #FFFFFF 0px -1px 0px, #FFFFFF 0px 1px 0px, #FFFFFF 1px 0px 0px, #FFFFFF -1px 1px 0px, #FFFFFF -1px -1px 0px, #FFFFFF 1px 1px 0px;';
                                
                            } else {
                                thisChld.innerHTML = '<span style="' + param['pointLabelStyle'] + '">' + thisChld.innerHTML + '</span>';
                            }

                            // adjust the label to the a little bit since with the decemal trimmed, it may seem off-centered
                            var thisLeftNbrValue = String(thisChld.style.left).replace('px', '') * 1;       // remove "px" from string, and conver to number
                            var thisTopNbrValue = String(thisChld.style.top).replace('px', '') * 1;       // remove "px" from string, and conver to number
                            thisLeftNbrValue += param['pointLabelAdjustX'];
                            thisTopNbrValue += param['pointLabelAdjustY'];
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

function removeOverlappingPointLabels(param) {

        // This function will deal with removing point labels that collie with eachother
        // There is no need for this unless this is a stacked-bar or stacked-line chart
        if (param['chartType'] != 'stackedbar' && param['chartType'] != 'stackedline') {
            return;
        }

        var chartOnDOM = document.getElementById(param['uniqueid']);

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

                    var thisLeftNbrValue = String(thisChld.style.left).replace('px', '') * 1; // remove "px" from string, and conver to number
                    var thisTopNbrValue = String(thisChld.style.top).replace('px', '') * 1; // remove "px" from string, and conver to number
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
                        
                        removeOverlappingPointLabels(param)
                        return;
                    }
                }
            }
            
        }
        
}

function hideButtonClick(scope, param, obj)
{
    setChartSettingsVisibility(param , false);
}

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

function globalSettingRefreashUI(param)
{
    /*
        Every input-element (setting UI) has an id equal to the cookie name 
        to which its value is stored. So wee we have to do is look for a
        cookie based on the id for each input element
    */
    
    // get this chart's GlobSettings menue
    var settingsMenue = document.getElementById(param['uniqueid'] + 'GlobSettings');
    
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

function showSetingMode(showBasic) {
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

function getGlobalSetting(settingName) {

    var rtnValue = getCookie('chartGlobSetting_' + settingName, '-RETURN-DEFAULT-SETTING-');

    if (rtnValue != '-RETURN-DEFAULT-SETTING-') {
        return rtnValue;
    } else {
    
        if (typeof globalSettingsDefaults[settingName] == 'undefined') {
            alert('You have referenced a global setting (' + settingName + '), but have not defined a default value for it! Please defined a def-value in the object called globalSettingsDefaults that is located within the global scope of jqplotWrapper.js');
        } else {
            return String(globalSettingsDefaults[settingName]);
        }
    }

}

function setGlobalSetting(settingName, newValue) {
    setCookie('chartGlobSetting_' + settingName, newValue);
}

function alterChartByGlobals(chartParamObj) {
    
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

function redrawAllCharts() {

    for (var uniqueid in chartsOnDOM) {
    
        var thisParamObj = chartsOnDOM[uniqueid];
        
        // redraw chart
        createJQChart(thisParamObj);
        
        // refreash Global Settings UI
        globalSettingRefreashUI(thisParamObj);
    }

}

function getNextNumberDivisibleBy5(nbr) {

    nbr = Math.round(nbr);

    for (var x = 0; x < 8; x++ ) {
    
        var dividedBy5 = (nbr / 5) * 1;

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
