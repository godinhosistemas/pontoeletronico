<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PlansSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Plano Básico',
                'slug' => 'basico',
                'description' => 'Ideal para pequenas empresas com até 10 funcionários',
                'price' => 49.90,
                'max_users' => 2,
                'max_employees' => 10,
                'billing_cycle_days' => 30,
                'trial_days' => 7,
                'is_active' => true,
                'features' => [
                    'Registro de ponto eletrônico',
                    'Relatórios básicos',
                    'Até 2 usuários',
                    'Até 10 funcionários',
                    'Suporte por email',
                ],
            ],
            [
                'name' => 'Plano Profissional',
                'slug' => 'profissional',
                'description' => 'Para empresas em crescimento com até 50 funcionários',
                'price' => 99.90,
                'max_users' => 5,
                'max_employees' => 50,
                'billing_cycle_days' => 30,
                'trial_days' => 14,
                'is_active' => true,
                'features' => [
                    'Registro de ponto eletrônico',
                    'Relatórios avançados',
                    'Até 5 usuários',
                    'Até 50 funcionários',
                    'Gestão de escalas',
                    'Banco de horas',
                    'Suporte prioritário',
                ],
            ],
            [
                'name' => 'Plano Empresarial',
                'slug' => 'empresarial',
                'description' => 'Solução completa para grandes empresas',
                'price' => 199.90,
                'max_users' => 15,
                'max_employees' => 200,
                'billing_cycle_days' => 30,
                'trial_days' => 30,
                'is_active' => true,
                'features' => [
                    'Registro de ponto eletrônico',
                    'Relatórios personalizados',
                    'Até 15 usuários',
                    'Até 200 funcionários',
                    'Gestão de escalas',
                    'Banco de horas',
                    'Integração com folha de pagamento',
                    'API de integração',
                    'Suporte 24/7',
                    'Gerente de conta dedicado',
                ],
            ],
        ];

        foreach ($plans as $plan) {
            \App\Models\Plan::create($plan);
        }
    }
}
