<?php

class createUsersTable extends Migration
{
	public function up()
	{
		$table = new Table;

		Schema::create('users',
			$table->id(),
			$table->string('username', 20),
			$table->string('password', 50),
			$table->string('email', 50),
			$table->string('validation', 10),
			$table->string('name', 100),
			$table->string('token', 100),
			$table->bigInteger('token_timestamp'),
		);

	}

	public function down()
	{
		Schema::dropIfExists('users');
	}
}