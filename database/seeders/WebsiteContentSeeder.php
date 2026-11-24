<?php

namespace Database\Seeders;

use App\Models\WebsiteContent;
use Illuminate\Database\Seeder;

class WebsiteContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        WebsiteContent::firstOrCreate(['id' => 1], [
            'gallery' => [],
            'plans' => [],
            'testimonials' => [],
            'faqs' => [],
        ]);
    }
}
