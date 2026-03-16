<?php

namespace Database\Seeders;

use Faker\Factory;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Factory::create('fr_FR');
        $count = 10;

        for ($i = 0; $i < $count; $i++) {
            $email = $faker->unique()->safeEmail();

            try {
                // On crée un objet Client WooCommerce
                $customer = new \WC_Customer;

                // On remplit les infos de base
                $customer->set_username($faker->userName());
                $customer->set_password('password');
                $customer->set_email($email);
                $customer->set_first_name($faker->firstName());
                $customer->set_last_name($faker->lastName());
                $customer->set_role('customer'); // C'est ici qu'on définit le rôle

                // On peut même lui mettre une adresse bidon pour qu'il ait l'air "réel"
                $customer->set_billing_city($faker->city());
                $customer->set_billing_country('FR');

                // On sauvegarde (ça crée l'user WP + les metas WC + le lookup)
                $customer->save();

            } catch (\Exception $e) {
                // En cas d'erreur (ex: l'user existe déjà)
                continue;
            }
        }
    }
}
