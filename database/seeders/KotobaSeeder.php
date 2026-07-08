<?php

namespace Database\Seeders;

use App\Models\Chapter;
use App\Models\Kotoba;
use Illuminate\Database\Seeder;

class KotobaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $chapter = Chapter::firstOrCreate(
            ['slug' => 'n5-minna-bab-1'],
            [
                'level' => 'N5',
                'name' => 'Minna no Nihongo Bab 1',
                'title' => 'Minna no Nihongo Bab 1',
                'position' => 1,
            ],
        );

        $words = [
            ['watashi', 'わたし', '私', 'Saya'],
            ['anata', 'あなた', null, 'Kamu'],
            ['ano hito', 'あのひと', 'あの人', 'Orang itu (biasa)'],
            ['ano kata', 'あのかた', 'あの方', 'Orang itu (sopan)'],
            ['san', '～さん', null, 'Saudara, Tuan, Nyonya'],
            ['chan', '～ちゃん', null, 'Panggilan untuk anak perempuan'],
            ['jin', '～じん', '～人', 'Orang... (akhiran untuk warga negara)'],
            ['sensei', 'せんせい', '先生', 'Guru, dosen (tidak dipakai untuk kalangan sendiri)'],
            ['kyoushi', 'きょうし', '教師', 'Guru, dosen (dipakai untuk kalangan sendiri)'],
            ['gakusei', 'がくせい', '学生', 'Siswa, murid'],
            ['kaishain', 'かいしゃいん', '会社員', 'Pegawai perusahaan'],
            ['shain', 'しゃいん', '社員', 'Pegawai~'],
            ['ginkouin', 'ぎんこういん', '銀行員', 'Pegawai bank'],
            ['isha', 'いしゃ', '医者', 'Dokter'],
            ['kenkyuusha', 'けんきゅうしゃ', '研究者', 'Peneliti'],
            ['daigaku', 'だいがく', '大学', 'Universitas'],
            ['byouin', 'びょういん', '病院', 'Rumah sakit'],
            ['dare', 'だれ', null, 'Siapa (biasa)'],
            ['donata', 'どなた', null, 'Siapa (sopan)'],
            ['sai', '～さい', '～歳', 'Umur~'],
            ['nan sai', 'なんさい', '何歳', 'Umur berapa? (biasa)'],
            ['oikutsu', 'おいくつ', null, 'Umur berapa? (sopan)'],
            ['hai', 'はい', null, 'Iya'],
            ['iie', 'いいえ', null, 'Tidak'],
            ['hajimemashite', 'はじめまして', null, 'Perkenalkan'],
            ['kara kimashita', '～からきました', '～から来ました', 'Datang dari..'],
            ['douzo yoroshiku onegaishimasu', 'どうぞよろしくおねがいします', 'どうぞよろしくお願いします', 'Senang bertemu dengan anda'],
            ['shitsurei desu ga', 'しつれいですが', '失礼ですが', 'Permisi'],
            ['onamae wa', 'おなまえは', 'お名前は', 'Nama anda siapa?'],
            ['amerika', 'アメリカ', null, 'Amerika'],
            ['igirisu', 'イギリス', null, 'Inggris'],
            ['indo', 'インド', null, 'India'],
            ['indoneshia', 'インドネシア', null, 'Indonesia'],
            ['kankoku', 'かんこく', '韓国', 'Korea'],
            ['chuugoku', 'ちゅうごく', '中国', 'China'],
            ['doitsu', 'ドイツ', null, 'Jerman'],
            ['nihon', 'にほん', '日本', 'Jepang'],
        ];

        foreach ($words as [$romaji, $kana, $kanji, $meaning]) {
            Kotoba::updateOrCreate(
                [
                    'chapter_id' => $chapter->id,
                    'romaji' => $romaji,
                    'kana' => $kana,
                ],
                [
                    'kanji' => $kanji,
                    'hiragana' => $kana,
                    'meaning' => $meaning,
                    'is_default' => true,
                ],
            );
        }
    }
}
