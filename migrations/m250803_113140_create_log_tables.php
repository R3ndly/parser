<?php

use yii\db\Migration;

class m250803_113140_create_log_tables extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$tableOptions = null;
    	if ($this->db->driverName === 'mysql') {
        	$tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
    	}

    	$this->createTable('{{%browser}}', [
        	'id' => $this->primaryKey(),
        	'name' => $this->string()->notNull(),
        	'version' => $this->string(),
    	], $tableOptions);

    	$this->createTable('{{%os}}', [
        	'id' => $this->primaryKey(),
        	'name' => $this->string()->notNull(),
        	'architecture' => $this->string(10)->notNull(), // x86 или x64
    	], $tableOptions);

    	$this->createTable('{{%url}}', [
        	'id' => $this->primaryKey(),
        	'path' => $this->string()->notNull(),
    	], $tableOptions);

    	$this->createTable('{{%log}}', [
        	'id' => $this->primaryKey(),
        	'ip' => $this->string(15)->notNull(),
        	'request_date' => $this->dateTime()->notNull(),
        	'url_id' => $this->integer()->notNull(),
        	'browser_id' => $this->integer()->notNull(),
        	'os_id' => $this->integer()->notNull(),
    	], $tableOptions);

    	$this->addForeignKey(
        	'fk_log_url',
        	'{{%log}}',
        	'url_id',
        	'{{%url}}',
        	'id',
        	'CASCADE',
        	'CASCADE'
    	);

    	$this->addForeignKey(
        	'fk_log_browser',
        	'{{%log}}',
        	'browser_id',
        	'{{%browser}}',
        	'id',
        	'CASCADE',
        	'CASCADE'
    	);

    	$this->addForeignKey(
        	'fk_log_os',
        	'{{%log}}',
        	'os_id',
        	'{{%os}}',
        	'id',
        	'CASCADE',
        	'CASCADE'
    	);

    	$this->createIndex('idx_log_date', '{{%log}}', 'request_date');
    	$this->createIndex('idx_log_ip', '{{%log}}', 'ip');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m250803_113140_create_log_tables cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250803_113140_create_log_tables cannot be reverted.\n";

        return false;
    }
    */
}
