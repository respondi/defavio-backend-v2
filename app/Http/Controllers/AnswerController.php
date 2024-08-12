<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Respondent;
use Illuminate\Http\Request;

class AnswerController extends Controller
{


	/**
	 * Store a newly created resource in storage.
	 */
	public function store(Request $request)
	{

		$validated = $request->validate([
			'respondent_id' => 'sometimes|nullable|exists:respondents,public_id',
			'form_id' => 'required|exists:forms,slug',
			'question' => 'required|string',
			'value' => 'required',
			'field_id' => 'required',
			'type' => 'required|string',
			'is_last' => 'sometimes|boolean'
		]);


		$answer = Answer::create([
			'respondent_id' =>  $validated["respondent_id"] ?? Respondent::create(["form_id" => $validated["form_id"]])->public_id,
			'form_id' =>  $validated["form_id"],
			'question' => $validated['question'],
			'value' => $validated['value'],
			'type' => $validated['type'],
			'field_id' => $validated['field_id']
		]);


        /**
         * O parametro "is_last" determina que essa resposta
         * será a última possível para este formulário,
         * e a sessão pode ser considerada concluída.
         */
        if($request->has("is_last")){
            $respondent = $answer->respondent;
            $respondent->setAsCompleted();
        }

		return response()->json(['data' => $answer], 201);
	}

	public function index(){}
	public function show(string $id){}
	public function update(Request $request, string $id){}
	public function destroy(string $id){}
}
