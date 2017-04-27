describe('XDMoD.JobViewer', function () {
    var jv = new XDMoD.Module.JobViewer();

    describe('compareNodePath tests', function () {
        it('matching', function () {
            var node = {
                attributes: {
                    dtype: 'b',
                    b: 2
                },
                parentNode: {
                    attributes: {
                        dtype: 'a',
                        a: 1
                    },
                    parentNode: {}
                }
            };

            var path = [{ dtype: 'a', value: '1' }, { dtype: 'b', value: '2' }];

            expect(jv.compareNodePath(node, path)).to.be.true;
        });

        it('diff dtype', function () {
            var node = {
                attributes: {
                    dtype: 'b',
                    b: 2
                },
                parentNode: {
                    attributes: {
                        dtype: 'z',
                        z: 1
                    },
                    parentNode: {}
                }
            };

            var path = [{ dtype: 'a', value: '1' }, { dtype: 'b', value: '2' }];

            expect(jv.compareNodePath(node, path)).to.be.false;
        });

        it('diff array longer', function () {
            var node = {
                attributes: {
                    dtype: 'b',
                    b: 2
                },
                parentNode: {
                    attributes: {
                        dtype: 'a',
                        a: 1
                    },
                    parentNode: {}
                }
            };

            var path = [{ dtype: 'a', value: '1' }, { dtype: 'b', value: '2' }, { dtype: 'c', value: '3' }];

            expect(jv.compareNodePath(node, path)).to.be.false;
        });

        it('diff node path longer', function () {
            var node = {
                attributes: {
                    dtype: 'b',
                    b: 2
                },
                parentNode: {
                    attributes: {
                        dtype: 'a',
                        a: 1
                    },
                    parentNode: {}
                }
            };

            var path = [{ dtype: 'b', value: '2' }];

            expect(jv.compareNodePath(node, path)).to.be.false;
        });
    });
});
