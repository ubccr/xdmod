describe('XDMoD.Viewer', function () {
    describe('Various Successful Tokenizations', function () {
        it('tab panel / tab', function () {
            var token = CCR.tokenize('#main_tab_panel:tg_summary');

            expect(token).to.deep.equal({
                raw: '#main_tab_panel:tg_summary',
                content: 'main_tab_panel:tg_summary',
                root: 'main_tab_panel',
                tab: 'tg_summary',
                subtab: '',
                params: ''
            });
        });

        it('tab only', function () {
            var token = CCR.tokenize('#tg_summary');

            expect(token).to.deep.equal({
                raw: '#tg_summary',
                content: 'tg_summary',
                root: '',
                tab: 'tg_summary',
                subtab: '',
                params: ''
            });
        });

        it('tab only params', function () {
            var content = 'tg_usage?node=statistic_Jobs_none_total_cpu_hours';
            var token = CCR.tokenize('#' + content);

            expect(token).to.deep.equal({
                raw: '#' + content,
                content: content,
                root: '',
                tab: 'tg_usage',
                subtab: '',
                params: 'node=statistic_Jobs_none_total_cpu_hours'
            });
        });

        it('tab panel / tab w/ params', function () {
            var content = 'main_tab_panel:job_viewer?realm=SUPREMM&recordid=29&jobid=7193418&infoid=0';
            var token = CCR.tokenize('#' + content);

            expect(token).to.deep.equal({
                raw: '#' + content,
                content: content,
                root: 'main_tab_panel',
                tab: 'job_viewer',
                subtab: '',
                params: 'realm=SUPREMM&recordid=29&jobid=7193418&infoid=0'
            });
        });

        it('tab panel / tab / subtab w/ params', function () {
            var content = 'main_tab_panel:app_kernels:app_kernel_viewer?kernel=29&start=2017-03-01&end=2017-03-31';
            var token = CCR.tokenize('#' + content);

            expect(token).to.deep.equal({
                raw: '#' + content,
                content: content,
                root: 'main_tab_panel',
                tab: 'app_kernels',
                subtab: 'app_kernel_viewer',
                params: 'kernel=29&start=2017-03-01&end=2017-03-31'
            });
        });
    });
});
