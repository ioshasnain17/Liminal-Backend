<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeader extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Plan::create([
            'id'=> 1,
            'name'=>'Seeker',
            'monthly_audio_tokens'=>0,
            'monthly_text_tokens'=>0,
            'daily_audio_tokens'=>0,
            'daily_text_tokens'=>0
        ]);
            Plan::create([
                'id'=> 2,
                'name'=>'Illuminator',
                'monthly_audio_tokens'=>15000,
                'monthly_text_tokens'=>100000,
                'daily_audio_tokens'=>500,
                'daily_text_tokens'=>3333
            ]);
                Plan::create([
                'id'=> 3,
                'name'=>'Enlightenment',
                'monthly_audio_tokens'=>60000,
                'monthly_text_tokens'=>500000,
                'daily_audio_tokens'=>2000,
                'daily_text_tokens'=>16666
                ]);
                    Plan::create([
                'id'=> 4,
                'name'=>'Transcendence',
                'monthly_audio_tokens'=>60000,
                'monthly_text_tokens'=>500000,
                'daily_audio_tokens'=>2000,
                'daily_text_tokens'=>16666

            ]);
    }
}
