<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $permissions = collect([
            ['name' => 'users.view', 'label' => 'View the user directory'],
            ['name' => 'users.manage', 'label' => 'Invite and deactivate users'],
        ])->map(fn (array $p) => Permission::create($p));

        $admin = Role::create(['name' => 'admin', 'label' => 'Administrator']);
        $admin->permissions()->attach($permissions->pluck('id'));

        $manager = Role::create(['name' => 'manager', 'label' => 'Sales Manager']);
        $manager->permissions()->attach($permissions->where('name', 'users.view')->pluck('id'));

        $agent = Role::create(['name' => 'agent', 'label' => 'Sales Agent']);

        $adminUser = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role_id' => $admin->id,
        ]);

        $team = User::factory(2)->create(['role_id' => $manager->id])
            ->concat(User::factory(5)->create(['role_id' => $agent->id]))
            ->push($adminUser);

        // Pipeline weighted toward the top of the funnel, like a real book of business.
        $byStage = collect([
            'new' => 120, 'contacted' => 80, 'qualified' => 60,
            'proposal' => 40, 'won' => 60, 'lost' => 40,
        ])->flatMap(fn (int $count, string $stage) => Lead::factory($count)
            ->stage($stage)
            ->recycle($team)
            ->create());

        $path = ['new', 'contacted', 'qualified', 'proposal', 'won'];

        foreach ($byStage as $lead) {
            $steps = $lead->stage === 'lost'
                ? array_slice($path, 0, random_int(1, 3))
                : array_slice($path, 0, array_search($lead->stage, $path, true) + 1);

            if ($lead->stage === 'lost') {
                $steps[] = 'lost';
            }

            for ($i = 1; $i < count($steps); $i++) {
                $lead->stageHistories()->create([
                    'from_stage' => $steps[$i - 1],
                    'to_stage' => $steps[$i],
                    'changed_by' => $lead->owner_id,
                    'created_at' => fake()->dateTimeBetween($lead->created_at),
                ]);
            }
        }

        // Won leads become customers; a handful of legacy customers have no lead.
        $customers = $byStage->where('stage', 'won')
            ->map(fn (Lead $lead) => Customer::factory()->create([
                'lead_id' => $lead->id,
                'name' => $lead->name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'company' => $lead->company,
                'owner_id' => $lead->owner_id,
            ]))
            ->concat(Customer::factory(20)->recycle($team)->create());

        FollowUp::factory(90)
            ->sequence(fn ($sequence) => ['customer_id' => $customers->random()->id, 'assigned_to' => $team->random()->id])
            ->create();

        FollowUp::factory(40)->done()
            ->sequence(fn ($sequence) => ['customer_id' => $customers->random()->id, 'assigned_to' => $team->random()->id])
            ->create();

        // Open leads get follow-ups too.
        $openLeads = $byStage->whereIn('stage', ['contacted', 'qualified', 'proposal']);

        FollowUp::factory(50)
            ->sequence(fn ($sequence) => ['lead_id' => $openLeads->random()->id, 'assigned_to' => $team->random()->id])
            ->create();
    }
}
