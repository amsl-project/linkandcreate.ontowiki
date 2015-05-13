/*
 * On click use selected values and proceed
 */

$(document).ready(function() {
    var button = $("#linkandcreateSubmit");
    var select = $("#linkCandidates");
    button.click(function(){
        var selected = $( "#linkCandidates option:selected" ).text();
        if (select.val() !== 'ignore') {
            var values = select.val().split('öÖ');
            if (values.length === 3) {
                createAndLink(values[0],values[1],values[2]);
            }
        }
    });
});

/*
 * This methods populates an RDFauthor with help of the given data
 * On success the method will call an action that links to the new resource
 */
function createAndLink(fromResource, useProperty, type, dataCallback) {
    var serviceUri = urlBase + 'service/rdfauthorinit';

    // check if an resource is in editing mode
    if (typeof RDFAUTHOR_STATUS != 'undefined') {
        if(RDFAUTHOR_STATUS === 'active') {
            alert("Please finish all other editing actions before creating a new instance.");
            return;
        }
    }

    // remove resource menus
    removeResourceMenus();

    loadRDFauthor(function() {
        $.getJSON(serviceUri, {
            mode: 'class',
            uri: type
        }, function(data) {
            if (data.hasOwnProperty('propertyOrder')) {
                var propertyOrder = data.propertyOrder;
                delete data.propertyOrder;
            }
            else {
                var propertyOrder = null;
            }
            // pass data through callback
            if (typeof dataCallback == 'function') {
                data = dataCallback(data);
            }
            var addPropertyValues = data['addPropertyValues'];
            var addOptionalPropertyValues = data['addOptionalPropertyValues'];
            delete data.addPropertyValues;
            delete data.addOptionalPropertyValues;

            // get default resource uri for subjects in added statements (issue 673)
            // grab first object key
            for (var subjectUri in data) {break;};
            // add statements to RDFauthor
            var graphURI = selectedGraph.URI;
            populateRDFauthor(data, true, subjectUri, graphURI, 'class');
            RDFauthor.setOptions({
                saveButtonTitle: 'Create Resource',
                cancelButtonTitle: 'Cancel',
                title: ['createNewInstanceOf', type],
                autoParse: false,
                showPropertyButton: true,
                loadOwStylesheet: false,
                addPropertyValues: addPropertyValues,
                addOptionalPropertyValues: addOptionalPropertyValues,
                onSubmitSuccess: function (responseData) {
                    newObjectSpec = resourceURL(responseData.changed);
                    if (responseData && responseData.changed) {
                        var objectUri = resourceURL(responseData.changed);
                        var pos = objectUri.indexOf('=', objectUri);
                        objectUri = objectUri.substring(pos+1,objectUri.length);
                        $.ajax({
                            type: "POST",
                            url: urlBase + 'linkandcreate/linktriple',
                            data: {predicate: useProperty, object: objectUri }
                        });
                        // HACK: reload whole page after 500 ms
                        window.setTimeout(function () {
                            window.location.href = window.location.href;
                        }, 500);
                    }
                },
                onCancel: function () {
                    // everything fine
                }
            });

            var options = {};
            if (propertyOrder != null) {
                options.propertyOrder = propertyOrder;
            }
            options.workingMode = 'class';
            RDFauthor.start(null, options);
        })
    });
}
