<?php

return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=localhost;dbname=Yii',
    'username' => 'root',
    'password' => 'Q1qqqqqq',
	'charset' => 'utf8',
	'attributes' => [
        PDO::ATTR_PERSISTENT => true,
    ],

    // Schema cache options (for production environment)
    //'enableSchemaCache' => true,
    //'schemaCacheDuration' => 60,
    //'schemaCache' => 'cache',
];
