<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // [name, description, severity 1–5 used by the priority score (F7)]
        $categories = [
            ['Waterlogging', 'Standing water on roads or in neighbourhoods after rain', 4],
            ['Blocked Drain', 'Clogged or overflowing drains and sewers', 3],
            ['Damaged Road', 'Cracked, broken or collapsed road surface', 3],
            ['Pothole', 'Holes in the carriageway that endanger vehicles and people', 3],
            ['Illegal Garbage Dumping', 'Waste dumped outside designated collection points', 2],
            ['Broken Streetlight', 'Streetlights that are dark, flickering or damaged', 2],
            ['Open Manhole', 'Missing or broken manhole covers', 5],
        ];

        foreach ($categories as [$name, $description, $severity]) {
            Category::updateOrCreate(['name' => $name], ['description' => $description, 'severity' => $severity]);
        }
    }
}
