(function() {
    Fisma.Storage.Persistent = function(namespace) {
        Fisma.Storage.Persistent.superclass.constructor.call(this, namespace);
    };
    YAHOO.extend(Fisma.Storage.Persistent, Fisma.Storage, {
        _modified: {},

        get: function(key) {
            /*
             * @todo: sanity check for key existence.
             *        if key doesn't exist, perform sync() and then forcefully set the key to null if it still doesn't
             *        exist.
             */
            return this._get(key);
        },
        set: function(key, value) {
            this._modified[key] = true;
            return this._set(key, value);
        },

        init: function(values) {
            foreach (var key in values) {
                this._set(key, values[key]);
            }
        },
        sync: function() {
            /*
             * Not yet implemented.
             * Step 1: Send modified keys to server.
             * Step 2: Call init() with response object.
             * Step 3: Clear modified object
             */
        }
    };
})();
