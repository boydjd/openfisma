
(function() {
    Fisma.Menu = {
        resolveOnClickObjects: function(obj) {
            if (obj.onclick && obj.onclick.fn) {
                obj.onclick.fn = Fisma.Util.getObjectFromName(obj.onclick.fn);
            }

            if (obj.submenu) {
                var groups = obj.submenu.itemdata;
                for (var i in groups) {
                    var group = groups[i];
                    for (var j in group) {
                        var item = group[j];
                        Fisma.Menu.resolveOnClickObjects(item);
                    }
                }
            }
        },

        goTo: function(eType, eObject, param) {
            // create dialog
            var Dom = YAHOO.util.Dom,
                Event = YAHOO.util.Event,
                Panel = YAHOO.widget.Panel,
                form = document.createElement('form'),
                textField = document.createElement('input'),
                button = document.createElement('input');
            Dom.setAttribute(textField, "type", "text");
            Dom.setAttribute(button, "type", "submit");
            Dom.setAttribute(button, "value", "Go");
            form.innerHTML = "ID: ";
            form.appendChild(textField);
            form.appendChild(button);

            // Add event listener
            var fn = function(ev, obj) {
                Event.stopEvent(ev);
                var url = obj.controller + "/view/id/" + obj.textField.value;
                window.location = url;
            };
            param.textField = textField;
            Event.addListener(form, "submit", fn, param);

            // show the panel
            var panel = new Panel(Dom.generateId(), {modal: true});
            panel.setHeader("Go To " + param.model + "...");
            panel.setBody(form);
            panel.render(document.body);
            panel.center();
            panel.show();
            textField.focus();
        }
    };
})();
