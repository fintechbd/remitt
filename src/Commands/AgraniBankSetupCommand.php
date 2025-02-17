<?php

namespace Fintech\Remit\Commands;

use Fintech\Business\Facades\Business;
use Fintech\Core\Facades\Core;
use Fintech\MetaData\Facades\MetaData;
use Illuminate\Console\Command;
use Throwable;

class AgraniBankSetupCommand extends Command
{
    const AGRANI_CODES = [
        'AFG' => ['name' => 'Afghanistan', 'agrani_code' => 'AF'],
        'ALB' => ['name' => 'Albania', 'agrani_code' => 'AL'],
        'DZA' => ['name' => 'Algeria', 'agrani_code' => 'DZ'],
        'ASM' => ['name' => 'American Samoa', 'agrani_code' => 'AS'],
        'AND' => ['name' => 'Andorra', 'agrani_code' => 'AD'],
        'AGO' => ['name' => 'Angola', 'agrani_code' => 'AO'],
        'AIA' => ['name' => 'Anguilla', 'agrani_code' => 'AI'],
        'ATG' => ['name' => 'Antigua And Barbuda', 'agrani_code' => 'AG'],
        'ARG' => ['name' => 'Argentina', 'agrani_code' => 'AR'],
        'ARM' => ['name' => 'Armenia', 'agrani_code' => 'AM'],
        'ABW' => ['name' => 'Aruba', 'agrani_code' => 'AW'],
        'AUS' => ['name' => 'Australia', 'agrani_code' => 'AU'],
        'AUT' => ['name' => 'Austria', 'agrani_code' => 'AT'],
        'AZE' => ['name' => 'Azerbaijan', 'agrani_code' => 'AZ'],
        'BHS' => ['name' => 'Bahamas', 'agrani_code' => 'BS'],
        'BHR' => ['name' => 'Bahrain', 'agrani_code' => 'BH'],
        'BGD' => ['name' => 'Bangladesh', 'agrani_code' => 'BD'],
        'BRB' => ['name' => 'Barbados', 'agrani_code' => 'BB'],
        'BLR' => ['name' => 'Belarus', 'agrani_code' => 'BY'],
        'BEL' => ['name' => 'Belgium', 'agrani_code' => 'BE'],
        'BLZ' => ['name' => 'Belize', 'agrani_code' => 'BZ'],
        'BEN' => ['name' => 'Benin', 'agrani_code' => 'BJ'],
        'BMU' => ['name' => 'Bermuda', 'agrani_code' => 'BM'],
        'BTN' => ['name' => 'Bhutan', 'agrani_code' => 'BT'],
        'BOL' => ['name' => 'Bolivia', 'agrani_code' => 'BO'],
        'BIH' => ['name' => 'Bosnia And Herzegovina', 'agrani_code' => 'BA'],
        'BWA' => ['name' => 'Botswana', 'agrani_code' => 'BW'],
        'BRA' => ['name' => 'Brazil', 'agrani_code' => 'BR'],
        'BRN' => ['name' => 'Brunei Darussalam', 'agrani_code' => 'BN'],
        'BGR' => ['name' => 'Bulgaria', 'agrani_code' => 'BG'],
        'BFA' => ['name' => 'Burkina Faso', 'agrani_code' => 'BF'],
        'BDI' => ['name' => 'Burundi', 'agrani_code' => 'BI'],
        'KHM' => ['name' => 'Cambodia', 'agrani_code' => 'KH'],
        'CMR' => ['name' => 'Cameroon', 'agrani_code' => 'CM'],
        'CAN' => ['name' => 'Canada', 'agrani_code' => 'CA'],
        'CPV' => ['name' => 'Cape Verde', 'agrani_code' => 'CV'],
        'CYM' => ['name' => 'Cayman Islands', 'agrani_code' => 'KY'],
        'CAF' => ['name' => 'Central African Republic', 'agrani_code' => 'CF'],
        'TCD' => ['name' => 'Chad', 'agrani_code' => 'TD'],
        'CHL' => ['name' => 'Chile', 'agrani_code' => 'CL'],
        'CHN' => ['name' => 'China', 'agrani_code' => 'CN'],
        'COL' => ['name' => 'Colombia', 'agrani_code' => 'CO'],
        'COM' => ['name' => 'Comoros', 'agrani_code' => 'KM'],
        'COG' => ['name' => 'Congo', 'agrani_code' => 'CG'],
        'COD' => ['name' => 'Congo, The Democratic Republic Of The', 'agrani_code' => 'CD'],
        'COK' => ['name' => 'Cook Islands', 'agrani_code' => 'CK'],
        'CRI' => ['name' => 'Costa Rica', 'agrani_code' => 'CR'],
        'CIV' => ['name' => "Cote D'ivoire", 'agrani_code' => 'CI'],
        'HRV' => ['name' => 'Croatia', 'agrani_code' => 'HR'],
        'CUB' => ['name' => 'Cuba', 'agrani_code' => 'CU'],
        'CYP' => ['name' => 'Cyprus', 'agrani_code' => 'CY'],
        'CZE' => ['name' => 'Czech Republic', 'agrani_code' => 'CZ'],
        'DNK' => ['name' => 'Denmark', 'agrani_code' => 'DK'],
        'DJI' => ['name' => 'Djibouti', 'agrani_code' => 'DJ'],
        'DMA' => ['name' => 'Dominica', 'agrani_code' => 'DM'],
        'DOM' => ['name' => 'Dominican Republic', 'agrani_code' => 'DO'],
        'ECU' => ['name' => 'Ecuador', 'agrani_code' => 'EC'],
        'EGY' => ['name' => 'Egypt', 'agrani_code' => 'EG'],
        'SLV' => ['name' => 'El Salvador', 'agrani_code' => 'SV'],
        'GNQ' => ['name' => 'Equatorial Guinea', 'agrani_code' => 'GQ'],
        'ERI' => ['name' => 'Eritrea', 'agrani_code' => 'ER'],
        'EST' => ['name' => 'Estonia', 'agrani_code' => 'EE'],
        'ETH' => ['name' => 'Ethiopia', 'agrani_code' => 'ET'],
        'FLK' => ['name' => 'Falkland Islands (malvinas)', 'agrani_code' => 'FK'],
        'FRO' => ['name' => 'Faroe Islands', 'agrani_code' => 'FO'],
        'FJI' => ['name' => 'Fiji', 'agrani_code' => 'FJ'],
        'FIN' => ['name' => 'Finland', 'agrani_code' => 'FI'],
        'FRA' => ['name' => 'France', 'agrani_code' => 'FR'],
        'GUF' => ['name' => 'French Guiana', 'agrani_code' => 'GF'],
        'PYF' => ['name' => 'French Polynesia', 'agrani_code' => 'PF'],
        'GAB' => ['name' => 'Gabon', 'agrani_code' => 'GA'],
        'GMB' => ['name' => 'Gambia', 'agrani_code' => 'GM'],
        'GEO' => ['name' => 'Georgia', 'agrani_code' => 'GE'],
        'DEU' => ['name' => 'Germany', 'agrani_code' => 'DE'],
        'GHA' => ['name' => 'Ghana', 'agrani_code' => 'GH'],
        'GIB' => ['name' => 'Gibraltar', 'agrani_code' => 'GI'],
        'GRC' => ['name' => 'Greece', 'agrani_code' => 'GR'],
        'GRL' => ['name' => 'Greenland', 'agrani_code' => 'GL'],
        'GRD' => ['name' => 'Grenada', 'agrani_code' => 'GD'],
        'GLP' => ['name' => 'Guadeloupe', 'agrani_code' => 'GP'],
        'GUM' => ['name' => 'Guam', 'agrani_code' => 'GU'],
        'GTM' => ['name' => 'Guatemala', 'agrani_code' => 'GT'],
        'GIN' => ['name' => 'Guinea', 'agrani_code' => 'GN'],
        'GNB' => ['name' => 'Guinea-bissau', 'agrani_code' => 'GW'],
        'GUY' => ['name' => 'Guyana', 'agrani_code' => 'GY'],
        'HTI' => ['name' => 'Haiti', 'agrani_code' => 'HT'],
        'VAT' => ['name' => 'Holy See (vatican City State)', 'agrani_code' => 'VA'],
        'HND' => ['name' => 'Honduras', 'agrani_code' => 'HN'],
        'HKG' => ['name' => 'Hong Kong', 'agrani_code' => 'HK'],
        'HUN' => ['name' => 'Hungary', 'agrani_code' => 'HU'],
        'ISL' => ['name' => 'Iceland', 'agrani_code' => 'IS'],
        'IND' => ['name' => 'India', 'agrani_code' => 'IN'],
        'IDN' => ['name' => 'Indonesia', 'agrani_code' => 'ID'],
        'IRN' => ['name' => 'Iran, Islamic Republic Of', 'agrani_code' => 'IR'],
        'IRQ' => ['name' => 'Iraq', 'agrani_code' => 'IQ'],
        'IRL' => ['name' => 'Ireland', 'agrani_code' => 'IE'],
        'ISR' => ['name' => 'Israel', 'agrani_code' => 'IL'],
        'ITA' => ['name' => 'Italy', 'agrani_code' => 'IT'],
        'JAM' => ['name' => 'Jamaica', 'agrani_code' => 'JM'],
        'JPN' => ['name' => 'Japan', 'agrani_code' => 'JP'],
        'JOR' => ['name' => 'Jordan', 'agrani_code' => 'JO'],
        'KAZ' => ['name' => 'Kazakhstan', 'agrani_code' => 'KZ'],
        'KEN' => ['name' => 'Kenya', 'agrani_code' => 'KE'],
        'KIR' => ['name' => 'Kiribati', 'agrani_code' => 'KI'],
        'PRK' => ['name' => "Korea, Democratic People's Republic Of", 'agrani_code' => 'KP'],
        'KOR' => ['name' => 'Korea, Republic Of', 'agrani_code' => 'KR'],
        'KWT' => ['name' => 'Kuwait', 'agrani_code' => 'KW'],
        'KGZ' => ['name' => 'Kyrgyzstan', 'agrani_code' => 'KG'],
        'LAO' => ['name' => "Lao People's Democratic Republic", 'agrani_code' => 'LA'],
        'LVA' => ['name' => 'Latvia', 'agrani_code' => 'LV'],
        'LBN' => ['name' => 'Lebanon', 'agrani_code' => 'LB'],
        'LSO' => ['name' => 'Lesotho', 'agrani_code' => 'LS'],
        'LBR' => ['name' => 'Liberia', 'agrani_code' => 'LR'],
        'LBY' => ['name' => 'Libyan Arab Jamahiriya', 'agrani_code' => 'LY'],
        'LIE' => ['name' => 'Liechtenstein', 'agrani_code' => 'LI'],
        'LTU' => ['name' => 'Lithuania', 'agrani_code' => 'LT'],
        'LUX' => ['name' => 'Luxembourg', 'agrani_code' => 'LU'],
        'MAC' => ['name' => 'Macao', 'agrani_code' => 'MO'],
        'MKD' => ['name' => 'Macedonia, The Former Yugoslav Republic Of', 'agrani_code' => 'MK'],
        'MDG' => ['name' => 'Madagascar', 'agrani_code' => 'MG'],
        'MWI' => ['name' => 'Malawi', 'agrani_code' => 'MW'],
        'MYS' => ['name' => 'Malaysia', 'agrani_code' => 'MY'],
        'MDV' => ['name' => 'Maldives', 'agrani_code' => 'MV'],
        'MLI' => ['name' => 'Mali', 'agrani_code' => 'ML'],
        'MLT' => ['name' => 'Malta', 'agrani_code' => 'MT'],
        'MHL' => ['name' => 'Marshall Islands', 'agrani_code' => 'MH'],
        'MTQ' => ['name' => 'Martinique', 'agrani_code' => 'MQ'],
        'MRT' => ['name' => 'Mauritania', 'agrani_code' => 'MR'],
        'MUS' => ['name' => 'Mauritius', 'agrani_code' => 'MU'],
        'MEX' => ['name' => 'Mexico', 'agrani_code' => 'MX'],
        'FSM' => ['name' => 'Micronesia, Federated States Of', 'agrani_code' => 'FM'],
        'MDA' => ['name' => 'Moldova, Republic Of', 'agrani_code' => 'MD'],
        'MCO' => ['name' => 'Monaco', 'agrani_code' => 'MC'],
        'MNG' => ['name' => 'Mongolia', 'agrani_code' => 'MN'],
        'MSR' => ['name' => 'Montserrat', 'agrani_code' => 'MS'],
        'MAR' => ['name' => 'Morocco', 'agrani_code' => 'MA'],
        'MOZ' => ['name' => 'Mozambique', 'agrani_code' => 'MZ'],
        'MMR' => ['name' => 'Myanmar', 'agrani_code' => 'MM'],
        'NAM' => ['name' => 'Namibia', 'agrani_code' => 'NA'],
        'NRU' => ['name' => 'Nauru', 'agrani_code' => 'NR'],
        'NPL' => ['name' => 'Nepal', 'agrani_code' => 'NP'],
        'NLD' => ['name' => 'Netherlands', 'agrani_code' => 'NL'],
        'ANT' => ['name' => 'Netherlands Antilles', 'agrani_code' => 'AN'],
        'NCL' => ['name' => 'New Caledonia', 'agrani_code' => 'NC'],
        'NZL' => ['name' => 'New Zealand', 'agrani_code' => 'NZ'],
        'NIC' => ['name' => 'Nicaragua', 'agrani_code' => 'NI'],
        'NER' => ['name' => 'Niger', 'agrani_code' => 'NE'],
        'NGA' => ['name' => 'Nigeria', 'agrani_code' => 'NG'],
        'NIU' => ['name' => 'Niue', 'agrani_code' => 'NU'],
        'NFK' => ['name' => 'Norfolk Island', 'agrani_code' => 'NF'],
        'MNP' => ['name' => 'Northern Mariana Islands', 'agrani_code' => 'MP'],
        'NOR' => ['name' => 'Norway', 'agrani_code' => 'NO'],
        'OMN' => ['name' => 'Oman', 'agrani_code' => 'OM'],
        'PAK' => ['name' => 'Pakistan', 'agrani_code' => 'PK'],
        'PLW' => ['name' => 'Palau', 'agrani_code' => 'PW'],
        'PAN' => ['name' => 'Panama', 'agrani_code' => 'PA'],
        'PNG' => ['name' => 'Papua New Guinea', 'agrani_code' => 'PG'],
        'PRY' => ['name' => 'Paraguay', 'agrani_code' => 'PY'],
        'PER' => ['name' => 'Peru', 'agrani_code' => 'PE'],
        'PHL' => ['name' => 'Philippines', 'agrani_code' => 'PH'],
        'PCN' => ['name' => 'Pitcairn', 'agrani_code' => 'PN'],
        'POL' => ['name' => 'Poland', 'agrani_code' => 'PL'],
        'PRT' => ['name' => 'Portugal', 'agrani_code' => 'PT'],
        'PRI' => ['name' => 'Puerto Rico', 'agrani_code' => 'PR'],
        'QAT' => ['name' => 'Qatar', 'agrani_code' => 'QA'],
        'REU' => ['name' => 'Reunion', 'agrani_code' => 'RE'],
        'ROM' => ['name' => 'Romania', 'agrani_code' => 'RO'],
        'RUS' => ['name' => 'Russian Federation', 'agrani_code' => 'RU'],
        'RWA' => ['name' => 'Rwanda', 'agrani_code' => 'RW'],
        'SHN' => ['name' => 'Saint Helena', 'agrani_code' => 'SH'],
        'KNA' => ['name' => 'Saint Kitts And Nevis', 'agrani_code' => 'KN'],
        'LCA' => ['name' => 'Saint Lucia', 'agrani_code' => 'LC'],
        'SPM' => ['name' => 'Saint Pierre And Miquelon', 'agrani_code' => 'PM'],
        'VCT' => ['name' => 'Saint Vincent And The Grenadines', 'agrani_code' => 'VC'],
        'WSM' => ['name' => 'Samoa', 'agrani_code' => 'WS'],
        'SMR' => ['name' => 'San Marino', 'agrani_code' => 'SM'],
        'STP' => ['name' => 'Sao Tome And Principe', 'agrani_code' => 'ST'],
        'SAU' => ['name' => 'Saudi Arabia', 'agrani_code' => 'SA'],
        'SEN' => ['name' => 'Senegal', 'agrani_code' => 'SN'],
        'SYC' => ['name' => 'Seychelles', 'agrani_code' => 'SC'],
        'SLE' => ['name' => 'Sierra Leone', 'agrani_code' => 'SL'],
        'SGP' => ['name' => 'Singapore', 'agrani_code' => 'SG'],
        'SVK' => ['name' => 'Slovakia', 'agrani_code' => 'SK'],
        'SVN' => ['name' => 'Slovenia', 'agrani_code' => 'SI'],
        'SLB' => ['name' => 'Solomon Islands', 'agrani_code' => 'SB'],
        'SOM' => ['name' => 'Somalia', 'agrani_code' => 'SO'],
        'ZAF' => ['name' => 'South Africa', 'agrani_code' => 'ZA'],
        'ESP' => ['name' => 'Spain', 'agrani_code' => 'ES'],
        'LKA' => ['name' => 'Sri Lanka', 'agrani_code' => 'LK'],
        'SDN' => ['name' => 'Sudan', 'agrani_code' => 'SD'],
        'SUR' => ['name' => 'Suriname', 'agrani_code' => 'SR'],
        'SJM' => ['name' => 'Svalbard And Jan Mayen', 'agrani_code' => 'SJ'],
        'SWZ' => ['name' => 'Swaziland', 'agrani_code' => 'SZ'],
        'SWE' => ['name' => 'Sweden', 'agrani_code' => 'SE'],
        'CHE' => ['name' => 'Switzerland', 'agrani_code' => 'CH'],
        'SYR' => ['name' => 'Syrian Arab Republic', 'agrani_code' => 'SY'],
        'TWN' => ['name' => 'Taiwan, Province Of China', 'agrani_code' => 'TW'],
        'TJK' => ['name' => 'Tajikistan', 'agrani_code' => 'TJ'],
        'TZA' => ['name' => 'Tanzania, United Republic Of', 'agrani_code' => 'TZ'],
        'THA' => ['name' => 'Thailand', 'agrani_code' => 'TH'],
        'TGO' => ['name' => 'Togo', 'agrani_code' => 'TG'],
        'TKL' => ['name' => 'Tokelau', 'agrani_code' => 'TK'],
        'TON' => ['name' => 'Tonga', 'agrani_code' => 'TO'],
        'TTO' => ['name' => 'Trinidad And Tobago', 'agrani_code' => 'TT'],
        'TUN' => ['name' => 'Tunisia', 'agrani_code' => 'TN'],
        'TUR' => ['name' => 'Turkey', 'agrani_code' => 'TR'],
        'TKM' => ['name' => 'Turkmenistan', 'agrani_code' => 'TM'],
        'TCA' => ['name' => 'Turks And Caicos Islands', 'agrani_code' => 'TC'],
        'TUV' => ['name' => 'Tuvalu', 'agrani_code' => 'TV'],
        'UGA' => ['name' => 'Uganda', 'agrani_code' => 'UG'],
        'UKR' => ['name' => 'Ukraine', 'agrani_code' => 'UA'],
        'ARE' => ['name' => 'United Arab Emirates', 'agrani_code' => 'AE'],
        'GBR' => ['name' => 'United Kingdom', 'agrani_code' => 'GB'],
        'USA' => ['name' => 'United States', 'agrani_code' => 'US'],
        'URY' => ['name' => 'Uruguay', 'agrani_code' => 'UY'],
        'UZB' => ['name' => 'Uzbekistan', 'agrani_code' => 'UZ'],
        'VUT' => ['name' => 'Vanuatu', 'agrani_code' => 'VU'],
        'VEN' => ['name' => 'Venezuela', 'agrani_code' => 'VE'],
        'VNM' => ['name' => 'Viet Nam', 'agrani_code' => 'VN'],
        'VGB' => ['name' => 'Virgin Islands, British', 'agrani_code' => 'VG'],
        'VIR' => ['name' => 'Virgin Islands, U.s.', 'agrani_code' => 'VI'],
        'WLF' => ['name' => 'Wallis And Futuna', 'agrani_code' => 'WF'],
        'ESH' => ['name' => 'Western Sahara', 'agrani_code' => 'EH'],
        'YEM' => ['name' => 'Yemen', 'agrani_code' => 'YE'],
        'ZMB' => ['name' => 'Zambia', 'agrani_code' => 'ZM'],
        'ZWE' => ['name' => 'Zimbabwe', 'agrani_code' => 'ZW'],
        'XTA' => ['name' => 'External Territories Of Australia', 'agrani_code' => 'XA'],
        'GY9' => ['name' => 'Guernsey And Alderney', 'agrani_code' => 'GG'],
        'JE1' => ['name' => 'Jersey', 'agrani_code' => 'JE'],
        'IM1' => ['name' => 'Man (isle Of)', 'agrani_code' => 'IM'],
    ];

    const PURPOSE_OF_REMITTANCES = [
        'build-acquisition-renovation-property' => null,
        'business-travel' => '13',
        'buying-goods-from-suppliers' => '10',
        'compensation' => '06',
        'educational-expenses' => '12',
        'family-maintenance-or-savings' => '04',
        'grants-and-gifts' => null,
        'insurance-premium' => '06',
        'investment-in-equity-shares' => '10',
        'investment-in-real-estate' => '10',
        'investment-in-securities' => '10',
        'medical-expenses' => '11',
        'pay-employee-salary' => '13',
        'payment-to-foreign-worker-agency' => '01',
        'personal-travels-and-tours' => '13',
        'religious-festival' => null,
        'rental-payment' => '08',
        'repatriation-of-business-profits' => '10',
        'repayment-of-loans' => '06',
        'tax-payment' => '10',
        'travel-and-transportation-expenses' => '13',
        'family-or-living-expense' => '04',
        'charity-donation' => null,
        'payment-for-services' => '08',
        'travel-expenses' => '13',
        'personal-asset-allocation' => '02',
        'payment-for-goods' => '10',
        'capital-transfer' => '10',
        'employee-payroll' => '10',
        'goods-trade' => '10',
        'services-trade' => '13',
        'return-of-export-trade' => '10',
    ];

    public $signature = 'remit:agrani-bank-setup';

    public $description = 'install/update required fields for agrani bank';

    public function handle(): int
    {
        try {
            if (Core::packageExists('MetaData')) {
                $this->updateRemittancePurpose();
            } else {
                $this->info('`fintech/metadata` is not installed. Skipped');
            }

            if (Core::packageExists('Business')) {
                $this->addServiceVendor();
            } else {
                $this->info('`fintech/business` is not installed. Skipped');
            }

            $this->info('Agrani Bank Remit service vendor setup completed.');

            return self::SUCCESS;

        } catch (Throwable $th) {

            $this->error($th->getMessage());

            return self::FAILURE;
        }
    }

    private function updateRemittancePurpose(): void
    {

        $bar = $this->output->createProgressBar(count(self::PURPOSE_OF_REMITTANCES));

        $bar->start();

        foreach (self::PURPOSE_OF_REMITTANCES as $code => $name) {

            $purposeOfRemittance = MetaData::remittancePurpose()
                ->list(['code' => $code])->first();

            if (! $purposeOfRemittance) {
                continue;
            }

            $vendor_code = $purposeOfRemittance->vendor_code;

            if ($vendor_code == null) {
                $vendor_code = [];
            }

            if (is_string($vendor_code)) {
                $vendor_code = json_decode($vendor_code, true);
            }

            $vendor_code['remit']['argani'] = $name;

            if (MetaData::remittancePurpose()->update(
                $purposeOfRemittance->getKey(),
                ['vendor_code' => $vendor_code])
            ) {
                $this->line("Purpose of Remittance ID: {$purposeOfRemittance->getKey()} updated successful.");
            }

            $bar->advance();
        }

        $bar->finish();

        $this->info('Purpose of remittance metadata updated successfully.');
    }

    private function addServiceVendor(): void
    {
        $dir = __DIR__.'/../../resources/img/service_vendor/';

        $vendor = [
            'service_vendor_name' => 'Agrani Bank',
            'service_vendor_slug' => 'agrani',
            'service_vendor_data' => [],
            'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents("{$dir}/logo_png/agrani.png")),
            'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents("{$dir}/logo_svg/agrani.svg")),
            'enabled' => false,
        ];

        if (Business::serviceVendor()->findWhere(['service_vendor_slug' => $vendor['service_vendor_slug']])) {
            $this->info('Service vendor already exists. Skipping');
        } else {
            Business::serviceVendor()->create($vendor);
            $this->info('Service vendor created successfully.');
        }
    }

    // add country code all country
    public function addCountryCodeToCountries(): void
    {
        if (Core::packageExists('MetaData')) {
            MetaData::country()
                ->list(['paginate' => false])
                ->each(function ($country) {
                    $countryData = $country->country_data;
                    $countryData['vendor_code']['agrani_code'] = self::AGRANI_CODES[$country->iso3]['agrani_code'] ?? null;
                    MetaData::country()->update($country->getKey(), ['country_data' => $countryData]);
                    $this->info("Country ID: {$country->getKey()} successful.");
                });
        }
    }
}
