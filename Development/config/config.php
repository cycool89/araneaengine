<?php
use aecore\Config;

Config::addEntry('Application', 'App');
Config::addEntry('AppReqWord', 'Aranea');

//Alapértelmezett vezérlés
Config::addEntry('Module', '');
Config::addEntry('Controller', '');
Config::addEntry('Method', '');
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