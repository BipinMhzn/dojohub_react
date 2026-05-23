import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { Coach } from '@/pages/coaches/index';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import React from 'react';

interface Props {
    coach: Coach;
}

export default function EditCoach({ coach }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Coaches', href: '/manage/coaches' },
        { title: coach.full_name, href: `/manage/coaches/${coach.id}/edit` },
    ];

    const { data, setData, put, processing, errors } = useForm({
        full_name: coach.full_name,
        email: coach.email ?? '',
        phone_number: coach.phone_number ?? '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/manage/coaches/${coach.id}`);
    };

    const activeClubs = coach.active_memberships ?? [];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Coach: ${coach.full_name}`} />
            <div className="px-4 py-6">
                <Heading title="Edit Coach" description={`Edit ${coach.full_name}`} />

                <form onSubmit={handleSubmit} className="mt-6 max-w-xl space-y-4">
                    <div>
                        <Label htmlFor="full_name">Full Name</Label>
                        <Input
                            id="full_name"
                            value={data.full_name}
                            onChange={(e) => setData('full_name', e.target.value)}
                            className="mt-1 block w-full"
                            disabled={processing}
                        />
                        <InputError message={errors.full_name} />
                    </div>

                    <div>
                        <Label htmlFor="email">
                            Email <span className="text-xs font-light">(Optional)</span>
                        </Label>
                        <Input
                            id="email"
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            className="mt-1 block w-full"
                            disabled={processing}
                        />
                        <InputError message={errors.email} />
                    </div>

                    <div>
                        <Label htmlFor="phone_number">
                            Phone Number <span className="text-xs font-light">(Optional)</span>
                        </Label>
                        <Input
                            id="phone_number"
                            value={data.phone_number}
                            onChange={(e) => setData('phone_number', e.target.value)}
                            className="mt-1 block w-full"
                            disabled={processing}
                        />
                        <InputError message={errors.phone_number} />
                    </div>

                    <div>
                        <Label>Active Clubs</Label>
                        <div className="mt-1 flex flex-wrap gap-2">
                            {activeClubs.length === 0 ? (
                                <span className="text-muted-foreground text-sm">
                                    Not assigned to any club. Assign from the club&apos;s page.
                                </span>
                            ) : (
                                activeClubs.map((m) => (
                                    <Link key={m.id} href={`/manage/clubs/${m.club_id}/coaches`}>
                                        <Badge variant="secondary">{m.club?.name ?? m.club_id}</Badge>
                                    </Link>
                                ))
                            )}
                        </div>
                    </div>

                    <div className="flex gap-4">
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Saving...' : 'Save'}
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <a href="/manage/coaches">Cancel</a>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
