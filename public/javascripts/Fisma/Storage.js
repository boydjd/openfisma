(function() {
    Fisma.Storage = function(namespace) {
        this.namespace = namespace;
        this.storageEngine = YAHOO.util.StorageManager.get(
            null, // no preferred engine
            YAHOO.util.StorageManager.LOCATION_SESSION,
        );

    };
    Fisma.Storage.prototype = {
        onReady: funtion(fn) {
            this.storageEngine.subscribe(this.storageEngine.CE_READY, fn);
        },

        get: function(key) {
            throw new Fisma.Storage.UnimplementedException("get");
        },
        set: function(key, value) {
            throw new Fisma.Storage.UnimplementedException("set");
        },

        _get: function(key) {
            this.storageEngine.getItem(namespace + ":" + key);
        },
        _set: function(key, value) {
            this.storageEngine.setItem(namespace + ":" + key, value);
        }
    };
})();
