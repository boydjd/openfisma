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

FSC = new Object();

FSC = {
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
            criterionContainer.children().eq(1).find("select").val(criterionField).change();
            criterionContainer.children().eq(2).find("select").val(criterionType).change();

            if ( facetType === 'organization')
            {
                //add another criteria for the organization itself

                panel.addCriteria(panelChildren.eq(newIndex+1));

                // holds the last criterion HTML element
                criterionContainer = $(panel.container).children().last();

                criterionContainer.attr('id', 'organization_exact_criterion');
                criterionContainer.children().eq(1).find("select").val(criterionField).change();
                criterionContainer.children().eq(2).find("select").val('textExactMatch').change();
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
                    panel.removeCriteria($('#organization_exact_criterion')[0]);
                }
            }

        }

        // @todo: add count to view, dependent upon type of criterion
    },
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
            var criterionContainerOperands = $(facetId).children().eq(3).find("input,select");

            for (var operands = 0; operands < criterionContainerOperands.length; operands++)
            {
                criterionContainerOperands.eq(operands).val('');
            }

            // insert values from the facets
            switch (facetContainer.attr('type'))
            {
                case 'range' :
                    criterionContainerOperands.eq(0).val(facetContainer.find('input[name="' + criterionField + '_from"]').first().val());
                    criterionContainerOperands.eq(1).val(facetContainer.find('input[name="' + criterionField + '_to"]').first().val());
                    break;
                case 'enum' :
                    // holds the number of selected enum values from the facet
                    var enum_vals = facetContainer.find("span.value input:checked");

                    if (enum_vals.length === 0) {
                        panel.removeCriteria($(facetId)[0]);
                    }
                    else {
                        if (enum_vals.length >= 1) {
                            criterionContainerOperands = $(facetId).children().eq(3).find("input");
                            
                            for (var newValue = 0; newValue < enum_vals.length; newValue++)
                            {
                                criterionContainerOperands.eq(0).val(criterionContainerOperands.eq(0).val() + enum_vals.eq(newValue).val() + ',');
                            }

                            criterionContainerOperands.eq(0).val(criterionContainerOperands.eq(0).val().slice(0, -1));
                        }
                    }
                    break;

                case 'cvssvector':
                    var vectors = facetContainer.find('span#' + criterionField + '_list input:checked');

                    for (var vinputs = 0; vinputs < vectors.length; vinputs++)
                    {
                        if (vectors.eq(vinputs).val() !== "")
                        {
                            criterionContainerOperands.eq(0).val(criterionContainerOperands.eq(0).val() + '"'
                                    + vectors.eq(vinputs).attr('name') + ':' + vectors.eq(vinputs).val() + '"' + ',');
                        }

                    }

                    if (criterionContainerOperands.eq(0).val().length > 0)
                    {
                        criterionContainerOperands.eq(0).val(criterionContainerOperands.eq(0).val().slice(0, -1));
                    }

                    break;
                case 'date_group':
                    criterionContainerOperands.eq(0).val(facetContainer.find('input[name="' + criterionField + '"]:checked').val());
                    break;
                case 'organization':

                    // holds the  first criterion HTML element
                    var criterionContainer = $(panel.container).children().first();

                   // determines whether or not to include the organization in the results
                   var orgExact = $('#organization_exact').attr('checked');

                    switch (facetContainer.find('input[name="' + criterionField + '_children"]:checked').val())
                    {
                        case 'immediate':

                            if ( orgExact !== 'checked' )
                            {
                                 $('#organization_exact_criterion').children().eq(2).find("select").val('textNotExactMatch').change();
                            }
                            
                            $('#organization_exact_criterion').children().eq(3).find("input").val( facetContainer.find('input[name="' + criterionField + '"]').val() );
                            criterionContainer.children().eq(2).find("select").val('organizationChildren').change();
                            criterionContainer.children().eq(3).find("input").val(facetContainer.find('input[name="' + criterionField + '"]').val());

                            break;
                        case 'all':
                            
                             if ( orgExact !== 'checked' )
                            {
                                 $('#organization_exact_criterion').children().eq(2).find("select").val('textNotExactMatch').change();
                            }
                            
                            $('#organization_exact_criterion').children().eq(3).find("input").val( facetContainer.find('input[name="' + criterionField + '"]').val() );
                            criterionContainer.children().eq(2).find("select").val('organizationSubtree').change();
                            criterionContainer.children().eq(3).find("input").val(facetContainer.find('input[name="' + criterionField + '"]').val());

                            break;
                        case 'none':
                            if (orgExact === 'checked')
                            {
                                $('#organization_exact_criterion').children().eq(2).find("select").val('textExactMatch').change();
                                $('#organization_exact_criterion').children().eq(3).find("input").val( facetContainer.find('input[name="' + criterionField + '"]').val() );
                            }
                            else {
                                $('#organization_exact_criterion').children().eq(3).find("input").val('');
                            }
                    }

                    break;
                case 'id':
                    if (facetContainer.find('input[name="' + criterionField + '_exact"]:checked').length === 1)
                    {
                        $(facetId).children().eq(2).find("select").val('integerEquals').change();
                    }
                    criterionContainerOperands.eq(0).val(facetContainer.find('input[name="' + criterionField + '"]').first().val());
                    break;
                case 'text':
                    if (facetContainer.find('input[name="' + criterionField + '_exact"]:checked').length === 1)
                    {
                        $(facetId).children().eq(2).find("select").val('textExactMatch').change();
                    }
                    
                    criterionContainerOperands.eq(0).val(facetContainer.find('input[name="' + criterionField + '"]').first().val());

            }

        });
    },
    // toggles the display of 'itself' input element
    orgExactHandler: function(inputElement) {

         if ($(inputElement).attr('checked') === "checked")
        {
            $(inputElement).next().next().next().removeAttr('style').next().removeAttr('style');

        }
        else {
            $(inputElement).next().next().next().removeAttr('checked').css('display', 'none').next().css('display', 'none');
            
        }

    }
};
Fisma.Search.Criterion = FSC;
