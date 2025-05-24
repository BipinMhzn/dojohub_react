import { Country } from '@/types';

export const countries: Country[] = [
    {
        name: 'Nepal',
        states: [
            {
                name: 'Koshi Province',
                cities: [
                    'Bhojpur',
                    'Dhankuta',
                    'Ilam',
                    'Jhapa',
                    'Khotang',
                    'Morang',
                    'Okhaldhunga',
                    'Panchthar',
                    'Sankhuwasabha',
                    'Solukhumbu',
                    'Sunsari',
                    'Taplejung',
                    'Terhathum',
                    'Udayapur',
                ],
            },
            {
                name: 'Madhesh Province',
                cities: ['Bara', 'Dhanusha', 'Mahottari', 'Parsa', 'Rautahat', 'Saptari', 'Sarlahi', 'Siraha'],
            },
            {
                name: 'Bagmati Province',
                cities: [
                    'Bhaktapur',
                    'Chitwan',
                    'Dhading',
                    'Dolakha',
                    'Kathmandu',
                    'Kavrepalanchok',
                    'Lalitpur',
                    'Makwanpur',
                    'Nuwakot',
                    'Ramechhap',
                    'Rasuwa',
                    'Sindhuli',
                    'Sindhupalchok',
                ],
            },
            {
                name: 'Gandaki Province',
                cities: ['Baglung', 'Gorkha', 'Kaski', 'Lamjung', 'Manang', 'Mustang', 'Myagdi', 'Nawalpur', 'Parbat', 'Syangja', 'Tanahun'],
            },
            {
                name: 'Lumbini Province',
                cities: [
                    'Arghakhanchi',
                    'Banke',
                    'Bardiya',
                    'Dang',
                    'Eastern Rukum',
                    'Gulmi',
                    'Kapilvastu',
                    'Nawalparasi West',
                    'Palpa',
                    'Pyuthan',
                    'Rolpa',
                    'Rupandehi',
                ],
            },
            {
                name: 'Karnali Province',
                cities: ['Dailekh', 'Dolpa', 'Humla', 'Jajarkot', 'Jumla', 'Kalikot', 'Mugu', 'Salyan', 'Surkhet', 'Western Rukum'],
            },
            {
                name: 'Sudurpashchim Province',
                cities: ['Achham', 'Baitadi', 'Bajhang', 'Bajura', 'Dadeldhura', 'Darchula', 'Doti', 'Kailali', 'Kanchanpur'],
            },
        ],
    },
];

export default countries;
