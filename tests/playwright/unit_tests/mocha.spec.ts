import {test, expect} from '@playwright/test';
import fs from 'fs';
import globalConfig from '../playwright.config';
import selectors from '../lib/mocha.selectors';

test.describe('Mocha Tests', async() => {
    const fileName = 'stats.md';
    test('All Pass', async({page}) => {
        await page.goto(globalConfig.use.baseURL + '/' + selectors.index);
        let total = 0;
        let values = '';
        for (let i = 0; i < 20; i++) {
            await test.step('Check initial passes/failures', async() => {
                await page.reload();
                await page.waitForLoadState();
                const numOfPasses = await page.locator(selectors.passes).textContent();
                await expect(numOfPasses).toEqual('18');
                const numOfFails = await page.locator(selectors.fails).textContent();
                await expect(numOfFails).toEqual('0');
                const duration = Number(await page.locator(selectors.time).textContent());
                const percentage = Number(numOfPasses)/18;
                const num = i + 1;
                total += duration;
                values += 'Num: ' + num + ' | Time: ' + duration + ' | Percentage: ' + percentage + '\n';
            });
            await test.step('Message Box error - authentication needed', async() => {
                //when testing, received "0 communication failure", but locally
                //results in a 404/401 error because of XDMoD.REST.url issue
                await expect(page.locator(selectors.messageBox.window)).toBeVisible();
                const windowContent = await page.locator(selectors.messageBox.window).textContent();
                await expect(windowContent).not.toBeNull();
                await expect(page.locator(selectors.messageBox.button.ok())).toBeVisible();
                await expect(page.locator(selectors.messageBox.button.yes())).toBeVisible();
                await expect(page.locator(selectors.messageBox.button.no())).toBeVisible();
                await expect(page.locator(selectors.messageBox.button.cancel())).toBeVisible();
            });
        }
        await test.step('Code blocks hidden', async() => {
            const blocks = await page.$$(selectors.codeBlocks);
            await expect(blocks.length).toEqual(18);
        });
        fs.writeFileSync(fileName, (Date() + '\n' + values));
        fs.appendFileSync(fileName, ('\nAverage Duration: ' + total/20 + '\n'));
    });
    test('Sectional Pass - ChangeStack', async({page}) => {
        const changeStack = [
            'empty config',
            'baseParams',
            'add some changes',
            'linear push pop',
            'save state'
        ];
        await page.goto(selectors.index);
        for (const task of changeStack){
            let subheader;
            if (task == 'add some changes'){
                subheader = 'Auto commit';
            } else if (task == 'linear push pop'|| task == 'save state'){
                subheader = 'Stack Operations';
            } else {
                subheader = 'Object Initialization';
            }
            await test.step(`XDMoD.ChangeStack - ${task}`, async () => {
                await page.click(selectors.taskNav(task));
                await page.waitForLoadState();
                await expect(page.locator(selectors.headers.navHeader('XDMoD.ChangeStack'))).toBeVisible();
                await expect(page.locator(selectors.headers.navHeader(subheader))).toBeVisible();
                const numOfTests = await page.$$(selectors.tasksDisplayed);
                const numOfPasses = await page.locator(selectors.passes).textContent();
                const numOfFails = await page.locator(selectors.fails).textContent();
                if (task !== 'save state'){
                    await expect(numOfPasses).toEqual(String(numOfTests.length));
                    await expect(numOfFails).toEqual('0');
                } else {
                    // save state is meant to fail on its own since
                    // it depends on previous step ("linear push pop") to pass
                    await expect(numOfPasses).toEqual('0');
                    await expect(numOfFails).toEqual('1');
                }
                await page.goBack();
            });
        }
        await test.step('Message Box error - authentication needed', async() => {
            await expect(page.locator(selectors.messageBox.window)).toBeVisible();
            const windowContent = await page.locator(selectors.messageBox.window).textContent();
            await expect(windowContent).not.toBeNull();
            await expect(page.locator(selectors.messageBox.button.ok())).toBeVisible();
            await expect(page.locator(selectors.messageBox.button.yes())).toBeVisible();
            await expect(page.locator(selectors.messageBox.button.no())).toBeVisible();
            await expect(page.locator(selectors.messageBox.button.cancel())).toBeVisible();
        });
    });
    test('Sectional Pass - Viewer', async({page}) => {
        const viewer = [
            'tab panel / tab',
            'tab only',
            'tab only params',
            'tab panel / tab w/ params',
            'tab panel / tab / subtab w/ params'
        ];
        await page.goto(selectors.index);
        for (const task of viewer){
            await test.step(`XDMoD.Viewer - ${task}`, async () => {
                await page.click(selectors.taskNav(task));
                await page.waitForLoadState();
                await expect(page.locator(selectors.headers.navHeader('XDMoD.Viewer'))).toBeVisible();
                await expect(page.locator(selectors.headers.navHeader('Various Successful Tokenizations'))).toBeVisible();
                const numOfTests = await page.$$(selectors.tasksDisplayed);
                const numOfPasses = await page.locator(selectors.passes).textContent();
                const numOfFails = await page.locator(selectors.fails).textContent();
                await expect(numOfPasses).toEqual(String(numOfTests.length));
                await expect(numOfFails).toEqual('0');
                await page.goBack();
            });
        }
        await test.step('Message Box error - authentication needed', async() => {
            await expect(page.locator(selectors.messageBox.window)).toBeVisible();
            const windowContent = await page.locator(selectors.messageBox.window).textContent();
            await expect(windowContent).not.toBeNull();
            await expect(page.locator(selectors.messageBox.button.ok())).toBeVisible();
            await expect(page.locator(selectors.messageBox.button.yes())).toBeVisible();
            await expect(page.locator(selectors.messageBox.button.no())).toBeVisible();
            await expect(page.locator(selectors.messageBox.button.cancel())).toBeVisible();
        });
    });
    test('Sectional Pass - JobViewer', async({page}) => {
        const jobViewer = [
            'matching',
            'diff dtype',
            'diff array longer',
            'diff node path longer',
            'data format functions'
        ];
        await page.goto(selectors.index);
        for (const task of jobViewer){
            await test.step(`XDMoD.jobViewer - ${task}`, async () => {
                await page.click(selectors.taskNav(task));
                await page.waitForLoadState();
                await expect(page.locator(selectors.headers.navHeader('XDMoD.JobViewer'))).toBeVisible();
                await expect(page.locator(selectors.headers.navHeader('compareNodePath tests'))).toBeVisible();
                const numOfTests = await page.$$(selectors.tasksDisplayed);
                const numOfPasses = await page.locator(selectors.passes).textContent();
                const numOfFails = await page.locator(selectors.fails).textContent();
                await expect(numOfPasses).toEqual(String(numOfTests.length));
                await expect(numOfFails).toEqual('0');
                await page.goBack();
            });
        }
        await test.step('Message Box error - authentication needed', async() => {
            await expect(page.locator(selectors.messageBox.window)).toBeVisible();
            const windowContent = await page.locator(selectors.messageBox.window).textContent();
            await expect(windowContent).not.toBeNull();
            await expect(page.locator(selectors.messageBox.button.ok())).toBeVisible();
            await expect(page.locator(selectors.messageBox.button.yes())).toBeVisible();
            await expect(page.locator(selectors.messageBox.button.no())).toBeVisible();
            await expect(page.locator(selectors.messageBox.button.cancel())).toBeVisible();
        });
    });
    test('Sectional Pass - Format', async({page}) => {
        const format = [
            'SI formatting',
            'Binary formatting',
            'Elapsed time'
        ];
        await page.goto(selectors.index);
        for (const task of format){
            await test.step(`XDMoD.Format - ${task}`, async () => {
                await page.click(selectors.taskNav(task));
                await page.waitForLoadState();
                await expect(page.locator(selectors.headers.navHeader('XDMoD.Format'))).toBeVisible();
                await expect(page.locator(selectors.headers.navHeader('Check Format functions'))).toBeVisible();
                const numOfTests = await page.$$(selectors.tasksDisplayed);
                const numOfPasses = await page.locator(selectors.passes).textContent();
                const numOfFails = await page.locator(selectors.fails).textContent();
                await expect(numOfPasses).toEqual(String(numOfTests.length));
                await expect(numOfFails).toEqual('0');
                await page.goBack();
            });
        }
        await test.step('Message Box error - authentication needed', async() => {
            await expect(page.locator(selectors.messageBox.window)).toBeVisible();
            const windowContent = await page.locator(selectors.messageBox.window).textContent();
            await expect(windowContent).not.toBeNull();
            await expect(page.locator(selectors.messageBox.button.ok())).toBeVisible();
            await expect(page.locator(selectors.messageBox.button.yes())).toBeVisible();
            await expect(page.locator(selectors.messageBox.button.no())).toBeVisible();
            await expect(page.locator(selectors.messageBox.button.cancel())).toBeVisible();
        });
    });
    test('Message Box Window', async({page}) => {
        await page.goto(selectors.index)
        await test.step('Initial State', async() => {
            await expect(page.locator(selectors.messageBox.window)).toBeVisible();
            const windowContent = await page.locator(selectors.messageBox.window).textContent();
            await expect(windowContent).not.toBeNull();
            await expect(page.locator(selectors.messageBox.button.ok())).toBeVisible();
            await expect(page.locator(selectors.messageBox.button.yes())).toBeVisible();
            await expect(page.locator(selectors.messageBox.button.no())).toBeVisible();
            await expect(page.locator(selectors.messageBox.button.cancel())).toBeVisible();
        });
        await test.step('Ok Pressed', async() =>{
            await page.click(selectors.messageBox.button.ok());
            await expect(page.locator(selectors.messageBox.button.ok())).toBeHidden();
            await expect(page.locator(selectors.messageBox.window)).toBeHidden();
        });
        await test.step('Yes Pressed', async() =>{
            await page.reload();
            await page.click(selectors.messageBox.button.yes());
            await expect(page.locator(selectors.messageBox.button.yes())).toBeHidden();
            await expect(page.locator(selectors.messageBox.window)).toBeHidden();
        });
        await test.step('No Pressed', async() =>{
            await page.reload();
            await page.click(selectors.messageBox.button.no());
            await expect(page.locator(selectors.messageBox.button.no())).toBeHidden();
            await expect(page.locator(selectors.messageBox.window)).toBeHidden();
        });
        await test.step('Cancel Pressed', async() =>{
            await page.reload();
            await page.click(selectors.messageBox.button.cancel());
            await expect(page.locator(selectors.messageBox.button.cancel())).toBeHidden();
            await expect(page.locator(selectors.messageBox.window)).toBeHidden();
        });
    });
    test('Code Block Pop ups', async({page}) => {
        await page.goto(selectors.index);
        await page.click(selectors.messageBox.button.cancel());
        const allTasks = await page.locator(selectors.tasksDisplayed);
        for (let i = 0; i < await allTasks.count(); i++){
            await allTasks.nth(i).click();
        }
        const allBlocks = await page.$$(selectors.codeBlocks);
        for (const block of allBlocks){
            const isBlockVisible = await block.isVisible();
            await expect(isBlockVisible).toBeTruthy();
        }
        for (let i = 0; i < await allTasks.count(); i++){
            await allTasks.nth(i).click();
        }
        for (const block of allBlocks){
            const isBlockHidden = await block.isHidden();
            await expect(isBlockHidden).toBeTruthy();
        }
    });
});
