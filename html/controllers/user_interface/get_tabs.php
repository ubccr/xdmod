<?php

// Operation: user_interface->get_tabs

$returnData = array();

try {
    $user = \xd_security\detectUser(array(XDUser::PUBLIC_USER));

    $tabs = array();

    $roles = $user->getRoles();

    foreach ($roles as $role_abbrev) {
        if ($role_abbrev == 'dev') {
            continue;
        }

        $role = \User\aRole::factory($user->_getFormalRoleName($role_abbrev));
        $modules = $role->getPermittedModules();

        foreach ($modules as $module) {
            if (! isset($tabs[$module->getName()])) {
                $tabs[$module->getName()] = array(
                    'tab' => $module->getName(),
                    'isDefault' => $module->isDefault(),
                    'title' => $module->getTitle(),
                    'pos' => $module->getPosition(),
                    'permitted_modules' => $module->getPermittedModules(),
                    'javascriptClass' => $module->getJavascriptClass(),
                    'javascriptReference' => $module->getJavascriptReference(),
                    'tooltip' => $module->getTooltip(),
                    'userManualSectionName' => $module->getUserManualSectionName()
                );
            } else {
                if ($module->getPermittedModules() !== null) {
                    // if module with same name already added, merge permitted_modules
                    if ($tabs[$module->getName()]['permitted_modules'] === null) {
                        $tabs[$module->getName()]['permitted_modules'] = $module->getPermittedModules();
                    } else {
                        $tabs[$module->getName()]['permitted_modules'] = array_values(
                            array_unique(
                                array_merge(
                                    $tabs[$module->getName()]['permitted_modules'],
                                    $module->getPermittedModules()
                                )
                            )
                        );
                    }
                }
            }
        }
    }

    // Sort tabs
    usort(
        $tabs,
        function ($a, $b) {
            return ($a['pos'] < $b['pos']) ? -1 : 1;
        }
    );

    $returnData = array(
        'success'    => true,
        'totalCount' => 1,
        'message'    => '',
        'data'       => array(
            array('tabs' => json_encode(array_values($tabs)))
        ),
    );
} catch (SessionExpiredException $see) {
    // TODO: Refactor generic catch block below to handle specific exceptions,
    //       which would allow this block to be removed.
    throw $see;
} catch (Exception $e) {
    $returnData = array(
        'success'    => false,
        'totalCount' => 0,
        'message'    => $e->getMessage(),
        'stacktrace' => $e->getTrace(),
        'data'       => array(),
    );
}

xd_controller\returnJSON($returnData);
