
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
                container = document.createElement('form'),
                textField = document.createElement('input'),
                button = document.createElement('input');
            Dom.setAttribute(textField, "type", "text");
            Dom.setAttribute(button, "type", "button");
            Dom.setAttribute(button, "value", "Go");
            container.innerHTML = "ID: ";
            container.appendChild(textField);
            container.appendChild(button);

            // Add event listener
            var fn = function(ev, obj) {
                var url = obj.controller + "/view/id/" + obj.textField.value;
                window.location = url;
            };
            param.textField = textField;
            Event.addListener(button, "click", fn, param);

            // show the panel
            var panel = new Panel(Dom.generateId(), {modal: true});
            panel.setHeader("Go To " + param.model + "...");
            panel.setBody(container);
            panel.render(document.body);
            panel.center();
            panel.show();
        }
    };
})();
