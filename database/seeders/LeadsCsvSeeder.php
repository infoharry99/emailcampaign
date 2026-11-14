<?php

namespace Database\Seeders;

use App\Models\MarketingConsentLog;
use App\Models\MarketingContact;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LeadsCsvSeeder extends Seeder
{
    public function run(): void
    {
        $leads = [
            [
                'name' => 'Cathy Hodge',
                'email' => 'cathy.hodge@smartmaritimenetwork.com',
                'company' => 'Smart Maritime Network',
                'website' => 'https://www.smartmaritimenetwork.com',
                'phone' => '+353-1-477-3033',
                'business_type' => 'Maritime Transportation',
            ],
            [
                'name' => 'Manit Chander',
                'email' => 'manit.chander@hilomrm.com',
                'company' => 'HiLo Maritime Risk Management',
                'website' => 'https://www.hilomrm.com',
                'phone' => '02038550595',
                'business_type' => 'Maritime Transportation',
            ],
            [
                'name' => 'William Dawes',
                'email' => 'will@mkango.ca',
                'company' => 'Mkango Resources Ltd.',
                'website' => 'https://www.mkango.ca',
                'phone' => '+1 403 444 5979',
                'business_type' => 'Mining',
            ],
            [
                'name' => 'Liam Henderson',
                'email' => 'liam.henderson@railinnovationgroup.com',
                'company' => 'The Rail Innovation Group',
                'website' => 'https://www.railinnovationgroup.com',
                'phone' => null,
                'business_type' => 'Rail Transportation',
            ],
            [
                'name' => 'Ben Hubbard',
                'email' => 'ben@parsyl.com',
                'company' => 'Parsyl',
                'website' => 'https://www.parsyl.com',
                'phone' => '1-844-472-7795',
                'business_type' => 'Insurance',
            ],
            [
                'name' => 'Rachel McCarthy',
                'email' => 'rachel@teamforcelabour.co.uk',
                'company' => 'Teamforce Labour',
                'website' => 'https://www.teamforcelabour.co.uk',
                'phone' => '+44 0207 100 4147',
                'business_type' => 'Construction',
            ],
            [
                'name' => 'Marc Capodicasa',
                'email' => 'marccapodicasa@ecosoilgroup.com',
                'company' => 'Eco-Soil Group',
                'website' => 'https://www.ecosoilgroup.com',
                'phone' => '+1 305 882 9424',
                'business_type' => 'Mining',
            ],
            [
                'name' => 'Geoffrey de Mowbray',
                'email' => 'geoff@dints.com',
                'company' => 'Dints International',
                'website' => 'https://www.dints.com',
                'phone' => '+44 (0) 20 7611 1750',
                'business_type' => 'International Trade and Development',
            ],
            [
                'name' => 'Ben Knowles',
                'email' => 'benjamin.knowles@pedalme.co.uk',
                'company' => 'Pedal Me',
                'website' => 'https://www.pedalme.co.uk',
                'phone' => '44-0203-189-1612',
                'business_type' => 'Transportation, Logistics, Supply Chain and Storage',
            ],
            [
                'name' => 'David Morant',
                'email' => 'david.morant@blastr.no',
                'company' => 'Blastr Green Steel',
                'website' => 'https://www.blastr.no',
                'phone' => null,
                'business_type' => 'Primary Metal Manufacturing',
            ],
            [
                'name' => 'Hayden Locke',
                'email' => 'hlocke@marimaca.com',
                'company' => 'Marimaca Copper Corp',
                'website' => 'https://www.marimaca.com/',
                'phone' => '+56 2 2431 7608',
                'business_type' => 'Mining',
            ],
            [
                'name' => "Smith O'Connor",
                'email' => 'smith@amtcltd.co.uk',
                'company' => 'Atlantic Shipping Agency',
                'website' => 'https://www.atlantic-shippingagency.com',
                'phone' => null,
                'business_type' => 'Transportation, Logistics, Supply Chain and Storage',
            ],
            [
                'name' => 'Bernardo da Veiga',
                'email' => 'bdv@brazilianrareearths.com',
                'company' => 'Brazilian Rare Earths Limited',
                'website' => 'https://www.brazilianrareearths.com',
                'phone' => '+61 2 7208 8033',
                'business_type' => 'Mining',
            ],
            [
                'name' => 'Marco Riva',
                'email' => 'marco@crossbay.com',
                'company' => 'Crossbay',
                'website' => 'https://www.crossbay.com',
                'phone' => '+44 0 207 355 8300',
                'business_type' => 'Transportation, Logistics, Supply Chain and Storage',
            ],
            [
                'name' => 'David Halkyard',
                'email' => 'dhalkyard@meridianmining.net.br',
                'company' => 'Meridian Mining',
                'website' => 'https://www.meridianmining.co',
                'phone' => '+ 55 11 4328 9890',
                'business_type' => 'Mining',
            ],
            [
                'name' => 'Janet Blas',
                'email' => 'janet.blas@bacanoralithium.com',
                'company' => 'Bacanora Lithium',
                'website' => 'https://www.bacanoralithium.com',
                'phone' => '44-0-20-7236-1177',
                'business_type' => 'Mining',
            ],
            [
                'name' => 'Andrew Moll',
                'email' => 'am@cargostore.com',
                'company' => 'CARGOSTORE WORLDWIDE',
                'website' => 'https://www.cargostore.com',
                'phone' => '+44 (0)133 086 1016',
                'business_type' => 'Packaging & Containers',
            ],
            [
                'name' => 'Raman Jaggi',
                'email' => 'raman.jaggi@stanlowterminals.co.uk',
                'company' => 'Stanlow Terminals',
                'website' => 'https://www.stanlowterminals.co.uk',
                'phone' => '+44 0330 157 0000',
                'business_type' => 'Oil and Gas',
            ],
            [
                'name' => 'Sean Thijsse',
                'email' => 'sthijsse@giyanimetals.com',
                'company' => 'Giyani Metals Corp.',
                'website' => 'https://www.giyanimetals.com',
                'phone' => '+1 289-837-0066',
                'business_type' => 'Chemical Manufacturing',
            ],
            [
                'name' => 'Hannah Crookall',
                'email' => 'hannah.crookall@youngrailpro.com',
                'company' => 'Young Rail Professionals (YRP)',
                'website' => 'https://www.youngrailpro.com',
                'phone' => null,
                'business_type' => 'Rail Transportation',
            ],
        ];

        foreach ($leads as $data) {
            $contact = MarketingContact::updateOrCreate(
                ['email' => strtolower(trim($data['email']))],
                [
                    'name' => $data['name'],
                    'company' => $data['company'],
                    'website' => $data['website'],
                    'phone' => $data['phone'],
                    'business_type' => $data['business_type'],
                    'verification_status' => 'valid',
                    'consent_status' => 'granted',
                    'consent_date' => now(),
                    'consent_source' => 'csv_import',
                    'consent_proof' => 'Verified A-Leads B2B executive contact list.',
                    'subscription_status' => 'subscribed',
                ]
            );

            MarketingConsentLog::firstOrCreate(
                [
                    'contact_id' => $contact->id,
                    'email' => $contact->email,
                    'action' => 'granted',
                ],
                [
                    'source' => 'csv_import',
                    'notes' => 'Imported via Leads CSV Seeder with documented marketing permission.',
                    'logged_at' => now(),
                ]
            );
        }
    }
}
