<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use \App\Models\Group;
use App\Models\User;

class GroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $groups = Group::factory(5)->create();

        foreach ($groups as $group) {
            $group->users()->attach($group->created_by);
        }
        $userIds = User::where('id', '!=', $group->created_by)
            ->inRandomOrder()
            ->limit(rand(1, 3))
            ->pluck('id');

        $group->users()->syncWithoutDetaching($userIds);
    }
}
