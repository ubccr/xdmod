var dynamicSort = function(property) { 
    return function (obj1,obj2) {
        return String(obj1[property]) > String(obj2[property]) ? 1
            : String(obj1[property]) < String(obj2[property]) ? -1 : 0;
    }
};

exports.dynamicSortMultiple = function() {
    /*
     * save the arguments object as it will be overwritten
     * note that arguments object is an array-like object
     * consisting of the names of the properties to sort by
     */
    var props = arguments;
    return function (obj1, obj2) {
        var i = 0, result = 0, numberOfProperties = props.length;
        /* try getting a different result from 0 (equal)
         * as long as we have extra properties to compare
         */
        while(result === 0 && i < numberOfProperties) {
            result = dynamicSort(props[i])(obj1, obj2);
            i++;
        }
        return result;
    }
};
