<?php

use yii\db\Migration;
use \yii\db\mysql\Schema;

class m150921_103849_add_columns_to_user_drop_username extends Migration
{
    public function safeUp()
    {
        $this->dropColumn('{{%user}}', 'username');
        $this->dropColumn('{{%profile}}', 'name');
        $this->addColumn('{{%profile}}', 'first_name', Schema::TYPE_STRING . ' NOT NULL');
        $this->addColumn('{{%profile}}', 'last_name', Schema::TYPE_STRING . ' NOT NULL');
        $this->addColumn('{{%profile}}', 'organization', Schema::TYPE_STRING . ' NOT NULL');
        $this->addColumn('{{%profile}}', 'office', Schema::TYPE_STRING . ' NOT NULL');
        $this->addColumn('{{%profile}}', 'country', Schema::TYPE_STRING . ' NOT NULL');
    }

    public function safeDown()
    {
        $this->addColumn('{{%user}}', 'username', Schema::TYPE_STRING);
        $this->addColumn('{{%profile}}', 'name', Schema::TYPE_STRING);
        $this->dropColumn('{{%profile}}', 'first_name');
        $this->dropColumn('{{%profile}}', 'last_name');
        $this->dropColumn('{{%profile}}', 'organization');
        $this->dropColumn('{{%profile}}', 'office');
        $this->dropColumn('{{%profile}}', 'country');
    }
}
