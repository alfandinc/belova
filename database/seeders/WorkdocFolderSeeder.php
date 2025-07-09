<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Workdoc\Folder;
use App\Models\User;

class WorkdocFolderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get admin user or first user
        $admin = User::whereHas('roles', function($query) {
            $query->where('name', 'admin')
                  ->orWhere('name', 'super admin');
        })->first() ?? User::first();

        if (!$admin) {
            $this->command->error('No admin user found. Please create a user first.');
            return;
        }

        // Create root folder if it doesn't exist
        Folder::firstOrCreate(
            ['name' => 'Root', 'parent_id' => null],
            [
                'description' => 'Root directory for all files',
                'created_by' => $admin->id,
                'is_private' => false
            ]
        );

        // Create some default folders
        $rootFolder = Folder::where('name', 'Root')->first();

        // Create Public Documents folder
        Folder::firstOrCreate(
            ['name' => 'Public Documents', 'parent_id' => $rootFolder->id],
            [
                'description' => 'Shared documents accessible by everyone',
                'created_by' => $admin->id,
                'is_private' => false
            ]
        );

        // Create Templates folder
        Folder::firstOrCreate(
            ['name' => 'Templates', 'parent_id' => $rootFolder->id],
            [
                'description' => 'Document templates for various purposes',
                'created_by' => $admin->id,
                'is_private' => false
            ]
        );

        // Create Archive folder
        Folder::firstOrCreate(
            ['name' => 'Archive', 'parent_id' => $rootFolder->id],
            [
                'description' => 'Archive for old documents',
                'created_by' => $admin->id,
                'is_private' => false
            ]
        );
    }
}
