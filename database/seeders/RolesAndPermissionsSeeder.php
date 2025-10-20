<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Criar permissões
        $permissions = [
            // Gestão de Tenants
            'tenants.view',
            'tenants.create',
            'tenants.edit',
            'tenants.delete',

            // Gestão de Planos
            'plans.view',
            'plans.create',
            'plans.edit',
            'plans.delete',

            // Gestão de Assinaturas
            'subscriptions.view',
            'subscriptions.create',
            'subscriptions.edit',
            'subscriptions.delete',
            'subscriptions.suspend',
            'subscriptions.cancel',
            'subscriptions.renew',

            // Gestão de Usuários
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',

            // Gestão de Roles
            'roles.view',
            'roles.create',
            'roles.edit',
            'roles.delete',

            // Gestão de Funcionários
            'employees.view',
            'employees.create',
            'employees.edit',
            'employees.delete',

            // Ponto Eletrônico
            'timesheet.view',
            'timesheet.create',
            'timesheet.edit',
            'timesheet.delete',
            'timesheet.approve',
            'timesheet.export',

            // Relatórios
            'reports.view',
            'reports.export',
        ];

        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::create(['name' => $permission]);
        }

        // Criar roles

        // Super Admin - Acesso total ao sistema
        $superAdmin = \Spatie\Permission\Models\Role::create(['name' => 'super-admin']);
        $superAdmin->givePermissionTo(\Spatie\Permission\Models\Permission::all());

        // Admin Tenant - Gerencia seu próprio tenant
        $adminTenant = \Spatie\Permission\Models\Role::create(['name' => 'admin-tenant']);
        $adminTenant->givePermissionTo([
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'employees.view', 'employees.create', 'employees.edit', 'employees.delete',
            'timesheet.view', 'timesheet.create', 'timesheet.edit', 'timesheet.delete', 'timesheet.approve', 'timesheet.export',
            'reports.view', 'reports.export',
            'subscriptions.view',
        ]);

        // Manager - Gerencia funcionários e ponto
        $manager = \Spatie\Permission\Models\Role::create(['name' => 'manager']);
        $manager->givePermissionTo([
            'employees.view', 'employees.create', 'employees.edit',
            'timesheet.view', 'timesheet.approve', 'timesheet.export',
            'reports.view', 'reports.export',
        ]);

        // Employee - Apenas visualiza e registra próprio ponto
        $employee = \Spatie\Permission\Models\Role::create(['name' => 'employee']);
        $employee->givePermissionTo([
            'timesheet.view',
            'timesheet.create',
        ]);
    }
}
