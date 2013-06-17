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
 * Helper functions for rendering criteria
 *
 * @author    Yusef Pogue <yusef.pogue@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2012 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/content/license
 */

var FSC = {
    /**
     * criteria for all of the checked facets
     *
     * @type Array
     */
    checkedFacetCriteria: [],
    /**
     * manipulates the search criteria
     * @param jQuery_selector inputElement target HTML element
     * @returns
     */
    legendHandler: function(inputElement) {
        var $input = $(inputElement),
            $criterion = $(inputElement).parents('fieldset.criterion').first(),
            $header = $criterion.find('legend.header'),
            $content = $criterion.find('div.content'),
            checked = $input.is('input:checked');

        //advanced search panel
        var panel = Fisma.Search.advancedSearchPanel;

        var panelChildren = $(panel.container).children();
        var newIndex = panelChildren.length - 1;

        if (newIndex < 0) {
            newIndex = 0;
        }

        // holds the facet HTML element
        var facetContainer = $(inputElement).parent().parent();

        // name of the field
        var criterionField = facetContainer.attr('field');

        // type of criterion
        var criterionType = facetContainer.attr('criterion_type');

        // type of facet
        var facetType = facetContainer.attr('type');

        // id attribute for the search row associated with this facet
        var facetId = criterionField + '_criterion';

        if (checked) {
            $header
                .addClass('ui-accordion-header-active')
                .removeClass('ui-corner-all')
                .addClass('ui-corner-top');
            $content
                .addClass('ui-accordion-content-active');


            //add criterion

            panel.addCriteria(panelChildren.eq(newIndex));

            // holds the last criterion HTML element
            var criterionContainer = $(panel.container).children().last();

            criterionContainer.attr('id', facetId);
            criterionContainer.children().eq(1).find("select").val(
                criterionField).change();
            criterionContainer.children().eq(2).find("select").val(
                criterionType).change();

            if (facetType === 'organization')
            {
                //add another criteria for the organization itself
                panel.addCriteria(panelChildren.eq(newIndex + 1));

                // holds the last criterion HTML element
                criterionContainer = $(panel.container).children().last();

                criterionContainer.attr('id', 'organization_exact_criterion');
                criterionContainer.children().eq(1).find("select").val(
                    criterionField).change();
                criterionContainer.children().eq(2).find("select").val(
                    'textExactMatch').change();
            }

            // show the count for the inputs, if it is of type enum, date_group, or cvssvector
            if (facetType === 'enum')
            {
                Fisma.Search.Criterion.facetCountCheckbox(
                    $(inputElement).parent().siblings('div.content').find(
                    'input').first());
            }
            else if (facetType === 'date_group')
            {
                Fisma.Search.Criterion.facetCountRadio(
                    $(inputElement).parent().siblings('div.content').find(
                    ':checked').first());
            }
            else if (facetType === 'cvssvector')
            {
                $(inputElement).parent().siblings('div.content').find(
                    '#' + criterionField + '_list').children('span').each(
                    function(index, fInputElement) {
                        Fisma.Search.Criterion.facetCountRadio(
                            $(fInputElement).find(':checked').first());

                    });
            }

        } else {
            $header
                .removeClass('ui-accordion-header-active')
                .addClass('ui-corner-all')
                .removeClass('ui-corner-top');
            $content
                .removeClass('ui-accordion-content-active');

            // remove criterion

            if ($(panel.container).children().length > 0)
            {
                panel.removeCriteria($('#' + facetId)[0]);

                if (facetType === 'organization')
                {
                    // remove the criterion for the organizaiton itself
                    panel.removeCriteria($(
                        '#organization_exact_criterion')[0]);
                }

                // uncheck all checked values
                if (facetContainer.attr('type') === 'enum')
                {
                    facetContainer.find('div.content span.value input:checked')
                        .each(function(index, fInputElement) {
                        $(fInputElement).attr('checked', false);
                    });
                }
            }

        }

        Fisma.Search.Criterion.facetCount();

    },
    /**
     * copies the data from the facet to the search panel
     *
     * @returns
     */
    facetHandler: function() {
        $(".criterion legend input:checked").each(function(index) {

            // advanced search panel
            var panel = Fisma.Search.advancedSearchPanel;

            // holds the facet HTML element
            var facetContainer = $(this).parent().parent();
            // name of the field
            var criterionField = facetContainer.attr('field');
            // id attribute for the search row associated with this facet
            var facetId = '#' + criterionField + '_criterion';
            // holds the criterion HTML element
            var criterionContainerOperands = $(facetId).children().eq(3).find(
                "input,select");

            var operands;
            for (operands = 0; operands < criterionContainerOperands.length; operands ++)
            {
                criterionContainerOperands.eq(operands).val('');
            }

            // insert values from the facets
            switch (facetContainer.attr('type'))
            {
                case 'range':
                    criterionContainerOperands.eq(0).val(
                        facetContainer.find(
                        'input[name="' + criterionField + '_from"]').first()
                        .val()
                        );
                    criterionContainerOperands.eq(1).val(
                        facetContainer.find(
                        'input[name="' + criterionField + '_to"]').first()
                        .val()
                        );
                    break;
                case 'enum':
                    // holds the number of selected enum values from the facet
                    var enum_vals = facetContainer.find(
                        "span.value input:checked");

                    if (enum_vals.length === 0) {
                        panel.removeCriteria($(facetId)[0]);
                    }
                    else {
                        if (enum_vals.length >= 1) {
                            criterionContainerOperands = $(facetId).children()
                                .eq(3).find("input");

                            var newValue;
                            for (newValue = 0; newValue < enum_vals.length; newValue ++)
                            {
                                criterionContainerOperands.eq(0).val(
                                    criterionContainerOperands.eq(0).val() +
                                    enum_vals.eq(newValue).val() + ',');
                            }

                            criterionContainerOperands.eq(0).val(
                                criterionContainerOperands.eq(0).val().slice(0,
                                - 1));
                        }
                    }
                    break;

                case 'cvssvector':
                    var vectors = facetContainer.find(
                        'span#' + criterionField + '_list input:checked');

                    var vinputs;
                    for (vinputs = 0; vinputs < vectors.length; vinputs ++)
                    {
                        if (vectors.eq(vinputs).val() !== "")
                        {
                            criterionContainerOperands.eq(0).val(
                                criterionContainerOperands.eq(0).val()
                                + vectors.eq(vinputs).attr(
                                'name') + ':' + vectors.eq(vinputs)
                                .val() + ',');
                        }

                    }

                    if (criterionContainerOperands.eq(0).val().length > 0)
                    {
                        criterionContainerOperands.eq(0).val(
                            criterionContainerOperands.eq(0).val().slice(0,
                            - 1));
                    }

                    break;
                case 'date_group':
                    criterionContainerOperands.eq(0).val(
                        facetContainer.find(
                        'input[name="' + criterionField + '"]:checked').val()
                        );
                    break;
                case 'organization':
                    // holds the  first criterion HTML element
                    var criterionContainer = $(facetId);

                    // determines whether or not to include the organization in the results
                    var orgExact = $('#organization_exact').attr('checked');

                    if (orgExact !== 'checked') {
                        $('#organization_exact_criterion').children().eq(2).find("select")
                            .val('textNotExactMatch').change();
                        $('#organization_exact_criterion').children().eq(3).find("input")
                            .val(facetContainer.find('input[name="' + criterionField + '"]').val());
                    } else {
                        $('#organization_exact_criterion').children().eq(2).find("select")
                            .val('textExactMatch').change();
                        $('#organization_exact_criterion').children().eq(3).find("input").val('');
                    }

                    switch (facetContainer.find('input[name="' + criterionField + '_children"]:checked').val())
                    {
                        case 'immediate':
                            criterionContainer.children().eq(2).find("select")
                                .val('organizationChildren').change();
                            break;
                        case 'all':
                            criterionContainer.children().eq(2).find("select")
                                .val('organizationSubtree').change();
                            break;
                        case 'none':
                            criterionContainer.children().eq(2).find("select").val('textContains').change();
                            $('#organization_exact_criterion').children().eq(3).find("input")
                                .val(facetContainer.find('input[name="' + criterionField + '"]').val());
                            break;
                    }
                    criterionContainer.children().eq(3).find("input").val(
                        facetContainer.find(
                        'input[name="' + criterionField + '"]').val()
                        );

                    break;
                case 'id':
                    if (facetContainer.find(
                        'input[name="' + criterionField + '_exact"]:checked').length === 1)
                    {
                        $(facetId).children().eq(2).find("select").val(
                            'integerEquals').change();
                    }
                    criterionContainerOperands.eq(0).val(
                        facetContainer.find(
                        'input[name="' + criterionField + '"]').first().val()
                        );
                    break;
                case 'text':
                    if (facetContainer.find(
                        'input[name="' + criterionField + '_exact"]:checked').length === 1)
                    {
                        $(facetId).children().eq(2).find("select").val(
                            'textExactMatch').change();
                    }

                    criterionContainerOperands.eq(0).val(
                        facetContainer.find(
                        'input[name="' + criterionField + '"]').first().val()
                        );
                    break;
            }
        });
    },
    /**
     * toggles the display of 'itself' input element
     *
     * @param jQuery_selector inputElement target HTML element
     * @returns
     */
    orgExactHandler: function(inputElement) {
        if ($(inputElement).attr('checked') === "checked")
        {
            $(inputElement).next().next().next().removeAttr('style').next()
                .removeAttr('style');

        }
        else {
            $(inputElement).next().next().next().removeAttr('checked').css(
                'display', 'none').next().css('display', 'none');
        }
    },
    /**
     * adjusts the count for all facets
     */
    facetCount: function() {

        $("div.facetBox fieldset").each(function(index, inputElement) {

            // if the facet is checked, display the count at the input level
            if ($(inputElement).find(
                'legend.header input:checked').length === 1)
            {
                // remove existing count
                $(inputElement).find('span[id*="_count"]').remove();
                return;
            }

            // contains the search criteria
            var allCriteria = [];

            // operand, query for the main criterion
            var mainOperands = [];

            // whether or not to show the count, default: false
            var showCount = false;

            switch ($(inputElement).attr('type'))
            {

                case 'enum':

                    // values for the enum field
                    var enumVals = $(inputElement).find('span.value input');

                    var enumValNum;
                    for (enumValNum = 0; enumValNum < enumVals.length; enumValNum ++)
                    {
                        mainOperands.push(enumVals.eq(enumValNum).val());
                    }

                    showCount = true;
                    break;

            }

            var mainCriterion = {
                field: $(inputElement).attr('field'),
                operator: $(inputElement).attr('criterion_type'),
                operands: mainOperands
            };

            // combine the checked facet criteria and the main criterion
            var tempFSC = Fisma.Search.Criterion;
            tempFSC.generateCheckedFacetCriteria();
            allCriteria = tempFSC.checkedFacetCriteria;
            allCriteria.push(mainCriterion);

            if (showCount)
            {
                tempFSC.processCountCriteria(inputElement, 'header',
                    allCriteria);
            }

        });

    },
    /**
     * for type checkbox, adjusts the count for all facet inputs
     *
     * @param jQuery_selector inputElement
     * @returns {undefined}
     */
    facetCountCheckbox: function(inputElement) {

        var contentParentElement = $(inputElement).parents('div.content');
        var fieldsetParentElement = $(inputElement).parents('fieldset');

        if (! $(inputElement).attr('checked') && contentParentElement.find(
            ':checked').length === 0)
        {
            contentParentElement.find('input').each(function(index,
                fInputElement) {

                // criterion with the input's value as the operand
                var inputCriterion = {
                    field: fieldsetParentElement.attr('field'),
                    operator: fieldsetParentElement.attr('criterion_type'),
                    operands: [$(fInputElement).val()]
                };

                var tempFSC = Fisma.Search.Criterion;
                tempFSC.generateCheckedFacetCriteria();
                var allCriteria = tempFSC.checkedFacetCriteria;
                allCriteria.push(inputCriterion);
                tempFSC.processCountCriteria(fInputElement, 'input',
                    allCriteria);

            });
        }
        else {
            contentParentElement.find('input').each(function(index,
                fInputElement) {
                $(fInputElement).remove('span[id*="_input_count"]');
            });
        }

    },
    /**
     * for radio type, displays the count for all non-null facet inputs
     *
     * @param jQuery_selector inputElement
     * @returns void
     */
    facetCountRadio: function(inputElement) {

        var contentParentElement = $(inputElement).parents('div.content');
        var fieldsetParentElement = $(inputElement).parents('fieldset');

        if ($(inputElement).val() === "")
        {
            $(inputElement).siblings('input').each(function(index,
                fInputElement) {

                // criterion with the input's value as the operand
                var inputCriterion = {
                    field: fieldsetParentElement.attr('field'),
                    operator: fieldsetParentElement.attr('criterion_type'),
                    operands: [$(fInputElement).attr('value')]
                };

                if (fieldsetParentElement.attr('type') === 'cvssvector')
                {
                    inputCriterion.operands = [$(fInputElement).attr(
                            'name') + ':' + $(fInputElement).attr('value')];
                }

                var tempFSC = Fisma.Search.Criterion;
                tempFSC.generateCheckedFacetCriteria();
                var allCriteria = tempFSC.checkedFacetCriteria;
                allCriteria.push(inputCriterion);
                tempFSC.processCountCriteria(fInputElement, 'input',
                    allCriteria);

            });
        }
        else {
            contentParentElement.children('input').each(function(index,
                fInputElement) {
                $(fInputElement).remove('span[id*="_input_count"]');
            });
        }

    },
    /**
     *
     * updates the vector counts for the other cvss vectors
     *
     * @param jQuery_selector inputElement
     * @returns void
     */
    updateCvssVectorCount: function(inputElement)
    {
        $(inputElement).parent().siblings().find(':checked').each(function(
            index, fInputElement) {
            Fisma.Search.Criterion.facetCountRadio(fInputElement);
        });
    },
    /**
     * processes the ajax Request and displays the count
     *
     * @param jQuery_selector inputElement
     * @param string displayLocation
     * @param Array allCriteria
     * @returns void
     */
    processCountCriteria: function(inputElement, displayLocation,
        allCriteria) {

        // generating the post data
        var postData = {
            start: 0,
            csrf: $('#searchForm input[name="csrf"]').val(),
            showDeleted: Fisma.Search.showDeletedRecords,
            queryType: $('#searchType').val()
        };

        postData.queryOptions = YAHOO.lang.JSON.stringify(
            Fisma.Search.searchPreferences);
        postData.query = YAHOO.lang.JSON.stringify(allCriteria);

        // executes when there is an ajax error
        var ajaxError = function(jqXHR, textStatus, errorThrown)
        {
            throw new Error(
                " [error] status: " + textStatus + " - error text: '" + errorThrown + "'");
        };

        // displays the count
        var viewCount = function(data, textStatus, jqXHR)
        {

            // location where the count will be displayed
            var displayId = '';
            var displayText = '';

            // element where the count will be appended to
            var selElement = '';

            switch (displayLocation)
            {
                case 'header':
                    displayId = $(inputElement).attr('field') + '_count';
                    selElement = $(inputElement).find('legend.header label');
                    break;
                case 'input':
                    displayId = $(inputElement).val() + '_input_count';
                    var inputElementId = $(inputElement).attr('id');
                    selElement = $(inputElement).siblings(
                        'label[for="' + inputElementId + '"]');
                    break;
                default:

            }

            displayText = '<span id="' + displayId + '">(' + data.totalRecords + ')</span>';
            if ($(inputElement).find('span#' + displayId).length === 0)
            {
                selElement.html(selElement.html() + displayText);
            }
            else {
                selElement.find('span#' + displayId).html(
                    '(' + data.totalRecords + ')');
            }

        };

        // send the request and retrieve the results
        $.ajax({
            type: 'POST',
            url: 'search',
            data: postData,
            dataType: 'json',
            error: ajaxError,
            success: viewCount
        });

    },
    /**
     * generates the criteria from the checked facets
     *
     * @returns void
     */
    generateCheckedFacetCriteria: function() {

        if (this.checkedFacetCriteria.length > 0)
        {
            this.checkedFacetCriteria.length = 0;
        }

        // all of the facets that are checked
        var checkedFacets = $("div.facetBox fieldset").has(
            "legend input:checked");
        // generate the criteria based on those facets that are checked
        var checkedFacetNum;
        for (checkedFacetNum = 0; checkedFacetNum < checkedFacets.length; checkedFacetNum ++)
        {
            var currFacet = checkedFacets.eq(checkedFacetNum);

            // components of the query
            var criterionType = currFacet.attr('criterion_type');
            var criterionField = currFacet.attr('field');
            var cfOperands = [];

            switch (currFacet.attr('type'))
            {
                case 'range':
                    var rangeFacetInput = currFacet.find('input[type="text"]');

                    var rangeInputNum;
                    for (rangeInputNum = 0; rangeInputNum < rangeFacetInput.length; rangeInputNum ++)
                    {
                        cfOperands.push(rangeFacetInput.eq(rangeInputNum)
                            .val());
                    }

                    break;

                case 'enum':

                    var enumFacetInput = currFacet.find(
                        '.value input:checked');

                    var enumInputNum;
                    for (enumInputNum = 0; enumInputNum < enumFacetInput.length; enumInputNum ++)
                    {
                        cfOperands.push(enumFacetInput.eq(enumInputNum).val());
                    }

                    break;
                case 'cvssvector':
                    var cvssFacetInput = currFacet.find('input:checked');

                    var cvssInputNum;
                    for (cvssInputNum = 1; cvssInputNum < cvssFacetInput.length; cvssInputNum ++)
                    {
                        if (cvssFacetInput.eq(cvssInputNum).val() !== "")
                        {
                            cfOperands.push(cvssFacetInput.eq(cvssInputNum)
                                .attr('name') +
                                ':' + cvssFacetInput.eq(cvssInputNum).val());
                        }
                    }

                    break;
                case 'date_group':
                    var dateGroupValue = currFacet.find('input:checked').eq(1)
                        .val();
                    if (dateGroupValue !== "")
                    {
                        cfOperands.push(dateGroupValue);
                    }
                    break;
                case 'organization':
                    //var orgExactInput = currFacet.find('input#organization_exact');
                    var orgInput = currFacet.find('input:checked');

                    var orgExactCriterionType = '';

                    if (orgInput.find('#organization_exact').length > 1)
                    {
                        orgExactCriterionType = 'textExactMatch';
                    }
                    else {
                        orgExactCriterionType = 'textNotExactMatch';
                    }

                    var orgQueryInput = orgInput.find(
                        'input[name="organization"]').eq(0).val();

                    orgExactCriterion =
                        {
                            field: criterionField,
                            operator: orgExactCriterionType,
                            operands: orgQueryInput
                        };

                    this.checkedFacetCriteria.push(orgExactCriterion);

                    switch (orgInput.find('[id*="children"]'))
                    {
                        case 'immediate':
                            criterionType = 'organizationChildren';
                            break;
                        case 'all':
                            criterionType = 'organizationSubtree';
                            break;
                        case 'none':
                            criterionType = 'textContains';
                    }

                    break;
                case 'id':
                    break;
                case 'text':
                    cfOperands.push(currFacet.find('input').eq(0).val());
                    break;
                default:
                    var none = null;
            }

            var newCriterion = {
                field: criterionField,
                operator: criterionType,
                operands: cfOperands
            };

            if (newCriterion.operands.length > 0)
            {
                this.checkedFacetCriteria.push(newCriterion);
            }
        }
    }
};
Fisma.Search.Criterion = FSC;
