<?php

namespace Database\Seeders;

use App\Modules\BeltRanks\Models\BeltRank;
use Illuminate\Database\Seeder;

class BeltRankSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->belts() as $belt) {
            BeltRank::firstOrCreate(['code' => $belt['code']], $belt);
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function belts(): array
    {
        return [
            ['sort_order' => 1,  'code' => '6K',  'name' => 'Rokkyu',    'kind' => 0, 'color' => 'White'],
            ['sort_order' => 2,  'code' => '5K',  'name' => 'Gokyu',     'kind' => 0, 'color' => 'Yellow'],
            ['sort_order' => 3,  'code' => '4K',  'name' => 'Yonkyu',    'kind' => 0, 'color' => 'Orange'],
            ['sort_order' => 4,  'code' => '3K',  'name' => 'Sankyu',    'kind' => 0, 'color' => 'Green'],
            ['sort_order' => 5,  'code' => '2K',  'name' => 'Nikyu',     'kind' => 0, 'color' => 'Blue'],
            ['sort_order' => 6,  'code' => '1K',  'name' => 'Ikkyu',     'kind' => 0, 'color' => 'Brown'],
            ['sort_order' => 7,  'code' => '1D',  'name' => 'Shodan',    'kind' => 1, 'color' => 'Black'],
            ['sort_order' => 8,  'code' => '2D',  'name' => 'Nidan',     'kind' => 1, 'color' => 'Black'],
            ['sort_order' => 9,  'code' => '3D',  'name' => 'Sandan',    'kind' => 1, 'color' => 'Black'],
            ['sort_order' => 10, 'code' => '4D',  'name' => 'Yondan',    'kind' => 1, 'color' => 'Black'],
            ['sort_order' => 11, 'code' => '5D',  'name' => 'Godan',     'kind' => 1, 'color' => 'Black'],
            ['sort_order' => 12, 'code' => '6D',  'name' => 'Rokudan',   'kind' => 1, 'color' => 'Red/White'],
            ['sort_order' => 13, 'code' => '7D',  'name' => 'Shichidan', 'kind' => 1, 'color' => 'Red/White'],
            ['sort_order' => 14, 'code' => '8D',  'name' => 'Hachidan',  'kind' => 1, 'color' => 'Red/White'],
            ['sort_order' => 15, 'code' => '9D',  'name' => 'Kudan',     'kind' => 1, 'color' => 'Red'],
            ['sort_order' => 16, 'code' => '10D', 'name' => 'Judan',     'kind' => 1, 'color' => 'Red'],
        ];
    }
}
