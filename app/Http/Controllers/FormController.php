<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\Respondent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FormController extends Controller
{

	public function index()
	{
		return Form::simplePaginate();
	}

	public function store(Request $request)
	{
		$validData = $request->validate([
			'title' => 'required|max:255',
			'fields' => 'required|array',
			'fields.*.label' => 'required|string',
			'fields.*.type' => 'required|string',
			'fields.*.required' => 'required|boolean',
		]);

        if(Gate::denies('forms:create')){
            return response([], 404);
        };

		$validData['user_id'] = auth()->user()->public_id;

		return response(["data" => Form::create($validData)], 201);
	}

	public function show(Form $form)
	{
		return $form;
	}

	public function update(Form $form, Request $request)
	{
        if(Gate::denies('forms:update', $form)){
            return response([], 404);
        };

		$validatedData = $request->validate([
			'title' => 'required|max:255',
			'fields' => 'required|array',
            'fields.*.label' => 'required|string',
			'fields.*.type' => 'required|string',
			'fields.*.required' => 'required|boolean',
			'fields.*.field_id' => 'sometimes|string',

		]);

		$form->update($validatedData);

		return response()->json(['data' => $form], 200);
	}



	public function destroy(Form $form)
	{
        if(Gate::denies('forms:delete', $form)){
            return response([], 404);
        };

		$form->delete();

		return response([], 204);
	}
}
