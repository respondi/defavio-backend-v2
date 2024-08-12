<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Form;
use App\Models\User;
use App\Models\Answer;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FormTest extends TestCase
{
	use RefreshDatabase;

	public function test_user_can_create_form()
	{
		$user = User::factory()->create();

		$formData = [
			'title' => 'Example Form ' . time(),
			'fields' => [
				['type' => 'text', 'label' => 'Name', 'required' => true],
				['type' => 'email', 'label' => 'Email', 'required' => true]
			],
		];

		$response = $this->actingAs($user)->post('/api/forms', $formData);

		$response->assertStatus(201);
		$this->assertDatabaseHas('forms', [
			'title' => $formData['title']
		]);
	}

	public function test_user_should_send_correct_body_to_create_a_form()
	{
		$user = User::factory()->create();
		$post = $this->actingAs($user)->post('/api/forms', []);

		$post->assertJsonStructure(['errors' => ['title', 'fields']]);
		$post->assertStatus(422);
	}

	public function test_user_can_show_form()
	{
		$user = User::factory()->create();
		$form = Form::factory()->for($user)->create();

		$response = $this->actingAs($user)->get("/api/forms/$form->slug");

		$response->assertStatus(200);
		$response->assertJson($form->toArray());
	}

	public function test_user_can_update_form()
	{
		$user = User::factory()->create();
		$form = Form::factory()->for($user)->create(["title" => "old title"]);

		$updatedData = [
			'title' => 'Updated Title',
			'fields' => [
				[
					'type' => 'text',
					'label' => 'Name ' . time(),
					'required' => true,
					'field_id' => "field_123"
				]
			]
		];

		$put = $this->actingAs($user)->put('/api/forms/' . $form->slug, $updatedData);

		$put->assertStatus(200);
		$this->assertDatabaseHas('forms', [
			'title' => 'Updated Title',
		]);
	}

	/** @test */
	public function test_user_can_delete_form()
	{

		$user = User::factory()->create();
		$form = Form::factory()->for($user)->create();

		$response = $this->actingAs($user)->delete('/api/forms/' . $form->slug);

		$response->assertStatus(204);
		$this->assertDatabaseMissing('forms', ['id' => $form->slug]);
	}

	public function test_user_can_list_their_forms()
	{
		$user = User::factory()->create();
		$forms = Form::factory(5)->for($user)->create();
		$get = $this->actingAs($user)->get('/api/forms/');

		$get->assertStatus(200);
		$get->assertJsonCount(5, 'data');
	}

	public function test_user_cannot_list_other_users_forms()
	{
		$form = Form::factory()->create();
		$this->assertDatabaseHas("forms", ["slug" => $form->slug]);

		// Anonymus
		$response = $this->get('/api/forms/');
		$response->assertStatus(401);

		// Other user
		$user = User::factory()->create();
		$response = $this->actingAs($user)->get('/api/forms/');
		$response->assertStatus(200);
		$response->assertJsonCount(0, 'data');
	}

	public function test_user_cannot_update_other_users_forms()
	{
		$form = Form::factory()->create(["title" => "test"]);
		$this->assertDatabaseHas("forms", ["slug" => $form->slug, "title" => "test"]);

		// Anonymus
		$response = $this->put("/api/forms/$form->slug");
		$response->assertStatus(401);

		// Other user
		$user = User::factory()->create();
		$response = $this->actingAs($user)->put("/api/forms/$form->slug", ["title" => time()]);
		$response->assertStatus(404);
		$this->assertDatabaseHas("forms", ["slug" => $form->slug, "title" => "test"]);
	}

	public function test_user_cannot_delete_other_users_forms()
	{
		$form = Form::factory()->create(["title" => "test"]);
		$this->assertDatabaseHas("forms", ["slug" => $form->slug]);

		// Anonymus
		$response = $this->delete("/api/forms/$form->slug");
		$response->assertStatus(401);

		// Other user
		$user = User::factory()->create();
		$response = $this->actingAs($user)->delete("/api/forms/$form->slug");
		$response->assertStatus(404);
		$this->assertDatabaseHas("forms", ["slug" => $form->slug, "title" => "test"]);
	}

	public function test_form_generates_field_id_for_each_field()
	{
		$fields = [
			[
				'type' => 'text',
				'label' => 'field label 1',
				'required' => false,
			],
			[
				'type' => 'text',
				'label' => 'field label 2',
				'required' => false,
			],
		];

		$form = Form::factory()->create(["fields" => $fields]);
		$this->assertNotNull($form->fields[0]["field_id"]);
		$this->assertNotNull($form->fields[1]["field_id"]);
	}
}
