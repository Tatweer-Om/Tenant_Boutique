<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Tenant;
use App\Models\User;
use Modules\Branch\Models\Branch;
use Modules\Setting\Models\Setting;


class SeedTenantJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    protected $tenant;
    public function __construct(Tenant $tenant)
    {
        $this->tenant = $tenant;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->tenant->run(function(){
            $user = User::create([
                'user_name' => $this->tenant->name,
                'user_email' => $this->tenant->email,
                'user_phone' => $this->tenant->contact, 
                'password' => $this->tenant->password,
'permissions' => json_encode([1,2,3,4,5,6,7,8,9,10,11,12]),
                'added_by' => 'system',
                'user_id' => 1,
            ]);
             
            // $user->assignRole('admin');
        });
    }
}