import type { BreadcrumbItem } from '@/types';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import React from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Edit Venue',
        href: '/manage/venues/create',
    },
];

export default function EditVenue() {

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Venues" />

        </AppLayout>
    );
}
