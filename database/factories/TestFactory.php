<?php

class TestFactory extends Factory
{
	protected $model = Test::class;

	public function definition()
	{
		return array(
			'name' => $this->faker->unique()->name(),
			'email' => $this->faker->unique()->email(),
			'city' => $this->faker->city(),
			'country' => $this->faker->country()
		);
	}

}