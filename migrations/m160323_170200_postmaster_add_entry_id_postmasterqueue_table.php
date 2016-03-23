<?php
namespace Craft;

class m160323_170200_postmaster_add_entry_id_postmasterqueue_table extends BaseMigration
{
	public function safeUp()
	{
		$table = new Postmaster_QueueRecord;

		$this->addColumnAfter($table->getTableName(), 'entryId', ColumnType::Int, 'id');

        $this->addForeignKey($table->getTableName(), 'entryId', 'entries', 'id', 'CASCADE');

		return true;
	}
}
