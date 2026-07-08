<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kotobas', function (Blueprint $table) {
            if (! Schema::hasColumn('kotobas', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('chapter_id')->constrained()->nullOnDelete();
            }
        });

        Schema::table('quiz_results', function (Blueprint $table) {
            if (! Schema::hasColumn('quiz_results', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('chapter_id')->constrained()->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('quiz_results', function (Blueprint $table) {
            if (Schema::hasColumn('quiz_results', 'user_id')) {
                $table->dropConstrainedForeignId('user_id');
            }
        });

        Schema::table('kotobas', function (Blueprint $table) {
            if (Schema::hasColumn('kotobas', 'user_id')) {
                $table->dropConstrainedForeignId('user_id');
            }
        });
    }
};
