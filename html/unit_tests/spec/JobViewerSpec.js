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

        it('data format functions', function () {
            expect(jv.formatData(60, 'seconds')).to.equal('1 minute ');
            expect(jv.formatData(10240, 'B/s')).to.equal('10.00 KiB/s');
            expect(jv.formatData(11100000000, '1')).to.equal('11.1 G');
        });
    });
});
