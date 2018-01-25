if (!Object.values) {
    Object.values = function(obj) {
        return Object.keys(obj).map(
          function(key) {
              return obj[key];
          }
        );
    }
}