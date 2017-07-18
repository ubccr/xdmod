/*
 *  Handle errors from screenshots taken from webdrivercss
 *
 *	@param {object} err - errors
 *  @param {object} res - results
 *
 */
module.exports = function handleScreenshots(err, res ) {
  expect(err).to.equal(undefined);
  for(var screenshotSection in res){
    if(res.hasOwnProperty(screenshotSection)){
      var thisSection = res[screenshotSection];
      var len = thisSection.length;
      while(len--){
        expect(thisSection[len].isWithinMisMatchTolerance).to.equal(true, thisSection[len].message);
      }
    }
  }
};
