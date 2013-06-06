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
 * @author    Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2012 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/content/license
 */

FSC = {
    legendHandler: function(inputElement) {
        var $input = $(inputElement),
                $criterion = $(inputElement).parents('fieldset.criterion').first(),
                $header = $criterion.find('legend.header'),
                $content = $criterion.find('div.content'),
                checked = $input.is('input:checked');

        //advanced search panel 
        var panel = Fisma.Search.advancedSearchPanel;

        var panel_children = $(panel.container).children();
        var new_index = panel_children.length - 1;

        if (new_index < 0) {
            new_index = 0;
        }

        // holds the facet HTML element
        var facet_container = $(inputElement).parent().parent();

        // name of the field
        var criterion_field = facet_container.attr('field');

        // type of criterion
        var criterion_type = facet_container.attr('criterion_type');

        // id attribute for the search row associated with this facet
        var facet_id = criterion_field + '_' + $(inputElement).index();

        if (checked) {
            $header
                    .addClass('ui-accordion-header-active')
                    .removeClass('ui-corner-all')
                    .addClass('ui-corner-top');
            $content
                    .addClass('ui-accordion-content-active');

            //---- add criteria

            panel.addCriteria(panel_children.eq(new_index));

            // holds the  first criterion HTML element
            var criterion_container = $(panel.container).children().last();

            criterion_container.attr('id', facet_id);
            criterion_container.children().eq(1).find("select").val(criterion_field).change();
            criterion_container.children().eq(2).find("select").val(criterion_type).change();

        } else {
            $header
                    .removeClass('ui-accordion-header-active')
                    .addClass('ui-corner-all')
                    .removeClass('ui-corner-top');
            $content
                    .removeClass('ui-accordion-content-active');

            // ---remove criteria

            if ($(panel.container).children().length > 0)
            {
                // holds the  first criterion HTML element
                var remove_criterion_container = $('#' + facet_id)[0];
                panel.removeCriteria(remove_criterion_container);
            }
        }

        // add count to view, dependent upon type of criterion
    },
    facetHandler: function() {

        $(".criterion legend input:checked").each(function(index) {

            // advanced search panel
            var panel = Fisma.Search.advancedSearchPanel;

            // holds the facet HTML element
            var facet_container = $(this).parent().parent();
            // name of the field
            var criterion_field = facet_container.attr('field');
            // id attribute for the search row associated with this facet
            var facet_id = '#' + criterion_field + '_' + $(this).index();
            // holds the criterion HTML element
            var criterion_container_operands = $(facet_id).children().eq(3).find("input,select");


            // insert values from the filters
            switch (facet_container.attr('type'))
            {
                case 'range' :
                    criterion_container_operands.eq(0).val(facet_container.find('input[name="' + criterion_field + '_from"]').first().val());
                    criterion_container_operands.eq(1).val(facet_container.find('input[name="' + criterion_field + '_to"]').first().val());
                    break;
                case 'enum' :
                    // holds the number of selected enum values from the facet
                    var enum_vals = facet_container.find("span.value input:checked");
                    console.info("Enum values", enum_vals);
                    if (enum_vals.length === 0) {
                        panel.removeCriteria($(facet_id)[0]);
                    }
                    else {
                        if (enum_vals.length > 1) {
                            criterion_container_operands = $(facet_id).children().eq(3).find("input");

                            for (new_value = 0; new_value < enum_vals.length; new_value++)
                            {
                                criterion_container_operands.eq(0).val(criterion_container_operands.eq(0).val() + enum_vals.eq(new_value).val() + ',');

                            }
                            criterion_container_operands.eq(0).val(criterion_container_operands.eq(0).val().slice(0, -1));
                        }
                    }
                    break;

                case 'cvssvector':
                    break;
                case 'date_group':
                    criterion_container_operands.eq(0).val(facet_container.find('input[name="' + criterion_field + '"]:checked').val());
                    break;
                case 'organization':

                    if (facet_container.find('input[name="' + criterion_field + '_exact"]:checked').length === 1)
                    {

                        switch (facet_container.find('input[name="' + criterion_field + '_children"]').first().val())
                        {
                            case 'immediate':
                                break;
                            case 'all':
                                break;
                            case 'none':
                            default:
                                $(facet_id).children().eq(2).find("select").val('textContains').change();
                        }
                    }
                    else {
                        switch (facet_container.find('input[name="' + criterion_field + '_children"]:checked').val())
                        {
                            case 'immediate':
                                // @todo: verify
                                $(facet_id).children().eq(2).find("select").val('organizationChildren').change();
                                break;
                            case 'all':
                                // @todo: verify
                                $(facet_id).children().eq(2).find("select").val('organizationSubtree').change();
                                break;
                            case 'none':
                            default:
                                panel.removeCriteria($(facet_id)[0]);
                        }
                    }
                    break;
                case 'id':
                    if (facet_container.find('input[name="' + criterion_field + '_exact"]:checked').length === 1)
                    {
                        $(facet_id).children().eq(2).find("select").val('integerEquals').change();
                    }
                    criterion_container_operands.eq(0).val(facet_container.find('input[name="' + criterion_field + '"]').first().val());
                    break;
                case 'text':
                default:
                    if (facet_container.find('input[name="' + criterion_field + '_exact"]:checked').length === 1)
                    {
                        $(facet_id).children().eq(2).find("select").val('textExactMatch').change();
                    }
                    criterion_container_operands.eq(0).val(facet_container.find('input[name="' + criterion_field + '"]').first().val());

            }

            console.info(null, "Criterion Container Operands: ", criterion_container_operands);
            console.info(null, "Facet Container: ", facet_container);

        });
    },
    orgExactHandler: function (inputElement) {

            if ( $(inputElement).attr('checked') === "checked" )
                {
                    $(inputElement).next().next().next().removeAttr('style').next().removeAttr('style');
                }
                else {
                    $(inputElement).next().next().next().css('display', 'none').next().css('display', 'none');
                }
    }
};
Fisma.Search.Criterion = FSC;
