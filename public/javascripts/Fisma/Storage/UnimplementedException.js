(function() {
    Fisma.Storage.UnimplementedException = function(method) {
        this.method = method;
    };
    Fisma.Storage.UnimplementedException.prototype = {
        toString: function() {
            return "The " + this.method + " method of Fisma.Storage must be implemented by a subclass.";
        }
    }
})();
