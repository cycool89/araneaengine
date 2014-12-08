<?php
Config::addEntry('Application', 'App');
Config::addEntry('AppReqWord', 'Aranea');

Config::addEntry('Module', 'Menu');
Config::addEntry('Controller', 'menuController');
Config::addEntry('Method', 'index');
Config::addEntry('Params', array(
    'index'
));

Config::addEntry('Multilanguage', true);
Config::addEntry('Languages', array(
    'hu' => 'Magyar',
    'en' => 'English'
));

Config::addEntry('DatabaseEngine', 'mysqli');
Config::addEntry('Timezone', 'Europe/Budapest');

Config::addEntry('ModuleDirectory', AE_BASE_DIR . 'Modules' . DS);