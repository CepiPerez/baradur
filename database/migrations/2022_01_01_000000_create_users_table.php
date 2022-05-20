<?php

class createUsersTable extends Migration
{
	public function up()
	{
		$table = new Table;

		Schema::create('users',
			$table->string('username', 20)->unique(),
			$table->string('password', 50),
			$table->string('email', 50),
			$table->string('validation', 10)->nullable(),
			$table->string('roles', 200)->nullable(),
			$table->string('name', 100),
			$table->string('token', 100)->nullable(),
			$table->bigInteger('token_timestamp')->nullable()
		);

	}

	public function down()
	{
		Schema::dropIfExists('users');
	}
}