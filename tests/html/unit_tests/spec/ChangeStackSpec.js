describe("XDMoD.ChangeStack", function() {
    var spy = chai.spy();

    describe("Object Initialization", function() {

        it("empty config", function() {

            var cs = new XDMoD.ChangeStack({});

            expect(cs.canUndo()).to.be.false;
            expect(cs.canRedo()).to.be.false;
            expect(cs.isMarked()).to.be.false;
            expect(cs.canRevert()).to.be.false;
            expect(cs.empty()).to.be.true;

            expect(function() { cs.mark(); }).to.throw(Error);
            expect(function() { cs.undo(); }).to.throw(Error);
            expect(function() { cs.redo(); }).to.throw(Error);
            expect(function() { cs.revertToMarked(); }).to.throw(Error);
            expect(function() { cs.add(); }).to.throw(Error);
        });

        it("baseParams", function() {

            var entry = {test: 1};

            var cs = new XDMoD.ChangeStack({baseParams: entry});

            expect(cs.canUndo()).to.be.false;
            expect(cs.canRedo()).to.be.false;
            expect(cs.isMarked()).to.be.false;
            expect(cs.canRevert()).to.be.false;
            expect(cs.empty()).to.be.false;

            cs.on('update', spy);

            cs.mark();
            expect(spy).to.have.been.called.with(cs, {test: 1}, 'mark');

            expect(cs.isMarked()).to.be.true;
        });
    });

    describe("Auto commit", function() {

        var cs = new XDMoD.ChangeStack({});
        cs.on('update', spy);

        it("add some changes", function() {

            cs.disableAutocommit();

            cs.add({test: 1});
            expect(spy).to.have.been.called.with(cs, {test: 1}, 'add');

            expect(cs.canUndo()).to.be.false;
            expect(cs.canRedo()).to.be.false;
            expect(cs.empty()).to.be.true;

            cs.add({test: 2});
            expect(spy).to.have.been.called.with(cs, {test: 2}, 'add');

            cs.commit()
            expect(spy).to.have.been.called.with(cs, {test: 2}, 'commit');
            expect(cs.empty()).to.be.false;

            cs.enableAutocommit();

            cs.commit()
            expect(spy).to.have.not.been.called;

            cs.add({test: 3});
            expect(spy).to.have.been.called.with(cs, {test: 3}, 'add');

            cs.undo();
            expect(spy).to.have.been.called.with(cs, {test: 2}, 'undo');

            expect(cs.canUndo()).to.be.false;
        });
    });

    describe("Stack Operations", function() {

        var cs = new XDMoD.ChangeStack({});
        cs.on('update', spy);

        it("linear push pop", function() {

            var i;
            for(i = 0; i < 10; i++) {
                cs.add({test: i});
            }
            expect(cs.canRedo()).to.be.false;
            expect(cs.canUndo()).to.be.true;

            cs.undo();
            expect(spy).to.have.been.called.with(cs, {test: 8}, 'undo');

            expect(cs.canRedo()).to.be.true;
            expect(cs.canUndo()).to.be.true;

            cs.undo();
            expect(spy).to.have.been.called.with(cs, {test: 7}, 'undo');

            cs.redo();
            expect(spy).to.have.been.called.with(cs, {test: 8}, 'redo');
        });

        it("save state", function() {

            cs.undo();
            expect(spy).to.have.been.called.with(cs, {test: 7}, 'undo');

            expect(cs.canRevert()).to.be.false;

            cs.mark();
            expect(spy).to.have.been.called.with(cs, {test: 7}, 'mark');
            expect(cs.isMarked()).to.be.true;
            expect(cs.canRevert()).to.be.false;

            cs.undo();
            expect(spy).to.have.been.called.with(cs, {test: 6}, 'undo');
            expect(cs.isMarked()).to.be.false;
            expect(cs.canRevert()).to.be.true;

            cs.undo();
            expect(spy).to.have.been.called.with(cs, {test: 5}, 'undo');
            cs.undo();
            expect(spy).to.have.been.called.with(cs, {test: 4}, 'undo');

            cs.revertToMarked();
            expect(spy).to.have.been.called.with(cs, {test: 7}, 'reverttomarked');
            expect(cs.isMarked()).to.be.true;

            expect(cs.canRedo()).to.be.false;

            cs.undo();
            expect(spy).to.have.been.called.with(cs, {test: 4}, 'undo');
        });
    });
});
