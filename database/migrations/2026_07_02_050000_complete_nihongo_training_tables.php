<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chapters', function (Blueprint $table) {
            if (! Schema::hasColumn('chapters', 'level')) {
                $table->string('level')->default('N5')->after('id');
            }

            if (! Schema::hasColumn('chapters', 'title')) {
                $table->string('title')->default('Minna no Nihongo Bab 1')->after('level');
            }

            if (! Schema::hasColumn('chapters', 'slug')) {
                $table->string('slug')->nullable()->unique()->after('title');
            }

            if (! Schema::hasColumn('chapters', 'position')) {
                $table->unsignedSmallInteger('position')->default(1)->after('slug');
            }
        });

        Schema::table('kotobas', function (Blueprint $table) {
            if (! Schema::hasColumn('kotobas', 'chapter_id')) {
                $table->foreignId('chapter_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            }

            if (! Schema::hasColumn('kotobas', 'romaji')) {
                $table->string('romaji')->after('chapter_id');
            }

            if (! Schema::hasColumn('kotobas', 'kana')) {
                $table->string('kana')->after('romaji');
            }

            if (! Schema::hasColumn('kotobas', 'kanji')) {
                $table->string('kanji')->nullable()->after('kana');
            }

            if (! Schema::hasColumn('kotobas', 'meaning')) {
                $table->string('meaning')->after('kanji');
            }

            if (! Schema::hasColumn('kotobas', 'is_default')) {
                $table->boolean('is_default')->default(false)->after('meaning');
            }
        });

        Schema::table('quiz_results', function (Blueprint $table) {
            if (! Schema::hasColumn('quiz_results', 'chapter_id')) {
                $table->foreignId('chapter_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            }

            if (! Schema::hasColumn('quiz_results', 'mode')) {
                $table->string('mode')->after('chapter_id');
            }

            if (! Schema::hasColumn('quiz_results', 'total_questions')) {
                $table->unsignedSmallInteger('total_questions')->after('mode');
            }

            if (! Schema::hasColumn('quiz_results', 'correct_answers')) {
                $table->unsignedSmallInteger('correct_answers')->after('total_questions');
            }

            if (! Schema::hasColumn('quiz_results', 'answers')) {
                $table->json('answers')->nullable()->after('correct_answers');
            }
        });
    }

    public function down(): void
    {
        Schema::table('quiz_results', function (Blueprint $table) {
            foreach (['answers', 'correct_answers', 'total_questions', 'mode', 'chapter_id'] as $column) {
                if (Schema::hasColumn('quiz_results', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('kotobas', function (Blueprint $table) {
            foreach (['is_default', 'meaning', 'kanji', 'kana', 'romaji', 'chapter_id'] as $column) {
                if (Schema::hasColumn('kotobas', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('chapters', function (Blueprint $table) {
            foreach (['position', 'slug', 'title', 'level'] as $column) {
                if (Schema::hasColumn('chapters', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
