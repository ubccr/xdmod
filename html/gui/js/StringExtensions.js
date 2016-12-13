/*  
 * JavaScript Document
 * Viewer
 * @author Amin Ghadersohi
 * @date 2012-Aug-13
 *
 *
 */

String.prototype.wordWrap = function (m, b, c) {
    var i, j, l, s, r;
    if (m < 1)
        return this;
    for (i = -1, l = (r = this.split("\n")).length; ++i < l; r[i] += s)
        for (s = r[i], r[i] = ""; s.length > m; r[i] += s.slice(0, j) + ((s = s.slice(j)).length ? b : ""))
            j = c == 2 || (j = s.slice(0, m + 1).match(/\S*(\s)?$/))[1] ? m : j.input.length - j[0].length || c == 1 && m || j.input.length + (j = s.slice(m).match(/^\S*/)).input.length;
    return r.join("\n");
};

/**
 * Checks if a string starts with a given string.
 *
 * Written by Mozilla Contributors: https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/String/startsWith
 *
 * @param searchString The string to search for at the start of this string.
 * @param position (Optional) The position in this string to begin searching
 *                            for the given string. (Defaults to 0.)
 * @return True if the string was found, otherwise false.
 */
if (!String.prototype.startsWith) {
    String.prototype.startsWith = function(searchString, position) {
        position = position || 0;
        return this.lastIndexOf(searchString, position) === position;
    };
}
