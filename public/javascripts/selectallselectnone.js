/* This function is unsafe because it selects all checkboxes on the page, regardless
   of what grouping they belong to.
   @todo Write a safe version of this function called selectAll that takes some kind
   of scope as a parameter so that it can be limited. */
function selectAllUnsafe() {
    var checkboxes = YAHOO.util.Dom.getElementsBy(
        function (el) {
            return (el.tagName == 'INPUT' && el.type == 'checkbox')
        }
    );
    for (i in checkboxes) {
        checkboxes[i].checked = 'checked';
    }
}

function selectAll() {
    alert("Not implemented");
}

function selectNoneUnsafe() {
    var checkboxes = YAHOO.util.Dom.getElementsBy(
        function (el) {
            return (el.tagName == 'INPUT' && el.type == 'checkbox')
        }
    );
    for (i in checkboxes) {
        checkboxes[i].checked = '';
    }
}

function selectNone() {
    alert("Not implemented");
}

function elDump(el) {
    props = '';
    for (prop in el) {
        props += prop + ' : ' + el[prop] + '\n';
    }
    alert(props);
}