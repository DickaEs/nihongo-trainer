<?php

namespace Database\Seeders;

use App\Models\Chapter;
use Illuminate\Database\Seeder;

class ChapterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Chapter::updateOrCreate(
            ['slug' => 'n5-minna-bab-1'],
            [
                'level' => 'N5',
                'name' => 'Minna no Nihongo Bab 1',
                'title' => 'Minna no Nihongo Bab 1',
                'position' => 1,
            ],
        );
    }
}
