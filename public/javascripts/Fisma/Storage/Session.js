(function() {
    Fisma.Storage.Session = function(namespace) {
        Fisma.Storage.Session.superclass.constructor.call(this, namespace);
    };
    YAHOO.extend(Fisma.Storage.Session, Fisma.Storage, {
        get: function(key) {
            return this._get(key);
        },
        set: function(key, value) {
            return this._set(key, value);
        }
    };
})();
