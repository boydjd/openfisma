
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
        }
    };
})();
