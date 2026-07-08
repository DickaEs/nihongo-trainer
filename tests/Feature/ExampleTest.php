<?php

test('the application returns a successful response', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});

test('guests are redirected from trainer to login', function () {
    $this->get('/trainer')
        ->assertRedirect('/login');
});

test('a kotoba can be added for practice', function () {
    $this->actingAs(\App\Models\User::factory()->create());

    $this->get('/trainer');

    $response = $this->postJson('/kotoba', [
        'romaji' => 'watashitachi',
        'kana' => 'わたしたち',
        'kanji' => '私たち',
        'meaning' => 'Kami',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('romaji', 'watashitachi')
        ->assertJsonPath('meaning', 'Kami');

    $this->assertDatabaseHas('kotobas', [
        'romaji' => 'watashitachi',
        'kana' => 'わたしたち',
        'meaning' => 'Kami',
    ]);
});

test('a quiz result can be saved', function () {
    $this->actingAs(\App\Models\User::factory()->create());

    $this->get('/trainer');

    $response = $this->postJson('/quiz-results', [
        'mode' => 'jp-id',
        'total_questions' => 5,
        'correct_answers' => 4,
        'answers' => [
            ['word_id' => 1, 'correct' => true],
        ],
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('mode', 'jp-id')
        ->assertJsonPath('correct_answers', 4);
});
