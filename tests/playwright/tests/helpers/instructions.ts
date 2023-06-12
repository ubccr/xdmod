const sections = {
    metricExplorer: {
        instructions: '<div class="x-grid-empty"><b style="font-size: 150%">No data is available for viewing</b><br><br>Please refer to the instructions below:<br><br><img src="gui/images/metric_explorer_instructions.png"><br><br><div style="background-image: url(\'gui/images/user_manual.png\'); background-repeat: no-repeat; height: 36px; padding-left: 40px; padding-top: 10px">For more information, please refer to the <a href="javascript:void(0)" onclick="CCR.xdmod.ui.userManualNav(\'metric+explorer\')">User Manual</a></div></div>'
    }
};
export async function instructions(page, section, selector){
    const computed = await page.innerHTML(selector + ' .x-grid-empty');
    return ('<div class="x-grid-empty">' + computed + '</div>') === (sections[section].instructions);
};
