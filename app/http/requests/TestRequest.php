<?php

class TestRequest extends FormRequest
{
	public function authorize(Categoria $categoria)
	{
		//return true;
		Gate::authorize('admin-categoria', $categoria);
	}

	/* protected function prepareForValidation()
	{
		$this->merge([
			'descripcion' => '',
		]);
	} */

	public function rules()
	{
		return [
			'id' => 'required',
			'descripcion' => 'required|max:30'
		];
	}

	protected function passedValidation()
	{
		$this->replace(['id' => 'caca']);
	}

	/* public function messages()
	{
		return [
			'id' => 'ID REQUERIDO!'
		];
	} */

	/* public function attributes()
	{
		return [
			'id' => 'Identificacion'
		];
	} */


}